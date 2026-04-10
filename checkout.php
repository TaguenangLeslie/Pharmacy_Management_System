<?php
/**
 * Checkout Page (Pharmacy Specific)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if (empty($_SESSION['cart'])) {
    redirect('inventory.php');
}

$target_pharmacy_id = $_GET['pharmacy_id'] ?? null;
if (!$target_pharmacy_id) {
    redirect('cart.php'); // Must checkout per pharmacy now
}

$page_title = 'Review Order';
$active_page = 'cart';
include 'includes/templates/header.php';

// Filter cart for this specific pharmacy
$pharmacy_items = [];
$pharmacy_name = '';
$subtotal = 0;

foreach ($_SESSION['cart'] as $item) {
    if ($item['pharmacy_id'] == $target_pharmacy_id) {
        $pharmacy_items[] = $item;
        $pharmacy_name = $item['pharmacy_name'];
        $subtotal += ($item['price'] * $item['quantity']);
    }
}

if (empty($pharmacy_items)) {
    redirect('cart.php');
}

$grand_total = $subtotal;
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12 text-center">
        <h1 class="h2">Finalize Your Order</h1>
        <p class="text-muted">You are ordering from <strong><?php echo $pharmacy_name; ?></strong>. Please pick up and pay in person.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form action="place_order.php" method="POST">
            <input type="hidden" name="action" value="cart_checkout">
            <input type="hidden" name="pharmacy_id" value="<?php echo $target_pharmacy_id; ?>">
            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 ps-4 border-0">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-hospital me-2"></i> <?php echo $pharmacy_name; ?></h5>
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
                            <?php foreach ($pharmacy_items as $item): ?>
                            <tr>
                                <td class="ps-4 py-3"><?php echo $item['name']; ?></td>
                                <td><?php echo format_currency($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td class="text-end pe-4 fw-bold"><?php echo format_currency($item['price'] * $item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="ps-4 fw-bold">Subtotal</td>
                                <td class="text-end pe-4 fw-bold text-primary h5"><?php echo format_currency($subtotal); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow rounded-4 p-4 mb-5">
                <h5 class="fw-bold mb-4">Order Confirmation</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info border-0 rounded-4">
                            <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> Since we do not offer delivery, please visit the pharmacy to validate and pay for your order.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Payment Method (At Pharmacy)</label>
                        <select name="payment_method" class="form-select border-0 bg-light" required>
                            <option value="cash">Cash at Counter</option>
                            <option value="mobile">Mobile Money (In-Person)</option>
                            <option value="card">Credit/Debit Card (In-Person)</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block small">Due on Pickup</span>
                        <span class="h3 fw-bold text-primary mb-0"><?php echo format_currency($grand_total); ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                        Confirm Order <i class="fas fa-check-circle ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
