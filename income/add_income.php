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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];               
    $source = trim($_POST['source']);        
    $date = $_POST['date'];                  
    $description = trim($_POST['description']); 

    // Validate amount (must be numeric and greater than 0)
    if (!is_numeric($amount) || $amount <= 0) {
        $message = "Amount must be a positive number!";
        $messageType = "error";
    } else {

        // Prepare SQL query to insert data into income table
        $stmt = $conn->prepare(
            "INSERT INTO income (user_id, amount, source, description, date) VALUES (?, ?, ?, ?, ?)"
        );

        // Bind values to query placeholders
        $stmt->bind_param("idsss", $user_id, $amount, $source, $description, $date);

        // Execute the query
        if ($stmt->execute()) {
            $message = "Income added successfully!";
            $messageType = "success";
        } else {
            $message = "Error adding income!";
            $messageType = "error";
        }

        
        $stmt->close();
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
    <div class="sidebar">

        <!-- Logo section -->
        <div class="logo">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <!-- SVG icon -->
                <path stroke-width="2" d="M3 7h18v13H3zM16 3H5a2 2 0 00-2 2v2h18V5a2 2 0 00-2-2z"/>
            </svg>
            FinTrack 
        </div>

        <!-- Dashboard link -->
        <a href="../dashboard/dashboard.html">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>
            </svg>
            Dashboard
        </a>

        <!-- Add Income (current active page) -->
        <a href="add_income.php" class="active">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
            Add Income
        </a>

        <!-- Add Expenses link -->
        <a href="../expenses/add_expenses.php">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M5 12h14"/>
            </svg>
            Add Expenses
        </a>

        <!-- Logout link-->
        <a href="../auth/logout.php" class="logout">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M15 12H3m6-6l-6 6 6 6M21 3v18"/>
            </svg>
            Log Out
        </a>

    </div>

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