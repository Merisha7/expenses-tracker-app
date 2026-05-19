<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===== MAILTRAP CONFIG =====
const SMTP_HOST   = 'sandbox.smtp.mailtrap.io';
const SMTP_PORT   = 587;
const SMTP_USER   = '30b6215c6ea70d';
const SMTP_PASS   = '0a31704b9f06ed';
const FROM_EMAIL  = 'no-reply@fintrack.test';
const FROM_NAME   = 'FinTrack';

$GLOBALS['SMTP_LAST_ERROR'] = '';

$page  = $_GET['action'] ?? 'login';
$error = $success = "";

// Initialize variables from session to prevent undefined variable warnings
$user_name  = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$login_time = $_SESSION['login_time'] ?? '';
$reset_email = $_SESSION['reset_email'] ?? '';

if ($page == 'login' && isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/dashboard.html');
    exit;
}

if ($page == 'welcome') {
    if (!isset($_SESSION['user_id'])) {
        redirect('login');
    }
    header('Location: ../dashboard/dashboard.html');
    exit;
}

// ===== LOGIN =====
if ($page == 'login' && isPost()) {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {

       $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {

            if (!isset($_SESSION['verified'][$email])) {
                $code = rand(100000, 999999);

                $_SESSION['verify_code']    = $code;
                $_SESSION['verify_email']   = $email;
                $_SESSION['verify_expires'] = time() + 300;

                if (sendOtpEmail($email, $code, 'verify')) {
                    redirect('verify');
                } else {
                    $error = "Could not send verification email: " . $GLOBALS['SMTP_LAST_ERROR'];
                }
            } else {
                loginUser($user['user_id'], $email, $user['name']);
            }

        } else {
            $error = "Invalid email or password.";
        }
    }
}

// ===== VERIFY =====
if ($page == 'verify' && isPost()) {
    // --- Resend button clicked ---
    if (isset($_POST['resend'])) {
        $newCode = rand(100000, 999999);
        $_SESSION['verify_code']    = $newCode;
        $_SESSION['verify_expires'] = time() + 300;

        if (sendOtpEmail($_SESSION['verify_email'] ?? '', $newCode, 'verify')) {
            $success = "A new code has been sent to your email.";
        } else {
            $error = "Failed to resend code. Please try again.";
        }
    }
    // --- Verify OTP ---
    else {
        $code = trim(
            ($_POST['digit_1'] ?? '') . ($_POST['digit_2'] ?? '') . ($_POST['digit_3'] ?? '') .
            ($_POST['digit_4'] ?? '') . ($_POST['digit_5'] ?? '') . ($_POST['digit_6'] ?? '')
        );

        if (time() > ($_SESSION['verify_expires'] ?? 0)) {
            $error = "Code expired. Please log in again.";
        } elseif ($code != $_SESSION['verify_code']) {
            $error = "Wrong code. Try again.";
        } else {
            $_SESSION['verified'][$_SESSION['verify_email']] = true;

           $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['verify_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_expires']);

            loginUser($user['user_id'], $user['email'], $user['name']);
        }
    }
}

// ===== FORGOT PASSWORD =====
if ($page == 'forgot' && isPost()) {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {

       $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
       $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {

            $code = rand(100000, 999999);

            $_SESSION['reset_code']    = $code;
            $_SESSION['reset_email']   = $email;
            $_SESSION['reset_expires'] = time() + 300;

            if (sendOtpEmail($email, $code, 'reset')) {
                redirect('reset');
            } else {
                $error = "Could not send reset email.";
            }

        } else {
            $success = "If that email exists, a reset code was sent.";
        }
    }
}

