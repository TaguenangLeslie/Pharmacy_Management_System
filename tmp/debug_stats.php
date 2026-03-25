<?php
session_start();
$_SESSION['user_id'] = 1; // Mock user
$_SESSION['role'] = 'admin';
$_SESSION['pharmacy_id'] = null; // Test global admin

require_once 'c:/Users/ekuty/Desktop/php/htdocs/Pharmacy_Management_System/includes/config/database.php';
require_once 'c:/Users/ekuty/Desktop/php/htdocs/Pharmacy_Management_System/includes/functions/auth.php';
require_once 'c:/Users/ekuty/Desktop/php/htdocs/Pharmacy_Management_System/includes/functions/helpers.php';

echo "Testing stats...\n";
try {
    $pharma_id = $_SESSION['pharmacy_id'] ?? null;
    $ph_filter_and = $pharma_id ? " AND pharmacy_id = $pharma_id" : "";

    $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) = CURDATE() $ph_filter_and");
    $stmt->execute();
    $today_sales = $stmt->fetchColumn() ?: 0;
    echo "Today Sales: " . $today_sales . "\n";
    echo "Formatted: " . format_currency($today_sales) . "\n";

    echo "Translation Test (today_sales): " . __('today_sales') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
