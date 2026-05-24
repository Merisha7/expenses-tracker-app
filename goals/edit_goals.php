<?php
session_start();
require_once '../config/db.php';

$user_id = (int) ($_SESSION['user_id'] ?? 1);
$errorMessage = '';

$goal_id = (int) ($_GET['goal_id'] ?? $_POST['goal_id'] ?? 0);
if ($goal_id <= 0) {
    header('Location: view_goals.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_name = trim($_POST['goal_name'] ?? '');
    $required_amount = $_POST['required_amount'] ?? '';
    $current_savings = $_POST['current_savings'] ?? '';
    $total_budget = $_POST['total_budget'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if (
        $goal_name === '' ||
        $required_amount === '' ||
        $current_savings === '' ||
        $total_budget === '' ||
        $start_date === '' ||
        $due_date === ''
    ) {
        $errorMessage = 'Please fill in all fields.';
    } elseif (!is_numeric($required_amount) || !is_numeric($current_savings) || !is_numeric($total_budget)) {
        $errorMessage = 'Please enter valid amounts.';
    } else {
        $start_parts = explode('/', $start_date);
        $due_parts = explode('/', $due_date);

        if (count($start_parts) === 3 && count($due_parts) === 3) {
            $start_date_db = $start_parts[2] . '-' . $start_parts[0] . '-' . $start_parts[1];
            $due_date_db = $due_parts[2] . '-' . $due_parts[0] . '-' . $due_parts[1];

            $stmt = $pdo->prepare('UPDATE savings_goals SET goal_name = ?, required_amount = ?, current_savings = ?, total_budget = ?, start_date = ?, due_date = ? WHERE goal_id = ? AND user_id = ?');
            $stmt->execute([
                $goal_name,
                $required_amount,
                $current_savings,
                $total_budget,
                $start_date_db,
                $due_date_db,
                $goal_id,
                $user_id
            ]);

            header('Location: view_goals.php');
            exit();
        } else {
            $errorMessage = 'Invalid date format. Please use mm/dd/yyyy.';
        }
    }
}

$stmt = $pdo->prepare('SELECT goal_id, goal_name, required_amount, current_savings, total_budget, start_date, due_date FROM savings_goals WHERE goal_id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$goal_id, $user_id]);
$goal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$goal) {
    header('Location: view_goals.php');
    exit();
}

$start_date_value = date('m/d/Y', strtotime($goal['start_date']));
$due_date_value = date('m/d/Y', strtotime($goal['due_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Saving Goal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/set_goals.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand-box">
                <span class="brand-icon" aria-hidden="true">
                    <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none">
                        <rect x="3.5" y="6.5" width="17" height="12" rx="1.8" stroke="currentColor" stroke-width="1.9"></rect>
                        <path d="M3.5 10.2H20.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"></path>
                    </svg>
                </span>
                <strong>FinTrack</strong>
            </div>

            <nav class="menu">
                <a class="menu-item" href="../dashboard/dashboard.html"><i class="fas fa-tachometer-alt menu-icon"></i>Dashboard</a>
                <a class="menu-item" href="../income/add_income.php"><i class="fas fa-plus-circle menu-icon"></i>Add Income</a>
                <a class="menu-item" href="../expenses/add_expenses.php"><i class="fas fa-minus-circle menu-icon"></i>Add Expenses</a>
                <a class="menu-item" href="set_goals.php"><i class="fas fa-bullseye menu-icon"></i>Set Goals</a>
                <a class="menu-item active" href="view_goals.php"><i class="fas fa-list-check menu-icon"></i>View Goals</a>
                <a class="menu-item" href="../calendar/calendar.html"><i class="fas fa-calendar-alt menu-icon"></i>Calendar</a>
            </nav>

            <a class="logout-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt logout-icon"></i>Log Out</a>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <h1><i class="fas fa-edit"></i> EDIT GOAL</h1>
                <p>Update your savings goal details</p>
            </header>

            <div class="form-container">
                <?php if ($errorMessage): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form id="page-edit-form" method="POST" action="edit_goals.php?goal_id=<?php echo (int) $goal_id; ?>">
                    <input type="hidden" name="goal_id" id="goal-id" value="<?php echo (int) $goal['goal_id']; ?>">
                    <div class="form-group">
                        <label for="goal-name">Goal Name:</label>
                        <input type="text" id="goal-name" name="goal_name" placeholder="e.g., New Car, Vacation, Emergency Fund" required value="<?php echo htmlspecialchars($goal['goal_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="required-amount">Required Amount:</label>
                        <input type="text" id="required-amount" name="required_amount" placeholder="Target amount to reach" required value="<?php echo htmlspecialchars((string) $goal['required_amount']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="current-savings">Current Savings:</label>
                        <input type="text" id="current-savings" name="current_savings" placeholder="Amount already saved" required value="<?php echo htmlspecialchars((string) $goal['current_savings']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="total-budget">Total Budget Available:</label>
                        <input type="text" id="total-budget" name="total_budget" placeholder="Total budget you can allocate" required value="<?php echo htmlspecialchars((string) $goal['total_budget']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="start-date">Start Date:</label>
                        <input type="text" id="start-date" name="start_date" placeholder="mm/dd/yyyy" inputmode="numeric" maxlength="10" oninput="formatDateInput(this)" pattern="^\d{2}/\d{2}/\d{4}$" title="Enter date in mm/dd/yyyy format" required value="<?php echo htmlspecialchars($start_date_value); ?>">
                    </div>
                    <div class="form-group">
                        <label for="due-date">Due Date:</label>
                        <input type="text" id="due-date" name="due_date" placeholder="mm/dd/yyyy" inputmode="numeric" maxlength="10" oninput="formatDateInput(this)" pattern="^\d{2}/\d{2}/\d{4}$" title="Enter date in mm/dd/yyyy format" required value="<?php echo htmlspecialchars($due_date_value); ?>">
                    </div>
                    <div class="form-group button-group">
                        <button type="button" class="cancel-goal-btn" onclick="window.location.href='view_goals.php'">CANCEL</button>
                        <button type="submit" class="save-goal-btn">EDIT GOAL</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function formatDateInput(input) {
            var digits = input.value.replace(/\D/g, "").slice(0, 8);

            var month = digits.slice(0, 2);
            var day = digits.slice(2, 4);
            var year = digits.slice(4, 8);

            if (month.length === 2) {
                var monthNum = parseInt(month, 10);
                if (monthNum < 1) monthNum = 1;
                if (monthNum > 12) monthNum = 12;
                month = String(monthNum).padStart(2, "0");
            }

            if (day.length === 2) {
                var dayNum = parseInt(day, 10);
                if (dayNum < 1) dayNum = 1;
                if (dayNum > 31) dayNum = 31;
                day = String(dayNum).padStart(2, "0");
            }

            var formatted = month;
            if (digits.length > 2) {
                formatted += "/" + day;
            }
            if (digits.length > 4) {
                formatted += "/" + year;
            }

            input.value = formatted;
        }
    </script>
</body>
</html>