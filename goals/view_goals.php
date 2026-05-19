<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, create a test session if no user is logged in
    $_SESSION['user_id'] = 1;  // Test user ID
}

// Include database connection
include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch all goals for the current user
$sql = "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY due_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_goal_id'])) {
    $goal_id = $_POST['delete_goal_id'];
    
    // Verify goal belongs to current user
    $verify_sql = "SELECT goal_id FROM savings_goals WHERE goal_id = ? AND user_id = ?";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute([$goal_id, $user_id]);
    $verify_result = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($verify_result) > 0) {
        $delete_sql = "DELETE FROM savings_goals WHERE goal_id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$goal_id]);
        
        header('Location: view_goals.php');
        exit();
    }
}

// Handle mark as achieved request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['achieve_goal_id'])) {
    $goal_id = $_POST['achieve_goal_id'];
    $is_achieved = $_POST['is_achieved'] == '1' ? 0 : 1;
    
    // Verify goal belongs to current user
    $verify_sql = "SELECT goal_id FROM savings_goals WHERE goal_id = ? AND user_id = ?";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute([$goal_id, $user_id]);
    $verify_result = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($verify_result) > 0) {
        $update_sql = "UPDATE savings_goals SET is_achieved = ? WHERE goal_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$is_achieved, $goal_id]);
        
        header('Location: view_goals.php');
        exit();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>View Goals</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/css/transactions.css">
<link rel="stylesheet" href="../assets/css/goals.css">
</head>

<body>

<div class="main">

<!-- SIDEBAR-->
<div class="sidebar">

        <div class="logo">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M3 7h18v13H3zM16 3H5a2 2 0 00-2 2v2h18V5a2 2 0 00-2-2z"/>
            </svg>
            FinTrack
        </div>

        <a href="../dashboard/dashboard.html">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>
            </svg>
            Dashboard
        </a>

        <a href="../income/add_income.php">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
            Add Income
        </a>

        <a href="../expenses/add_expenses.php">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M5 12h14"/>
            </svg>
            Add Expenses
        </a>

        <a href="../goals/set_goals.php">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                <circle cx="12" cy="12" r="4" stroke-width="2"/>
            </svg>
            Set Goals
        </a>

        <a href="view_goals.php" class="active">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M9 6h11M9 12h11M9 18h11"/>
                <path stroke-width="2" d="M5 6l1.5 1.5L8 5M5 12l1.5 1.5L8 11M5 18l1.5 1.5L8 17"/>
            </svg>
            View Goals
        </a>

        <a href="../calendar/calendar.html">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M3 8h18M8 3v5M16 3v5M3 8v13h18V8"/>
            </svg>
            Calendar
        </a>

        <a href="../auth/logout.php" class="logout">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M15 12H3m6-6l-6 6 6 6M21 3v18"/>
            </svg>
            Log Out
        </a>

    </div>

<!-- CONTENT -->
<div class="content">

    <h1>YOUR GOALS</h1>
    <p class="subtitle">View and manage your saved goals</p>

    <?php if (empty($goals)): ?>
        <p style="text-align: center; margin-top: 40px; color: #999;">No goals found. <a href="set_goals.php">Create a new goal</a></p>
    <?php else: ?>
        <?php foreach ($goals as $goal): ?>
            <?php
                // Calculate progress
                $progress_percentage = ($goal['current_savings'] / $goal['required_amount']) * 100;
                if ($progress_percentage > 100) $progress_percentage = 100;
                $progress_percentage = round($progress_percentage, 1);
                
                // Calculate remaining amount
                $remaining_amount = max(0, $goal['required_amount'] - $goal['current_savings']);
                
                // Format dates
                $start_date = date('m/d/Y', strtotime($goal['start_date']));
                $due_date = date('m/d/Y', strtotime($goal['due_date']));
            ?>
    <div class="goal-card">

        <!-- TOP -->
        <div class="goal-top">
            <div>
                <h2><?php echo htmlspecialchars($goal['goal_name']); ?></h2>
                <p class="date"><?php echo $start_date; ?> - <?php echo $due_date; ?></p>
            </div>

            <div class="icons">

    <!-- EDIT ICON -->
    <a href="edit_goals.php?goal_id=<?php echo $goal['goal_id']; ?>" style="cursor: pointer; text-decoration: none; color: inherit;">
        <svg class="icon-btn" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-width="2" d="M12 20h9"/>
            <path stroke-width="2" d="M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z"/>
        </svg>
    </a>

    <!-- DELETE ICON -->
    <form method="POST" style="display: inline;">
        <input type="hidden" name="delete_goal_id" value="<?php echo $goal['goal_id']; ?>">
        <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0; color: inherit;" onclick="return confirm('Are you sure you want to delete this goal?')">
            <svg class="icon-btn delete" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M3 6h18"/>
                <path stroke-width="2" d="M8 6V4h8v2"/>
                <path stroke-width="2" d="M6 6v14h12V6"/>
                <path stroke-width="2" d="M10 11v6"/>
                <path stroke-width="2" d="M14 11v6"/>
            </svg>
        </button>
    </form>

</div>
        </div>

        <!-- PROGRESS -->
        <div class="progress-header">
            <span>Goal Progress</span>
            <span><?php echo $progress_percentage; ?>%</span>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" style="width:<?php echo $progress_percentage; ?>%"></div>
        </div>

        <!-- STATS ROW -->
        <div class="stats">

            <div class="box green">
                <p>Current Savings</p>
                <h3>Rs.<?php echo number_format($goal['current_savings'], 2); ?></h3>
            </div>

            <div class="box purple">
                <p>Target Amount</p>
                <h3>Rs.<?php echo number_format($goal['required_amount'], 2); ?></h3>
            </div>

            <div class="box red">
                <p>Remaining Amount</p>
                <h3>Rs.<?php echo number_format($remaining_amount, 2); ?></h3>
            </div>

        </div>

        <!-- BOTTOM -->
        <div class="bottom">

            <p class="budget">Budget Rs <?php echo number_format($goal['total_budget'], 2); ?></p>

            <form method="POST" style="display: inline;">
                <input type="hidden" name="achieve_goal_id" value="<?php echo $goal['goal_id']; ?>">
                <input type="hidden" name="is_achieved" value="<?php echo $goal['is_achieved']; ?>">
                <label class="checkbox">
                    <input type="checkbox" <?php echo ($goal['is_achieved'] == 1) ? 'checked' : ''; ?> onchange="this.form.submit()">
                    <span>Mark as achieved</span>
                </label>
            </form>

        </div>

        
    </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</div>

</body>
</html>