<?php
$host    = 'localhost';       
$dbname  = 'expenses_db';   
$db_user = 'root';           
$db_pass = '';               

// Try to connect. If it fails, stop and show an error.
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// CHECK IF USER IS LOGGED IN
// We get the logged-in user's ID from the session.
// If no one is logged in, stop here.
session_start();

$current_user_id = $_SESSION['user_id'] ?? 0; // 0 means no one is logged in

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// THE DELETE FUNCTION
function deleteRecord(PDO $pdo, int $record_id, int $user_id): array
{
    // Make sure the record ID is a positive number
    if ($record_id <= 0) {
        return ['success' => false, 'message' => 'Invalid record ID.'];
    }

    // Look for the record in the database
    $stmt = $pdo->prepare("SELECT id, user_id, type, description, amount FROM records WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();

    // If no record was found, stop
    if (!$record) {
        return ['success' => false, 'message' => 'Record not found.'];
    }

    // If the record belongs to someone else, stop
    if ((int)$record['user_id'] !== $user_id) {
        return ['success' => false, 'message' => 'Permission denied. You can only delete your own records.'];
    }

    // All checks passed → delete the record
    $del = $pdo->prepare("DELETE FROM records WHERE id = ? AND user_id = ?");
    $del->execute([$record_id, $user_id]);

    return ['success' => true, 'message' => 'Record deleted successfully.'];
}

// SHOW CONFIRMATION PAGE
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['record_id'])) {

    $record_id = (int)$_GET['record_id'];

    // Get the record from the database to display its details
    $stmt = $pdo->prepare("SELECT id, user_id, type, description, amount FROM records WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();

    // Record doesn't exist? Stop.
    if (!$record) {
        die('Record not found.');
    }

    // Record belongs to someone else? Stop.
    if ((int)$record['user_id'] !== $current_user_id) {
        die('Permission denied. You can only delete your own records.');
    }

    // Show the confirmation page with record details
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Confirm Delete</title>
    </head>
    <body>
        <h2>Are you sure you want to delete this record?</h2>
        <p><strong>Type:</strong> <?= htmlspecialchars(ucfirst($record['type'])) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($record['description']) ?></p>
        <p><strong>Amount:</strong> $<?= number_format($record['amount'], 2) ?></p>

        <!-- If user clicks "Yes, Delete" → form submits as POST to trigger actual deletion -->
        <!-- If user clicks "Cancel" → goes back to the previous page -->
        <form method="POST" action="delete.php">
            <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
            <input type="hidden" name="confirmed" value="yes"> <!-- proof that user confirmed -->
            <button type="submit">Yes, Delete</button>
            <a href="javascript:history.back()">Cancel</a>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// ACTUALLY DELETE THE RECORD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    // If somehow confirmed=yes is missing, reject the request
    // (prevents accidental or malicious direct POST calls)
    if (($_POST['confirmed'] ?? '') !== 'yes') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Deletion not confirmed.']);
        exit;
    }

    // Get the record ID from the form
    $record_id = (int)($_POST['record_id'] ?? 0);

    // Run the delete function and return the result as JSON
    $result = deleteRecord($pdo, $record_id, $current_user_id);

    http_response_code($result['success'] ? 200 : 403);
    echo json_encode($result);
    exit;
}

// FALLBACK: Wrong request method (not GET or POST)
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
exit;