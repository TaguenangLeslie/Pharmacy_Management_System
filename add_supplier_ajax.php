<?php
/**
 * AJAX Endpoint: Quick-Add Supplier
 * Returns JSON with the new supplier id and name.
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_role(['admin', 'pharmacist', 'cashier'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$name = trim($_POST['name'] ?? '');
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Supplier name is required.']);
    exit;
}

$contact_person  = sanitize_input($_POST['contact_person'] ?? '');
$phone           = sanitize_input($_POST['phone'] ?? '');
$email           = sanitize_input($_POST['email'] ?? '');
$address         = sanitize_input($_POST['address'] ?? '');
$payment_terms   = sanitize_input($_POST['payment_terms'] ?? '');
$pharmacy_id     = $_SESSION['pharmacy_id'];

try {
    $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, payment_terms, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $contact_person, $phone, $email, $address, $payment_terms, $pharmacy_id]);
    $new_id = $pdo->lastInsertId();
    log_activity($pdo, $_SESSION['user_id'], 'ADD_SUPPLIER', 'suppliers', $new_id);
    echo json_encode(['success' => true, 'id' => $new_id, 'name' => $name]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
