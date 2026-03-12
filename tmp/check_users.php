<?php
require_once 'includes/config/database.php';
try {
    $stmt = $pdo->query("SELECT username, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