// ===== RESET PASSWORD =====
if ($page == 'reset' && isPost()) {
    // --- Resend button clicked ---
    if (isset($_POST['resend'])) {
        $newCode = rand(100000, 999999);
        $_SESSION['reset_code']    = $newCode;
        $_SESSION['reset_expires'] = time() + 300;

        if (sendOtpEmail($_SESSION['reset_email'] ?? '', $newCode, 'reset')) {
            $success = "A new code has been sent to your email.";
            unset($_SESSION['reset_otp_verified']);
        } else {
            $error = "Failed to resend code. Please try again.";
        }
    }
    // --- OTP verification and password reset ---
    else {
        $code    = trim(($_POST['digit_1'] ?? '') . ($_POST['digit_2'] ?? '') . ($_POST['digit_3'] ?? '') . ($_POST['digit_4'] ?? '') . ($_POST['digit_5'] ?? '') . ($_POST['digit_6'] ?? ''));
        $newpass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (time() > ($_SESSION['reset_expires'] ?? 0)) {
            $error = "Code expired. Start again.";
        } elseif (!$code) {
            $error = "Please enter the reset code.";
        } elseif ($code != $_SESSION['reset_code']) {
            $error = "Wrong code. Try again.";
        } elseif (!$newpass || !$confirm) {
            $error = "Please fill in both password fields.";
        } elseif (strlen($newpass) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($newpass != $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $_SESSION['reset_email']]);

            unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_expires'], $_SESSION['reset_otp_verified']);

            redirect('login', 'password_reset');
        }
    }
}

// ===== HELPERS =====
function isPost() { return $_SERVER['REQUEST_METHOD'] == 'POST'; }

function redirect($action, $msg = '') {
    $url = "login.php?action=$action" . ($msg ? "&msg=$msg" : '');
    header("Location: $url"); exit;
}

function loginUser($userId, $email, $name) {
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $name;
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
    header("Location: ../dashboard/dashboard.html"); exit;
}

function alerts($error, $success) {
    if ($error)   echo "<div class='alert err'>" . htmlspecialchars($error)   . "</div>";
    if ($success) echo "<div class='alert ok'>"  . htmlspecialchars($success) . "</div>";
}

/**
 * Send an OTP email through Mailtrap SMTP.
 * Auto-picks the correct encryption based on port:
 *   - 465        → SMTPS (implicit TLS)
 *   - 25/587/2525 → STARTTLS
 */
function sendOtpEmail($toEmail, $code, $purpose = 'verify') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPSecure = (SMTP_PORT === 465)
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout    = 10;

        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        if ($purpose === 'reset') {
            $mail->Subject = 'FinTrack — Password Reset Code';
            $heading = 'Reset Your Password';
            $intro   = 'Use the code below to reset your FinTrack password.';
        } else {
            $mail->Subject = 'FinTrack — Email Verification Code';
            $heading = 'Verify Your Email';
            $intro   = 'Use the code below to complete your login to FinTrack.';
        }

        $mail->Body    = otpEmailTemplate($heading, $intro, $code);
        $mail->AltBody = "$heading\n\n$intro\n\nYour code: $code\n\nThis code expires in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log("[FinTrack] SMTP failed (port " . SMTP_PORT . "): $errMsg");
        $GLOBALS['SMTP_LAST_ERROR'] = $errMsg;
        return false;
    }
}

function otpEmailTemplate($heading, $intro, $code) {
    $code = htmlspecialchars((string)$code);
    $heading = htmlspecialchars($heading);
    $intro = htmlspecialchars($intro);
    return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#eceaf6;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#eceaf6;padding:40px 20px;">
    <tr><td align="center">
      <table width="100%" style="max-width:480px;background:#ffffff;border-radius:16px;border-top:5px solid #c8b8f0;box-shadow:0 4px 24px rgba(100,70,190,0.08);overflow:hidden;">
        <tr><td style="padding:32px 32px 8px;text-align:center;">
          <div style="display:inline-block;width:56px;height:56px;border-radius:14px;background:linear-gradient(150deg,#a98cd8,#7c50c0);line-height:56px;color:#fff;font-weight:bold;font-size:22px;">F</div>
          <div style="margin-top:10px;font-size:14px;font-weight:bold;color:#2b2640;letter-spacing:0.5px;">FinTrack</div>
        </td></tr>
        <tr><td style="padding:8px 32px 0;text-align:center;">
          <h1 style="margin:16px 0 8px;font-size:22px;color:#2b2640;letter-spacing:0.04em;">$heading</h1>
          <p style="margin:0 0 24px;font-size:14px;color:#6b6688;line-height:1.6;">$intro</p>
        </td></tr>
        <tr><td style="padding:0 32px;text-align:center;">
          <div style="display:inline-block;background:#f0ecf8;border:2px solid #d9d2f0;border-radius:14px;padding:18px 32px;font-size:34px;font-weight:bold;color:#2b2640;letter-spacing:8px;">
            $code
          </div>
        </td></tr>
        <tr><td style="padding:24px 32px 32px;text-align:center;">
          <p style="margin:0;font-size:13px;color:#9b97b2;line-height:1.6;">
            This code expires in <b style="color:#7c5cbf;">5 minutes</b>.<br>
            If you didn't request this, you can safely ignore this email.
          </p>
        </td></tr>
      </table>
      <p style="margin:20px 0 0;font-size:12px;color:#9b97b2;">© FinTrack — Secure expense tracking</p>
    </td></tr>
  </table>
</body></html>
HTML;
}

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

