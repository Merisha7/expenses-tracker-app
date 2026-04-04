<?php
// Include database connection file
include '../config/db.php';

// Hardcoded user ID (for now, assumes user with ID = 1)
$user_id = 1;

// Variables to store message and message type (success/error)
$message = "";
$messageType = "";

// Check if the form is submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];                  
    $date = $_POST['date'];                     
    $description = trim($_POST['description']);  
    if (!is_numeric($amount) || $amount <= 0) {
        $message = "Amount must be a positive number!";
        $messageType = "error";
    } else {

        // Prepare SQL query to insert expense into database
        $stmt = $conn->prepare(
            "INSERT INTO expenses (user_id, amount, description, date) VALUES (?, ?, ?, ?)"
        );

        // Bind parameters to the SQL query
        $stmt->bind_param("idss", $user_id, $amount, $description, $date);

        // Execute the query
        if ($stmt->execute()) {
            // If successful, set success message
            $message = "Expense added successfully!";
            $messageType = "success";
        } else {
            // If error occurs, set error message
            $message = "Error adding expense!";
            $messageType = "error";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Expense</title>

    <!-- Makes the page responsive on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="../assets/css/transactions.css">
</head>
<body>

<div class="main">

    <!-- POPUP MESSAGE SECTION -->
    <?php if (!empty($message)): ?> <!-- Show popup only if message is not empty -->
    <div class="popup">
        <div class="popup-content <?php echo $messageType; ?>"> <!-- Add class based on success/error -->

            <!-- Close button (reloads page) -->
            <a href="add_expenses.php" class="close-btn">×</a>

            <!-- Display message -->
            <p><?php echo $message; ?></p>

            <!-- If success, show button to go to dashboard -->
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

        <!-- Logo Section -->
        <div class="logo">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <!-- SVG icon path -->
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

        <!-- Add Income link -->
        <a href="../income/add_income.php">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
            Add Income
        </a>

        <!-- Add Expenses link (active page) -->
        <a href="add_expenses.php" class="active">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M5 12h14"/>
            </svg>
            Add Expenses
        </a>

        <!-- Logout link -->
        <a href="../auth/logout.php" class="logout">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" d="M15 12H3m6-6l-6 6 6 6M21 3v18"/>
            </svg>
            Log Out
        </a>

    </div>

    <!-- Main Content Area -->
    <div class="content">
        <!-- Page heading -->
        <h1>ADD EXPENSES</h1>

        <!-- Subtitle -->
        <p class="subtitle">Record a new expense entry</p>

        <!-- Card container for form -->
        <div class="card">
            <!-- Form to submit expense -->
            <form method="POST">

                <!-- Input for amount -->
                <label>Expense Amount:</label>
                <input type="text" name="amount" required>

                <!-- Input for date -->
                <label>Date:</label>
                <input type="date" name="date" required>

                <!-- Textarea for description -->
                <label>Description:</label>
                <textarea name="description"></textarea>

                <!-- Submit button -->
                <button class="btn-expense">ADD EXPENSE</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>