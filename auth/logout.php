<?php
session_start();
session_destroy();
header("Location: /expenses-tracker-app/auth/login.php?action=login&msg=logged_out");
exit;
