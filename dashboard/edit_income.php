<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$currentUserId = $_SESSION['user_id'] ?? 0;
if (!$currentUserId) {
    header('Location: ../auth/login.php');
    exit;
}
$message = '';
$income = [
    'id' => '',
    'amount' => '',
    'source' => '',
    'date' => '',
    'description' => ''
];
$recordId = 0;
$loadFromDb = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int) ($_POST['id'] ?? 0);
    $amount = trim($_POST['amount'] ?? '');
    $source = trim($_POST['source'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($recordId <= 0 || empty($amount) || empty($source) || empty($date)) {
        $message = '<div class="message error">Please fill in Amount, Source, and Date.</div>';
        $income = [
            'id' => $recordId,
            'amount' => $amount,
            'source' => $source,
            'date' => $date,
            'description' => $description
        ];
        $loadFromDb = false;
    } else {
        $stmt = $pdo->prepare('UPDATE income SET amount = ?, source = ?, date = ?, description = ? WHERE income_id = ? AND user_id = ?');
        $stmt->execute([$amount, $source, $date, $description, $recordId, $currentUserId]);

        if ($stmt->rowCount() === 0) {
            $message = '<div class="message error">Income not found or permission denied.</div>';
        } else {
            $message = '<div class="message success">Income updated successfully!</div>';
        }
    }
} else {
    $recordId = (int) ($_GET['id'] ?? 0);
}

if ($recordId <= 0) {
    die('Invalid income ID.');
}

if ($loadFromDb) {
    $stmt = $pdo->prepare('SELECT income_id AS id, amount, source, date, COALESCE(description, "") AS description FROM income WHERE income_id = ? AND user_id = ?');
    $stmt->execute([$recordId, $currentUserId]);
    $income = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$income) {
        die('Income not found.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>FinTrack - Edit Income</title>
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

      <h1>EDIT INCOME</h1>
      <p class="subtitle">Update income entry details</p>

      <?php echo $message; ?>

      <form method="POST" action="edit_income.php">

        <input type="hidden" name="id" value="<?php echo $income['id']; ?>" />

        <label>Income Amount:</label>
        <input type="number" name="amount" value="<?php echo $income['amount']; ?>" />

        <label>Source:</label>
        <input type="text" name="source" value="<?php echo $income['source']; ?>" />

        <label>Date:</label>
        <input type="date" name="date" value="<?php echo $income['date']; ?>" />

        <label>Description:</label>
        <textarea name="description"><?php echo $income['description']; ?></textarea>

        <div class="btn-row">
          <a class="btn btn-cancel" href="dashboard.html">CANCEL</a>
          <button class="btn btn-save-income" type="submit">SAVE EDIT</button>
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