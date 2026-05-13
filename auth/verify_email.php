<?php
require_once '../config/db.php';
session_start();

// Redirect back if someone opens this page directly
if (empty($_SESSION['verify_email']) || empty($_SESSION['verify_password'])) {
    header("Location: register.php");
    exit;
}

$email   = $_SESSION['verify_email'];
$name    = $_SESSION['verify_name'] ?? '';
$password_hash = $_SESSION['verify_password'] ?? '';
$error   = "";
$success = "";

// -------------------------------------------------------
// Helper: send OTP email via Mailtrap
// -------------------------------------------------------
function sendOtpEmail($toEmail, $otp) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    die("Composer missing. Run: composer require phpmailer/phpmailer");
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
        $mail->addAddress($toEmail);

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
                    <p style='color:#555;font-size:15px;'>Your verification code is:</p>
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

// -------------------------------------------------------
// Handle POST requests
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- Resend button clicked ---
    if (isset($_POST['resend'])) {
        $newOtp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['verify_code']      = $newOtp;
        $_SESSION['verify_code_time'] = time();

        $result = sendOtpEmail($email, $newOtp);
        if ($result === true) {
            $success = "A new code has been sent to your email.";
        } else {
            $error = "Failed to resend. Please try again. (Error: {$result})";
        }

    // --- Verify button clicked ---
    } else {
        $entered_code = trim(
            $_POST['digit_1'] . $_POST['digit_2'] . $_POST['digit_3'] .
            $_POST['digit_4'] . $_POST['digit_5'] . $_POST['digit_6']
        );

        if (strlen($entered_code) < 6) {
            $error = "Please enter all 6 digits.";

        } elseif (time() - $_SESSION['verify_code_time'] > 600) {
            $error = "Your code has expired. Please request a new one.";

        } elseif ($entered_code !== $_SESSION['verify_code']) {
            $error = "Incorrect code. Please try again.";

        } else {
            // ✅ Verified! Save user to database
            try {
                if ($pdo) {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$name, $email, $password_hash]);
                    
                    // Clear session after successful registration
                    unset($_SESSION['verify_code'], $_SESSION['verify_code_time'], $_SESSION['verify_email'], $_SESSION['verify_name'], $_SESSION['verify_password']);
                    
                    header("Location: login.php?verified=1");
                    exit;
                } else {
                    $error = "Database connection failed. Please try again later.";
                }
            }catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "This email is already registered.";
                    } else {
                        $error = "Database error. Try again.";
                        }
}
        }
    }
}

// How many seconds are left before the code expires
$timeLeft = 600 - (time() - ($_SESSION['verify_code_time'] ?? time()));
$timeLeft = max(0, $timeLeft);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email – FinTrack</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link rel="stylesheet" href="../assets/css/verify_email.css">
</head>
<body>

<div class="card">

    <!-- Back arrow -->
    <a class="back-link" href="register.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="#7c5cbf" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>

    <h2 class="verify-title">VERIFY EMAIL</h2>

    <!-- Mail icon -->
    <div class="mail-icon-wrap">
        <div class="mail-icon-box">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <polyline points="2,4 12,13 22,4"/>
            </svg>
        </div>
    </div>

    <p class="verify-subtitle">
        We've sent a 6-digit verification code to<br>
        <span><?php echo htmlspecialchars($email); ?></span>
    </p>

    <!-- Countdown timer -->
    <?php if ($timeLeft > 0): ?>
        <p class="timer-text">
            Code expires in <span id="countdown"><?php echo gmdate("i:s", $timeLeft); ?></span>
        </p>
    <?php else: ?>
        <p class="timer-text expired">Code has expired. Please resend.</p>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success != ""): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- 6-digit input form -->
    <form method="POST" action="" onsubmit="return validateCode()">
        <div class="code-row">
            <input class="code-box" type="text" name="digit_1" id="d1" maxlength="1" inputmode="numeric" autocomplete="off">
            <input class="code-box" type="text" name="digit_2" id="d2" maxlength="1" inputmode="numeric" autocomplete="off">
            <input class="code-box" type="text" name="digit_3" id="d3" maxlength="1" inputmode="numeric" autocomplete="off">
            <input class="code-box" type="text" name="digit_4" id="d4" maxlength="1" inputmode="numeric" autocomplete="off">
            <input class="code-box" type="text" name="digit_5" id="d5" maxlength="1" inputmode="numeric" autocomplete="off">
            <input class="code-box" type="text" name="digit_6" id="d6" maxlength="1" inputmode="numeric" autocomplete="off">
        </div>
        <button type="submit">VERIFY EMAIL</button>
    </form>

    <!-- Resend form -->
    <div class="resend-row">
        Didn't receive the code?&nbsp;
        <form method="POST" action="" style="display:inline;">
            <button type="submit" name="resend" value="1" class="resend-btn">Resend Code</button>
        </form>
    </div>

</div>

<script>
    // ---- Box auto-advance & backspace ----
    var boxes = ['d1','d2','d3','d4','d5','d6'].map(function(id) {
        return document.getElementById(id);
    });

    boxes.forEach(function(box, i) {

        box.addEventListener('input', function() {
            box.value = box.value.replace(/[^0-9]/g, ''); // digits only
            if (box.value !== '' && i < boxes.length - 1) {
                boxes[i + 1].focus();
            }
        });

        box.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && box.value === '' && i > 0) {
                boxes[i - 1].focus();
            }
        });

        // Paste handler: paste full 6-digit code from email
        box.addEventListener('paste', function(e) {
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
            if (/^\d{6}$/.test(pasted)) {
                boxes.forEach(function(b, idx) { b.value = pasted[idx]; });
                boxes[5].focus();
            }
        });
    });

    // Auto-focus first box
    boxes[0].focus();

    // Validate all boxes filled before submit
    function validateCode() {
        var allFilled = boxes.every(function(b) { return b.value !== ''; });
        if (!allFilled) {
            alert('Please enter all 6 digits.');
            return false;
        }
        return true;
    }

    // ---- Countdown timer ----
    var secondsLeft = <?php echo $timeLeft; ?>;

    function updateTimer() {
        var el = document.getElementById('countdown');
        if (!el || secondsLeft <= 0) return;

        secondsLeft--;
        var m = Math.floor(secondsLeft / 60);
        var s = secondsLeft % 60;
        el.textContent = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);

        if (secondsLeft <= 0) {
            el.parentElement.className = 'timer-text expired';
            el.parentElement.textContent = 'Code has expired. Please resend.';
        }
    }

    if (secondsLeft > 0) {
        setInterval(updateTimer, 1000);
    }
</script>

</body>
</html>
