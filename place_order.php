<?php
/**
 * Place Order Logic (Customers)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role('customer')) {
    $action = $_POST['action'] ?? 'single';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'cart_checkout' && !empty($_SESSION['cart'])) {
            // Group items by pharmacy
            $grouped = [];
            foreach ($_SESSION['cart'] as $item) {
                $grouped[$item['pharmacy_id']][] = $item;
            }
            
            foreach ($grouped as $pharmacy_id => $items) {
                $total_amount = 0;
                foreach ($items as $item) $total_amount += ($item['price'] * $item['quantity']);
                
                $invoice_no = generate_invoice_no();
                
                // 1. Create Sale record for this pharmacy
                $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, user_id, customer_name, total_amount, grand_total, payment_method, payment_status, order_status, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?)");
                $stmt->execute([
                    $invoice_no,
                    $_SESSION['user_id'],
                    $_SESSION['full_name'],
                    $total_amount,
                    $total_amount,
                    $payment_method,
                    $pharmacy_id
                ]);
                $sale_id = $pdo->lastInsertId();
                
                foreach ($items as $item) {
                    // 2. Double check stock
                    $stmt = $pdo->prepare("SELECT quantity FROM medicines WHERE id = ?");
                    $stmt->execute([$item['id']]);
                    $current_qty = $stmt->fetchColumn();
                    
                    if ($current_qty < $item['quantity']) {
                        throw new Exception("Insufficient stock for '{$item['name']}' at " . $items[0]['pharmacy_name']);
                    }
                    
                    // 3. Add Sale Item
                    $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $sale_id, 
                        $item['id'], 
                        $item['quantity'], 
                        $item['price'], 
                        ($item['price'] * $item['quantity'])
                    ]);
                    
                    // 4. Update Stock (Hardened with pharmacy context)
                    $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ? AND pharmacy_id = ?");
                    $stmt->execute([$item['quantity'], $item['id'], $pharmacy_id]);
                }
                
                log_activity($pdo, $_SESSION['user_id'], 'PLACE_CART_ORDER', 'sales', $sale_id, null, "Cart order $invoice_no placed", $pharmacy_id);
            }
            
            $_SESSION['cart'] = []; // Clear cart
            $pdo->commit();
            redirect('orders.php?success=orders_placed');
            
        } elseif ($action === 'single') {
            // Legacy single item order logic (for safety)
            $medicine_id = $_POST['medicine_id'];
            $pharmacy_id = $_POST['pharmacy_id'];
            $quantity = (int)$_POST['quantity'];
            
            $stmt = $pdo->prepare("SELECT name, price, quantity FROM medicines WHERE id = ? AND pharmacy_id = ?");
            $stmt->execute([$medicine_id, $pharmacy_id]);
            $med = $stmt->fetch();
            
            if (!$med || $med['quantity'] < $quantity) {
                throw new Exception("Medicine not available or insufficient stock.");
            }
            
            $total_amount = $med['price'] * $quantity;
            $invoice_no = generate_invoice_no();
            
            $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, user_id, customer_name, total_amount, grand_total, payment_method, payment_status, order_status, pharmacy_id) VALUES (?, ?, ?, ?, ?, 'cash', 'pending', 'pending', ?)");
            $stmt->execute([$invoice_no, $_SESSION['user_id'], $_SESSION['full_name'], $total_amount, $total_amount, $pharmacy_id]);
            $sale_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$sale_id, $medicine_id, $quantity, $med['price'], $total_amount]);
            
            $stmt = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ? AND pharmacy_id = ?");
            $stmt->execute([$quantity, $medicine_id, $pharmacy_id]);
            
            log_activity($pdo, $_SESSION['user_id'], 'PLACE_ORDER', 'sales', $sale_id, null, "Order $invoice_no placed", $pharmacy_id);
            
            $pdo->commit();
            redirect('orders.php?success=order_placed');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        redirect('inventory.php?error=' . urlencode($e->getMessage()));
    }
} else {
    redirect('inventory.php');
}
