<?php
/**
 * Order Success Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = 'Order Placed';
$active_page = 'orders';

$last_pharma = $_SESSION['last_pharmacy_id'] ?? null;
$return_url = $last_pharma ? "inventory.php?pharma=$last_pharma" : "explore.php";

include 'includes/templates/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg rounded-4 p-5">
            <div class="mb-4">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                    <i class="fas fa-check fa-4x"></i>
                </div>
            </div>
            
            <h2 class="fw-bold mb-3">Order Placed Successfully!</h2>
            <p class="text-muted mb-5">Your order has been sent to the pharmacy. You can track its status in the "My Orders" section.</p>
            
            <div class="d-grid gap-3">
                <a href="<?php echo $return_url; ?>" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                    <i class="fas fa-shopping-basket me-2"></i> Continue Shopping
                </a>
                <a href="orders.php" class="btn btn-outline-secondary btn-lg rounded-pill">
                    <i class="fas fa-list-alt me-2"></i> View My Orders
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
