<?php
session_start();
require_once '../config/db.php';

$user_id = (int) ($_SESSION['user_id'] ?? 1);

$successMessage = '';
$errorMessage = '';
$goal_name = '';
$required_amount = '';
$current_savings = '';
$total_budget = '';
$start_date = '';
$due_date = '';

function normalizeNumberInput(string $value): string
{
    return str_replace(',', '', trim($value));
}

// Handle Set Goals form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_name = trim($_POST['goal_name'] ?? '');
    $required_amount = normalizeNumberInput((string) ($_POST['required_amount'] ?? ''));
    $current_savings = normalizeNumberInput((string) ($_POST['current_savings'] ?? ''));
    $total_budget = normalizeNumberInput((string) ($_POST['total_budget'] ?? ''));
    $start_date = $_POST['start_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    // Treat optional numeric fields as zero if left empty.
    if ($current_savings == '') {
        $current_savings = '0';
    }
    if ($total_budget == '') {
        $total_budget = '0';
    }
    
    // Validate inputs
    if (empty($goal_name) || empty($required_amount) || empty($start_date) || empty($due_date)) {
        $errorMessage = 'Please fill in all required fields.';
    } elseif (!is_numeric($required_amount) || !is_numeric($current_savings) || !is_numeric($total_budget)) {
        $errorMessage = 'Please enter valid amounts.';
    } elseif ((float) $required_amount <= 0 || (float) $current_savings < 0 || (float) $total_budget < 0) {
        $errorMessage = 'Amounts must be positive (required amount) and non-negative (savings/budget).';
    } else {
        try {
            $startDateObj = DateTime::createFromFormat('Y-m-d', $start_date);
            $dueDateObj = DateTime::createFromFormat('Y-m-d', $due_date);

            if (!$startDateObj || !$dueDateObj || $startDateObj->format('Y-m-d') !== $start_date || $dueDateObj->format('Y-m-d') !== $due_date) {
                $errorMessage = 'Please provide valid dates.';
            } elseif ($dueDateObj < $startDateObj) {
                $errorMessage = 'Due date must be on or after start date.';
            }

            if ($errorMessage !== '') {
                throw new RuntimeException('Validation failed');
            }

            $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, required_amount, current_savings, total_budget, start_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$user_id, $goal_name, $required_amount, $current_savings, $total_budget, $start_date, $due_date])) {
                $successMessage = 'Goal saved successfully!';
                // Clear form
                $goal_name = '';
                $required_amount = '';
                $current_savings = '';
                $total_budget = '';
                $start_date = '';
                $due_date = '';
            } else {
                $errorMessage = 'Error saving goal.';
            }
        } catch (RuntimeException $e) {
            // Validation error already assigned to $errorMessage.
        } catch (PDOException $e) {
            $errorMessage = 'Error preparing statement: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Saving Goals</title>
    <link rel="stylesheet" href="../assets/css/set_goals.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand-box">
                <span class="brand-icon" aria-hidden="true">
                    <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none">
                        <path d="M8 4.75h6.1L18.25 8.9V19a1.25 1.25 0 0 1-1.25 1.25H8A1.25 1.25 0 0 1 6.75 19V6A1.25 1.25 0 0 1 8 4.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path>
                        <path d="M14 4.75V9h4.25" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path>
                        <path d="M9.75 13h5.5M9.75 16h5.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path>
                    </svg>
                </span>
                <strong>FinTrack</strong>
            </div>

                <nav class="menu">
                <a class="menu-item" href="../dashboard/dashboard.html"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 13h7V4H4v9zm9 7h7v-9h-7v9zM13 4v7h7V4h-7zM4 20h7v-4H4v4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>Dashboard</a>
                <a class="menu-item" href="../income/add_income.php"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>Add Income</a>
                <a class="menu-item" href="../expenses/add_expenses.php"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>Add Expenses</a>
                <a class="menu-item active" href="../goals/set_goals.php"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.8"/></svg>Set Goals</a>
                <a class="menu-item" href="../goals/view_goals.php"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5.5 6.5l1.2 1.2L8.8 5.6M5.5 12.5l1.2 1.2L8.8 11.6M5.5 18.5l1.2 1.2L8.8 17.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>View Goals</a>
                <a class="menu-item" href="../calendar/calendar.html"><svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 8.5h17M7.5 3.5v5M16.5 3.5v5M5 5.5h14A1.5 1.5 0 0 1 20.5 7v11A1.5 1.5 0 0 1 19 19.5H5A1.5 1.5 0 0 1 3.5 18V7A1.5 1.5 0 0 1 5 5.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Calendar</a>
            </nav>

            <a class="logout-link" href="../auth/logout.php"><svg class="logout-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 7V5.5A1.5 1.5 0 0 0 12.5 4h-6A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20h6A1.5 1.5 0 0 0 14 18.5V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 12h9m-3-3 3 3-3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Log Out</a>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <h1>
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.8"/></svg>
                    SAVING GOALS
                </h1>
                <p>Set your financial goals and track your progress</p>
            </header>

            <div class="form-container">
                <?php if ($successMessage): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form action="set_goals.php" method="post">
                    <div class="form-group">
                        <label for="goal-name">Goal Name:</label>
                        <input type="text" id="goal-name" name="goal_name" placeholder="e.g., New Car, Vacation, Emergency Fund" value="<?php echo htmlspecialchars($goal_name); ?>">
                    </div>
                    <div class="form-group">
                        <label for="required-amount">Required Amount (Rs.):</label>
                        <input type="text" id="required-amount" name="required_amount" placeholder="e.g., 50,000" inputmode="decimal" data-number-format value="<?php echo htmlspecialchars($required_amount); ?>">
                    </div>
                    <div class="form-group">
                        <label for="current-savings">Current Savings (Rs.):</label>
                        <input type="text" id="current-savings" name="current_savings" placeholder="e.g., 12,000" inputmode="decimal" data-number-format value="<?php echo htmlspecialchars($current_savings); ?>">
                    </div>
                    <div class="form-group">
                        <label for="total-budget">Total Budget Available (Rs.):</label>
                        <input type="text" id="total-budget" name="total_budget" placeholder="e.g., 60,000" inputmode="decimal" data-number-format value="<?php echo htmlspecialchars($total_budget); ?>">
                    </div>
                    <div class="form-group">
                        <label for="start-date">Start Date:</label>
                            <input type="date" id="start-date" name="start_date" required value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="due-date">Due Date:</label>
                            <input type="date" id="due-date" name="due_date" required value="<?php echo htmlspecialchars($due_date); ?>">
                    </div>
                    <div class="form-group form-actions">
                        <button type="button" class="cancel-goal-btn" onclick="window.location.href='view_goals.php'">CANCEL</button>
                        <button type="submit" class="save-goal-btn">SAVE GOAL</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        (() => {
            const formatNumber = (value) => {
                const cleaned = String(value).replace(/,/g, '').replace(/[^\d.]/g, '');
                if (!cleaned) return '';

                const [integerPart, decimalPart] = cleaned.split('.');
                const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                return decimalPart !== undefined ? `${formattedInteger}.${decimalPart}` : formattedInteger;
            };

            document.querySelectorAll('[data-number-format]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = formatNumber(input.value);
                });

                if (input.value) {
                    input.value = formatNumber(input.value);
                }
            });
        })();
    </script>
</body>
</html>