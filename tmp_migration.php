<?php
require_once 'includes/config/database.php';
try {
    // 1. Update prescriptions table
    $stmt = $pdo->query("SHOW COLUMNS FROM prescriptions LIKE 'user_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE prescriptions ADD COLUMN user_id INT AFTER prescription_date");
        $pdo->exec("ALTER TABLE prescriptions ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added user_id to prescriptions\n";
    }

    // 2. Update sales table
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'pharmacist_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN pharmacist_id INT AFTER user_id");
        $pdo->exec("ALTER TABLE sales ADD FOREIGN KEY (pharmacist_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added pharmacist_id to sales\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'processed_by'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN processed_by INT AFTER pharmacist_id");
        $pdo->exec("ALTER TABLE sales ADD FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added processed_by to sales\n";
    }

    echo "Migration completed successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
unlink(__FILE__);
