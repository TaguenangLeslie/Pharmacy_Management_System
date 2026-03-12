<?php
/**
 * Data Seeder - Populates the pharmacy system with realistic sample data
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/helpers.php';

echo "<h2>PharmaCare Data Seeder</h2>";

try {
    // 1. Users
    $roles = ['admin', 'pharmacist', 'cashier'];
    foreach ($roles as $role) {
        $username = $role . '_user';
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            $pass = password_hash('password123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, "$role@example.com", $pass, $role, "Sam " . ucfirst($role)]);
            echo "Added User: $username<br>";
        }
    }

    // 2. Suppliers
    $supplier_names = ['Global Pharma', 'BioHealth Supplies', 'MedDirect Inc', 'Apex Medical', 'VitalHealth Co'];
    foreach ($supplier_names as $name) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, 'John Doe', '123456789', "contact@$name.com", '123 Pharma St']);
    }
    $supplier_ids = $pdo->query("SELECT id FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);
    echo "Added " . count($supplier_names) . " Suppliers<br>";

    // 3. Medicines
    $medicines = [
        ['Paracetamol', 'Acetaminophen', 'Tablet', 5.50, 2.00, 500, 'Box'],
        ['Amoxicillin', 'Amoxicillin', 'Capsule', 12.00, 6.50, 200, 'Pack'],
        ['Ibuprofen', 'Ibuprofen', 'Tablet', 8.25, 3.10, 350, 'Box'],
        ['Cough Relief', 'Guaifenesin', 'Syrup', 15.00, 7.00, 100, 'Bottle'],
        ['Vitamin C', 'Ascorbic Acid', 'Tablet', 25.00, 12.00, 150, 'Bottle'],
        ['Insulin', 'Insulin Glargine', 'Injection', 45.00, 28.00, 50, 'Vial'],
        ['Eye Drops', 'Naphazoline', 'Drops', 9.50, 4.00, 80, 'Bottle'],
        ['Skin Cream', 'Hydrocortisone', 'Ointment', 11.00, 5.20, 120, 'Tube']
    ];
    foreach ($medicines as $m) {
        $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, category, supplier_id, price, cost_price, quantity, unit, expiry_date, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $expiry = date('Y-m-d', strtotime('+' . rand(6, 24) . ' months'));
        $stmt->execute([$m[0], $m[1], $m[2], $supplier_ids[array_rand($supplier_ids)], $m[3], $m[4], $m[5], $m[6], $expiry, 'BAR' . rand(1000,9999)]);
    }
    $medicine_data = $pdo->query("SELECT id, price, cost_price FROM medicines")->fetchAll();
    echo "Added " . count($medicines) . " Medicines<br>";

    // 4. Customers
    $customers = ['Alice Smith', 'Bob Johnson', 'Charlie Brown', 'Diana Prince', 'Edward Norton'];
    foreach ($customers as $name) {
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, loyalty_points) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, '555-01' . rand(10,99), strtolower(str_replace(' ','.',$name)).'@email.com', rand(0, 100)]);
    }
    $customer_ids = $pdo->query("SELECT id FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    echo "Added " . count($customers) . " Customers<br>";

    // 5. Sales (History over the last 30 days)
    for ($i = 0; $i < 20; $i++) {
        $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
        $cust_id = $customer_ids[array_rand($customer_ids)];
        $inv = 'INV-' . strtoupper(substr(uniqid(), -8));
        
        $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, customer_name, total_amount, tax, discount, grand_total, payment_method, sale_date, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inv, $cust_id, 'Sample Customer', 0, 0, 0, 0, 'Cash', $date, 1]);
        $sale_id = $pdo->lastInsertId();
        
        $total = 0;
        $num_items = rand(1, 3);
        for ($j = 0; $j < $num_items; $j++) {
            $med = $medicine_data[array_rand($medicine_data)];
            $qty = rand(1, 5);
            $item_total = $med['price'] * $qty;
            $total += $item_total;
            
            $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt_item->execute([$sale_id, $med['id'], $qty, $med['price'], $item_total]);
        }
        
        $stmt_upd = $pdo->prepare("UPDATE sales SET total_amount = ?, grand_total = ? WHERE id = ?");
        $stmt_upd->execute([$total, $total, $sale_id]);
    }
    echo "Generated 20 Sales Transactions<br>";

    // 6. Expenses
    $exp_cats = ['rent', 'utilities', 'salary', 'supplies'];
    for ($i = 0; $i < 10; $i++) {
        $stmt = $pdo->prepare("INSERT INTO expenses (category, amount, description, date, user_id) VALUES (?, ?, ?, ?, ?)");
        $date = date('Y-m-d', strtotime('-' . rand(0, 30) . ' days'));
        $stmt->execute([$exp_cats[array_rand($exp_cats)], rand(50, 500), 'Sample operational expense', $date, 1]);
    }
    echo "Added 10 Expense Records<br>";

    // 7. Prescriptions
    $prescrip = [
        ['John Miller', 45, 'Dr. Smith', '2026-03-10', 'pending'],
        ['Sarah Connor', 30, 'Dr. Aris', '2026-03-11', 'filled'],
        ['Peter Parker', 19, 'Dr. Strange', '2026-03-12', 'pending']
    ];
    foreach ($prescrip as $p) {
        $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_name, patient_age, doctor_name, prescription_date, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($p);
    }
    echo "Added " . count($prescrip) . " Prescriptions<br>";

    // 8. Audit Logs
    for ($i = 0; $i < 15; $i++) {
        log_activity($pdo, 1, 'SEED_DATA', 'system', null, null, 'Running system seeder to populate sample data');
    }
    echo "Added Audit Log entries<br>";

    echo "<h3 style='color: green;'>Seeding Completed Successfully!</h3>";
    echo "<p><a href='dashboard.php'>View Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Seeding failed: " . $e->getMessage() . "</p>";
}
