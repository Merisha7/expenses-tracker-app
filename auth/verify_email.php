<?php
// sessions let us pass data between pages (like passing a note from one page to another)
session_start();

// if someone opens this page directly without registering, kick them back
if (empty($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit;
}

// get the email the user typed during registration
$email   = $_SESSION['verify_email'];
$error   = "";
$success = "";

// set the demo code the first time this page loads
// SPRINT 2: swap "123456" with a real random code and send it by email
if (empty($_SESSION['verify_code'])) {
    $_SESSION['verify_code']      = "123456"; // demo code — always 123456 for now
    $_SESSION['verify_code_time'] = time();   // save what time the code was created
}

// this runs when the user clicks any button on the page
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // user clicked "Resend Code"
    if (isset($_POST['resend'])) {
        $_SESSION['verify_code']      = "123456"; // reset demo code
        $_SESSION['verify_code_time'] = time();
        $success = "Code resent! Use demo code: 123456";

    // user clicked "Verify Email"
    } else {

        // join the 6 boxes into one string e.g. "1","2","3","4","5","6" → "123456"
        $entered_code = $_POST['digit_1'] . $_POST['digit_2'] . $_POST['digit_3']
                      . $_POST['digit_4'] . $_POST['digit_5'] . $_POST['digit_6'];

        // check 1 — did the user fill all 6 boxes?
        if (strlen($entered_code) < 6) {
            $error = "Please enter all 6 digits.";

        // check 2 — was the code created more than 10 minutes ago? (600 seconds)
        } elseif (time() - $_SESSION['verify_code_time'] > 600) {
            $error = "Your code has expired. Please request a new one.";

        // check 3 — does what the user typed match the code we saved?
        } elseif ($entered_code !== $_SESSION['verify_code']) {
            $error = "Incorrect code. Please try again.";

        // all 3 checks passed — the email is verified!
        } else {
            // clear the verification data from the session
            unset($_SESSION['verify_code']);
            unset($_SESSION['verify_code_time']);
            unset($_SESSION['verify_email']);

            // TODO Sprint 2: mark this user as verified in your database

            // send the user to the login page
            header("Location: login.php?verified=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link rel="stylesheet" href="../assets/css/verify_email.css">
</head>
<body>

<div class="card">

    <!-- back arrow to go back to register page -->
    <a class="back-link" href="register.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="#7c5cbf" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>

    <!-- page title -->
    <h2 class="verify-title">VERIFY EMAIL</h2>

    <!-- purple box with mail icon -->
    <div class="mail-icon-wrap">
        <div class="mail-icon-box">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <polyline points="2,4 12,13 22,4"/>
            </svg>
        </div>
    </div>

    <!-- tell the user which email we sent the code to -->
    <p class="verify-subtitle">
        We've sent a 6-digit verification code to<br>
        <span><?php echo htmlspecialchars($email); ?></span>
    </p>

    <!-- demo notice — remove this in Sprint 2 -->
    <div class="success" style="margin-bottom: 10px;">
        Demo mode: use code <strong>1 2 3 4 5 6</strong>
    </div>

    <!-- show error if something went wrong -->
    <?php if ($error != ""): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- show success message e.g. after resend -->
    <?php if ($success != ""): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- 6 digit input boxes -->
    <form method="POST" action="">
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

    <!-- resend button — separate form so it doesn't submit the digits -->
    <div class="resend-row">
        Didn't receive the code?&nbsp;
        <form method="POST" action="" style="display:inline;">
            <button type="submit" name="resend" value="1">Resend Code</button>
        </form>
    </div>

</div>

<script>
    // get each box by its id
    var box1 = document.getElementById('d1');
    var box2 = document.getElementById('d2');
    var box3 = document.getElementById('d3');
    var box4 = document.getElementById('d4');
    var box5 = document.getElementById('d5');
    var box6 = document.getElementById('d6');

    // when the user types in box 1, jump to box 2
    box1.addEventListener('input', function() {
        if (box1.value != '') { box2.focus(); }
    });

    // when the user types in box 2, jump to box 3
    box2.addEventListener('input', function() {
        if (box2.value != '') { box3.focus(); }
    });

    // when the user types in box 3, jump to box 4
    box3.addEventListener('input', function() {
        if (box3.value != '') { box4.focus(); }
    });

    // when the user types in box 4, jump to box 5
    box4.addEventListener('input', function() {
        if (box4.value != '') { box5.focus(); }
    });

    // when the user types in box 5, jump to box 6
    box5.addEventListener('input', function() {
        if (box5.value != '') { box6.focus(); }
    });

    // when the user presses backspace on an empty box, go back to the previous box
    box2.addEventListener('keydown', function(e) { if (e.key === 'Backspace' && box2.value === '') { box1.focus(); } });
    box3.addEventListener('keydown', function(e) { if (e.key === 'Backspace' && box3.value === '') { box2.focus(); } });
    box4.addEventListener('keydown', function(e) { if (e.key === 'Backspace' && box4.value === '') { box3.focus(); } });
    box5.addEventListener('keydown', function(e) { if (e.key === 'Backspace' && box5.value === '') { box4.focus(); } });
    box6.addEventListener('keydown', function(e) { if (e.key === 'Backspace' && box6.value === '') { box5.focus(); } });

    // put the cursor in box 1 as soon as the page loads
    box1.focus();
</script>

</body>
</html>
