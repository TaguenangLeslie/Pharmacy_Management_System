<?php
/**
 * Installation Script - Database Initialization
 */

require_once 'includes/config/database.php';

echo "<h2>Starting System Installation...</h2>";

try {
    // 1. Check if database exists or try to create it if we have permissions
    // Note: Our config already tries to connect to the specific DB.
    // We might need a separate connection to 'mysql' to create the DB first.
    
    $sql = file_get_contents('database/schema.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p style='color: green;'>✅ Database tables created successfully.</p>";
    echo "<p style='color: green;'>✅ Default settings initialized.</p>";
    
    // 2. Create Default Admin User
    $admin_user = 'admin';
    $admin_email = 'admin@pharmacare.com';
    $admin_pass = 'Admin@123';
    $hashed_pass = password_hash($admin_pass, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$admin_user]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name) VALUES (?, ?, ?, 'admin', 'System Administrator')");
        $stmt->execute([$admin_user, $admin_email, $hashed_pass]);
        echo "<p style='color: green;'>✅ Default admin user created.</p>";
        echo "<ul>
                <li>Username: <strong>admin</strong></li>
                <li>Password: <strong>Admin@123</strong></li>
              </ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Admin user already exists.</p>";
    }
    
    echo "<h3>Installation Complete!</h3>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Installation failed: " . $e->getMessage() . "</p>";
}
