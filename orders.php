<?php
/**
 * Customer Orders & Tracking
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

if (!has_role('customer')) {
    redirect('dashboard.php');
}

$page_title = 'My Orders';
$active_page = 'orders';

$success = $_GET['success'] ?? '';

// Fetch customer orders
try {
    $stmt = $pdo->prepare("SELECT s.*, p.name as pharmacy_name 
                           FROM sales s 
                           JOIN pharmacies p ON s.pharmacy_id = p.id 
                           WHERE s.user_id = ? 
                           ORDER BY s.sale_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12">
        <h1 class="h2">My Orders</h1>
        <p class="text-muted">Track your recent medicine purchases and their status.</p>
    </div>
</div>

<?php if ($success === 'order_placed'): ?>
<div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
    <i class="fas fa-check-circle me-2"></i> Order placed successfully! The pharmacy will process it shortly.
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Invoice #</th>
                    <th>Pharmacy</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">You haven't placed any orders yet. <a href="explore.php">Start exploring!</a></td></tr>
                <?php else: foreach ($orders as $o): 
                    $status_class = 'bg-secondary';
                    switch($o['order_status']) {
                        case 'pending': $status_class = 'bg-warning text-dark'; break;
                        case 'processing': $status_class = 'bg-info text-white'; break;
                        case 'completed': $status_class = 'bg-success text-white'; break;
                        case 'cancelled': $status_class = 'bg-danger text-white'; break;
                    }
                ?>
                <tr>
                    <td class="ps-4 fw-bold"><?php echo $o['invoice_no']; ?></td>
                    <td><span class="text-primary fw-bold"><?php echo $o['pharmacy_name']; ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($o['sale_date'])); ?></td>
                    <td class="fw-bold"><?php echo format_currency($o['grand_total']); ?></td>
                    <td><span class="badge <?php echo $status_class; ?> rounded-pill px-3"><?php echo ucfirst($o['order_status']); ?></span></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light rounded-pill px-3" onclick="alert('Tracking details coming soon!')">
                            <i class="fas fa-truck me-1"></i> Track
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
