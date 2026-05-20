<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once __DIR__ . '/../config/db.php';

function querySingleValue(PDO $pdo, string $sql, int $userId): float
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_NUM);

    return $row ? round((float) $row[0], 2) : 0.0;
}

function getFirstUser(PDO $pdo): ?array
{
    $stmt = $pdo->prepare('SELECT user_id, name FROM users ORDER BY user_id ASC LIMIT 1');
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buildMonthBuckets(): array
{
    $buckets = [];
    $start = new DateTimeImmutable('first day of this month');

    for ($i = 5; $i >= 0; $i--) {
        $month = $start->modify("-$i months");
        $key = $month->format('Y-m');
        $buckets[$key] = [
            'label' => $month->format('M'),
            'income' => 0.0,
            'expenses' => 0.0,
        ];
    }

    return $buckets;
}

function getLatestTableMonth(PDO $pdo, string $table, int $userId): ?string
{
    if (!in_array($table, ['income', 'expenses'], true)) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT DATE_FORMAT(MAX(date), '%Y-%m') AS month_key FROM {$table} WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row['month_key'] ?: null;
}

function getMonthlyTotal(PDO $pdo, string $table, int $userId, string $monthKey): float
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$table} WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?"
    );
    $stmt->execute([$userId, $monthKey]);
    $row = $stmt->fetch(PDO::FETCH_NUM);

    return $row ? round((float) $row[0], 2) : 0.0;
}

