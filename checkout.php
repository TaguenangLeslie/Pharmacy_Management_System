<?php
/**
 * Checkout Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if (empty($_SESSION['cart'])) {
    redirect('inventory.php');
}

$page_title = 'Checkout';
$active_page = 'cart';
include 'includes/templates/header.php';

// Group items by pharmacy for multiple invoices
$grouped_cart = [];
foreach ($_SESSION['cart'] as $item) {
    if (!isset($grouped_cart[$item['pharmacy_id']])) {
        $grouped_cart[$item['pharmacy_id']] = [
            'name' => $item['pharmacy_name'],
            'items' => [],
            'total' => 0
        ];
    }
    $grouped_cart[$item['pharmacy_id']]['items'][] = $item;
    $grouped_cart[$item['pharmacy_id']]['total'] += ($item['price'] * $item['quantity']);
}

$grand_total = 0;
foreach($grouped_cart as $p) $grand_total += $p['total'];
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12 text-center">
        <h1 class="h2">Checkout</h1>
        <p class="text-muted">You are about to place orders with <?php echo count($grouped_cart); ?> pharmacies.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form action="place_order.php" method="POST">
            <input type="hidden" name="action" value="cart_checkout">
            
            <?php foreach ($grouped_cart as $pharma_id => $data): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-light py-3 ps-4 border-0">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-hospital me-2"></i> <?php echo $data['name']; ?></h5>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="small text-muted">
                            <tr>
                                <th class="ps-4">Item</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th class="text-end pe-4">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                            <tr>
                                <td class="ps-4"><?php echo $item['name']; ?></td>
                                <td><?php echo format_currency($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td class="text-end pe-4 fw-bold"><?php echo format_currency($item['price'] * $item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="ps-4 fw-bold">Subtotal for <?php echo $data['name']; ?></td>
                                <td class="text-end pe-4 fw-bold text-primary"><?php echo format_currency($data['total']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="card border-0 shadow rounded-4 p-4 mb-5">
                <h5 class="fw-bold mb-4">Finalize Order</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash on Delivery</option>
                            <option value="mobile">Mobile Money</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Delivery Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional notes for delivery">
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block small">Grand Total</span>
                        <span class="h3 fw-bold text-primary mb-0"><?php echo format_currency($grand_total); ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                        Place All Orders <i class="fas fa-check-circle ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
