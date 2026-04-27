<?php
$host    = 'localhost';
$dbname  = 'expenses_db';
$db_user = 'root';
$db_pass = '';

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

session_start();


// Check if the current request is a form submission
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Redirect to another page (with optional message in URL)
function redirect($action, $msg = '') {
    $url = "/expenses-tracker-app/auth/login.php?action=$action" . ($msg ? "&msg=$msg" : '');
    header("Location: $url");
    exit;
}

// Log in a user and go to the dashboard
function loginUser($email, $name = '') {
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $name ?: "User";
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
    header("Location: /expenses-tracker-app/dashboard/dashboard.php");
    exit;
}

// Show a red error box or green success box
function alerts($error, $success) {
    if ($error)   echo "<div class='alert err'>" . htmlspecialchars($error)   . "</div>";
    if ($success) echo "<div class='alert ok'>"  . htmlspecialchars($success) . "</div>";
}

// Print the FinTrack logo — same on every page
function logo() { ?>
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="15" rx="2"/>
        <path d="M2 10h20"/><path d="M15 15h2"/>
      </svg>
    </div>
    <span class="logo-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c5cbf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><polyline points="12 7 12 12 15 14"/>
      </svg>
      FinTrack
    </span>
  </div>
<?php }

// Print one input field (email, text, or password) with icon
function field($type, $name, $placeholder, $id = '', $value = '') {
    $id = $id ?: $name;
    $emailIcon    = '<rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,7 12,14 2,7"/>';
    $passwordIcon = '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>';
    $eyeIcon      = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    $icon = ($type == 'email') ? $emailIcon : $passwordIcon;
    ?>
    <div class="field"><div class="field-wrap">
      <input type="<?= $type ?>" name="<?= $name ?>" id="<?= $id ?>"
             placeholder="<?= $placeholder ?>" value="<?= htmlspecialchars($value) ?>">
      <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
           stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <?= $icon ?>
      </svg>
      <?php if ($type == 'password'): ?>
        <button type="button" class="eye-btn" onclick="togglePw('<?= $id ?>')">
          <svg id="eye_<?= $id ?>" width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <?= $eyeIcon ?>
          </svg>
        </button>
      <?php endif ?>
    </div></div>
    <?php
}

// Print 6 OTP digit boxes + a hidden input (PHP reads the hidden input as $_POST['code'])
function otpBoxes($hiddenId) { ?>
  <input type="hidden" name="code" id="<?= $hiddenId ?>">
  <div class="otp-boxes">
    <?php for ($i = 0; $i < 6; $i++): ?>
      <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
    <?php endfor ?>
  </div>
<?php }



// Which page are we on?
$page  = $_GET['action'] ?? 'login';
$error = $success = "";

// Redirect rules
if ($page == 'login'   && isset($_SESSION['user_email']))  { redirect('welcome'); }
if ($page == 'welcome' && !isset($_SESSION['user_email'])) { redirect('login'); }

// ---------- LOGIN ----------
if ($page == 'login' && isPost()) {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, name, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                if (!isset($_SESSION['verified'][$email])) {
                    // First time – send verify code
                    $_SESSION['verify_code']    = rand(100000, 999999);
                    $_SESSION['verify_email']   = $email;
                    $_SESSION['verify_expires'] = time() + 300;
                    $_SESSION['pending_user_id']   = $user['user_id'];
                    $_SESSION['pending_user_name'] = $user['name'];
                    redirect('verify');
                }
                $_SESSION['user_id'] = $user['user_id'];
                loginUser($email, $user['name']);
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again.";
        }
    }
}

// ---------- VERIFY ----------
if ($page == 'verify' && isPost()) {
        if (time() > ($_SESSION['verify_expires'] ?? 0)) {
        $error = "Code expired. Please log in again.";
    } elseif (($_POST['code'] ?? '') == ($_SESSION['verify_code'] ?? '')) {
        $email = $_SESSION['verify_email'];
        $name  = $_SESSION['pending_user_name'] ?? '';
        $_SESSION['verified'][$email] = true;
        $_SESSION['user_id'] = $_SESSION['pending_user_id'] ?? null;

        unset(
            $_SESSION['verify_code'],
            $_SESSION['verify_email'],
            $_SESSION['verify_expires'],
            $_SESSION['pending_user_id'],
            $_SESSION['pending_user_name']
        );

        loginUser($email, $name);
    } else {
        $error = "Wrong code. Try again.";
    }
}
if ($page == 'verify' && isset($_GET['resend'])) {
    $_SESSION['verify_code']    = rand(100000, 999999);
    $_SESSION['verify_expires'] = time() + 300;
    $success = "New code sent!";
}

// ---------- FORGOT PASSWORD ----------
if ($page == 'forgot' && isPost()) {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $userExists = $stmt->fetch();

            if ($userExists) {
                $_SESSION['reset_code']    = rand(100000, 999999);
                $_SESSION['reset_email']   = $email;
                $_SESSION['reset_expires'] = time() + 300;
                redirect('reset');
            } else {
                $success = "If that email exists, a reset code was sent.";
            }
        } catch (PDOException $e) {
            $success = "If that email exists, a reset code was sent.";
        }
    }
}

// ---------- RESET PASSWORD ----------
if ($page == 'reset' && isPost()) {
    $code    = trim($_POST['code'] ?? '');
    $newpass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$code || !$newpass || !$confirm) {
        $error = "Please fill in all fields.";
    } elseif (time() > ($_SESSION['reset_expires'] ?? 0)) {
        $error = "Code expired. Start again.";
    } elseif ($code != ($_SESSION['reset_code'] ?? '')) {
        $error = "Wrong code. Try again.";
    } elseif (strlen($newpass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newpass != $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hashedPassword = password_hash($newpass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $_SESSION['reset_email']]);
            unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
            redirect('login', 'password_reset');
        } catch (PDOException $e) {
            $error = "An error occurred while updating the password.";
        }
    }
}

