<?php
declare(strict_types=1);

// Database connection settings for local XAMPP.
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'expenses_tracker';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
	die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
