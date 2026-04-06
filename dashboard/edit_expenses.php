<?php
// edit_expenses.php

// --- STEP 1: Load existing expense data (replace with real DB query) ---
// Example real query: SELECT * FROM expenses WHERE id = $_GET['id']
$expense = [];

// --- STEP 2: Handle form submission ---
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $amount      = trim($_POST["amount"]);
  $date        = trim($_POST["date"]);
  $description = trim($_POST["description"]);

  if (empty($amount) || empty($date)) {
    $message = '<div class="message error">Please fill in Amount and Date.</div>';
  } else {
    // Real project: UPDATE expenses SET amount=?, date=?, description=? WHERE id=?

    $message = '<div class="message success">Expense updated successfully!</div>';

    $expense["amount"]      = $amount;
    $expense["date"]        = $date;
    $expense["description"] = $description;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>FinTrack - Edit Expenses</title>
  <link rel="stylesheet" href="assets/css/edit_form.css" />
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

    <a class="nav-link" href="dashboard.php">⊞ Dashboard</a>
    <a class="nav-link" href="add_income.php">⊕ Add Income</a>
    <a class="nav-link" href="add_expenses.php">⊖ Add Expenses</a>
    <a class="logout" href="logout.php">↩ Log Out</a>

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
          <a class="btn btn-cancel" href="dashboard.php">CANCEL</a>
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
