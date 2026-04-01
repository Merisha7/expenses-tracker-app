<?php
// ─────────────────────────────────────────────────────────────────────────────
// START SESSION — remembers who is logged in (like a wristband)
// ─────────────────────────────────────────────────────────────────────────────
session_start();

// ─────────────────────────────────────────────────────────────────────────────
// DEMO USER — in a real app this would come from a database
// ─────────────────────────────────────────────────────────────────────────────
$correct_email    = "demo@expenses.com";
$correct_password = "password123";

// ─────────────────────────────────────────────────────────────────────────────
// WHICH PAGE TO SHOW? Read ?action= from the URL
// Possible values: login, dashboard, logout, forgot, verify, reset
// ─────────────────────────────────────────────────────────────────────────────
$page = $_GET['action'] ?? 'login';

// ─────────────────────────────────────────────────────────────────────────────
// REDIRECT RULES
// ─────────────────────────────────────────────────────────────────────────────

// Already logged in → skip login page, go straight to dashboard
if ($page == 'login' && isset($_SESSION['user_email'])) {
    header("Location: login.php?action=dashboard");
    exit;
}

// Not logged in → cannot access dashboard
if ($page == 'dashboard' && !isset($_SESSION['user_email'])) {
    header("Location: login.php?action=login");
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// LOGOUT — destroy session and go back to login
// ─────────────────────────────────────────────────────────────────────────────
if ($page == 'logout') {
    session_destroy();
    header("Location: login.php?action=login&msg=logged_out");
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// VARIABLES used across all pages
// ─────────────────────────────────────────────────────────────────────────────
$error   = "";
$success = "";

// ═════════════════════════════════════════════════════════════════════════════
// PAGE 1: LOGIN — check email + password when form is submitted
// ═════════════════════════════════════════════════════════════════════════════
if ($page == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif ($email == $correct_email && $password == $correct_password) {

        // ── EMAIL VERIFICATION CHECK ──────────────────────────────────────────
        // Check if this email is already verified in the session storage
        // In a real app you'd check a "verified" column in your database
        if (!isset($_SESSION['verified_emails'][$email])) {
            // Email not verified yet → generate a 6-digit code
            $code = rand(100000, 999999);                   // e.g. 483920
            $_SESSION['verify_code']      = $code;          // save the code
            $_SESSION['verify_email']     = $email;         // save who it's for
            $_SESSION['verify_expires']   = time() + 300;   // expires in 5 minutes
            $_SESSION['pending_name']     = "Alex Morgan";  // save name for after verify

            // In a real app: mail($email, "Your code", "Your code is: $code");
            // For local testing we just show the code on screen
            header("Location: login.php?action=verify");
            exit;
        }

        // Email IS verified → log them in
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name']  = "Alex Morgan";
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header("Location: login.php?action=dashboard");
        exit;

    } else {
        $error = "Invalid email or password.";
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// PAGE 2: VERIFY EMAIL — user types the 6-digit code
// ═════════════════════════════════════════════════════════════════════════════
if ($page == 'verify' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_code = trim($_POST['code'] ?? '');

    // Check if code has expired (older than 5 minutes)
    if (time() > ($_SESSION['verify_expires'] ?? 0)) {
        $error = "Code expired. Please log in again to get a new code.";

    // Check if the code matches
    } elseif ($entered_code == $_SESSION['verify_code']) {
        // ✅ Mark this email as verified so next login skips verification
        $_SESSION['verified_emails'][$_SESSION['verify_email']] = true;

        // Now actually log the user in
        $_SESSION['user_email'] = $_SESSION['verify_email'];
        $_SESSION['user_name']  = $_SESSION['pending_name'];
        $_SESSION['login_time'] = date('Y-m-d H:i:s');

        // Clean up the temporary verification data
        unset($_SESSION['verify_code'], $_SESSION['verify_email'],
              $_SESSION['verify_expires'], $_SESSION['pending_name']);

        header("Location: login.php?action=dashboard");
        exit;

    } else {
        $error = "Wrong code. Please try again.";
    }
}

// Handle "Resend Code" button on verify page
if ($page == 'verify' && isset($_GET['resend'])) {
    $code = rand(100000, 999999);
    $_SESSION['verify_code']    = $code;
    $_SESSION['verify_expires'] = time() + 300;
    $success = "A new code has been sent! (Check the code box below for your demo code)";
}

// ═════════════════════════════════════════════════════════════════════════════
// PAGE 3: FORGOT PASSWORD — user enters their email to get a reset code
// ═════════════════════════════════════════════════════════════════════════════
if ($page == 'forgot' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif ($email != $correct_email) {
        // Don't reveal if email exists — just say "if it exists we sent a code"
        $success = "If that email is registered, a reset code has been sent.";

    } else {
        // Generate a 6-digit reset code
        $reset_code = rand(100000, 999999);
        $_SESSION['reset_code']    = $reset_code;      // save code
        $_SESSION['reset_email']   = $email;            // save email
        $_SESSION['reset_expires'] = time() + 300;      // expires in 5 min

        // In a real app: mail($email, "Reset code", "Your reset code: $reset_code");
        // For local testing we show the code on screen
        header("Location: login.php?action=reset");
        exit;
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// PAGE 4: RESET PASSWORD — user enters code + new password
// ═════════════════════════════════════════════════════════════════════════════
if ($page == 'reset' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_code = trim($_POST['code']         ?? '');
    $new_password =      $_POST['new_password'] ?? '';
    $confirm      =      $_POST['confirm']      ?? '';

    if (empty($entered_code) || empty($new_password) || empty($confirm)) {
        $error = "Please fill in all fields.";

    } elseif (time() > ($_SESSION['reset_expires'] ?? 0)) {
        $error = "Code expired. Please start again.";

    } elseif ($entered_code != $_SESSION['reset_code']) {
        $error = "Wrong code. Please try again.";

    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";

    } elseif ($new_password != $confirm) {
        $error = "Passwords do not match.";

    } else {
        // ✅ In a real app: UPDATE users SET password = hash($new_password) WHERE email = ...
        // For demo: just clear reset data and send back to login
        unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
        header("Location: login.php?action=login&msg=password_reset");
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FLASH MESSAGES — shown after a redirect
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'logged_out')      $success = "You've been logged out successfully.";
    if ($_GET['msg'] == 'password_reset')  $success = "Password reset! You can now log in with your new password.";
}

// ─────────────────────────────────────────────────────────────────────────────
// DASHBOARD DATA — pull from session
// ─────────────────────────────────────────────────────────────────────────────
$user_name  = $_SESSION['user_name']  ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$login_time = $_SESSION['login_time'] ?? '';
$initial    = strtoupper(substr($user_name, 0, 1));

// Grab temp data for verify/reset pages
$verify_email  = $_SESSION['verify_email']  ?? '';
$verify_code   = $_SESSION['verify_code']   ?? '';   // shown on screen for demo
$reset_code    = $_SESSION['reset_code']    ?? '';   // shown on screen for demo
$reset_email   = $_SESSION['reset_email']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>
    <?php
      if ($page == 'dashboard') echo 'Dashboard';
      elseif ($page == 'verify') echo 'Verify Email';
      elseif ($page == 'forgot') echo 'Forgot Password';
      elseif ($page == 'reset')  echo 'Reset Password';
      else echo 'Login';
    ?>
    — Expenses Tracking
  </title>
  <link rel="stylesheet" href="login.css" />
</head>
<body>

<!-- ███████████████████████████ LOGIN PAGE ███████████████████████████ -->
<?php if ($page == 'login'): ?>

<div class="card">

  <!-- Logo -->
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24">
        <rect x="2" y="5" width="20" height="15" rx="2.5"/>
        <path d="M2 10h20"/><path d="M6 15h4"/>
      </svg>
    </div>
    <span class="logo-label">⏱ Expenses Tracking</span>
  </div>

  <h1 class="page-title">LOGIN</h1>

  <!-- Error or success message -->
  <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <!-- Login form -->
  <form id="loginForm" method="POST" action="login.php?action=login">

    <!-- Email input -->
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/>
      </svg>
      <input type="email" name="email" id="email" class="form-input"
             placeholder="Email Address"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
    </div>

    <!-- Password input + show/hide eye button -->
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <input type="password" name="password" id="password" class="form-input"
             placeholder="Password" />
      <button type="button" class="toggle-pw" id="togglePw" aria-label="Show or hide password">
        <svg id="eyeIcon" viewBox="0 0 24 24">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>

    <!-- Forgot password link — goes to the forgot page -->
    <a href="login.php?action=forgot" class="forgot-link">Forgot Password?</a>

    <!-- Submit button with spinner -->
    <button type="submit" class="btn-primary" id="loginBtn">
      <span class="spinner" id="spinner"></span>
      <span id="btnText">LOGIN</span>
    </button>

  </form>

  <p class="register-link">Don't have an account yet? <a href="#">Register Here</a></p>

  <!-- Demo credentials hint -->
  <div class="divider">demo credentials</div>
  <div class="demo-box">
    📧 <strong>demo@expenses.com</strong><br>
    🔑 <strong>password123</strong>
  </div>

</div>


<!-- ███████████████████████████ VERIFY EMAIL PAGE ███████████████████████████ -->
<?php elseif ($page == 'verify'): ?>

<div class="card">

  <div class="logo-wrap">
    <div class="logo-icon" style="background:linear-gradient(135deg,#4caf7d,#2e8b57);">
      <svg viewBox="0 0 24 24" style="stroke:#fff;fill:none;stroke-width:1.8;width:30px;height:30px;">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/>
        <path d="M11 11a5 5 0 1 0 0-10 5 5 0 0 0 0 10z"/>
        <path d="M22 16l-4 4-2-2"/>
      </svg>
    </div>
    <span class="logo-label">⏱ Expenses Tracking</span>
  </div>

  <h1 class="page-title">VERIFY EMAIL</h1>
  <p class="sub-text">We sent a 6-digit code to <strong><?= htmlspecialchars($verify_email) ?></strong></p>

  <!-- Error or success -->
  <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <!-- Demo: show the code on screen since we have no mail server -->
  <?php if ($verify_code): ?>
  <div class="code-hint">
    🔐 Your demo code is: <strong><?= $verify_code ?></strong>
    <br><small>(In a real app this would be emailed to you)</small>
  </div>
  <?php endif; ?>

  <!-- Verification form -->
  <form method="POST" action="login.php?action=verify">
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <input type="text" name="code" class="form-input code-input"
             placeholder="Enter 6-digit code" maxlength="6" />
    </div>

    <button type="submit" class="btn-primary">VERIFY & LOGIN</button>
  </form>

  <!-- Resend code link -->
  <p style="text-align:center;margin-top:16px;font-size:13px;color:var(--gray-600);">
    Didn't get the code?
    <a href="login.php?action=verify&resend=1" style="color:var(--purple-deep);font-weight:700;">Resend Code</a>
  </p>

  <p style="text-align:center;margin-top:10px;font-size:13px;">
    <a href="login.php?action=login" style="color:var(--gray-600);">← Back to Login</a>
  </p>

</div>


<!-- ███████████████████████████ FORGOT PASSWORD PAGE ███████████████████████████ -->
<?php elseif ($page == 'forgot'): ?>

<div class="card">

  <div class="logo-wrap">
    <div class="logo-icon" style="background:linear-gradient(135deg,#f0a500,#e07b00);">
      <svg viewBox="0 0 24 24" style="stroke:#fff;fill:none;stroke-width:1.8;width:30px;height:30px;">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 8v4"/><circle cx="12" cy="16" r="1" fill="#fff"/>
      </svg>
    </div>
    <span class="logo-label">⏱ Expenses Tracking</span>
  </div>

  <h1 class="page-title">FORGOT PASSWORD</h1>
  <p class="sub-text">Enter your email and we'll send you a reset code.</p>

  <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <!-- Forgot password form -->
  <form method="POST" action="login.php?action=forgot">
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/>
      </svg>
      <input type="email" name="email" class="form-input"
             placeholder="Your Email Address"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
    </div>

    <button type="submit" class="btn-primary">SEND RESET CODE</button>
  </form>

  <p style="text-align:center;margin-top:16px;font-size:13px;">
    <a href="login.php?action=login" style="color:var(--gray-600);">← Back to Login</a>
  </p>

</div>


<!-- ███████████████████████████ RESET PASSWORD PAGE ███████████████████████████ -->
<?php elseif ($page == 'reset'): ?>

<div class="card">

  <div class="logo-wrap">
    <div class="logo-icon" style="background:linear-gradient(135deg,#5b3fa6,#7c5cbf);">
      <svg viewBox="0 0 24 24" style="stroke:#fff;fill:none;stroke-width:1.8;width:30px;height:30px;">
        <rect x="3" y="11" width="18" height="11" rx="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
    </div>
    <span class="logo-label">⏱ Expenses Tracking</span>
  </div>

  <h1 class="page-title">RESET PASSWORD</h1>
  <p class="sub-text">Enter the code sent to <strong><?= htmlspecialchars($reset_email) ?></strong> and choose a new password.</p>

  <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <!-- Demo: show reset code on screen -->
  <?php if ($reset_code): ?>
  <div class="code-hint">
    🔐 Your demo reset code is: <strong><?= $reset_code ?></strong>
    <br><small>(In a real app this would be emailed to you)</small>
  </div>
  <?php endif; ?>

  <!-- Reset password form -->
  <form method="POST" action="login.php?action=reset" id="resetForm">

    <!-- Reset code -->
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <input type="text" name="code" class="form-input code-input"
             placeholder="Enter 6-digit reset code" maxlength="6" />
    </div>

    <!-- New password + show/hide -->
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <input type="password" name="new_password" id="newPassword" class="form-input"
             placeholder="New Password" />
      <button type="button" class="toggle-pw" onclick="toggleField('newPassword','eyeNew')">
        <svg id="eyeNew" viewBox="0 0 24 24">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>

    <!-- Confirm password + show/hide -->
    <div class="form-group">
      <svg class="form-icon" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <input type="password" name="confirm" id="confirmPassword" class="form-input"
             placeholder="Confirm New Password" />
      <button type="button" class="toggle-pw" onclick="toggleField('confirmPassword','eyeConfirm')">
        <svg id="eyeConfirm" viewBox="0 0 24 24">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>

    <button type="submit" class="btn-primary">SET NEW PASSWORD</button>
  </form>

  <p style="text-align:center;margin-top:16px;font-size:13px;">
    <a href="login.php?action=login" style="color:var(--gray-600);">← Back to Login</a>
  </p>

</div>


<!-- ███████████████████████████ DASHBOARD PAGE ███████████████████████████ -->
<?php elseif ($page == 'dashboard'): ?>

<div class="dashboard-card">

  <!-- Welcome header: avatar circle + name + email -->
  <div class="welcome-header">
    <div class="avatar"><?= $initial ?></div>
    <div class="welcome-text">
      <h2>Welcome back, <?= htmlspecialchars($user_name) ?>!</h2>
      <p><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>

  <!-- 4 stat boxes in a 2x2 grid -->
  <div class="stat-grid">
    <div class="stat-card"><div class="label">This Month</div><div class="value">$2,840</div></div>
    <div class="stat-card"><div class="label">Transactions</div><div class="value">47</div></div>
    <div class="stat-card"><div class="label">Budget Left</div><div class="value">$660</div></div>
    <div class="stat-card"><div class="label">Savings</div><div class="value">$1,200</div></div>
  </div>

  <!-- Login time -->
  <div class="session-info">
    🕐 Logged in since: <span><?= htmlspecialchars($login_time) ?></span>
  </div>

  <!-- Logout — JS asks to confirm before actually logging out -->
  <a href="login.php?action=logout" id="logoutBtn" class="btn-primary"
     style="display:block;text-align:center;text-decoration:none;padding:15px;border-radius:12px;"
     onclick="return confirmLogout(event, this)">
    Log Out
  </a>

  <a href="#" class="btn-secondary"
     style="display:block;text-align:center;text-decoration:none;padding:14px;border-radius:12px;margin-top:10px;">
    View Expenses
  </a>

</div>

<?php endif; ?>

<script src="login.js"></script>
</body>
</html>