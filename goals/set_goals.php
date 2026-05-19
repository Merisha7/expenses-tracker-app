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

// Handle Set Goals form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_name = trim($_POST['goal_name'] ?? '');
    $required_amount = $_POST['required_amount'] ?? '';
    $current_savings = $_POST['current_savings'] ?? '';
    $total_budget = $_POST['total_budget'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    
    // Validate inputs
    if (empty($goal_name) || empty($required_amount) || empty($start_date) || empty($due_date)) {
        $errorMessage = 'Please fill in all required fields.';
    } elseif (!is_numeric($required_amount) || !is_numeric($current_savings) || !is_numeric($total_budget)) {
        $errorMessage = 'Please enter valid amounts.';
    } else {
        // Convert date format from mm/dd/yyyy to yyyy-mm-dd
        $start_parts = explode('/', $start_date);
        $due_parts = explode('/', $due_date);
        
        if (count($start_parts) === 3 && count($due_parts) === 3) {
            $start_date_db = $start_parts[2] . '-' . $start_parts[0] . '-' . $start_parts[1];
            $due_date_db = $due_parts[2] . '-' . $due_parts[0] . '-' . $due_parts[1];
            
            try {
                $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, required_amount, current_savings, total_budget, start_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$user_id, $goal_name, $required_amount, $current_savings, $total_budget, $start_date_db, $due_date_db])) {
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
            } catch (PDOException $e) {
                $errorMessage = 'Error preparing statement: ' . $e->getMessage();
            }
        } else {
            $errorMessage = 'Invalid date format. Please use mm/dd/yyyy.';
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
                <a class="menu-item active" href="set_goals.php"><i class="fas fa-bullseye menu-icon"></i>Set Goals</a>
                <a class="menu-item" href="../calendar/calendar.html"><i class="fas fa-calendar-alt menu-icon"></i>Calendar</a>
            </nav>

            <a class="logout-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt logout-icon"></i>Log Out</a>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <h1><i class="fas fa-bullseye"></i> SAVING GOALS</h1>
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

                <form action="../goals/set_goals.php" method="post">
                    <div class="form-group">
                        <label for="goal-name">Goal Name:</label>
                        <input type="text" id="goal-name" name="goal_name" placeholder="e.g., New Car, Vacation, Emergency Fund" value="<?php echo htmlspecialchars($goal_name); ?>">
                    </div>
                    <div class="form-group">
                        <label for="required-amount">Required Amount:</label>
                        <input type="text" id="required-amount" name="required_amount" placeholder="Target amount to reach" value="<?php echo htmlspecialchars($required_amount); ?>">
                    </div>
                    <div class="form-group">
                        <label for="current-savings">Current Savings:</label>
                        <input type="text" id="current-savings" name="current_savings" placeholder="Amount already saved" value="<?php echo htmlspecialchars($current_savings); ?>">
                    </div>
                    <div class="form-group">
                        <label for="total-budget">Total Budget Available:</label>
                        <input type="text" id="total-budget" name="total_budget" placeholder="Total budget you can allocate" value="<?php echo htmlspecialchars($total_budget); ?>">
                    </div>
                    <div class="form-group">
                        <label for="start-date">Start Date:</label>
                            <input type="text" id="start-date" name="start_date" placeholder="mm/dd/yyyy" inputmode="numeric" maxlength="10" oninput="formatDateInput(this)" pattern="^\d{2}/\d{2}/\d{4}$" title="Enter date in mm/dd/yyyy format" required value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="due-date">Due Date:</label>
                            <input type="text" id="due-date" name="due_date" placeholder="mm/dd/yyyy" inputmode="numeric" maxlength="10" oninput="formatDateInput(this)" pattern="^\d{2}/\d{2}/\d{4}$" title="Enter date in mm/dd/yyyy format" required value="<?php echo htmlspecialchars($due_date); ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="save-goal-btn">SAVE GOAL</button>
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
