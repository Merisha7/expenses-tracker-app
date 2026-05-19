<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$current_user_id = $_SESSION['user_id'] ?? 0;
if (!$current_user_id) {
    header('Location: ../auth/login.php');
    exit;
}

function getRecord(PDO $pdo, string $type, int $id, int $user_id): ?array
{
    if ($id <= 0) {
        return null;
    }

    if ($type === 'income') {
        $stmt = $pdo->prepare('SELECT income_id AS id, COALESCE(description, "") AS description, source, amount, date FROM income WHERE income_id = ? AND user_id = ?');
    } elseif ($type === 'expense') {
        $stmt = $pdo->prepare('SELECT expenses_id AS id, COALESCE(description, "") AS description, amount, date FROM expenses WHERE expenses_id = ? AND user_id = ?');
    } elseif ($type === 'goal') {
        $stmt = $pdo->prepare('SELECT goal_id AS id, goal_name AS name, required_amount AS amount, start_date, due_date FROM savings_goals WHERE goal_id = ? AND user_id = ?');
    } else {
        return null;
    }

    $stmt->execute([$id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function deleteRecord(PDO $pdo, string $type, int $id, int $user_id): bool
{
    if ($type === 'income') {
        $stmt = $pdo->prepare('DELETE FROM income WHERE income_id = ? AND user_id = ?');
    } elseif ($type === 'expense') {
        $stmt = $pdo->prepare('DELETE FROM expenses WHERE expenses_id = ? AND user_id = ?');
    } elseif ($type === 'goal') {
        $stmt = $pdo->prepare('DELETE FROM savings_goals WHERE goal_id = ? AND user_id = ?');
    } else {
        return false;
    }

    $stmt->execute([$id, $user_id]);
    return $stmt->rowCount() > 0;
}

$allowedTypes = ['income', 'expense', 'goal'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $recordId = (int) ($_GET['id'] ?? 0);
    $recordType = $_GET['type'] ?? '';

    if ($recordId <= 0 || !in_array($recordType, $allowedTypes, true)) {
        die('Invalid delete request.');
    }

    $record = getRecord($pdo, $recordType, $recordId, $current_user_id);
    if (!$record) {
        die('Record not found.');
    }

    $recordLabel = $recordType === 'income' ? 'Income' : ($recordType === 'expense' ? 'Expense' : 'Goal');
    if ($recordType === 'goal') {
        $recordDescription = $record['name'];
        $startDateFormatted = date('m/d/Y', strtotime($record['start_date']));
        $dueDateFormatted = date('m/d/Y', strtotime($record['due_date']));
    } else {
        $recordDescription = $recordType === 'income' ? ($record['description'] !== '' ? $record['description'] : $record['source']) : $record['description'];
        $dateFormatted = !empty($record['date']) ? date('m/d/Y', strtotime($record['date'])) : 'N/A';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FinTrack - Delete <?= htmlspecialchars($recordLabel) ?></title>
        <link rel="stylesheet" href="../assets/css/delete_form.css">
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
                <h1>DELETE <?= htmlspecialchars(strtoupper($recordLabel)) ?></h1>
                <p class="subtitle">Review and delete this <?= htmlspecialchars(strtolower($recordLabel)) ?> entry</p>

                <div class="warning-message">
                    <div class="warning-icon">⚠</div>
                    <div class="warning-content">
                        <h3>Are you sure you want to delete this?</h3>
                        <p>This action cannot be undone. The <?= htmlspecialchars($recordLabel) ?> entry will be permanently removed from your records.</p>
                    </div>
                </div>

                <form method="POST" action="delete.php" id="deleteForm">
                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($recordType) ?>">
                    <input type="hidden" name="confirmed" value="yes">

                    <?php if ($recordType === 'goal'): ?>
                        <label>Goal Name:</label>
                        <div class="field-value"><?= htmlspecialchars($recordDescription) ?></div>

                        <label>Target Amount:</label>
                        <div class="field-value">Rs.<?= number_format((float) $record['amount'], 2) ?></div>

                        <label>Start Date:</label>
                        <div class="field-value"><?= htmlspecialchars($startDateFormatted) ?></div>

                        <label>Due Date:</label>
                        <div class="field-value"><?= htmlspecialchars($dueDateFormatted) ?></div>
                    <?php else: ?>
                        <label><?= htmlspecialchars($recordLabel) ?> Amount:</label>
                        <div class="field-value">$<?= number_format((float) $record['amount'], 2) ?></div>

                        <?php if ($recordType === 'income'): ?>
                            <label>Source:</label>
                            <div class="field-value"><?= htmlspecialchars($record['source']) ?></div>
                        <?php endif; ?>

                        <label>Date:</label>
                        <div class="field-value"><?= htmlspecialchars($dateFormatted) ?></div>

                        <label>Description:</label>
                        <div class="field-value"><?= htmlspecialchars($recordDescription !== '' ? $recordDescription : '(No description)') ?></div>
                    <?php endif; ?>

                    <div class="btn-row">
                        <button type="button" class="btn btn-cancel" onclick="history.back()">CANCEL</button>
                        <button type="submit" class="btn btn-delete">DELETE <?= htmlspecialchars(strtoupper($recordLabel)) ?></button>
                    </div>
                </form>
            </div>
        </div>

    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int) ($_POST['id'] ?? 0);
    $recordType = $_POST['type'] ?? '';
    $confirmed = $_POST['confirmed'] ?? '';

    if ($recordId <= 0 || !in_array($recordType, $allowedTypes, true) || $confirmed !== 'yes') {
        die('Invalid delete request.');
    }

    $deleted = deleteRecord($pdo, $recordType, $recordId, $current_user_id);
    if (!$deleted) {
        die('Unable to delete record. It may not exist or may belong to another user.');
    }

    // Set label for success message and redirect path
    $recordLabel = $recordType === 'income' ? 'Income' : ($recordType === 'expense' ? 'Expense' : 'Goal');
    $redirectPath = $recordType === 'goal' ? '../goals/view_goals.php' : 'dashboard.html';

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FinTrack - Record Deleted</title>
        <link rel="stylesheet" href="../assets/css/delete_form.css">
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
            <!-- SUCCESS MODAL -->
            <div class="modal-overlay show">
                <div class="success-modal">
                    <div class="success-icon">✓</div>
                    <h2>Success!</h2>
                    <p><?= htmlspecialchars($recordLabel) ?> entry has been deleted successfully.</p>
                    <a href="<?= $redirectPath ?>" class="btn-back">Back to Dashboard</a>
                </div>
            </div>
        </div>

    </body>
    </html>
    <?php
    exit;
}

http_response_code(405);
echo 'Method not allowed.';
exit;