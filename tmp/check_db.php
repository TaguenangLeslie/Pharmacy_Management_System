<?php
require_once 'c:/Users/ekuty/Desktop/php/htdocs/Pharmacy_Management_System/includes/config/database.php';

try {
    echo "SALES TABLE:\n";
    $stmt = $pdo->query("DESCRIBE sales");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\nUSERS TABLE:\n";
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\nSETTINGS TABLE:\n";
    $stmt = $pdo->query("SELECT * FROM settings WHERE pharmacy_id IS NULL");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