try {
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $userName = isset($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : '';

    if ($userId <= 0) {
        $firstUser = getFirstUser($pdo);
        if (!$firstUser) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No users found in database.'
            ]);
            exit;
        }

        $userId = (int) $firstUser['user_id'];
        $userName = $firstUser['name'];
    } elseif ($userName === '') {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found in database.'
            ]);
            exit;
        }

        $userName = $row['name'];
    }

    $currentMonthKey = (new DateTimeImmutable('first day of this month'))->format('Y-m');
    $monthlyIncome = getMonthlyTotal($pdo, 'income', $userId, $currentMonthKey);
    $monthlyExpenses = getMonthlyTotal($pdo, 'expenses', $userId, $currentMonthKey);

    if ($monthlyIncome == 0.0) {
        $latestIncomeMonthKey = getLatestTableMonth($pdo, 'income', $userId);
        if ($latestIncomeMonthKey && $latestIncomeMonthKey !== $currentMonthKey) {
            $monthlyIncome = getMonthlyTotal($pdo, 'income', $userId, $latestIncomeMonthKey);
        }
    }

    if ($monthlyExpenses == 0.0) {
        $latestExpenseMonthKey = getLatestTableMonth($pdo, 'expenses', $userId);
        if ($latestExpenseMonthKey && $latestExpenseMonthKey !== $currentMonthKey) {
            $monthlyExpenses = getMonthlyTotal($pdo, 'expenses', $userId, $latestExpenseMonthKey);
        }
    }

    $totalIncome = querySingleValue(
        $pdo,
        'SELECT COALESCE(SUM(amount), 0) FROM income WHERE user_id = ?',
        $userId
    );

    $totalExpenses = querySingleValue(
        $pdo,
        'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?',
        $userId
    );

    $totalBalance = round($monthlyIncome - $monthlyExpenses, 2);

    $recentActivity = [];
    $recentSql = '
        SELECT record_id, date, description, amount, type
        FROM (
            SELECT income_id AS record_id, date, COALESCE(NULLIF(description, ""), source) AS description, amount, "income" AS type
            FROM income
            WHERE user_id = ?
            UNION ALL
            SELECT expenses_id AS record_id, date, COALESCE(NULLIF(description, ""), "Expense") AS description, amount, "expense" AS type
            FROM expenses
            WHERE user_id = ?
        ) AS all_activity
        ORDER BY date DESC, record_id DESC
        LIMIT 5
    ';
    $stmt = $pdo->prepare($recentSql);
    $stmt->execute([$userId, $userId]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recentActivity[] = [
            'id' => (int) $row['record_id'],
            'date' => $row['date'],
            'description' => $row['description'],
            'amount' => round((float) $row['amount'], 2),
            'type' => $row['type']
        ];
    }

    $monthBuckets = buildMonthBuckets();
    $chartSql = '
        SELECT month_key, SUM(income_amount) AS income, SUM(expense_amount) AS expenses
        FROM (
            SELECT DATE_FORMAT(date, "%Y-%m") AS month_key, amount AS income_amount, 0 AS expense_amount
            FROM income
            WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 MONTH)
            UNION ALL
            SELECT DATE_FORMAT(date, "%Y-%m") AS month_key, 0 AS income_amount, amount AS expense_amount
            FROM expenses
            WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 MONTH)
        ) AS month_activity
        GROUP BY month_key
        ORDER BY month_key ASC
    ';
    $stmt = $pdo->prepare($chartSql);
    $stmt->execute([$userId, $userId]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($monthBuckets[$row['month_key']])) {
            $monthBuckets[$row['month_key']]['income'] = round((float) $row['income'], 2);
            $monthBuckets[$row['month_key']]['expenses'] = round((float) $row['expenses'], 2);
        }
    }

    $chartLabels = [];
    $chartIncome = [];
    $chartExpenses = [];
    foreach ($monthBuckets as $bucket) {
        $chartLabels[] = $bucket['label'];
        $chartIncome[] = $bucket['income'];
        $chartExpenses[] = $bucket['expenses'];
    }

    // Fetch goals data
    $goalsHtml = '';
    try {
        // Only fetch active goals (not achieved) for the dashboard
        $goalsSql = 'SELECT goal_id, goal_name, required_amount, current_savings, total_budget, start_date, due_date FROM savings_goals WHERE user_id = ? AND COALESCE(is_achieved, 0) = 0 ORDER BY due_date ASC';
        $goalsStmt = $pdo->prepare($goalsSql);
        $goalsStmt->execute([$userId]);
        $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);

        error_log('Dashboard: Fetched ' . count($goals) . ' goals for user_id: ' . $userId);

        if (!empty($goals)) {
            foreach ($goals as $goal) {
                $reqAmount = (float) $goal['required_amount'];
                $curSavings = (float) $goal['current_savings'];
                $progress = $reqAmount > 0 ? min(($curSavings / $reqAmount) * 100, 100) : 0;
                $progress = round($progress, 1);
                $remaining = max(0, $reqAmount - $curSavings);
                $startDate = date('m/d/Y', strtotime($goal['start_date']));
                $dueDate = date('m/d/Y', strtotime($goal['due_date']));

                $goalsHtml .= '
                    <div class="goal-card-dashboard">
                        <div class="goal-top-dashboard">
                            <div>
                                <h4>' . htmlspecialchars($goal['goal_name']) . '</h4>
                                <p class="goal-date">' . $startDate . ' - ' . $dueDate . '</p>
                            </div>
                        </div>
                        <div class="goal-progress-section">
                            <div class="goal-progress-header">
                                <span>Goal Progress</span>
                                <span>' . $progress . '%</span>
                            </div>
                            <div class="goal-progress-bar">
                                <div class="goal-progress-fill" style="width:' . $progress . '%"></div>
                            </div>
                        </div>
                        <div class="goal-stats-dashboard">
                            <div class="goal-stat-box goal-green">
                                <p>Current Savings</p>
                                <h5>Rs. ' . number_format((float) $curSavings, 2) . '</h5>
                            </div>
                            <div class="goal-stat-box goal-purple">
                                <p>Target Amount</p>
                                <h5>Rs. ' . number_format((float) $reqAmount, 2) . '</h5>
                            </div>
                            <div class="goal-stat-box goal-red">
                                <p>Remaining Amount</p>
                                <h5>Rs. ' . number_format((float) $remaining, 2) . '</h5>
                            </div>
                        </div>
                        <div class="goal-bottom">
                            <p class="goal-budget">Budget: Rs. ' . number_format((float) $goal['total_budget'], 2) . '</p>
                        </div>
                    </div>
                ';
            }
        } else {
            $goalsHtml = '<p style="text-align: center; color: #999; padding: 20px;">No goals set yet. <a href="../goals/set_goals.php">Create a goal</a></p>';
        }
    } catch (Throwable $e) {
        error_log('Dashboard goals error: ' . $e->getMessage() . ' - User ID: ' . $userId);
        $goalsHtml = '<p style="text-align: center; color: #999; padding: 20px;">Unable to load goals. <a href="../goals/set_goals.php">Manage goals</a></p>';
    }

    echo json_encode([
        'success' => true,
        'userName' => $userName,
        'totalBalance' => $totalBalance,
        'totalIncome' => $totalIncome,
        'totalExpenses' => $totalExpenses,
        'monthlyIncome' => $monthlyIncome,
        'monthlyExpenses' => $monthlyExpenses,
        'recentActivity' => $recentActivity,
        'chart' => [
            'labels' => $chartLabels,
            'income' => $chartIncome,
            'expenses' => $chartExpenses
        ],
        'goalsHtml' => $goalsHtml
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data from database.'
    ]);
}