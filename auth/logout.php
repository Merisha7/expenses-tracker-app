<?php
session_start();
session_destroy();
header("Location: ./login.php?action=login&msg=logged_out");
exit;
