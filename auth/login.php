<?php
session_start();
$correct_email = "demo@expenses.com";
$correct_password = "password123";
$page = $_GET['action'] ?? 'login';

if ($page == 'login' && isset($_SESSION['user_email'])) { header("Location: login.php?action=welcome"); exit; }
if ($page == 'welcome' && !isset($_SESSION['user_email'])) { header("Location: login.php?action=login"); exit; }
if ($page == 'logout') { session_destroy(); header("Location: login.php?action=login&msg=logged_out"); exit; }
$error = $success = "";

if ($page == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } elseif ($email == $correct_email && $pass == $correct_password) {
        if (!isset($_SESSION['verified'][$email])) {
            $_SESSION['verify_code'] = rand(100000, 999999);
            $_SESSION['verify_email'] = $email;
            $_SESSION['verify_expires'] = time() + 300;
            header("Location: login.php?action=verify"); exit;
        }
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = "Alex Morgan";
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header("Location: login.php?action=welcome"); exit;
    } else {
        $error = "Invalid email or password.";
    }
}

if ($page == 'verify' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (time() > ($_SESSION['verify_expires'] ?? 0)) {
        $error = "Code expired. Please log in again.";
    } elseif ($_POST['code'] == $_SESSION['verify_code']) {
        $_SESSION['verified'][$_SESSION['verify_email']] = true;
        $_SESSION['user_email'] = $_SESSION['verify_email'];
        $_SESSION['user_name'] = "Alex Morgan";
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_expires']);
        header("Location: login.php?action=welcome"); exit;
    } else {
        $error = "Wrong code. Try again.";
    }
}
if ($page == 'verify' && isset($_GET['resend'])) {
    $_SESSION['verify_code'] = rand(100000, 999999);
    $_SESSION['verify_expires'] = time() + 300;
    $success = "New code sent!";
}


if ($page == 'forgot' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } elseif ($email != $correct_email) {
        $success = "If that email exists, a reset code was sent.";
    } else {
        $_SESSION['reset_code'] = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_expires'] = time() + 300;
        header("Location: login.php?action=reset"); exit;
    }
}

if ($page == 'reset' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code'] ?? '');
    $newpass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!$code || !$newpass || !$confirm) {
        $error = "Please fill in all fields.";
    } elseif (time() > ($_SESSION['reset_expires'] ?? 0)) {
        $error = "Code expired. Start again.";
    } elseif ($code != $_SESSION['reset_code']) {
        $error = "Wrong code. Try again.";
    } elseif (strlen($newpass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newpass != $confirm) {
        $error = "Passwords do not match.";
    } else {
        unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
        header("Location: login.php?action=login&msg=password_reset"); exit;
    }
}

