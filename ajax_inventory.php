<?php
/**
 * AJAX Inventory Handler - Real-time Stock Sync
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';
cart_log("HIT: ajax_inventory.php | Action: " . ($_POST['action'] ?? 'NONE'));
require_once 'includes/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$session_id = session_id();

switch ($action) {
    case 'reserve':
        $medicine_id = (int)$_POST['medicine_id'];
        $pharmacy_id = (int)$_POST['pharmacy_id'];
        $qty = (int)$_POST['quantity'];
        
        try {
            $pdo->beginTransaction();
            
            // 1. Check current stock
            $stmt = $pdo->prepare("SELECT name, quantity FROM medicines WHERE id = ? AND pharmacy_id = ? FOR UPDATE");
            $stmt->execute([$medicine_id, $pharmacy_id]);
            $med = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$med) {
                $pdo->rollBack();
                $msg = "Medicine ID $medicine_id not found for Pharmacy $pharmacy_id";
                if (function_exists('cart_log')) cart_log("Ajax Error: " . $msg);
                echo json_encode(['status' => 'error', 'message' => 'Item not found in this pharmacy.']);
                exit;
            }
            
            $current_stock = (int)$med['quantity'];
            if ($current_stock < $qty) {
                $pdo->rollBack();
                $msg = "Insufficient stock for {$med['name']} (Req: $qty, Avail: $current_stock)";
                if (function_exists('cart_log')) cart_log("Ajax Error: " . $msg);
                echo json_encode(['status' => 'error', 'message' => "Only $current_stock items available."]);
                exit;
            }
            
            // 2. Add to reservations
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $stmt = $pdo->prepare("INSERT INTO cart_reservations (session_id, medicine_id, pharmacy_id, quantity, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$session_id, $medicine_id, $pharmacy_id, $qty, $expires_at]);
            
            // 3. Deduct from medicines
            $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$qty, $medicine_id]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'new_stock' => $current_stock - $qty]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'release':
        $medicine_id = (int)$_POST['medicine_id'];
        $pharmacy_id = (int)$_POST['pharmacy_id'];
        $qty = (int)$_POST['quantity'];
        
        try {
            $pdo->beginTransaction();
            
            // 1. Find and remove reservation (limit 1 to avoid over-releasing if multiple exist)
            $stmt = $pdo->prepare("DELETE FROM cart_reservations WHERE session_id = ? AND medicine_id = ? AND pharmacy_id = ? AND quantity >= ? LIMIT 1");
            $stmt->execute([$session_id, $medicine_id, $pharmacy_id, $qty]);
            
            if ($stmt->rowCount() > 0) {
                // 2. Add back to medicines
                $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$qty, $medicine_id]);
            }
            
            $pdo->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'poll':
        // Poll current stock for a list of IDs
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            echo json_encode(['status' => 'success', 'stocks' => []]);
            exit;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, quantity FROM medicines WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $stocks = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo json_encode(['status' => 'success', 'stocks' => $stocks]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
