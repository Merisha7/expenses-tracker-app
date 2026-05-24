<?php

session_start();
include '../config/db.php';

// Sets the response format as JSON
header('Content-Type: application/json');

// Gets logged-in user's ID from session
// If no session exists, default value becomes 1
$user_id = (int) ($_SESSION['user_id'] ?? 1);

// Creates an empty array to store all calendar data
$data = [];

/* FETCH INCOME DATA */
try {

    // SQL query to fetch income records of current user
    $stmt = $pdo->prepare("
        SELECT amount, description, date
        FROM income
        WHERE user_id = ?
    ");

    // Executes the query using user_id
    $stmt->execute([$user_id]);
    
    // Loops through each income record
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Stores income date in variable
        $date = $row['date'];

        // Adds income data into array using date as key
        $data[$date][] = [

            // Identifies record type as income
            "type" => "income",

            // Stores income amount
            "amount" => $row['amount'],

            // Stores income description
            "description" => $row['description'],

            // Stores income date
            "date" => $row['date']
        ];
    }

} catch (PDOException $e) {

    // Returns database error message in JSON format
    echo json_encode([
        "error" => "Database error: " . $e->getMessage()
    ]);

    // Stops further code execution
    exit();
}

/*FETCH EXPENSE DATA*/
try {

    // SQL query to fetch expense records of current user
    $stmt = $pdo->prepare("
        SELECT amount, description, date
        FROM expenses
        WHERE user_id = ?
    ");

    // Executes expense query
    $stmt->execute([$user_id]);
    
    // Loops through each expense record
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Stores expense date in variable
        $date = $row['date'];

        // Adds expense data into array using date as key
        $data[$date][] = [

            // Identifies record type as expense
            "type" => "expense",

            // Stores expense amount
            "amount" => $row['amount'],

            // Stores expense description
            "description" => $row['description'],

            // Stores expense date
            "date" => $row['date']
        ];
    }

} catch (PDOException $e) {

    // Returns database error message in JSON format
    echo json_encode([
        "error" => "Database error: " . $e->getMessage()
    ]);

    // Stops execution if error occurs
    exit();
}

/* FETCH GOALS DATA*/
try {

    // SQL query to fetch savings goals of current user
    $stmt = $pdo->prepare("
        SELECT
            goal_name,
            required_amount,
            current_savings,
            due_date
        FROM savings_goals
        WHERE user_id = ?
    ");

    // Executes goals query
    $stmt->execute([$user_id]);
    
    // Loops through each savings goal
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Stores goal due date in variable
        $date = $row['due_date'];

        // Calculates remaining amount needed to complete goal
        $remaining =
            $row['required_amount'] -
            $row['current_savings'];

        // Adds goal data into array using due date as key
        $data[$date][] = [

            // Identifies record type as goal
            "type" => "goal",

            // Stores goal title/name
            "title" => $row['goal_name'],

            // Stores required target amount
            "target" => $row['required_amount'],

            // Stores current saved amount
            "saved" => $row['current_savings'],

            // Stores remaining amount needed
            "remaining" => $remaining,

            // Stores goal deadline
            "deadline" => $row['due_date']
        ];
    }

} catch (PDOException $e) {

    // Returns database error message in JSON format
    echo json_encode([
        "error" => "Database error: " . $e->getMessage()
    ]);

    // Stops execution if error occurs
    exit();
}

/*RETURN FINAL JSON DATA*/

// Converts final calendar data array into JSON response
echo json_encode($data);