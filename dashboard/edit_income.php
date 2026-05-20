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
        // Check if the income exists for this user
        $checkStmt = $pdo->prepare('SELECT 1 FROM income WHERE income_id = ? AND user_id = ?');
        $checkStmt->execute([$recordId, $currentUserId]);
        
        if ($checkStmt->rowCount() === 0) {
            $message = '<div class="message error">Income not found or permission denied.</div>';
        } else {
            // Record exists, update it
            $stmt = $pdo->prepare('UPDATE income SET amount = ?, source = ?, date = ?, description = ? WHERE income_id = ? AND user_id = ?');
            $stmt->execute([$amount, $source, $date, $description, $recordId, $currentUserId]);
            $message = '<div class="message success">Income updated successfully!</div>';
        }
        
        $loadFromDb = false;
        $income = [
            'id' => $recordId,
            'amount' => $amount,
            'source' => $source,
            'date' => $date,
            'description' => $description
        ];
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