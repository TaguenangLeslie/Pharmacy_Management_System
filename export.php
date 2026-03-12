<?php
/**
 * Generic CSV Export Utility
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$type = $_GET['type'] ?? 'sales';
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

$filename = "pharmacy_report_" . $type . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '";');

$output = fopen('php://output', 'w');

try {
    if ($type === 'sales') {
        fputcsv($output, ['Invoice #', 'Date', 'Customer', 'Cashier', 'Method', 'Total']);
        $stmt = $pdo->prepare("SELECT s.invoice_no, s.sale_date, s.customer_name, u.username, s.payment_method, s.grand_total FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE DATE(s.sale_date) BETWEEN ? AND ? ORDER BY s.sale_date DESC");
        $stmt->execute([$start_date, $end_date]);
    } elseif ($type === 'expenses') {
        fputcsv($output, ['Date', 'Category', 'Description', 'Amount']);
        $stmt = $pdo->prepare("SELECT date, category, description, amount FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC");
        $stmt->execute([$start_date, $end_date]);
    } elseif ($type === 'inventory') {
        fputcsv($output, ['Medicine', 'Generic', 'Category', 'Stock', 'Price', 'Expiry']);
        $stmt = $pdo->query("SELECT name, generic_name, category, quantity, price, expiry_date FROM medicines ORDER BY name ASC");
    } else {
        die("Invalid report type.");
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} catch (PDOException $e) {
    fputcsv($output, ['Error generating report: ' . $e->getMessage()]);
}

fclose($output);
exit();