// URL message banners
if (($_GET['msg'] ?? '') == 'logged_out')     $success = "Logged out successfully.";
if (($_GET['msg'] ?? '') == 'password_reset') $success = "Password reset! You can now log in.";
if (($_GET['msg'] ?? '') == 'registered')     $success = "Registration successful! You can now log in.";

// Shortcut variables for the HTML below
$user_name    = $_SESSION['user_name']    ?? '';
$user_email   = $_SESSION['user_email']   ?? '';
$login_time   = $_SESSION['login_time']   ?? '';
$verify_email = $_SESSION['verify_email'] ?? '';
$verify_code  = $_SESSION['verify_code']  ?? '';
$reset_email  = $_SESSION['reset_email']  ?? '';
$reset_code   = $_SESSION['reset_code']   ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FinTrack</title>
  <link rel="stylesheet" href="/expenses-tracker-app/assets/css/login.css">
</head>
<body>

<!-- LOGIN -->
<?php if ($page == 'login'): ?>
<div class="card">
  <h1 class="title">LOGIN</h1>
  <?php alerts($error, $success) ?>
  <form id="loginForm" method="POST" action="/expenses-tracker-app/auth/login.php?action=login">
    <?php field('email',    'email',    'Email Address', 'email', $_POST['email'] ?? '') ?>
    <?php field('password', 'password', 'Password') ?>
    <a href="/expenses-tracker-app/auth/login.php?action=forgot" class="forgot">Forgot Password?</a>
    <button type="submit" class="btn" id="loginBtn">
      <span class="spinner" id="spinner"></span>
      <span id="btnText">LOGIN</span>
    </button>
  </form>
  <p class="bottom-link">Don't have an account yet? <a href="/expenses-tracker-app/auth/register.php">Register Here</a></p>
</div>

<!-- VERIFY EMAIL -->
<?php elseif ($page == 'verify'): ?>
<div class="card">
  <h1 class="title">VERIFY EMAIL</h1>
  <p class="sub">Code sent to <b><?= htmlspecialchars($verify_email) ?></b></p>
  <?php alerts($error, $success) ?>
  <?php if ($verify_code): ?>
    <div class="hint">🔐 Demo code: <b><?= $verify_code ?></b><br><small>(would be emailed in a real app)</small></div>
  <?php endif ?>
  <form method="POST" action="/expenses-tracker-app/auth/login.php?action=verify" id="verifyForm">
    <?php otpBoxes('verifyHiddenCode') ?>
    <button type="submit" class="btn">VERIFY &amp; LOGIN</button>
  </form>
  <p class="center">Didn't receive the code? <a href="/expenses-tracker-app/auth/login.php?action=verify&resend=1">Resend Code</a></p>
  <p class="center"><a href="/expenses-tracker-app/auth/login.php?action=login">← Back</a></p>
</div>

<!-- FORGOT PASSWORD -->
<?php elseif ($page == 'forgot'): ?>
<div class="card">
  <h1 class="title">FORGOT PASSWORD</h1>
  <p class="sub">Enter your email to receive a reset code.</p>
  <?php alerts($error, $success) ?>
  <form method="POST" action="/expenses-tracker-app/auth/login.php?action=forgot">
    <?php field('email', 'email', 'Your Email Address', 'email', $_POST['email'] ?? '') ?>
    <button type="submit" class="btn">SEND RESET CODE</button>
  </form>
  <p class="center"><a href="/expenses-tracker-app/auth/login.php?action=login">← Back to Login</a></p>
</div>

<!-- RESET PASSWORD -->
<?php elseif ($page == 'reset'): ?>
<div class="card">
  <h1 class="title">RESET PASSWORD</h1>
  <p class="sub">Code sent to <b><?= htmlspecialchars($reset_email) ?></b></p>
  <?php alerts($error, $success) ?>
  <?php if ($reset_code): ?>
    <div class="hint">🔐 Demo reset code: <b><?= $reset_code ?></b><br><small>(would be emailed in a real app)</small></div>
  <?php endif ?>
  <form method="POST" action="/expenses-tracker-app/auth/login.php?action=reset" id="resetForm">
    <?php otpBoxes('resetHiddenCode') ?>
    <?php field('password', 'new_password', 'New Password',         'new_password') ?>
    <?php field('password', 'confirm',      'Confirm New Password', 'confirm') ?>
    <button type="submit" class="btn">SET NEW PASSWORD</button>
  </form>
  <p class="center">Didn't receive the code? <a href="/expenses-tracker-app/auth/login.php?action=forgot">Resend Code</a></p>
  <p class="center"><a href="/expenses-tracker-app/auth/login.php?action=login">← Back to Login</a></p>
</div>

<!-- WELCOME -->
<?php elseif ($page == 'welcome'): ?>
<div class="card">
  <h1 class="title">WELCOME!</h1>
  <p class="sub">Hello, <b><?= htmlspecialchars($user_name) ?></b>!</p>
  <p class="sub" style="margin-bottom:24px;">
    Logged in as <b><?= htmlspecialchars($user_email) ?></b><br>
    Session started: <?= htmlspecialchars($login_time) ?>
  </p>
  <a href="/expenses-tracker-app/auth/logout.php" class="btn" id="logoutBtn"
     onclick="return confirmLogout(event,this)">↩ Log Out</a>
</div>

<?php endif ?>
<script src="/expenses-tracker-app/assets/js/login.js"></script>
</body>
</html>