<?php
require_once '../config/db.php';
session_start();

function isStrongPassword($password) {
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
}

function sendOtpEmail($email, $full_name, $otp) {
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    die("Composer missing: run composer require phpmailer/phpmailer");
}
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Port = 587;
        $mail->Username   = '19198d8c9212a8';
        $mail->Password   = '768c8dbc28e41c';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom('no-reply@fintrack.app', 'FinTrack');
        $mail->addAddress($email, $full_name);

        $mail->isHTML(true);
        $mail->Subject = 'Your FinTrack Verification Code';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;
                        padding:30px;background:#f0ecfb;border-radius:12px;'>
                <h2 style='color:#2d2060;text-align:center;margin-bottom:4px;'>FinTrack</h2>
                <p style='text-align:center;color:#7c5cbf;font-size:13px;margin-top:0;'>
                    Expense Tracker
                </p>
                <div style='background:#fff;border-radius:10px;padding:28px;text-align:center;
                            box-shadow:0 4px 20px rgba(100,80,180,0.10);'>
                    <p style='color:#555;font-size:15px;margin-bottom:4px;'>
                        Hi <strong>" . htmlspecialchars($full_name) . "</strong>,
                    </p>
                    <p style='color:#555;font-size:15px;'>
                        Your email verification code is:
                    </p>
                    <div style='font-size:40px;font-weight:bold;letter-spacing:12px;
                                color:#7c5cbf;margin:24px 0;background:#f5f2fd;
                                padding:16px;border-radius:10px;'>{$otp}</div>
                    <p style='color:#aaa;font-size:13px;'>
                        This code expires in <strong>10 minutes</strong>.
                    </p>
                    <p style='color:#aaa;font-size:12px;margin-top:16px;'>
                        If you did not create a FinTrack account, you can safely ignore this email.
                    </p>
                </div>
            </div>
        ";
        $mail->AltBody = "Your FinTrack verification code is: {$otp}. It expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return $mail->ErrorInfo;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["name"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm_password"];
    $error     = "";

    // CHECK 1: Full name — letters and spaces only, at least 3 characters
    if (!preg_match('/^[a-zA-Z ]{3,}$/', $full_name)) {
        $error = "Please enter a valid full name (letters and spaces only, at least 3 characters).";

    // CHECK 2: Valid email format
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    // CHECK 3: Passwords must match
    } elseif (!isStrongPassword($password)) {
        $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";

    // CHECK 4: Passwords must match
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";

    // All checks passed — generate OTP and send email
    } else {

        // Generate a secure random 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save in session so verify_email.php can check it
        $_SESSION['verify_email']     = $email;
        $_SESSION['verify_name']      = $full_name;
        $_SESSION['verify_password']  = password_hash($password, PASSWORD_BCRYPT);
        $_SESSION['verify_code']      = $otp;
        $_SESSION['verify_code_time'] = time();

        $sendResult = sendOtpEmail($email, $full_name, $otp);
        if ($sendResult === true) {
            // Redirect to verification page
            header("Location: verify_email.php");
            exit;
        } else {
            $error = "Could not send verification email. Please try again. ({$sendResult})";
            unset($_SESSION['verify_code'], $_SESSION['verify_code_time'], $_SESSION['verify_email'], $_SESSION['verify_name'], $_SESSION['verify_password']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account – FinTrack</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>

<div class="card">

    <h2>CREATE ACCOUNT</h2>

    <p class="app-name">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#7c5cbf"
             style="vertical-align:middle;margin-right:5px;">
            <circle cx="12" cy="8" r="5"/>
            <ellipse cx="12" cy="16" rx="7" ry="3"/>
        </svg>
        FinTrack
    </p>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return validateForm()">

        <div id="client-error" class="error" style="display:none;"></div>

        <!-- FULL NAME -->
        <label>FULL NAME</label>
        <div class="input-box">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <input type="text" name="name" placeholder="Full Name"
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                   required>
        </div>

        <!-- EMAIL -->
        <label>EMAIL</label>
        <div class="input-box">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <polyline points="2,4 12,13 22,4"/>
            </svg>
            <input type="email" name="email" placeholder="Email Address"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   required>
        </div>

        <!-- PASSWORD -->
        <label>PASSWORD</label>
        <div class="input-box">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <svg class="eye-icon" onclick="togglePassword('password')" viewBox="0 0 24 24"
                 fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>

        <!-- CONFIRM PASSWORD -->
        <label><span class="confirm-word">CONFIRM</span> PASSWORD</label>
        <div class="input-box">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input type="password" name="confirm_password" id="confirm_password"
                   placeholder="Confirm Password" required>
            <svg class="eye-icon" onclick="togglePassword('confirm_password')" viewBox="0 0 24 24"
                 fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>

        <button type="submit" id="submitBtn">CREATE ACCOUNT</button>

    </form>

    <p class="login-link">Already have an account? <a href="login.php">Log In Here</a></p>

</div>

<script>
    function togglePassword(fieldId) {
        var field = document.getElementById(fieldId);
        field.type = field.type === "password" ? "text" : "password";
    }

    function validateForm() {
        var fullName    = document.querySelector('input[name="name"]').value.trim();
        var password    = document.getElementById('password').value;
        var confirm     = document.getElementById('confirm_password').value;
        var clientError = document.getElementById('client-error');
        var isValidName = /^[a-zA-Z ]{3,}$/.test(fullName);
        var isStrongPwd = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(password);

        if (!isValidName) {
            clientError.textContent = "Please enter a valid full name (letters and spaces only, min 3 characters).";
            clientError.style.display = "block";
            return false;
        }

        if (!isStrongPwd) {
            clientError.textContent = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
            clientError.style.display = "block";
            return false;
        }

        if (password !== confirm) {
            clientError.textContent = "Passwords do not match!";
            clientError.style.display = "block";
            return false;
        }

        clientError.style.display = "none";

        // Show loading state on button
        var btn = document.getElementById('submitBtn');
        btn.textContent = "SENDING CODE...";
        btn.disabled = true;

        return true;
    }
</script>

</body>
</html>
