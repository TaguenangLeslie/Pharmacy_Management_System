<?php
/**
 * Receipt View Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if (!isset($_GET['id'])) {
    redirect('pos.php');
}

$sale_id = $_GET['id'];

try {
    // Fetch sale details with pharmacy info
    $stmt = $pdo->prepare("SELECT s.*, u.full_name as cashier_name, p.name as pharmacy_name, p.address as pharmacy_address, p.phone as pharmacy_phone 
                           FROM sales s 
                           LEFT JOIN users u ON s.user_id = u.id 
                           JOIN pharmacies p ON s.pharmacy_id = p.id
                           WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        die("Sale not found.");
    }

    // Security Check: Only staff of that pharmacy, the customer themselves, or platform admin can view
    if (!has_role('admin') && $_SESSION['pharmacy_id'] != $sale['pharmacy_id'] && $_SESSION['user_id'] != $sale['user_id']) {
        die("Access Denied: You do not have permission to view this receipt.");
    }

    // Fetch sale items
    $stmt = $pdo->prepare("SELECT si.*, m.name as medicine_name FROM sale_items si JOIN medicines m ON si.medicine_id = m.id WHERE si.sale_id = ?");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = 'Receipt #' . $sale['invoice_no'];
include 'includes/templates/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5">
                <div class="text-center mb-5">
                    <h3 class="text-primary mb-1"><i class="fas fa-hand-holding-medical"></i> <?php echo $sale['pharmacy_name']; ?></h3>
                    <p class="text-muted small mb-0"><?php echo $sale['pharmacy_address']; ?></p>
                    <p class="text-muted small mb-0">Tel: <?php echo $sale['pharmacy_phone']; ?></p>
                </div>
                
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted small">Customer</div>
                        <div class="fw-bold"><?php echo $sale['customer_name']; ?></div>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <div class="text-muted small">Invoice #</div>
                        <div class="fw-bold"><?php echo $sale['invoice_no']; ?></div>
                        <div class="text-muted small"><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-borderless align-middle">
                        <thead class="border-bottom">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr class="border-bottom-dashed">
                                <td class="py-3"><?php echo $item['medicine_name']; ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo format_currency($item['unit_price']); ?></td>
                                <td class="text-end fw-bold"><?php echo format_currency($item['total_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span class="fw-bold"><?php echo format_currency($sale['total_amount']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (0%)</span>
                            <span class="fw-bold"><?php echo format_currency($sale['tax']); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-0">
                            <h4 class="mb-0">Total</h4>
                            <h4 class="mb-0 text-primary fw-bold"><?php echo format_currency($sale['grand_total']); ?></h4>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-top text-center text-muted small">
                    <p class="mb-1">Payment Method: <strong><?php echo strtoupper($sale['payment_method']); ?></strong></p>
                    <p class="mb-0">Served by: <strong><?php echo $sale['cashier_name']; ?></strong></p>
                    <p class="mt-4 fw-bold">Thank you for your business!</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary me-2">
                <i class="fas fa-print me-1"></i> Print Receipt
            </button>
            <a href="pos.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Sale
            </a>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, #sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .card { box-shadow: none !important; }
}
.border-bottom-dashed { border-bottom: 1px dashed #dee2e6; }
</style>

<?php include 'includes/templates/footer.php'; ?>
