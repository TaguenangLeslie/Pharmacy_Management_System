<?php
/**
 * POS Page - Point of Sale
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = __('pos');
$active_page = 'pos';

// Handle Pharmacy Context for Platform Admin
$pharmacy_id = $_SESSION['pharmacy_id'];
$is_platform_admin = (has_role('admin') && !$pharmacy_id);

if ($is_platform_admin && isset($_GET['pharmacy_id'])) {
    $pharmacy_id = $_GET['pharmacy_id'];
}

// Fetch all medicines with stock for the POS (Filtered by Pharmacy)
try {
    $all_medicines = [];
    if ($pharmacy_id) {
        $stmt = $pdo->prepare("SELECT id, name, generic_name, price, quantity, unit FROM medicines WHERE quantity > 0 AND pharmacy_id = ? ORDER BY name ASC");
        $stmt->execute([$pharmacy_id]);
        $all_medicines = $stmt->fetchAll();
    }
    
    // Fetch customers (Limited to pharmacy or shared)
    if ($pharmacy_id) {
        $stmt = $pdo->prepare("SELECT id, name FROM customers WHERE pharmacy_id = ? OR pharmacy_id IS NULL ORDER BY name ASC");
        $stmt->execute([$pharmacy_id]);
        $customers = $stmt->fetchAll();
    } else {
        $customers = [];
    }

    // Fetch list of pharmacies for System Admin selector
    $pharmacies_list = [];
    if ($is_platform_admin) {
        $pharmacies_list = $pdo->query("SELECT id, name FROM pharmacies WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    }
} catch (PDOException $e) {
    $all_medicines = [];
    $customers = [];
    $pharmacies_list = [];
}

include 'includes/templates/header.php';
?>

<?php if ($is_platform_admin): ?>
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-1 fw-bold"><i class="fas fa-user-shield text-primary me-2"></i> System Admin POS Console</h5>
                <p class="text-muted small mb-0">Select a pharmacy to start processing local sales on their behalf.</p>
            </div>
            <div class="col-md-6">
                <form action="pos.php" method="GET" class="d-flex gap-2">
                    <select name="pharmacy_id" class="form-select shadow-none" onchange="this.form.submit()">
                        <option value="">-- Choose Pharmacy --</option>
                        <?php foreach ($pharmacies_list as $ph): ?>
                        <option value="<?php echo $ph['id']; ?>" <?php echo ($pharmacy_id == $ph['id']) ? 'selected' : ''; ?>>
                            <?php echo $ph['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$pharmacy_id && $is_platform_admin): ?>
    <div class="text-center py-5">
        <i class="fas fa-hospital fa-4x text-muted mb-3"></i>
        <h3>Please select a pharmacy</h3>
        <p>To use the Point of Sale, you must first select which branch you are representing.</p>
    </div>
<?php else: ?>

<div class="row">
    <!-- POS Left Side: Product Search & List -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Select Medicines</h5>
                <div class="input-group w-50">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                    <input type="text" id="pos-search" class="form-control bg-light border-0" placeholder="Search medicine name...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="ps-4">Medicine</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th class="text-center pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody id="pos-product-list">
                            <?php if (empty($all_medicines)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No medicines available in stock.</td></tr>
                            <?php else: foreach ($all_medicines as $med): ?>
                            <tr class="product-row" data-name="<?php echo strtolower($med['name']); ?>">
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $med['name']; ?></div>
                                    <div class="small text-muted"><?php echo $med['generic_name']; ?></div>
                                </td>
                                <td><?php echo format_currency($med['price']); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo $med['quantity']; ?> <?php echo $med['unit']; ?></span></td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-primary rounded-pill px-3 add-to-cart" 
                                            data-id="<?php echo $med['id']; ?>" 
                                            data-name="<?php echo $med['name']; ?>" 
                                            data-price="<?php echo $med['price']; ?>"
                                            data-stock="<?php echo $med['quantity']; ?>">
                                        <i class="fas fa-plus me-1"></i> Add
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- POS Right Side: Shopping Cart -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm sticky-top" style="top: 2rem;">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Current Order</h5>
            </div>
            <div class="card-body p-0">
                <div id="cart-container" class="px-3" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-borderless align-middle">
                        <tbody id="cart-items">
                            <!-- Items added via JS -->
                            <tr class="cart-empty-msg"><td colspan="3" class="text-center py-5 text-muted">Cart is empty</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <hr class="mx-3 mt-0">
                
                <div class="p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="cart-subtotal" class="fw-bold">0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (0%)</span>
                        <span id="cart-tax" class="fw-bold">0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="mb-0">Total</h4>
                        <h4 id="cart-total" class="mb-0 text-primary fw-bold">0 FCFA</h4>
                    </div>
                    
                    <form id="checkout-form" action="process_sale.php" method="POST">
                        <input type="hidden" name="cart_data" id="cart-data-input">
                        <input type="hidden" name="pharmacy_id" value="<?php echo $pharmacy_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Customer</label>
                            <select name="customer_id" id="pos-customer-id" class="form-select shadow-none">
                                <option value="">Walk-in Customer</option>
                                <?php foreach($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Customer Display Name</label>
                            <input type="text" name="customer_name" id="pos-customer-name" class="form-control" placeholder="Defaults to selected customer">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile">Mobile Money</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" id="checkout-btn" disabled>
                            <i class="fas fa-receipt me-1"></i> Confirm Sale
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php 
$extra_js = '
<script>
    let cart = [];
    
    function updateCartUI() {
        const cartTable = $("#cart-items");
        cartTable.empty();
        
        if (cart.length === 0) {
            cartTable.append("<tr class=\"cart-empty-msg\"><td colspan=\"3\" class=\"text-center py-5 text-muted\">Cart is empty</td></tr>");
            $("#checkout-btn").prop("disabled", true);
            $("#cart-subtotal, #cart-total").text("0 FCFA");
            return;
        }
        
        $("#checkout-btn").prop("disabled", false);
        let subtotal = 0;
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            cartTable.append(`
                <tr>
                    <td>
                        <div class="fw-bold">${item.name}</div>
                        <div class="small text-muted">${Math.round(item.price).toLocaleString()} FCFA x ${item.quantity}</div>
                    </td>
                    <td class="text-end fw-bold">${Math.round(itemTotal).toLocaleString()} FCFA</td>
                    <td class="text-end">
                        <button class="btn btn-sm text-danger remove-item" data-index="${index}"><i class="fas fa-times"></i></button>
                    </td>
                </tr>
            `);
        });
        
        $("#cart-subtotal, #cart-total").text(Math.round(subtotal).toLocaleString() + " FCFA");
        $("#cart-data-input").val(JSON.stringify(cart));
    }
    
    $(document).ready(function() {
        // Add to Cart
        $(".add-to-cart").click(function() {
            const id = $(this).data("id");
            const name = $(this).data("name");
            const price = $(this).data("price");
            const stock = $(this).data("stock");
            
            const existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.quantity < stock) {
                    existing.quantity++;
                } else {
                    Swal.fire("Out of stock!", "Cannot add more than available quantity.", "warning");
                }
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            updateCartUI();
        });
        
        // Remove from Cart
        $(document).on("click", ".remove-item", function() {
            const index = $(this).data("index");
            cart.splice(index, 1);
            updateCartUI();
        });
        
        // Search Filter
        $("#pos-search").on("keyup", function() {
            const value = $(this).val().toLowerCase();
            $(".product-row").filter(function() {
                $(this).toggle($(this).data("name").indexOf(value) > -1)
            });
        });

        // Customer Selection Auto-fill
        $("#pos-customer-id").on("change", function() {
            const selectedText = $(this).find("option:selected").text();
            if ($(this).val()) {
                $("#pos-customer-name").val(selectedText);
            } else {
                $("#pos-customer-name").val("");
            }
        });
        
        // Form Submit Validation
        $("#checkout-form").submit(function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                Swal.fire("Error", "Your cart is empty!", "error");
            }
        });
    });
</script>
';
include 'includes/templates/footer.php'; 
?>
