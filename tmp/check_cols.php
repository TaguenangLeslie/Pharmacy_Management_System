<?php
require_once 'includes/config/database.php';

function check_table($pdo, $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}

check_table($pdo, 'sales');
check_table($pdo, 'users');
check_table($pdo, 'settings');
check_table($pdo, 'pharmacies');

echo "\n--- Global Settings ---\n";
$stmt = $pdo->query("SELECT * FROM settings WHERE pharmacy_id IS NULL");
print_r($stmt->fetchAll());
?>
