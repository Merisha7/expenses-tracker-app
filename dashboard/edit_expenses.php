<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$currentUserId = $_SESSION['user_id'] ?? 0;
if (!$currentUserId) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$expense = [
    'id' => '',
    'amount' => '',
    'date' => '',
    'description' => ''
];
$recordId = 0;
$loadFromDb = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int) ($_POST['id'] ?? 0);
    $amount = trim($_POST['amount'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($recordId <= 0 || empty($amount) || empty($date)) {
        $message = '<div class="message error">Please fill in Amount and Date.</div>';
        $expense = [
            'id' => $recordId,
            'amount' => $amount,
            'date' => $date,
            'description' => $description
        ];
        $loadFromDb = false;
    } else {
        // Check if the expense exists for this user
        $checkStmt = $pdo->prepare('SELECT 1 FROM expenses WHERE expenses_id = ? AND user_id = ?');
        $checkStmt->execute([$recordId, $currentUserId]);
        
        if ($checkStmt->rowCount() === 0) {
            $message = '<div class="message error">Expense not found or permission denied.</div>';
        } else {
            // Record exists, update it
            $stmt = $pdo->prepare('UPDATE expenses SET amount = ?, date = ?, description = ? WHERE expenses_id = ? AND user_id = ?');
            $stmt->execute([$amount, $date, $description, $recordId, $currentUserId]);
            $message = '<div class="message success">Expense updated successfully!</div>';
        }
        
        $loadFromDb = false;
        $expense = [
            'id' => $recordId,
            'amount' => $amount,
            'date' => $date,
            'description' => $description
        ];
    }
} else {
    $recordId = (int) ($_GET['id'] ?? 0);
}

if ($recordId <= 0) {
    die('Invalid expense ID.');
}

if ($loadFromDb) {
    $stmt = $pdo->prepare('SELECT expenses_id AS id, amount, date, COALESCE(description, "") AS description FROM expenses WHERE expenses_id = ? AND user_id = ?');
    $stmt->execute([$recordId, $currentUserId]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        die('Expense not found.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>FinTrack - Edit Expenses</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/edit_form.css" />
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="brand-box">
        <span class="brand-icon" aria-hidden="true">
            <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none">
                <rect x="3.5" y="6.5" width="17" height="12" rx="1.8" stroke="currentColor" stroke-width="1.9"></rect>
                <path d="M3.5 10.2H20.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"></path>
            </svg>
        </span>
        <strong>FinTrack</strong>
    </div>

    <nav class="menu">
        <a class="menu-item active" href="dashboard.html"><i class="fas fa-tachometer-alt menu-icon"></i>Dashboard</a>
        <a class="menu-item" href="../income/add_income.php"><i class="fas fa-plus-circle menu-icon"></i>Add Income</a>
        <a class="menu-item" href="../expenses/add_expenses.php"><i class="fas fa-minus-circle menu-icon"></i>Add Expenses</a>
        <a class="menu-item" href="../goals/set_goals.php"><i class="fas fa-bullseye menu-icon"></i>Set Goals</a>
        <a class="menu-item" href="../goals/view_goals.php"><i class="fas fa-list-check menu-icon"></i>View Goals</a>
        <a class="menu-item" href="../calendar/calendar.html"><i class="fas fa-calendar-alt menu-icon"></i>Calendar</a>
    </nav>

    <a class="logout-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt logout-icon"></i>Log Out</a>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main">
    <div class="card">

      <h1>EDIT EXPENSES</h1>
      <p class="subtitle">Update expense entry details</p>

      <?php echo $message; ?>

      <form method="POST" action="edit_expenses.php">

        <input type="hidden" name="id" value="<?php echo $expense['id']; ?>" />

        <label>Expenses Amount:</label>
        <input type="number" name="amount" value="<?php echo $expense['amount']; ?>" />

        <label>Date:</label>
        <input type="date" name="date" value="<?php echo $expense['date']; ?>" />

        <label>Description:</label>
        <textarea name="description"><?php echo $expense['description']; ?></textarea>

        <div class="btn-row">
          <a class="btn btn-cancel" href="dashboard.html">CANCEL</a>
          <button class="btn btn-save-expense" type="submit">SAVE EDIT</button>
        </div>

      </form>

    </div>
  </div>

  <script>
    // Auto-dismiss success message after 3.5 seconds
    const successMsg = document.querySelector('.message.success');
    if (successMsg) {
      setTimeout(() => {
        successMsg.style.opacity = '0';
        successMsg.style.transition = 'opacity 0.5s ease-in-out';
        setTimeout(() => {
          successMsg.style.display = 'none';
        }, 500);
      }, 3500);
    }
  </script>

</body>
</html>