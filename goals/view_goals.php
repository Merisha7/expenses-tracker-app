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

// Handle mark as achieved / restore request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['achieve_goal_id'])) {
    $goal_id = (int)$_POST['achieve_goal_id'];
    $is_achieved = isset($_POST['is_achieved']) && $_POST['is_achieved'] === '1' ? 1 : 0;
    
    // Fetch goal name for a friendly notification (if available)
    $goal_name_sql = "SELECT goal_name FROM savings_goals WHERE goal_id = ? AND user_id = ?";
    $goal_name_stmt = $pdo->prepare($goal_name_sql);
    $goal_name_stmt->execute([$goal_id, $user_id]);
    $goal_name_result = $goal_name_stmt->fetch(PDO::FETCH_ASSOC);

    // Verify goal belongs to current user
    $verify_sql = "SELECT goal_id FROM savings_goals WHERE goal_id = ? AND user_id = ?";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute([$goal_id, $user_id]);
    $verify_result = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($verify_result) > 0) {
        // Update with explicit user scoping
        $update_sql = "UPDATE savings_goals SET is_achieved = ? WHERE goal_id = ? AND user_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$is_achieved, $goal_id, $user_id]);

        // Set a session notification message
        if ($is_achieved == 1) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => "🎉 Goal '" . htmlspecialchars($goal_name_result['goal_name'] ?? 'Goal') . "' marked as completed!"
            ];
        } else {
            $_SESSION['notification'] = [
                'type' => 'info',
                'message' => "Goal '" . htmlspecialchars($goal_name_result['goal_name'] ?? 'Goal') . "' restored to active goals."
            ];
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
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

<!-- Sidebar Navigation -->
    <aside class="sidebar">

        <div class="brand-box">
            <span class="brand-icon" aria-hidden="true">
                <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none">
                    <path d="M8 4.75h6.1L18.25 8.9V19a1.25 1.25 0 0 1-1.25 1.25H8A1.25 1.25 0 0 1 6.75 19V6A1.25 1.25 0 0 1 8 4.75Z"
                        stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path>

                    <path d="M14 4.75V9h4.25"
                        stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path>

                    <path d="M9.75 13h5.5M9.75 16h5.5"
                        stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path>
                </svg>
            </span>

            <strong>FinTrack</strong>
        </div>

        <nav class="menu">

            <!-- Dashboard -->
            <a class="menu-item" href="../dashboard/dashboard.html">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M4 13h7V4H4v9zm9 7h7v-9h-7v9zM13 4v7h7V4h-7zM4 20h7v-4H4v4z"
                        stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
                Dashboard
            </a>

            <!-- Add Income -->
            <a class="menu-item" href="../income/add_income.php">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Add Income
            </a>

            <!-- Add Expenses -->
            <a class="menu-item" href="../expenses/add_expenses.php">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Add Expenses
            </a>

            <!-- Set Goals -->
            <a class="menu-item" href="../goals/set_goals.php">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="8.5"
                        stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="12" cy="12" r="3.5"
                        stroke="currentColor" stroke-width="1.8"/>
                </svg>
                Set Goals
            </a>

            <!-- View Goals -->
            <a class="menu-item active" href="../goals/view_goals.php">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M9 6h11M9 12h11M9 18h11"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>

                    <path d="M5.5 6.5l1.2 1.2L8.8 5.6M5.5 12.5l1.2 1.2L8.8 11.6M5.5 18.5l1.2 1.2L8.8 17.6"
                        stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                View Goals
            </a>

            <!-- Calendar -->
            <a class="menu-item" href="../calendar/calendar.html">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M3.5 8.5h17M7.5 3.5v5M16.5 3.5v5M5 5.5h14A1.5 1.5 0 0 1 20.5 7v11A1.5 1.5 0 0 1 19 19.5H5A1.5 1.5 0 0 1 3.5 18V7A1.5 1.5 0 0 1 5 5.5Z"
                        stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Calendar
            </a>

        </nav>

        <!-- Logout -->
        <a class="logout-link" href="../auth/logout.php">
            <svg class="logout-icon" viewBox="0 0 24 24" fill="none">
                <path d="M14 7V5.5A1.5 1.5 0 0 0 12.5 4h-6A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20h6A1.5 1.5 0 0 0 14 18.5V17"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"/>

                <path d="M10 12h9m-3-3 3 3-3 3"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            Log Out
        </a>

    </aside>

<!-- CONTENT -->
<div class="content">

    <h1>YOUR GOALS</h1>
    <p class="subtitle">View and manage your saved goals</p>

    <?php
        // Split goals into active and completed for clearer UI
        $active_goals = array_filter($goals, function($g){ return (int)$g['is_achieved'] === 0; });
        $completed_goals = array_filter($goals, function($g){ return (int)$g['is_achieved'] === 1; });
    ?>

    <?php if (isset($_SESSION['notification'])): ?>
        <div class="notification notification-<?php echo $_SESSION['notification']['type']; ?>" id="notification">
            <span class="notification-message"><?php echo $_SESSION['notification']['message']; ?></span>
            <button class="notification-close" onclick="closeNotification()">×</button>
        </div>
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    <!-- ACTIVE GOALS -->
    <?php if (empty($active_goals)): ?>
        <p style="text-align: center; margin-top: 40px; color: #999;">No active goals found. <a href="set_goals.php">Create a new goal</a></p>
    <?php else: ?>
        <?php foreach ($active_goals as $goal): ?>
            <?php
                $progress_percentage = ($goal['current_savings'] / $goal['required_amount']) * 100;
                if ($progress_percentage > 100) $progress_percentage = 100;
                $progress_percentage = round($progress_percentage, 1);
                $remaining_amount = max(0, $goal['required_amount'] - $goal['current_savings']);
                $start_date = date('m/d/Y', strtotime($goal['start_date']));
                $due_date = date('m/d/Y', strtotime($goal['due_date']));
            ?>
            <div class="goal-card">
                <div class="goal-top">
                    <div>
                        <h2><?php echo htmlspecialchars($goal['goal_name']); ?></h2>
                        <p class="date"><?php echo $start_date; ?> - <?php echo $due_date; ?></p>
                    </div>

                    <div class="icons">
                        <a href="edit_goals.php?goal_id=<?php echo $goal['goal_id']; ?>" style="cursor: pointer; text-decoration: none; color: inherit;">
                            <svg class="icon-btn" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-width="2" d="M12 20h9"/>
                                <path stroke-width="2" d="M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z"/>
                            </svg>
                        </a>
                        <a href="../dashboard/delete.php?id=<?php echo $goal['goal_id']; ?>&type=goal" style="cursor: pointer; text-decoration: none; color: inherit;">
                            <svg class="icon-btn delete" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-width="2" d="M3 6h18"/>
                                <path stroke-width="2" d="M8 6V4h8v2"/>
                                <path stroke-width="2" d="M6 6v14h12V6"/>
                                <path stroke-width="2" d="M10 11v6"/>
                                <path stroke-width="2" d="M14 11v6"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="progress-header">
                    <span>Goal Progress</span>
                    <span><?php echo $progress_percentage; ?>%</span>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?php echo $progress_percentage; ?>%"></div>
                </div>

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

                <div class="bottom">
                    <p class="budget">Budget Rs <?php echo number_format($goal['total_budget'], 2); ?></p>

                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display: inline;">
                        <input type="hidden" name="achieve_goal_id" value="<?php echo $goal['goal_id']; ?>">
                        <input type="hidden" name="is_achieved" value="1">
                        <label class="checkbox">
                            <input type="checkbox" onchange="this.form.submit()">
                            <span>Mark as achieved</span>
                        </label>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- COMPLETED GOALS -->
    <?php if (!empty($completed_goals)): ?>
        <h2 style="margin-top: 50px; margin-bottom: 25px;">Completed Goals</h2>
        <?php foreach ($completed_goals as $goal): ?>
            <?php
                $progress_percentage = ($goal['current_savings'] / $goal['required_amount']) * 100;
                if ($progress_percentage > 100) $progress_percentage = 100;
                $progress_percentage = round($progress_percentage, 1);
                $remaining_amount = max(0, $goal['required_amount'] - $goal['current_savings']);
                $start_date = date('m/d/Y', strtotime($goal['start_date']));
                $due_date = date('m/d/Y', strtotime($goal['due_date']));
            ?>
            <div class="goal-card goal-completed">
                <div class="goal-top">
                    <div>
                        <h2><?php echo htmlspecialchars($goal['goal_name']); ?></h2>
                        <p class="date"><?php echo $start_date; ?> - <?php echo $due_date; ?></p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="goal-status goal-status-complete">Goal Completed</span>
                        <div class="icons">
                            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display: inline;">
                                <input type="hidden" name="achieve_goal_id" value="<?php echo $goal['goal_id']; ?>">
                                <input type="hidden" name="is_achieved" value="0">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0; color: inherit;" title="Restore">
                                    <svg class="icon-btn" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" d="M3 7v6h6M21 17v-6h-6"/>
                                        <path stroke-width="2" d="M16.83 13a5 5 0 0 1-9.66-1M7.17 11a5 5 0 0 0 9.66 1"/>
                                    </svg>
                                </button>
                            </form>

                            <a href="../dashboard/delete.php?id=<?php echo $goal['goal_id']; ?>&type=goal" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <svg class="icon-btn delete" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-width="2" d="M3 6h18"/>
                                    <path stroke-width="2" d="M8 6V4h8v2"/>
                                    <path stroke-width="2" d="M6 6v14h12V6"/>
                                    <path stroke-width="2" d="M10 11v6"/>
                                    <path stroke-width="2" d="M14 11v6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="progress-header">
                    <span>Goal Progress</span>
                    <span><?php echo $progress_percentage; ?>%</span>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?php echo $progress_percentage; ?>%"></div>
                </div>

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

                <div class="bottom">
                    <p class="budget">Budget Rs <?php echo number_format($goal['total_budget'], 2); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notification = document.getElementById('notification');
    if (notification) {
        // Auto-dismiss after 5s
        setTimeout(function() {
            notification.classList.add('notification-fade-out');
            setTimeout(function() { notification.style.display = 'none'; }, 260);
        }, 5000);

        // attach click handler if button exists
        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) closeBtn.addEventListener('click', closeNotification);
    }
});

function closeNotification() {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.classList.add('notification-fade-out');
        setTimeout(function() { notification.style.display = 'none'; }, 260);
    }
}
</script>

</body>
</html>