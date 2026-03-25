<?php
require 'includes/config/database.php';
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings_new (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50),
            setting_value TEXT,
            pharmacy_id INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_setting (setting_key, pharmacy_id),
            FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE
        );
    ");
    echo "Created settings_new\n";
    
    // Ignore duplicates if any
    $pdo->exec("INSERT IGNORE INTO settings_new (setting_key, setting_value, pharmacy_id, updated_at) SELECT setting_key, setting_value, pharmacy_id, updated_at FROM settings");
    echo "Copied data\n";
    
    $pdo->exec("DROP TABLE settings");
    echo "Dropped old settings\n";
    
    $pdo->exec("RENAME TABLE settings_new TO settings");
    echo "Renamed settings_new to settings\n";
    
} catch (Exception $e) { 
    echo $e->getMessage() . "\n"; 
}
echo "Migration complete.";
?>
