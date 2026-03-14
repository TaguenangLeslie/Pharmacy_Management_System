<?php
/**
 * Inventory Management Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = __('inventory');
$active_page = 'inventory';

// Read flash messages from session (set before any redirects)
$message = $_SESSION['flash_message'] ?? '';
$error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

// Handle Add/Edit Medicine (Restricted to Staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role(['admin', 'pharmacist', 'cashier'])) {
    if (isset($_POST['action'])) {
        $name = sanitize_input($_POST['name']);
        $generic_name = sanitize_input($_POST['generic_name']);
        $category = sanitize_input($_POST['category']);
        $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        $price = $_POST['price'];
        $cost_price = $_POST['cost_price'];
        $quantity = $_POST['quantity'];
        $unit = sanitize_input($_POST['unit']);
        $reorder_level = $_POST['reorder_level'];
        $expiry_date = $_POST['expiry_date'];
        $barcode = sanitize_input($_POST['barcode']);
        $description = sanitize_input($_POST['description']);

        if ($_POST['action'] === 'add') {
            try {
                // Determine pharmacy_id for global admin if not set
                $target_pharmacy = $_SESSION['pharmacy_id'] ?? ($_POST['target_pharmacy_id'] ?? null);
                if (!$target_pharmacy) throw new Exception("No pharmacy selected for this medicine.");

                $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, category, supplier_id, price, cost_price, quantity, unit, reorder_level, expiry_date, barcode, description, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description, $target_pharmacy]);
                
                log_activity($pdo, $_SESSION['user_id'], 'ADD_MEDICINE', 'medicines', $pdo->lastInsertId());
                $_SESSION['flash_message'] = "Medicine added successfully!";
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Error adding medicine: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['medicine_id'];
            try {
                // If admin without session pharmacy_id, just check the ID (or we can verify they have access to that pharma)
                if (has_role('admin') && !$_SESSION['pharmacy_id']) {
                    $stmt = $pdo->prepare("UPDATE medicines SET name=?, generic_name=?, category=?, supplier_id=?, price=?, cost_price=?, quantity=?, unit=?, reorder_level=?, expiry_date=?, barcode=?, description=? WHERE id=?");
                    $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE medicines SET name=?, generic_name=?, category=?, supplier_id=?, price=?, cost_price=?, quantity=?, unit=?, reorder_level=?, expiry_date=?, barcode=?, description=? WHERE id=? AND pharmacy_id=?");
                    $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description, $id, $_SESSION['pharmacy_id']]);
                }
                
                log_activity($pdo, $_SESSION['user_id'], 'UPDATE_MEDICINE', 'medicines', $id);
                $_SESSION['flash_message'] = "Medicine updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error updating medicine: " . $e->getMessage();
            }
        }
        header("Location: inventory.php");
        exit;
    }
}

// Handle Delete (Restricted to Staff)
if (isset($_GET['delete']) && has_role(['admin', 'pharmacist', 'cashier'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ? AND pharmacy_id = ?");
        $stmt->execute([$id, $_SESSION['pharmacy_id']]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_MEDICINE', 'medicines', $id);
        $_SESSION['flash_message'] = "Medicine deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Error deleting medicine: " . $e->getMessage();
    }
    header("Location: inventory.php");
    exit;
}

// Fetch Data
$pharmacy_id = $_SESSION['pharmacy_id'] ?? null;
$medicines = [];
$active_pharmacies = [];

try {
    if (has_role('customer')) {
        $selected_pharma = $_GET['pharma'] ?? null;
        if ($selected_pharma) {
            $stmt = $pdo->prepare("SELECT m.*, p.name as pharmacy_name FROM medicines m JOIN pharmacies p ON m.pharmacy_id = p.id WHERE m.pharmacy_id = ? AND p.status = 'active' ORDER BY m.name ASC");
            $stmt->execute([$selected_pharma]);
            $medicines = $stmt->fetchAll();
        } else {
            $active_pharmacies = $pdo->query("SELECT * FROM pharmacies WHERE status = 'active' ORDER BY name ASC")->fetchAll();
        }
    } elseif (has_role('admin') && !$pharmacy_id) {
        $stmt = $pdo->query("SELECT m.*, p.name as pharmacy_name, s.name as supplier_name 
                             FROM medicines m 
                             JOIN pharmacies p ON m.pharmacy_id = p.id 
                             LEFT JOIN suppliers s ON m.supplier_id = s.id 
                             ORDER BY p.name ASC, m.name ASC");
        $medicines = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT m.*, s.name as supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id WHERE m.pharmacy_id = ? ORDER BY m.name ASC");
        $stmt->execute([$pharmacy_id]);
        $medicines = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Fetch Suppliers for Modal
$suppliers = [];
if ($pharmacy_id) {
    $stmt = $pdo->prepare("SELECT id, name FROM suppliers WHERE pharmacy_id = ? ORDER BY name ASC");
    $stmt->execute([$pharmacy_id]);
    $suppliers = $stmt->fetchAll();
}

include 'includes/templates/header.php';
?>

<!-- Messages -->
<?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php if (has_role('customer') && !isset($_GET['pharma'])): ?>
    <!-- Customer Pharmacy Selection -->
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <i class="fas fa-hospital fa-3x text-primary mb-3"></i>
        <h3 class="fw-bold">Browse Drugs by Pharmacy</h3>
        <p class="text-muted mb-5">Please select a local pharmacy to view their available medicine inventory.</p>
        <div class="row g-4 justify-content-center">
            <?php foreach ($active_pharmacies as $ph): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift p-4">
                        <h5 class="fw-bold mb-2"><?php echo $ph['name']; ?></h5>
                        <p class="small text-muted mb-4"><?php echo $ph['address']; ?></p>
                        <a href="inventory.php?pharma=<?php echo $ph['id']; ?>" class="btn btn-primary rounded-pill w-100">View Inventory</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Main Inventory View -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Medicine Inventory</h5>
            <?php if (!has_role('customer')): ?>
                <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#medicineModal">
                    <i class="fas fa-plus me-1"></i> Add Medicine
                </button>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Medicine</th>
                        <th>Category</th>
                        <th><?php echo has_role('customer') ? 'Pharmacy' : 'Supplier'; ?></th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No medicines found.</td></tr>
                    <?php else: foreach ($medicines as $m): ?>
                        <tr data-id="<?php echo $m['id']; ?>">
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo $m['name']; ?></div>
                                <div class="small text-muted"><?php echo $m['generic_name']; ?></div>
                            </td>
                            <td><?php echo $m['category']; ?></td>
                            <td><?php echo has_role('customer') ? ($m['pharmacy_name'] ?? 'N/A') : ($m['supplier_name'] ?? 'N/A'); ?></td>
                            <td class="fw-bold"><?php echo format_currency($m['price']); ?></td>
                            <td><?php echo $m['quantity']; ?> <?php echo $m['unit']; ?></td>
                            <td class="text-end pe-4">
                                <?php if (has_role('customer')): ?>
                                    <button class="btn btn-primary btn-sm rounded-pill add-to-cart-btn" 
                                            data-id="<?php echo $m['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?>" 
                                            data-price="<?php echo $m['price']; ?>" 
                                            data-pharma-id="<?php echo $m['pharmacy_id']; ?>"
                                            data-pharma-name="<?php echo htmlspecialchars($m['pharmacy_name'] ?? 'Local Pharmacy', ENT_QUOTES); ?>">Add to Cart</button>
                                <?php else: ?>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info edit-medicine" 
                                                data-json="<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="inventory.php?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modals & Scripts -->
<div class="modal fade" id="medicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title" id="modalTitle">Add New Medicine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="medicineForm" method="POST">
                <input type="hidden" name="action" id="med_action" value="add">
                <input type="hidden" name="medicine_id" id="medicine_id">
                <input type="hidden" name="target_pharmacy_id" value="<?php echo $_GET['pharma'] ?? $_SESSION['pharmacy_id'] ?? ''; ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Medicine Name</label>
                            <input type="text" name="name" id="med_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Generic Name</label>
                            <input type="text" name="generic_name" id="med_generic" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category</label>
                            <input type="text" name="category" id="med_cat" class="form-control" placeholder="e.g. Antibiotics">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Supplier</label>
                            <select name="supplier_id" id="med_supplier" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Unit</label>
                            <input type="text" name="unit" id="med_unit" class="form-control" placeholder="e.g. Tablet, Bottle" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Selling Price</label>
                            <input type="number" step="0.01" name="price" id="med_price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cost Price</label>
                            <input type="number" step="0.01" name="cost_price" id="med_cost" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" name="quantity" id="med_qty" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reorder Level</label>
                            <input type="number" name="reorder_level" id="med_reorder" class="form-control" value="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expiry Date</label>
                            <input type="date" name="expiry_date" id="med_expiry" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Barcode</label>
                            <input type="text" name="barcode" id="med_barcode" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="med_desc" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Medicine</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow text-center">
            <div class="modal-header pink-gradient text-white border-0 text-center">
                <h5 class="modal-title w-100 ps-3 text-white">Add to Cart</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="orderForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" id="cart-med-id">
                <input type="hidden" name="name" id="cart-med-name-val">
                <input type="hidden" name="price" id="cart-med-price-val">
                <input type="hidden" name="pharmacy_id" id="cart-pharma-id">
                <input type="hidden" name="pharmacy_name" id="cart-pharma-name">
                <div class="modal-body p-4 pt-2">
                    <div class="bg-light rounded-4 p-3 mb-3">
                        <i class="fas fa-pills fa-2x text-primary mb-2"></i>
                        <h6 id="order-med-name" class="fw-bold mb-0"></h6>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small text-muted">Select Quantity</label>
                        <input type="number" name="quantity" class="form-control form-control-lg rounded-pill px-4" value="1" min="1" required>
                    </div>
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-4">
                        <span class="text-muted small">Estimated Total</span>
                        <span id="order-total" class="h5 fw-bold text-primary mb-0"></span>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm py-3" id="confirm-add-cart-btn">
                        Add to Shopping Cart <i class="fas fa-cart-plus ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (has_role('customer')): ?>
<a href="cart.php" class="btn btn-primary rounded-circle shadow-lg position-fixed d-flex align-items-center justify-content-center" style="bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1000;"><i class="fas fa-shopping-cart fa-lg"></i>    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
        <?php echo is_array($_SESSION['cart'] ?? null) ? count($_SESSION['cart']) : 0; ?>
    </span>
</a>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Use delegated events for better reliability
    $(document).on('click', '.add-to-cart-btn', function() {
        const d = $(this).data();
        console.log("Cart Button Data:", d);
        
        const medId = $(this).attr('data-id');
        const medName = $(this).attr('data-name');
        const medPrice = $(this).attr('data-price');
        const pharmaId = $(this).attr('data-pharma-id');
        const pharmaName = $(this).attr('data-pharma-name');

        $('#cart-med-id').val(medId); 
        $('#cart-med-name-val').val(medName); 
        $('#cart-med-price-val').val(medPrice); 
        $('#cart-pharma-id').val(pharmaId); 
        $('#cart-pharma-name').val(pharmaName);
        
        $('#order-med-name').text(medName); 
        $('#order-total').text(new Intl.NumberFormat().format(medPrice) + ' FCFA');
        $('#orderModal').modal('show');
    });

    $(document).on('click', '.edit-medicine', function() {
        const d = $(this).data('json');
        console.log("Editing Medicine Data:", d);
        if (!d) {
            Swal.fire('Error', 'Could not read medicine data. Try refreshing.', 'error');
            return;
        }
        $('#med_action').val('edit');
        $('#medicine_id').val(d.id);
        $('#med_name').val(d.name);
        $('#med_generic').val(d.generic_name);
        $('#med_cat').val(d.category);
        $('#med_supplier').val(d.supplier_id);
        $('#med_unit').val(d.unit);
        $('#med_price').val(d.price);
        $('#med_cost').val(d.cost_price);
        $('#med_qty').val(d.quantity);
        $('#med_reorder').val(d.reorder_level);
        $('#med_expiry').val(d.expiry_date);
        $('#med_barcode').val(d.barcode);
        $('#med_desc').val(d.description);
        $('#modalTitle').text('Edit Medicine');
        $('#medicineModal').modal('show');
    });

    // Reset modal on close
    $('#medicineModal').on('hidden.bs.modal', function() {
        $('#medicineForm')[0].reset();
        $('#modalTitle').text('Add New Medicine');
        $('#med_action').val('add');
        $('#medicine_id').val('');
    });

    $('#orderForm').submit(function(e) {
        e.preventDefault();
        const btn = $('#confirm-add-cart-btn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reserving...');
        
        const resData = { 
            action: 'reserve', 
            medicine_id: $('#cart-med-id').val(), 
            pharmacy_id: $('#cart-pharma-id').val(), 
            quantity: $('#orderForm input[name="quantity"]').val() 
        };
        console.log("Reservation Request:", resData);

        $.post('ajax_inventory.php', resData, function(res) {
            console.log("Reservation Response:", res);
            if (res.status === 'success') {
                $.post('cart.php', $('#orderForm').serialize(), function(r) {
                    console.log("Cart Response:", r);
                    if (r.status === 'success') {
                        $('#cart-count').text(r.count); 
                        $('#orderModal').modal('hide');
                        Swal.fire({ title: 'Success!', text: 'Added to cart.', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    } else {
                        Swal.fire('Error', r.message || 'Failed to add to cart session.', 'error');
                    }
                }, 'json').fail(function(xhr) {
                    console.error("Cart API Fail:", xhr.responseText);
                    Swal.fire('Error', 'Cart API failed. Check console.', 'error');
                });
            } else { 
                Swal.fire('Stock Error', res.message, 'error'); 
            }
        }, 'json').fail(function(xhr) {
            console.error("Reservation API Fail:", xhr.responseText);
            Swal.fire('Error', 'Stock reservation failed. Check console.', 'error');
        }).always(() => btn.prop('disabled', false).text('Add to Cart'));
    });
});
</script>

<?php include 'includes/templates/footer.php'; ?>
