<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once __DIR__ . '/../config/db.php';

function querySingleValue(mysqli $conn, string $sql, int $userId): float
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    $stmt->close();

    return $row ? round((float) $row[0], 2) : 0.0;
}

function getFirstUser(mysqli $conn): ?array
{
    $result = $conn->query('SELECT user_id, name FROM users ORDER BY user_id ASC LIMIT 1');
    return $result ? $result->fetch_assoc() : null;
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

try {
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $userName = isset($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : '';

    if ($userId <= 0) {
        $firstUser = getFirstUser($conn);
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
        $stmt = $conn->prepare('SELECT name FROM users WHERE user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

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

    $monthlyIncome = querySingleValue(
        $conn,
        'SELECT COALESCE(SUM(amount), 0) FROM income WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())',
        $userId
    );

    $monthlyExpenses = querySingleValue(
        $conn,
        'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())',
        $userId
    );

    $totalIncome = querySingleValue(
        $conn,
        'SELECT COALESCE(SUM(amount), 0) FROM income WHERE user_id = ?',
        $userId
    );

    $totalExpenses = querySingleValue(
        $conn,
        'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?',
        $userId
    );

    $totalBalance = round($totalIncome - $totalExpenses, 2);

    $recentActivity = [];
    $recentSql = '
        SELECT id, date, description, amount, type
        FROM (
            SELECT CONCAT("income-", income_id) AS id, date, COALESCE(NULLIF(description, ""), source) AS description, amount, "income" AS type
            FROM income
            WHERE user_id = ?
            UNION ALL
            SELECT CONCAT("expense-", expenses_id) AS id, date, COALESCE(NULLIF(description, ""), "Expense") AS description, amount, "expense" AS type
            FROM expenses
            WHERE user_id = ?
        ) AS all_activity
        ORDER BY date DESC, id DESC
        LIMIT 5
    ';
    $stmt = $conn->prepare($recentSql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentActivity[] = [
                'id' => $row['id'],
                'date' => $row['date'],
                'description' => $row['description'],
                'amount' => round((float) $row['amount'], 2),
                'type' => $row['type']
            ];
        }
    }
    $stmt->close();

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
    $stmt = $conn->prepare($chartSql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (isset($monthBuckets[$row['month_key']])) {
                $monthBuckets[$row['month_key']]['income'] = round((float) $row['income'], 2);
                $monthBuckets[$row['month_key']]['expenses'] = round((float) $row['expenses'], 2);
            }
        }
    }
    $stmt->close();

    $chartLabels = [];
    $chartIncome = [];
    $chartExpenses = [];
    foreach ($monthBuckets as $bucket) {
        $chartLabels[] = $bucket['label'];
        $chartIncome[] = $bucket['income'];
        $chartExpenses[] = $bucket['expenses'];
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
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data from database.'
    ]);
}