if (($_GET['msg'] ?? '') == 'logged_out') $success = "Logged out successfully.";
if (($_GET['msg'] ?? '') == 'password_reset') $success = "Password reset! You can now log in.";
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$login_time = $_SESSION['login_time'] ?? '';
$verify_email = $_SESSION['verify_email'] ?? '';
$verify_code = $_SESSION['verify_code'] ?? '';
$reset_email = $_SESSION['reset_email'] ?? '';
$reset_code = $_SESSION['reset_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FinTrack</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>

<?php
function alerts($e, $s) {
    if ($e) echo "<div class='alert err'>" . htmlspecialchars($e) . "</div>";
    if ($s) echo "<div class='alert ok'>"  . htmlspecialchars($s) . "</div>";
}
?>


<?php if ($page == 'login'): ?>
<div class="card">
  <h1 class="title">LOGIN</h1>

  <div class="logo-wrap">
    <div class="logo-icon">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
           stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="15" rx="2"/>
        <path d="M2 10h20"/>
        <path d="M15 15h2"/>
      </svg>
    </div>
    <span class="logo-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="#7c5cbf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 7 12 12 15 14"/>
      </svg>
      FinTrack
    </span>
  </div>

  <?php alerts($error, $success) ?>

  <form id="loginForm" method="POST" action="login.php?action=login">
    <div class="field">
      <div class="field-wrap">
        <input type="email" name="email" id="email" placeholder="Email Address"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="4" width="20" height="16" rx="2"/>
          <polyline points="22,7 12,14 2,7"/>
        </svg>
      </div>
    </div>
    <div class="field">
      <div class="field-wrap">
        <input type="password" name="password" id="password" placeholder="Password">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <button type="button" class="eye-btn" onclick="togglePw('password')">
          <svg id="eye_password" width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </div>
    <a href="login.php?action=forgot" class="forgot">Forgot Password?</a>
    <button type="submit" class="btn" id="loginBtn">
      <span class="spinner" id="spinner"></span>
      <span id="btnText">LOGIN</span>
    </button>
  </form>
  <p class="bottom-link">Don't have an account yet? <a href="#">Register Here</a></p>
</div>



<?php elseif ($page == 'verify'): ?>
<div class="card">
  <div class="logo-wrap">
    <div class="logo-icon" style="background:linear-gradient(135deg,#a98cd8,#7c50c0)">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
           stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="15" rx="2"/>
        <path d="M2 10h20"/>
        <path d="M15 15h2"/>
      </svg>
    </div>
    <span class="logo-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="#7c5cbf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 7 12 12 15 14"/>
      </svg>
      FinTrack
    </span>
  </div>
  <h1 class="title">VERIFY EMAIL</h1>
  <p class="sub">Code sent to <b><?= htmlspecialchars($verify_email) ?></b></p>
  <?php alerts($error, $success) ?>
  <?php if ($verify_code): ?>
    <div class="hint">🔐 Demo code: <b><?= $verify_code ?></b><br><small>(would be emailed in a real app)</small></div>
  <?php endif ?>
  <form method="POST" action="login.php?action=verify">
    <div class="field">
      <div class="field-wrap">
        <input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" class="code-input">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
    </div>
    <button type="submit" class="btn">VERIFY &amp; LOGIN</button>
  </form>
  <p class="center">
    <a href="login.php?action=verify&resend=1">Resend Code</a> &nbsp;·&nbsp;
    <a href="login.php?action=login">← Back</a>
  </p>
</div>



<?php elseif ($page == 'forgot'): ?>
<div class="card">
  <div class="logo-wrap">
    <div class="logo-icon" style="background:linear-gradient(135deg,#a98cd8,#7c50c0)">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
           stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="15" rx="2"/>
        <path d="M2 10h20"/>
        <path d="M15 15h2"/>
      </svg>
    </div>
    <span class="logo-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="#7c5cbf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 7 12 12 15 14"/>
      </svg>
      FinTrack
    </span>
  </div>
  <h1 class="title">FORGOT PASSWORD</h1>
  <p class="sub">Enter your email to receive a reset code.</p>
  <?php alerts($error, $success) ?>
  <form method="POST" action="login.php?action=forgot">
    <div class="field">
      <div class="field-wrap">
        <input type="email" name="email" placeholder="Your Email Address"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="4" width="20" height="16" rx="2"/>
          <polyline points="22,7 12,14 2,7"/>
        </svg>
      </div>
    </div>
    <button type="submit" class="btn">SEND RESET CODE</button>
  </form>
  <p class="center"><a href="login.php?action=login">← Back to Login</a></p>
</div>



<?php elseif ($page == 'reset'): ?>
<div class="card">
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
           stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="15" rx="2"/>
        <path d="M2 10h20"/>
        <path d="M15 15h2"/>
      </svg>
    </div>
    <span class="logo-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="#7c5cbf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 7 12 12 15 14"/>
      </svg>
      FinTrack
    </span>
  </div>
  <h1 class="title">RESET PASSWORD</h1>
  <p class="sub">Code sent to <b><?= htmlspecialchars($reset_email) ?></b></p>
  <?php alerts($error, $success) ?>
  <?php if ($reset_code): ?>
    <div class="hint">🔐 Demo reset code: <b><?= $reset_code ?></b><br><small>(would be emailed in a real app)</small></div>
  <?php endif ?>
  <form method="POST" action="login.php?action=reset" id="resetForm">
    <div class="field">
      <div class="field-wrap">
        <input type="text" name="code" placeholder="6-digit reset code" maxlength="6" class="code-input">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
    </div>
    <div class="field">
      <div class="field-wrap">
        <input type="password" name="new_password" id="new_password" placeholder="New Password">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <button type="button" class="eye-btn" onclick="togglePw('new_password')">
          <svg id="eye_new_password" width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="field">
      <div class="field-wrap">
        <input type="password" name="confirm" id="confirm" placeholder="Confirm New Password">
        <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <button type="button" class="eye-btn" onclick="togglePw('confirm')">
          <svg id="eye_confirm" width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </div>
    <button type="submit" class="btn">SET NEW PASSWORD</button>
  </form>
  <p class="center"><a href="login.php?action=login">← Back to Login</a></p>
</div>



<?php elseif ($page == 'welcome'): ?>
<div class="card">
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
           stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="15" rx="2"/>
        <path d="M2 10h20"/>
        <path d="M15 15h2"/>
      </svg>
    </div>
    <span class="logo-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="#7c5cbf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 7 12 12 15 14"/>
      </svg>
      FinTrack
    </span>
  </div>
  <h1 class="title">WELCOME!</h1>
  <p class="sub">Hello, <b><?= htmlspecialchars($user_name) ?></b>!</p>
  <p class="sub" style="margin-bottom:24px;">
    Logged in as <b><?= htmlspecialchars($user_email) ?></b><br>
    Session started: <?= htmlspecialchars($login_time) ?>
  </p>
  <a href="login.php?action=logout" class="btn" id="logoutBtn"
     onclick="return confirmLogout(event,this)">↩ Log Out</a>
</div>

<?php endif ?>
<script src="login.js"></script>
</body>
</html>