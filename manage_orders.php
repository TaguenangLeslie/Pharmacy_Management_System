<?php
/**
 * Global Order Management (Admin Only)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

// Strictly for Platform Admin only (no pharmacy_id)
if (!isset($_SESSION['pharmacy_id']) || $_SESSION['pharmacy_id'] !== null) {
    if ($_SESSION['pharmacy_id']) {
        redirect('dashboard.php');
    }
}

$page_title = 'Global Order Management';
$active_page = 'manage_orders';

$message = '';
$error = '';

// Handle Status Update
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['update_status'];
    try {
        $stmt = $pdo->prepare("UPDATE sales SET order_status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = "Order status updated to " . ucfirst($status);
        log_activity($pdo, $_SESSION['user_id'], 'UPDATE_ORDER_STATUS', 'sales', $id, null, $status);
    } catch (PDOException $e) {
        $error = "Error updating order: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_ORDER', 'sales', $id);
        $message = "Order deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Fetch all orders with pharmacy and user info
try {
    $stmt = $pdo->query("SELECT s.*, p.name as pharmacy_name, u.full_name as customer_name 
                           FROM sales s 
                           JOIN pharmacies p ON s.pharmacy_id = p.id 
                           LEFT JOIN users u ON s.user_id = u.id 
                           ORDER BY p.name ASC, s.sale_date DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error = "Database error: " . $e->getMessage();
}

include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12">
        <h1 class="h2">Global Order Management</h1>
        <p class="text-muted">Monitor and control every transaction across all registered pharmacies.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php
// Group orders by pharmacy
$grouped_orders = [];
foreach ($orders as $o) {
    $grouped_orders[$o['pharmacy_name']][] = $o;
}
?>

<div class="accordion border-0 shadow-sm rounded-4 overflow-hidden" id="ordersAccordion">
    <?php if (empty($grouped_orders)): ?>
        <div class="card border-0 p-5 text-center text-muted">No orders found in the system.</div>
    <?php else: 
        $idx = 0;
        foreach ($grouped_orders as $pharma_name => $p_orders): 
            $idx++;
            $accordion_id = "collapse" . $idx;
    ?>
    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header">
            <button class="accordion-button <?php echo ($idx > 1) ? 'collapsed' : ''; ?> fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $accordion_id; ?>">
                <i class="fas fa-hospital me-2 text-primary"></i> <?php echo $pharma_name; ?> 
                <span class="badge bg-light text-dark border ms-2"><?php echo count($p_orders); ?> Orders</span>
            </button>
        </h2>
        <div id="<?php echo $accordion_id; ?>" class="accordion-collapse collapse <?php echo ($idx == 1) ? 'show' : ''; ?>" data-bs-parent="#ordersAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($p_orders as $o): 
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
                                <td>
                                    <div class="fw-bold"><?php echo $o['customer_name'] ?: ($o['customer_name_manual'] ?? 'Walk-in Customer'); ?></div>
                                    <div class="small text-muted"><?php echo $o['customer_phone'] ?? ''; ?></div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($o['sale_date'])); ?></td>
                                <td class="fw-bold"><?php echo format_currency($o['grand_total']); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <span class="badge <?php echo $status_class; ?> rounded-pill px-3 dropdown-toggle cursor-pointer" data-bs-toggle="dropdown">
                                            <?php echo ucfirst($o['order_status']); ?>
                                        </span>
                                        <ul class="dropdown-menu border-0 shadow">
                                            <li><a class="dropdown-item" href="manage_orders.php?update_status=pending&id=<?php echo $o['id']; ?>">Pending</a></li>
                                            <li><a class="dropdown-item" href="manage_orders.php?update_status=processing&id=<?php echo $o['id']; ?>">Processing</a></li>
                                            <li><a class="dropdown-item" href="manage_orders.php?update_status=completed&id=<?php echo $o['id']; ?>">Completed</a></li>
                                            <li><a class="dropdown-item" href="manage_orders.php?update_status=cancelled&id=<?php echo $o['id']; ?>">Cancelled</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="receipt.php?id=<?php echo $o['id']; ?>" target="_blank" class="btn btn-sm btn-light border-0" title="View Invoice">
                                            <i class="fas fa-file-invoice text-muted"></i>
                                        </a>
                                        <a href="manage_orders.php?delete=<?php echo $o['id']; ?>" class="btn btn-sm btn-light border-0" onclick="return confirm('Are you sure you want to delete this order record? This cannot be undone.')" title="Delete">
                                            <i class="fas fa-trash-alt text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<style>
.cursor-pointer { cursor: pointer; }
</style>

<?php include 'includes/templates/footer.php'; ?>
