<?php
require_once 'includes/config/database.php';

echo "Database: " . DB_NAME . "\n";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables found: " . count($tables) . "\n";

$check_table = 'support_messages';
if (in_array($check_table, $tables)) {
    echo "✅ Table '$check_table' exists.\n";
    $cols = $pdo->query("DESCRIBE $check_table")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "❌ Table '$check_table' MISSING!\n";
}

$check_cols = [
    'medicines' => ['reorder_level', 'barcode'],
    'customers' => ['pharmacy_id'],
    'suppliers' => ['pharmacy_id'],
    'prescriptions' => ['user_id']
];

foreach ($check_cols as $table => $cols) {
    if (in_array($table, $tables)) {
        $existing_cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cols as $col) {
            if (in_array($col, $existing_cols)) {
                echo "✅ Column '$table.$col' exists.\n";
            } else {
                echo "❌ Column '$table.$col' MISSING!\n";
            }
        }
    } else {
        echo "❌ Table '$table' MISSING!\n";
    }
}
?>
