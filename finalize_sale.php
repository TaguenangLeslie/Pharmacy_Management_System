<?php
/**
 * Finalize Sale (Cashier Only)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if (!has_role('cashier') && !has_role('admin')) {
    redirect('dashboard.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('pending_sales.php');
}

$sale_id = $_GET['id'];
$pharmacy_id = $_SESSION['pharmacy_id'];

// Fetch Sale details
try {
    if ($pharmacy_id) {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as pharmacist_name 
                               FROM sales s 
                               LEFT JOIN users u ON s.pharmacist_id = u.id 
                               WHERE s.id = ? AND s.pharmacy_id = ? AND s.order_status = 'pending'");
        $stmt->execute([$sale_id, $pharmacy_id]);
    } else {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as pharmacist_name 
                               FROM sales s 
                               LEFT JOIN users u ON s.pharmacist_id = u.id 
                               WHERE s.id = ? AND s.order_status = 'pending'");
        $stmt->execute([$sale_id]);
    }
    $sale = $stmt->fetch();
    
    if (!$sale) {
        redirect('pending_sales.php?error=sale_not_found');
    }
    
    $stmt_items = $pdo->prepare("SELECT si.*, m.name 
                               FROM sale_items si 
                               JOIN medicines m ON si.medicine_id = m.id 
                               WHERE si.sale_id = ?");
    $stmt_items->execute([$sale_id]);
    $items = $stmt_items->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle Finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $amount_paid = $_POST['amount_paid'] ?? $sale['grand_total'];
    
    try {
        $pdo->beginTransaction();
        
        // Update Sale Record
        $stmt = $pdo->prepare("UPDATE sales SET 
                                payment_method = ?, 
                                payment_status = 'paid', 
                                order_status = 'completed', 
                                processed_by = ?,
                                sale_date = CURRENT_TIMESTAMP
                                WHERE id = ?");
        $stmt->execute([$payment_method, $_SESSION['user_id'], $sale_id]);
        
        // Update Loyalty Points if customer is known
        if ($sale['customer_id']) {
            $points = floor($sale['grand_total'] / 10);
            $stmt_loyalty = $pdo->prepare("UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt_loyalty->execute([$points, $sale['customer_id']]);
        }
        
        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], 'FINALIZE_SALE', 'sales', $sale_id);
        
        // Redirect to receipt
        redirect("receipt.php?id=" . $sale_id . "&success=completed");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Transaction failed: " . $e->getMessage();
    }
}

$page_title = 'Finalize Sale';
include 'includes/templates/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="pending_sales.php">Pending Sales</a></li>
        <li class="breadcrumb-item active">Finalize Invoice #<?php echo $sale['invoice_no']; ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Order Summary</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Medicine</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr class="border-bottom">
                                <td class="ps-3 py-3">
                                    <div class="fw-bold text-dark"><?php echo $item['name']; ?></div>
                                </td>
                                <td class="text-center font-monospace"><?php echo $item['quantity']; ?></td>
                                <td class="text-end text-muted"><?php echo format_currency($item['unit_price']); ?></td>
                                <td class="text-end fw-bold pe-3"><?php echo format_currency($item['total_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end pt-4">Subtotal:</td>
                                <td class="text-end fw-bold pt-4 pe-3"><?php echo format_currency($sale['total_amount']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-success">Discount:</td>
                                <td class="text-end text-success fw-bold pe-3">-<?php echo format_currency($sale['discount']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">
                                    <h4 class="mb-0">Grand Total:</h4>
                                </td>
                                <td class="text-end pe-3">
                                    <h4 class="mb-0 text-primary fw-bold"><?php echo format_currency($sale['grand_total']); ?></h4>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm rounded-4 no-print">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-circle fa-3x text-light"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="fw-bold mb-1">Customer: <?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></h6>
                        <p class="text-muted small mb-0">Order initiated by Pharmacist: <strong><?php echo $sale['pharmacist_name'] ?: 'Unknown'; ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-4 mb-4 checkout-sticky">
            <h5 class="fw-bold mb-3">Accept Payment</h5>
            <form action="finalize_sale.php?id=<?php echo $sale_id; ?>" method="POST" id="payment-form">
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase opacity-75">Payment Method</label>
                    <div class="d-flex gap-2 justify-content-center">
                        <input type="radio" class="btn-check" name="payment_method" id="pay-cash" value="cash" checked autocomplete="off">
                        <label class="btn btn-outline-primary px-3 py-2" for="pay-cash">
                            <i class="fas fa-money-bill-wave d-block mb-1"></i> Cash
                        </label>

                        <input type="radio" class="btn-check" name="payment_method" id="pay-card" value="card" autocomplete="off">
                        <label class="btn btn-outline-primary px-3 py-2" for="pay-card">
                            <i class="fas fa-credit-card d-block mb-1"></i> Card
                        </label>

                        <input type="radio" class="btn-check" name="payment_method" id="pay-momo" value="mobile" autocomplete="off">
                        <label class="btn btn-outline-primary px-3 py-2" for="pay-momo">
                            <i class="fas fa-mobile-alt d-block mb-1"></i> Mobile
                        </label>
                    </div>
                </div>
                
                <div class="mb-4 text-start">
                    <label class="form-label small fw-bold">Amount Received (Optional)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control form-control-lg border-0 bg-light" id="amount-received" placeholder="0.00" onkeyup="calculateChange()">
                        <span class="input-group-text border-0 bg-light">FCFA</span>
                    </div>
                </div>
                
                <div class="bg-light p-3 rounded-3 mb-4 text-start" id="change-display" style="display:none;">
                    <div class="d-flex justify-content-between">
                        <span>Change Due:</span>
                        <h4 class="mb-0 text-success fw-bold" id="change-amount">0 FCFA</h4>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-3 shadow-sm mb-3">
                    <i class="fas fa-check-double me-2"></i> Complete Sale & Print
                </button>
                
                <a href="pending_sales.php" class="text-decoration-none small text-muted">
                    <i class="fas fa-arrow-left me-1"></i> Back to Pending
                </a>
            </form>
        </div>
    </div>
</div>

<script>
function calculateChange() {
    const total = <?php echo $sale['grand_total']; ?>;
    const received = parseFloat(document.getElementById('amount-received').value);
    const display = document.getElementById('change-display');
    const amount = document.getElementById('change-amount');
    
    if (received > total) {
        display.style.display = 'block';
        amount.textContent = (received - total).toLocaleString() + ' FCFA';
    } else {
        display.style.display = 'none';
    }
}
</script>

<style>
.checkout-sticky {
    position: sticky;
    top: 2rem;
}
.btn-check:checked + .btn-outline-primary {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}
</style>

<?php include 'includes/templates/footer.php'; ?>
