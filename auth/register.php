<?php
// Runs when the user submits the form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm_password"];
 
    // Validate full name (letters and spaces only, 3+ characters)
    if (!preg_match('/^[a-zA-Z ]{3,}$/', $full_name)) {
        $error = "Please enter a valid full name (only letters and spaces, at least 3 characters).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // TODO: Save user to your database here
        $success = "Account created successfully!";
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
 
<div class="card">
 
    <h2>CREATE ACCOUNT</h2>
 
    <p class="app-name">
        <!-- Coin/stack icon using SVG - same as UI -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#7c5cbf" style="vertical-align:middle; margin-right:5px;">
            <circle cx="12" cy="8" r="5"/><ellipse cx="12" cy="16" rx="7" ry="3"/>
        </svg>
        Expenses Tracking
    </p>
 
    <!-- Error message -->
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
 
    <!-- Success message -->
    <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
 
    <form method="POST" action="" onsubmit="return validateName()">
        <div id="client-error" class="error" style="display:none;"></div>
 
        <!-- FULL NAME -->
        <label>FULL NAME</label>
        <div class="input-box">
            <!-- User icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <input type="text" name="full_name" placeholder="Full Name" required>
        </div>
 
        <!-- EMAIL -->
        <label>EMAIL</label>
        <div class="input-box">
            <!-- Envelope icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <polyline points="2,4 12,13 22,4"/>
            </svg>
            <input type="email" name="email" placeholder="Email Address" required>
        </div>
 
        <!-- PASSWORD -->
        <label>PASSWORD</label>
        <div class="input-box">
            <!-- Lock icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <!-- Eye icon to show/hide password -->
            <svg class="eye-icon" onclick="togglePassword()" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>
 
        <!-- CONFIRM PASSWORD -->
        <label><span class="confirm-word">CONFIRM</span> PASSWORD</label>
        <div class="input-box">
            <!-- Lock icon -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#a98fd4" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        </div>
 
        <button type="submit">CREATE ACCOUNT</button>
 
    </form>
 
    <p class="login-link">Already have an account? <a href="login.php">Log In Here</a></p>
 
</div>
 
<script>
    function togglePassword() {
        var field = document.getElementById("password");
        field.type = (field.type === "password") ? "text" : "password";
    }

    function validateName() {
        var fullName = document.querySelector('input[name="full_name"]').value.trim();
        var clientError = document.getElementById('client-error');
        var valid = /^[a-zA-Z ]{3,}$/.test(fullName);

        if (!valid) {
            clientError.textContent = 'Please enter a valid full name (letters + spaces only, min 3 chars).';
            clientError.style.display = 'block';
            return false;
        }

        clientError.style.display = 'none';
        return true;
    }
</script>
 
</body>
</html>
 