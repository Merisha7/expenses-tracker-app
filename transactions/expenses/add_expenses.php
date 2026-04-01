<?php
// include 'db.php';

$user_id = 1; // Temporary user ID
$message = "";

// Only process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];                  
    $date = $_POST['date'];                     
    $description = trim($_POST['description']);  

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        $message = "Amount must be a positive number!";
    } else {
        // Prepare statement to avoid SQL injection
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, amount, date, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $user_id, $amount, $date, $description);

        // Execute and check success
        if ($stmt->execute()) {
            $message = "Expense added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

