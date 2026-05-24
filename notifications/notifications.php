<?php
session_start();
require_once "../config/db.php";

// Sets the response type as JSON
header("Content-Type: application/json");

try {
    // USER ID

    // Gets user_id from session
    // If session user_id does not exist, default value becomes 1
    $user_id = $_SESSION['user_id'] ?? 1;

    // CHECK SAVINGS GOALS

    // SQL query to fetch all savings goals of the logged-in user
    $stmt = $pdo->prepare("
        SELECT
            goal_id,
            goal_name,
            required_amount,
            current_savings
        FROM savings_goals
        WHERE user_id = ?
    ");

    // Executes the query using user_id
    $stmt->execute([$user_id]);

    // Loops through each savings goal
    while ($goal = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Stores goal ID in variable
        $goal_id = $goal['goal_id'];

        // Converts required amount into float value
        $target = (float)$goal['required_amount'];

        // Converts current savings into float value
        $saved = (float)$goal['current_savings'];

        // If target amount is 0 or less, skip this goal
        if ($target <= 0) {
            continue;
        }

        // Calculates savings completion percentage
        $percent = ($saved / $target) * 100;

        // Initializes empty message variable
        $message = "";

        // Initializes empty notification type variable
        $type = "";

        // Checks if savings reached or exceeded 100%
        if ($percent >= 100) {

            // Completion notification message
            $message =
                "🎉 Goal '{$goal['goal_name']}' completed successfully!";

            // Notification type set as completed
            $type = "completed";
        }

        // Checks if savings reached 80% or more
        elseif ($percent >= 80) {

            // Progress notification message
            $message =
                "🚀 '{$goal['goal_name']}' is over 80% completed!";

            // Notification type set as progress
            $type = "progress";
        }


        // Runs only if notification type is not empty
        if ($type !== "") {

            // Checks whether same notification already exists
            $check = $pdo->prepare("
                SELECT notification_id
                FROM notifications
                WHERE user_id = ?
                AND goal_id = ?
                AND notification_type = ?
            ");

            // Executes duplicate-check query
            $check->execute([
                $user_id,
                $goal_id,
                $type
            ]);

            // If notification does not already exist
            if ($check->rowCount() == 0) {

                // Prepares query to insert new notification
                $insert = $pdo->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        goal_id,
                        message,
                        notification_type,
                        is_read
                    )
                    VALUES (?, ?, ?, ?, 0)
                ");

                // Executes insert query with values
                $insert->execute([
                    $user_id,
                    $goal_id,
                    $message,
                    $type
                ]);
            }
        }
    }

    // MARK AS READ

    // Checks if URL contains ?read parameter
    if (isset($_GET['read'])) {

        // Updates all notifications as read for current user
        $update = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
        ");

        // Executes update query
        $update->execute([$user_id]);

        // Returns success message in JSON format
        echo json_encode([
            "success" => true
        ]);

        // Stops further code execution
        exit;
    }

    // Query to fetch all notifications of the user
    $stmt = $pdo->prepare("
        SELECT
            notification_id,
            message,
            created_at,
            is_read
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    // Executes notification fetch query
    $stmt->execute([$user_id]);

    // Fetches all notifications as associative array
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Converts notifications array into JSON response
    echo json_encode($notifications);

} catch (PDOException $e) {

    // Returns database error message in JSON format
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}