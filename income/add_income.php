<?php
session_start();
// Include database connection file
include '../config/db.php';

// Redirect to login if user not logged in 
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

//Get logged in user's ID from session 
$user_id = $_SESSION['user_id'];


// For Testing
// $user_id = 1;

// Variables to store message text and type 
$message = "";
$messageType = "";

// Check if the form is submitted using POST method
// Check if the form is submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Remove commas from amount input
    $amount = str_replace(',', '', $_POST['amount']);

    // Remove extra spaces from source input
    $source = trim($_POST['source']);

    // Get selected date
    $date = $_POST['date'];

    // Remove extra spaces from description
    $description = trim($_POST['description']);

    /* VALIDATE AMOUNT */

    // Check if amount is NOT numeric
    // Example: abc
    if (!is_numeric($amount)) {

        $message = "Amount must be an integer!";
        $messageType = "error";

    // Check if amount contains decimal value
    // Example: 500.75
    } elseif (floor((float)$amount) != $amount) {

        $message = "Amount must be an integer!";
        $messageType = "error";

    // Check if amount is less than or equal to zero
    // Example: -100 or 0
    } elseif ($amount <= 0) {

        $message = "Amount must be a positive number!";
        $messageType = "error";

    } else {

        // Prepare SQL query to insert income data
        try {

            $stmt = $pdo->prepare(
                "INSERT INTO income (user_id, amount, source, description, date)
                 VALUES (?, ?, ?, ?, ?)"
            );

            // Execute insert query
            if ($stmt->execute([
                $user_id,
                $amount,
                $source,
                $description,
                $date
            ])) {

                // Success message
                $message = "Income added successfully!";
                $messageType = "success";

            } else {

                // Error message if insert fails
                $message = "Error adding income!";
                $messageType = "error";
            }

        } catch (PDOException $e) {

            // Database error message
            $message = "Database error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Page title -->
    <title>Add Income</title>

    <!-- Makes page responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Link external CSS -->
    <link rel="stylesheet" href="../assets/css/transactions.css">
</head>
<body>

<div class="main">

    <!-- POPUP MESSAGE -->
    <?php if (!empty($message)): ?> <!-- Show only if message exists -->
    <div class="popup">
        <!-- Add class based on message type (success/error) -->
        <div class="popup-content <?php echo $messageType; ?>">

            <!-- Close button (reloads page) -->
            <a href="add_income.php" class="close-btn">×</a>

            <!-- Display message -->
            <p><?php echo $message; ?></p>

            <!-- If success, show dashboard button -->
            <?php if ($messageType == "success"): ?>
            <a href="../dashboard/dashboard.html" class="popup-btn">
                Go to Dashboard
            </a>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

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
            <a class="menu-item active" href="add_income.php">
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
            <a class="menu-item" href="../goals/view_goals.php">
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

    <!-- Main Content -->
    <div class="content">
        <!-- Page heading -->
        <h1>ADD INCOME</h1>

        <!-- Subtitle -->
        <p class="subtitle">Record a new income entry</p>

        <!-- Form card -->
        <div class="card">
            <form method="POST">

                <!-- Input for amount -->
                <label>Income Amount:</label>
                <input type="text" name="amount" required> 

                <!-- Input for source -->
                <label>Source:</label>
                <input type="text" name="source" required> 

                <!-- Input for date -->
                <label>Date:</label>
                <input type="date" name="date" required> 

                <!-- Description textarea -->
                <label>Description:</label>
                <textarea name="description"></textarea> 

                <!-- Submit button -->
                <button class="btn-income">ADD INCOME</button> 
            </form>
        </div>
    </div>

</div>

</body>
</html>