function otpBoxes($hiddenId) { ?>
  <input type="hidden" name="code" id="<?= $hiddenId ?>">
  <div class="otp-boxes">
    <?php for ($i = 0; $i < 6; $i++): ?>
      <input class="otp-box" type="text" name="digit_<?= $i + 1 ?>" maxlength="1" inputmode="numeric" pattern="[0-9]">
    <?php endfor ?>
  </div>
<?php }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FinTrack</title>
<link rel="stylesheet" href="../assets/css/login.css">  
</head>
<body>

<?php if ($page == 'login'): ?>
<div class="card">
  <h1 class="title">LOGIN</h1>
  <?php logo() ?>
  <?php alerts($error, $success) ?>
  <form id="loginForm" method="POST" action="login.php?action=login">
    <?php field('email',    'email',    'Email Address', 'email', $_POST['email'] ?? '') ?>
    <?php field('password', 'password', 'Password') ?>
    <a href="login.php?action=forgot" class="forgot">Forgot Password?</a>
    <button type="submit" class="btn" id="loginBtn">
      <span class="spinner" id="spinner"></span>
      <span id="btnText">LOGIN</span>
    </button>
  </form>
  <p class="bottom-link">Don't have an account yet? <a href="register.php">Register Here</a></p>
</div>

<?php elseif ($page == 'verify'): ?>
<div class="card">
  <?php logo() ?>
  <h1 class="title">VERIFY EMAIL</h1>
  <p class="sub">A 6-digit code has been sent to <b><?= htmlspecialchars($_SESSION['verify_email'] ?? '') ?></b></p>
  <?php alerts($error, $success) ?>
  <form method="POST" action="login.php?action=verify" id="verifyForm">
    <?php otpBoxes('verifyHiddenCode') ?>
    <button type="submit" class="btn">VERIFY &amp; LOGIN</button>
  </form>
  <p class="center">Didn't receive the code?</p>
  <form method="POST" action="login.php?action=verify" class="resend-form">
    <button type="submit" name="resend" value="1" class="link-btn">Resend Code</button>
  </form>
  <p class="center"><a href="login.php?action=login">← Back</a></p>
</div>

<?php elseif ($page == 'forgot'): ?>
<div class="card">
  <?php logo() ?>
  <h1 class="title">FORGOT PASSWORD</h1>
  <p class="sub">Enter your email to receive a reset code.</p>
  <?php alerts($error, $success) ?>
  <form method="POST" action="login.php?action=forgot">
    <?php field('email', 'email', 'Your Email Address', 'email', $_POST['email'] ?? '') ?>
    <button type="submit" class="btn">SEND RESET CODE</button>
  </form>
  <p class="center"><a href="login.php?action=login">← Back to Login</a></p>
</div>

<?php elseif ($page == 'reset'): ?>
<div class="card">
  <?php logo() ?>
  <h1 class="title">RESET PASSWORD</h1>
  <p class="sub">A reset code has been sent to <b><?= htmlspecialchars($reset_email) ?></b></p>
  <?php alerts($error, $success) ?>
  <form method="POST" action="login.php?action=reset" id="resetForm">
    <p style="margin: 10px 0 15px; font-size: 13px; color: #666;">New password:</p>
    <?php field('password', 'new_password', 'New Password', 'new_password') ?>
    <?php field('password', 'confirm', 'Confirm New Password', 'confirm') ?>
    <p style="margin: 10px 0 15px; font-size: 13px; color: #666;">Enter the 6-digit code:</p>
    <?php otpBoxes('resetHiddenCode') ?>
    <button type="submit" class="btn">RESET PASSWORD</button>
  </form>
  <p class="center">Didn't receive the code?</p>
  <form method="POST" action="login.php?action=reset" class="resend-form">
    <button type="submit" name="resend" value="1" class="link-btn">Resend Code</button>
  </form>
  <p class="center"><a href="login.php?action=login">← Back to Login</a></p>
</div>

<?php endif ?>
<script src="../assets/js/login.js"></script>
</body>
</html>