<?php
/**
 * One-time fix for Platform Tax Revenue and Dashboard Analytics
 */
require_once '../includes/config/database.php';

echo "<h2>🔧 PharmaCare - Revenue System Fix</h2>";

try {
    // 1. Add missing columns to sales table
    $sales_cols = $pdo->query("DESCRIBE sales")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('platform_tax', $sales_cols)) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN platform_tax DECIMAL(10,2) DEFAULT 0 AFTER discount");
        echo "✅ Added 'platform_tax' column to sales table.<br>";
    }
    
    if (!in_array('pharmacist_id', $sales_cols)) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN pharmacist_id INT AFTER user_id, ADD FOREIGN KEY (pharmacist_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "✅ Added 'pharmacist_id' column to sales table.<br>";
    }

    // 2. Add missing columns to users table
    $users_cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('language', $users_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'en' AFTER role");
        echo "✅ Added 'language' column to users table.<br>";
    }

    // 3. Seed Global Platform Tax Rate if missing
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'platform_tax_rate' AND pharmacy_id IS NULL");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, pharmacy_id) VALUES ('platform_tax_rate', '2.00', NULL)");
        $stmt->execute();
        echo "✅ Global Platform Tax Rate initialized to 2.00%.<br>";
    }

    echo "<h3>✨ Revenue Fix Completed!</h3>";
    echo "<p>The dashboard analysis and platform revenue should now update correctly.</p>";
    echo "<a href='../dashboard.php'>Go to Dashboard</a>";

} catch (Exception $e) {
    echo "❌ Fix Failed: " . $e->getMessage();
}
?>
