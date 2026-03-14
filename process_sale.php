<?php
/**
 * Process Sale Transaction
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $cart_data = json_decode($_POST['cart_data'], true);
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $customer_name = !empty($_POST['customer_name']) ? sanitize_input($_POST['customer_name']) : (!empty($_POST['customer_search']) ? sanitize_input($_POST['customer_search']) : ($customer_id ? 'Known Customer' : 'Walk-in Customer'));
    $payment_method = $_POST['payment_method'];
    
    if (empty($cart_data)) {
        redirect('pos.php?error=empty_cart');
    }

    try {
        $pdo->beginTransaction();

        $invoice_no = generate_invoice_no();
        $user_id = $_SESSION['user_id'];
        $ph_id = $_SESSION['pharmacy_id'];

        // Platform Admin Override
        if (!$ph_id && has_role('admin') && !empty($_POST['pharmacy_id'])) {
            $ph_id = $_POST['pharmacy_id'];
        }

        if (!$ph_id) {
            throw new Exception("Target pharmacy is not defined.");
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cart_data as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $discount = 0; 
        $grand_total = $subtotal - $discount;

        // 1. Insert Sales Record
        $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, user_id, customer_name, total_amount, discount, grand_total, payment_method, payment_status, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $invoice_no,
            $customer_id,
            $user_id,
            $customer_name,
            $subtotal,
            $discount,
            $grand_total,
            $payment_method,
            'paid',
            $ph_id
        ]);
        $sale_id = $pdo->lastInsertId();

        // Update Loyalty Points if customer is known
        if ($customer_id) {
            $points = floor($grand_total / 10);
            $stmt_loyalty = $pdo->prepare("UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt_loyalty->execute([$points, $customer_id]);
        }

        // 2. Insert into sale_items and update stock
        $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt_update_stock = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ? AND pharmacy_id = ?");

        foreach ($cart_data as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $stmt_item->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $item_total]);
            
            // 3. Finalize Reservation (Deduction already happened in POS UI via AJAX)
            $stmt_del_res = $pdo->prepare("DELETE FROM cart_reservations WHERE session_id = ? AND medicine_id = ? AND pharmacy_id = ? LIMIT 1");
            $stmt_del_res->execute([session_id(), $item['id'], $ph_id]);
        }

        $pdo->commit();
        
        log_activity($pdo, $user_id, 'PROCESS_SALE', 'sales', $sale_id);
        
        // Redirect to receipt view
        redirect("receipt.php?id=" . $sale_id);

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Transaction failed: " . $e->getMessage());
    }
} else {
    redirect('pos.php');
}
