<?php
require_once 'includes/config/database.php';
echo "--- Settings (platform_tax_rate) ---\n";
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key = 'platform_tax_rate'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Recent Sales (Last 5) ---\n";
$stmt = $pdo->query("SELECT id, invoice_no, total_amount, grand_total, platform_tax, sale_date, pharmacy_id FROM sales ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Pharmacy Revenue Calculation Test ---\n";
$stmt = $pdo->query("
    SELECT p.name, COALESCE(SUM(s.platform_tax), 0) as total_tax
    FROM pharmacies p
    LEFT JOIN sales s ON p.id = s.pharmacy_id
    GROUP BY p.id
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
