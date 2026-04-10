<?php
/**
 * Place Order Logic (Customers)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

// Allow staff and customers to place orders (Staff can buy on behalf or for testing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role(['customer', 'admin', 'pharmacist', 'cashier'])) {
    $action = $_POST['action'] ?? 'single';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'cart_checkout' && !empty($_SESSION['cart'])) {
            $target_pharmacy_id = $_POST['pharmacy_id'] ?? null;
            
            // Group items by pharmacy
            $grouped = [];
            foreach ($_SESSION['cart'] as $i => $item) {
                $item['original_index'] = $i;
                $grouped[$item['pharmacy_id']][] = $item;
            }
            
            if ($target_pharmacy_id && !isset($grouped[$target_pharmacy_id])) {
                throw new Exception("No items found for the selected pharmacy.");
            }

            // Determine which pharmacies to process
            $to_process = $target_pharmacy_id ? [$target_pharmacy_id => $grouped[$target_pharmacy_id]] : $grouped;
            
            foreach ($to_process as $pharmacy_id => $items) {
                $total_amount = 0;
                foreach ($items as $item) $total_amount += ($item['price'] * $item['quantity']);
                
                $invoice_no = generate_invoice_no();
                
                // 1. Create Sale record for this pharmacy (Status: Pending for Pickup)
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
                
                $processed_indices = [];
                foreach ($items as $item) {
                    // 2. Add Sale Item 
                    $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $sale_id, 
                        $item['id'], 
                        $item['quantity'], 
                        $item['price'], 
                        ($item['price'] * $item['quantity'])
                    ]);
                    
                    // 3. Finalize Reservation
                    $stmt = $pdo->prepare("DELETE FROM cart_reservations WHERE session_id = ? AND medicine_id = ? AND pharmacy_id = ? LIMIT 1");
                    $stmt->execute([session_id(), $item['id'], $pharmacy_id]);
                    
                    $processed_indices[] = $item['original_index'];
                }

                // 4. Remove processed items from cart
                foreach ($processed_indices as $idx) {
                    unset($_SESSION['cart'][$idx]);
                }
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index for safety
                
                log_activity($pdo, $_SESSION['user_id'], 'PLACE_CART_ORDER', 'sales', $sale_id, null, "Cart order $invoice_no placed for pickup", $pharmacy_id);
            }
            
            $pdo->commit();
            redirect('order_success.php');
            
        }
 elseif ($action === 'single') {
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
            redirect('order_success.php');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        redirect('inventory.php?error=' . urlencode($e->getMessage()));
    }
} else {
    redirect('inventory.php');
}
