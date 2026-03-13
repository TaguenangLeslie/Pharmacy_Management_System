<?php
/**
 * Master Installation & Setup Script
 * Sets up database, schema, and sample data in one go.
 */
require_once 'includes/config/database.php';

// CSS for the installer page
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #FFF0F5; color: #333; line-height: 1.6; }
    .container { max-width: 800px; margin: 50px auto; background: white; padding: 40px; border-radius: 20px; shadow: 0 10px 30px rgba(0,0,0,0.05); }
    h2 { color: #FF1493; border-bottom: 2px solid #FFC0CB; padding-bottom: 10px; }
    .success { color: #2ecc71; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .info { color: #3498db; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 10px; font-size: 0.9em; }
    .btn { display: inline-block; background: #FF1493; color: white; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; margin-top: 20px; transition: 0.3s; }
    .btn:hover { background: #C71585; transform: translateY(-2px); }
</style>";

echo "<div class='container'>";
echo "<h2>🚀 PharmaCare - System Setup</h2>";

try {
    // 1. Re-connect to create DB if missing (using credentials from config)
    $temp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "<p class='success'>✅ Database '" . DB_NAME . "' ensured.</p>";
    
    // Re-connect to the actual DB
    $pdo->exec("USE " . DB_NAME);

    // 2. Run Schema (Create tables if they don't exist)
    $sql = file_get_contents('database/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) $pdo->exec($stmt);
    }
    echo "<p class='success'>✅ Database tables and relations ensured.</p>";

    // 2b. Schema Upgrade (Add columns to existing tables if missing)
    $columns_to_check = [
        'pharmacies' => [
            'license_no' => "ALTER TABLE pharmacies ADD COLUMN license_no VARCHAR(50) AFTER email",
            'pharmacy_type' => "ALTER TABLE pharmacies ADD COLUMN pharmacy_type ENUM('Retail', 'Wholesale', 'Hospital', 'Clinic') DEFAULT 'Retail' AFTER license_no",
            'owner_full_name' => "ALTER TABLE pharmacies ADD COLUMN owner_full_name VARCHAR(100) AFTER owner_id",
            'owner_id_doc' => "ALTER TABLE pharmacies ADD COLUMN owner_id_doc VARCHAR(255) AFTER owner_full_name",
            'pharmacy_license_doc' => "ALTER TABLE pharmacies ADD COLUMN pharmacy_license_doc VARCHAR(255) AFTER owner_id_doc",
            'business_reg_no' => "ALTER TABLE pharmacies ADD COLUMN business_reg_no VARCHAR(50) AFTER pharmacy_license_doc",
            'business_reg_doc' => "ALTER TABLE pharmacies ADD COLUMN business_reg_doc VARCHAR(255) AFTER business_reg_no",
            'pharmacist_name' => "ALTER TABLE pharmacies ADD COLUMN pharmacist_name VARCHAR(100) AFTER business_reg_doc",
            'pharmacist_license_no' => "ALTER TABLE pharmacies ADD COLUMN pharmacist_license_no VARCHAR(50) AFTER pharmacist_name",
            'pharmacist_doc' => "ALTER TABLE pharmacies ADD COLUMN pharmacist_doc VARCHAR(255) AFTER pharmacist_license_no"
        ],
        'customers' => [
            'pharmacy_id' => "ALTER TABLE customers ADD COLUMN pharmacy_id INT NULL AFTER id, ADD INDEX(pharmacy_id)"
        ],
        'suppliers' => [
            'pharmacy_id' => "ALTER TABLE suppliers ADD COLUMN pharmacy_id INT NULL AFTER id, ADD INDEX(pharmacy_id)"
        ],
        'prescriptions' => [
            'user_id' => "ALTER TABLE prescriptions ADD COLUMN user_id INT NULL AFTER id, ADD INDEX(user_id)"
        ]
    ];

    foreach ($columns_to_check as $table => $cols) {
        $existing_cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cols as $col_name => $sql) {
            if (!in_array($col_name, $existing_cols)) {
                try {
                    $pdo->exec($sql);
                    echo "<p class='success'>✅ Added missing column: <strong>$col_name</strong> to $table.</p>";
                } catch (PDOException $e) {
                    echo "<p class='error'>❌ Failed to add $col_name: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    echo "<p class='success'>✅ Schema upgrade check completed.</p>";

    // 3. Seed Pharmacies
    $pharmacies_to_seed = [
        ['Main PharmaCare', '123 Health Ave, Bamenda', '670000000', 'main@pharmacare.com', 'active', 'Retail'],
        ['Elite Wellness', '45 Commercial St, Douala', '671111111', 'elite@wellness.com', 'active', 'Wholesale'],
        ['Community Clinic', 'Health Center Rd, Yaounde', '672222222', 'yaounde@clinic.com', 'active', 'Clinic']
    ];
    
    $pharma_ids = [];
    foreach ($pharmacies_to_seed as $p) {
        $stmt = $pdo->prepare("SELECT id FROM pharmacies WHERE name = ?");
        $stmt->execute([$p[0]]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $stmt = $pdo->prepare("INSERT INTO pharmacies (name, address, phone, email, status, pharmacy_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute($p);
            $pharma_ids[$p[0]] = $pdo->lastInsertId();
            echo "<p class='success'>✅ Pharmacy '{$p[0]}' initialized.</p>";
        } else {
            $pharma_ids[$p[0]] = $existing['id'];
            echo "<p class='info'>ℹ️ Pharmacy '{$p[0]}' already exists.</p>";
        }
    }

    $main_id = $pharma_ids['Main PharmaCare'];
    $elite_id = $pharma_ids['Elite Wellness'];
    $clinic_id = $pharma_ids['Community Clinic'];

    // 4. Seed Essential Users
    $seed_users = [
        ['admin', 'admin@pharmacare.com', 'Admin@123', 'admin', 'System Admin', null],
        ['test_customer', 'customer@example.com', 'Customer@123', 'customer', 'Test Customer', null],
        
        ['pharmacist', 'pharma@example.com', 'Pharma@123', 'pharmacist', 'Dr. John Doe', $main_id],
        ['cashier', 'cashier@example.com', 'Cashier@123', 'cashier', 'Jane Cashier', $main_id],
        
        ['elite_pharma', 'elite_ph@example.com', 'Elite@123', 'pharmacist', 'Elite Pharmacist', $elite_id],
        ['elite_cash', 'elite_cs@example.com', 'Elite@123', 'cashier', 'Elite Cashier', $elite_id],
        
        ['clinic_pharma', 'clinic_ph@example.com', 'Clinic@123', 'pharmacist', 'Clinic Specialist', $clinic_id],
        ['clinic_cash', 'clinic_cs@example.com', 'Clinic@123', 'cashier', 'Clinic Cashier', $clinic_id]
    ];

    foreach ($seed_users as $u) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$u[0], $u[1]]);
        if (!$stmt->fetch()) {
            $hashed = password_hash($u[2], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, pharmacy_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$u[0], $u[1], $hashed, $u[3], $u[4], $u[5]]);
            echo "<p class='success'>✅ User created: <strong>{$u[0]}</strong> ({$u[3]})</p>";
        }
    }

    // Force admin to be Platform Admin (Global)
    $pdo->exec("UPDATE users SET pharmacy_id = NULL, role = 'admin' WHERE username = 'admin'");
    echo "<p class='success'>✅ Platform Admin '<strong>admin</strong>' verified as Global.</p>";

    // 5. System Fixes
    $pdo->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
    // Removed global setting insertion that violated FK setup. Settings will be seeded per pharmacy.

    // 6. Seed Medicines & Customers for all Pharmacies
    foreach ($pharma_ids as $p_name => $p_id) {
        // Seed Supplier per pharmacy
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE pharmacy_id = ? LIMIT 1");
        $stmt->execute([$p_id]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, pharmacy_id) VALUES (?, 'Mr. Supplier', '655000000', ?)");
            $stmt->execute(["Supplier for $p_name", $p_id]);
            $sup_id = $pdo->lastInsertId();
            
            $medicines = [
                ['Paracetamol', 'Analgesic', 'Tablet', 500, 200, 1000, 'Box'],
                ['Amoxicillin', 'Antibiotic', 'Capsule', 1500, 800, 500, 'Pack'],
                ['Vitamin C', 'Supplement', 'Tablet', 1200, 600, 300, 'Bottle'],
                ['Insulin', 'Diabetes', 'Injection', 4500, 2500, 50, 'Vial']
            ];

            foreach ($medicines as $m) {
                $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, category, price, cost_price, quantity, unit, pharmacy_id, supplier_id, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $m[6], $p_id, $sup_id, date('Y-12-31')]);
            }
            echo "<p class='success'>✅ Inventory seeded for <strong>$p_name</strong>.</p>";
        }

        // Seed Customers per pharmacy
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE pharmacy_id = ? LIMIT 1");
        $stmt->execute([$p_id]);
        if (!$stmt->fetch()) {
            $customers = [
                ["Patient One ($p_name)", "677-001", "p1@$p_name.com"],
                ["Loyal Client ($p_name)", "677-002", "lc@$p_name.com"]
            ];
            foreach ($customers as $c) {
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, pharmacy_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([...$c, $p_id]);
            }
            echo "<p class='success'>✅ Test customers seeded for <strong>$p_name</strong>.</p>";
        }
    }

    echo "<h3>✨ Security Hardening & Setup Complete!</h3>";
    echo "<p>Multi-tenancy isolation is now active. You can log in with different roles to test data gating.</p>";
    echo "<pre>Username: admin / Password: Admin@123</pre>";
    echo "<a href='login.php' class='btn'>Launch System</a>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Setup Error: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure your DB_USER and DB_PASS in <code>includes/config/database.php</code> are correct.</p>";
}

echo "</div>";
?>
