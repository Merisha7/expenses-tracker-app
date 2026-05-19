<?php
session_start();
include '../config/db.php';

// Return JSON response
header('Content-Type: application/json');

// Use the logged-in user when available; keep the existing local fallback behavior.
$user_id = (int) ($_SESSION['user_id'] ?? 1);

// Array to store all calendar data
$data = [];

/* FETCH INCOME DATA */
try {
    $stmt = $pdo->prepare("SELECT amount, description, date FROM income WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['date'];

        $data[$date][] = [
            "type" => "income",
            "amount" => $row['amount'],
            "description" => $row['description'],
            "date" => $row['date']
        ];
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit();
}

/*FETCH EXPENSE DATA*/
try {
    $stmt = $pdo->prepare("SELECT amount, description, date FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['date'];

        $data[$date][] = [
            "type" => "expense",
            "amount" => $row['amount'],
            "description" => $row['description'],
            "date" => $row['date']
        ];
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit();
}

/* FETCH GOALS DATA*/
try {
    $stmt = $pdo->prepare("SELECT goal_name, required_amount, current_savings, due_date FROM savings_goals WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $date = $row['due_date'];

        // Calculate remaining amount
        $remaining = $row['required_amount'] - $row['current_savings'];

        $data[$date][] = [
            "type" => "goal",
            "title" => $row['goal_name'],
            "target" => $row['required_amount'],
            "saved" => $row['current_savings'],
            "remaining" => $remaining,
            "deadline" => $row['due_date']
        ];
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit();
}

/* =========================================================
   RETURN FINAL JSON DATA
   ========================================================= */
echo json_encode($data);