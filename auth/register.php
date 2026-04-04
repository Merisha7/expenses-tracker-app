<?php
// Start the session so we can store data (like the email) across pages
session_start();

// This block only runs when the user clicks the "CREATE ACCOUNT" button
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Grab what the user typed in each field and clean up extra spaces
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm_password"];

    // CHECK 1: Full name must be letters and spaces only, at least 3 characters
    if (!preg_match('/^[a-zA-Z ]{3,}$/', $full_name)) {
        $error = "Please enter a valid full name (letters and spaces only, at least 3 characters).";

    // CHECK 2: Email must be a valid email format (e.g. abc@gmail.com)
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    // CHECK 3: Both password fields must match
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";

    // All checks passed — save data and go to email verification
    } else {

        // TODO: Save the user into your database here (mark them as "unverified" for now)

        // Save the email in the session so verify_email.php knows who to verify
        $_SESSION['verify_email'] = $email;

        // Send the user to the email verification page
        header("Location: verify_email.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <!-- All the styles for this page -->
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>

<div class="card">

    <!-- Page title -->
    <h2>CREATE ACCOUNT</h2>

    <!-- App name with a small coin icon -->
    <p class="app-name">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#7c5cbf" style="vertical-align:middle; margin-right:5px;">
            <circle cx="12" cy="8" r="5"/>
            <ellipse cx="12" cy="16" rx="7" ry="3"/>
        </svg>
        Expenses Tracking
    </p>

    <!-- Show the error message if one exists -->
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Registration form -->
    <!-- onsubmit calls validateName() in JavaScript before the form is sent -->
    <form method="POST" action="" onsubmit="return validateName()">

        <!-- This div is hidden by default — JavaScript will show it if the name is invalid -->
        <div id="client-error" class="error" style="display:none;"></div>

        <!-- FULL NAME field -->
        <label>FULL NAME</label>
        <div class="input-box">
            <!-- Person icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <input type="text" name="full_name" placeholder="Full Name" required>
        </div>

        <!-- EMAIL field -->
        <label>EMAIL</label>
        <div class="input-box">
            <!-- Envelope icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <polyline points="2,4 12,13 22,4"/>
            </svg>
            <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <!-- PASSWORD field -->
        <label>PASSWORD</label>
        <div class="input-box">
            <!-- Lock icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <!-- Eye icon — clicking it runs togglePassword() to show/hide the password -->
            <svg class="eye-icon" onclick="togglePassword()" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>

        <!-- CONFIRM PASSWORD field -->
        <label><span class="confirm-word">CONFIRM</span> PASSWORD</label>
        <div class="input-box">
            <!-- Lock icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
            <!-- Eye icon for confirm password field -->
            <svg class="eye-icon" onclick="toggleConfirmPassword()" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>

        <button type="submit">CREATE ACCOUNT</button>

    </form>

    <!-- Link to login page for users who already have an account -->
    <p class="login-link">Already have an account? <a href="login.php">Log In Here</a></p>

</div>

<script>
    // Show or hide the password when the eye icon is clicked
    function togglePassword() {
        var field = document.getElementById("password");
        // If type is "password" (hidden), change to "text" (visible) — and vice versa
        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }

    // Same thing for the confirm password field
    function toggleConfirmPassword() {
        var field = document.getElementById("confirm_password");
        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }

    // This runs when the form is submitted — checks the name BEFORE sending to server
    function validateName() {
        var fullName    = document.querySelector('input[name="full_name"]').value.trim();
        var clientError = document.getElementById('client-error');

        // Test if the name is letters and spaces only, at least 3 characters
        var isValid = /^[a-zA-Z ]{3,}$/.test(fullName);

        if (!isValid) {
            // Show the error message inside the form
            clientError.textContent = "Please enter a valid full name (letters and spaces only, min 3 characters).";
            clientError.style.display = "block";
            return false; // stops the form from being submitted
        }

        // Hide the error and allow the form to submit
        clientError.style.display = "none";
        return true;
    }
</script>

</body>
</html>
