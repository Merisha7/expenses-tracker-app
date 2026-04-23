<!DOCTYPE html>
<html lang="en">
<head>
   
    <meta charset="UTF-8">

    <title>Expenses Tracking</title>

    <!-- Responsive design for mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- External CSS file -->
    <link rel="stylesheet" href="assets/css/index.css">

    <!-- Google font -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<!-- Main wrapper (centers everything) -->
<div class="wrapper">

    <!-- Card container -->
    <div class="card">

        <!-- Icon box (top logo area) -->
        <div class="icon-box">

            <!-- SVG Wallet Icon -->
            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="white">
                <path d="M3 7h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"></path>
                <path d="M3 7l3-3h12l3 3"></path>
                <path d="M16 12h3"></path>
            </svg>

        </div>

        <!-- App title -->
        <h2>Expenses Tracking</h2>

        <!-- Short description -->
        <p class="subtitle">
            Take control of your finances.<br>
            Track income, expenses & budgets effortlessly.
        </p>

        <!-- Tagline -->
        <p class="tagline">
            "Every rupee has a story. Start writing yours."
        </p>

        <!-- LOGIN BUTTON -->
        <!-- Using GET because we are just redirecting (no data submission) -->
        <form action="auth/login.php" method="GET">
            <button type="submit" class="btn btn-login">

                <!-- Icon inside button -->
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="white">
                    <path d="M5 12h14"></path>
                    <path d="M13 6l6 6-6 6"></path>
                </svg>

                Log In
            </button>
        </form>

        <!-- REGISTER BUTTON -->
        <form action="auth/register.php" method="GET">
            <button type="submit" class="btn btn-register">

                <!-- Icon -->
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="#7b5ce6">
                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5z"></path>
                    <path d="M4 22c0-4 4-7 8-7s8 3 8 7"></path>
                </svg>

                Create Account
            </button>
        </form>

    </div>

</div>

</body>
</html>
