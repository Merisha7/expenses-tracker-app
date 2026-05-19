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
        $stmt = $pdo->prepare('UPDATE expenses SET amount = ?, date = ?, description = ? WHERE expenses_id = ? AND user_id = ?');
        $stmt->execute([$amount, $date, $description, $recordId, $currentUserId]);

        if ($stmt->rowCount() === 0) {
            $message = '<div class="message error">Expense not found or permission denied.</div>';
        } else {
            $message = '<div class="message success">Expense updated successfully!</div>';
        }
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
  <link rel="stylesheet" href="../assets/css/edit_form.css" />
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar">

    <div class="logo">
      <div class="logo-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M7 4h8l4 4v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z" />
          <path d="M15 4v4h4" />
          <path d="M9 11h6" />
          <path d="M9 15h6" />
        </svg>
      </div>
      <div class="logo-text">FinTrack</div>
    </div>

    <a class="nav-link" href="dashboard.html">⊞ Dashboard</a>
    <a class="nav-link" href="../income/add_income.php">⊕ Add Income</a>
    <a class="nav-link" href="../expenses/add_expenses.php">⊖ Add Expenses</a>
    <a class="nav-link" href="../goals/set_goals.php">◎ Set Goals</a>
    <a class="nav-link" href="../goals/view_goals.php">☑ View Goals</a>
    <a class="nav-link" href="../calendar/calendar.html">◷ Calendar</a>
    <a class="logout" href="../auth/logout.php">↩ Log Out</a>

  </div>

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