<?php
// Return JSON because this file is used as a data API.
header('Content-Type: application/json; charset=UTF-8');
session_start();

// Use session values when available, otherwise use demo user values.
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Test User';

// Demo values for now (no database connected yet).
$totalBalance = 5200.0;
$monthlyIncome = 3000.0;
$monthlyExpenses = 1800.0;
$recentActivity = [
    ['id' => 1, 'date' => '2026-03-28', 'description' => 'Salary', 'amount' => 4000.0, 'type' => 'income'],
    ['id' => 2, 'date' => '2026-03-26', 'description' => 'Utilities', 'amount' => 125.0, 'type' => 'expense'],
    ['id' => 3, 'date' => '2026-03-25', 'description' => 'Freelance Project', 'amount' => 650.0, 'type' => 'income'],
    ['id' => 4, 'date' => '2026-03-24', 'description' => 'Shopping', 'amount' => 89.50, 'type' => 'expense'],
    ['id' => 5, 'date' => '2026-03-22', 'description' => 'Restaurant', 'amount' => 42.75, 'type' => 'expense'],
    ['id' => 6, 'date' => '2026-03-20', 'description' => 'Groceries', 'amount' => 156.30, 'type' => 'expense'],
    ['id' => 7, 'date' => '2026-03-18', 'description' => 'Entertainment', 'amount' => 35.00, 'type' => 'expense'],
    ['id' => 8, 'date' => '2026-03-15', 'description' => 'Rent', 'amount' => 1200.0, 'type' => 'expense'],
    ['id' => 9, 'date' => '2026-03-12', 'description' => 'Gas', 'amount' => 55.80, 'type' => 'expense'],
    ['id' => 10, 'date' => '2026-03-10', 'description' => 'Freelance Work', 'amount' => 500.0, 'type' => 'income']
];
$chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$chartIncome = [3200, 3800, 4100, 3600, 4300, 5000];
$chartExpenses = [1800, 2200, 1900, 2500, 2100, 1750];

// Send one JSON response for the frontend JavaScript.
echo json_encode([
    'userName' => $userName,
    'totalBalance' => $totalBalance,
    'monthlyIncome' => $monthlyIncome,
    'monthlyExpenses' => $monthlyExpenses,
    'recentActivity' => $recentActivity,
    'chart' => [
        'labels' => $chartLabels,
        'income' => $chartIncome,
        'expenses' => $chartExpenses
    ]
]);