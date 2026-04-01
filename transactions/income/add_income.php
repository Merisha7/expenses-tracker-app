<?php
//include 'db.php';

// Temporary user ID 
$user_id = 1;

// Variable to store messages 
$message = "";

// Only run if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect form input values
    $amount = $_POST['amount'];               
    $source = trim($_POST['source']);        
    $date = $_POST['date'];                  
    $description = trim($_POST['description']); 

    // Validate amount: must be numeric and greater than 0
    if (!is_numeric($amount) || $amount <= 0) {
        $message = "Amount must be a positive number!";
    } else {

        // Prepare SQL statement to prevent SQL Injection
        $stmt = $conn->prepare(
            "INSERT INTO income_table (user_id, amount, source, date, description) VALUES (?, ?, ?, ?, ?)"
        );

        // Bind variables to placeholders 
        $stmt->bind_param("idsss", $user_id, $amount, $source, $date, $description);

        // Execute query and check success
        if ($stmt->execute()) {
            $message = "Income added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close(); 
    }
}
?>

