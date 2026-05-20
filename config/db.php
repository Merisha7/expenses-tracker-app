<?php
declare(strict_types=1);

// Database connection settings for local XAMPP.
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'expenses_tracker';

try {
	$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die('Database connection failed: ' . $e->getMessage());
}
?>
