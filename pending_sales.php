<?php
/**
 * Pending Sales Dashboard (Cashier Only)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

// Restricted to Cashiers and Branch Admins
if (!has_role('cashier') && !has_role('admin')) {
    redirect('dashboard.php');
}

$pharmacy_id = $_SESSION['pharmacy_id'];
if (!$pharmacy_id && !has_role('admin')) {
    die("Error: No pharmacy assigned to your account.");
}

$page_title = 'Pending Sales';
$active_page = 'pending_sales';

$message = '';
$error = '';

// Handle Cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $pdo->beginTransaction();
        
        // 1. Fetch sale items to restore stock
        $stmt_items = $pdo->prepare("SELECT medicine_id, quantity FROM sale_items WHERE sale_id = ?");
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll();
        
        foreach ($items as $item) {
            $stmt_restore = $pdo->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?");
            $stmt_restore->execute([$item['quantity'], $item['medicine_id']]);
        }
        
        // 2. Update sale status
        $stmt = $pdo->prepare("UPDATE sales SET order_status = 'cancelled', payment_status = 'cancelled' WHERE id = ? AND pharmacy_id = ? AND order_status = 'pending'");
        $stmt->execute([$id, $pharmacy_id]);
        
        $pdo->commit();
        $message = "Order cancelled and stock restored.";
        log_activity($pdo, $_SESSION['user_id'], 'CANCEL_PENDING_SALE', 'sales', $id);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error cancelling order: " . $e->getMessage();
    }
}

// Fetch Pending Sales
try {
    if ($pharmacy_id) {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as pharmacist_name 
                               FROM sales s 
                               LEFT JOIN users u ON s.pharmacist_id = u.id 
                               WHERE s.pharmacy_id = ? AND s.order_status = 'pending' 
                               ORDER BY s.sale_date DESC");
        $stmt->execute([$pharmacy_id]);
    } else { // Platform Admin view
        $stmt = $pdo->query("SELECT s.*, u.full_name as pharmacist_name, ph.name as pharmacy_name 
                              FROM sales s 
                              LEFT JOIN users u ON s.pharmacist_id = u.id 
                              JOIN pharmacies ph ON s.pharmacy_id = ph.id
                              WHERE s.order_status = 'pending' 
                              ORDER BY s.sale_date DESC");
    }
    $pending_sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $pending_sales = [];
    $error = "Database error: " . $e->getMessage();
}

include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12">
        <h1 class="h2">Pending Sales</h1>
        <p class="text-muted">Process payments for orders sent by pharmacists.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($pending_sales)): ?>
        <div class="col-12 text-center py-5">
            <div class="card border-0 shadow-sm p-5 rounded-4">
                <i class="fas fa-clock fa-4x text-muted mb-3 opacity-25"></i>
                <h4 class="text-muted">No pending sales at the moment.</h4>
                <p>When a pharmacist sends an order to the cashier, it will appear here.</p>
            </div>
        </div>
    <?php else: foreach ($pending_sales as $sale): ?>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-warning text-dark mb-2">Pending Payment</span>
                            <h5 class="fw-bold mb-0">Invoice #<?php echo $sale['invoice_no']; ?></h5>
                            <?php if (!$pharmacy_id): ?>
                                <small class="text-primary fw-bold"><i class="fas fa-hospital me-1"></i> <?php echo $sale['pharmacy_name']; ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <h4 class="text-primary fw-bold mb-0"><?php echo format_currency($sale['grand_total']); ?></h4>
                            <small class="text-muted"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-2x text-light"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold text-dark small">CUSTOMER</div>
                            <div class="text-muted"><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-dark small text-uppercase">Pharmacist</div>
                            <div class="text-muted"><?php echo $sale['pharmacist_name'] ?: 'Unknown'; ?></div>
                        </div>
                    </div>
                    
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <div class="fw-bold small mb-2 text-uppercase opacity-75">Items Summary</div>
                        <?php
                        // Fetch a few items for preview
                        $stmt_items = $pdo->prepare("SELECT si.*, m.name 
                                                   FROM sale_items si 
                                                   JOIN medicines m ON si.medicine_id = m.id 
                                                   WHERE si.sale_id = ? 
                                                   LIMIT 3");
                        $stmt_items->execute([$sale['id']]);
                        $items = $stmt_items->fetchAll();
                        foreach ($items as $item):
                        ?>
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?php echo $item['name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span><?php echo format_currency($item['total_price']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($items) >= 3): ?>
                        <div class="text-center mt-2 small text-muted">...and more</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="finalize_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-primary py-2 rounded-3 shadow-sm">
                            <i class="fas fa-cash-register me-2"></i> Process Payment
                        </a>
                        <a href="pending_sales.php?cancel=1&id=<?php echo $sale['id']; ?>" class="btn btn-outline-danger btn-sm border-0 py-2" onclick="return confirm('Cancel this order and return stock?')">
                            <i class="fas fa-trash-alt me-2"></i> Cancel Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php include 'includes/templates/footer.php'; ?>
