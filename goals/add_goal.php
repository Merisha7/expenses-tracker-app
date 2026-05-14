<?php
header("Content-Type: application/json");
session_start();

// 1. Check user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Please log in first."]);
    exit;
}

// 2. Get data sent from frontend
$data = json_decode(file_get_contents("php://input"), true);

$goal_name       = $data["goal_name"]       ?? "";
$required_amount = $data["required_amount"] ?? "";
$saved_amount    = $data["saved_amount"]    ?? "";
$total_budget    = $data["total_budget"]    ?? "";
$start_date      = $data["start_date"]      ?? "";
$due_date        = $data["due_date"]        ?? "";

// 3. Make sure nothing is empty
if (!$goal_name || !$required_amount || !$saved_amount || !$total_budget || !$start_date || !$due_date) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// 4. Make sure amounts are valid numbers
if ($required_amount <= 0 || $saved_amount < 0 || $total_budget <= 0) {
    echo json_encode(["success" => false, "message" => "Amounts must be positive numbers."]);
    exit;
}

// 5. Make sure due date is not before start date
if ($due_date < $start_date) {
    echo json_encode(["success" => false, "message" => "Due date cannot be before start date."]);
    exit;
}

// 6. Connect to database
$conn = new mysqli("localhost", "root", "", "expenses_tracker");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Could not connect to database."]);
    exit;
}

// 7. Save the goal
$stmt = $conn->prepare(
    "INSERT INTO saving_goals (user_id, goal_name, required_amount, saved_amount, total_budget, start_date, due_date)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("isdddss", $_SESSION["user_id"], $goal_name, $required_amount, $saved_amount, $total_budget, $start_date, $due_date);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Goal saved successfully.", "goal_id" => $stmt->insert_id]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save goal."]);
}

$stmt->close();
$conn->close();
