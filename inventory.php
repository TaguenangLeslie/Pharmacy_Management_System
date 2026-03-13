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

$message = '';
$error = '';

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
                $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, category, supplier_id, price, cost_price, quantity, unit, reorder_level, expiry_date, barcode, description, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description, $_SESSION['pharmacy_id']]);
                
                log_activity($pdo, $_SESSION['user_id'], 'ADD_MEDICINE', 'medicines', $pdo->lastInsertId());
                $message = "Medicine added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding medicine: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['medicine_id'];
            try {
                $stmt = $pdo->prepare("UPDATE medicines SET name=?, generic_name=?, category=?, supplier_id=?, price=?, cost_price=?, quantity=?, unit=?, reorder_level=?, expiry_date=?, barcode=?, description=? WHERE id=? AND pharmacy_id=?");
                $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description, $id, $_SESSION['pharmacy_id']]);
                
                log_activity($pdo, $_SESSION['user_id'], 'UPDATE_MEDICINE', 'medicines', $id);
                $message = "Medicine updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating medicine: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete (Restricted to Staff)
if (isset($_GET['delete']) && has_role(['admin', 'pharmacist', 'cashier'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ? AND pharmacy_id = ?");
        $stmt->execute([$id, $_SESSION['pharmacy_id']]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_MEDICINE', 'medicines', $id);
        $message = "Medicine deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting medicine: " . $e->getMessage();
    }
}

// Fetch Medicines (Filtered by Pharmacy)
$pharmacy_id = $_SESSION['pharmacy_id'] ?? null;
try {
    if (has_role('customer')) {
        // If customer, show drugs from a selected pharmacy or all active ones
        $selected_pharmacy = $_GET['pharma'] ?? null;
        if ($selected_pharmacy) {
            $stmt = $pdo->prepare("SELECT m.*, p.name as pharmacy_name FROM medicines m JOIN pharmacies p ON m.pharmacy_id = p.id WHERE m.pharmacy_id = ? AND p.status = 'active' ORDER BY m.name ASC");
            $stmt->execute([$selected_pharmacy]);
        } else {
            $stmt = $pdo->query("SELECT m.*, p.name as pharmacy_name FROM medicines m JOIN pharmacies p ON m.pharmacy_id = p.id WHERE p.status = 'active' ORDER BY m.name ASC");
        }
    } elseif (has_role('admin') && !$pharmacy_id) {
        // Platform Admin - Fetch all
        $stmt = $pdo->query("SELECT m.*, p.name as pharmacy_name, s.name as supplier_name 
                             FROM medicines m 
                             JOIN pharmacies p ON m.pharmacy_id = p.id 
                             LEFT JOIN suppliers s ON m.supplier_id = s.id 
                             ORDER BY p.name ASC, m.name ASC");
    } else {
        $stmt = $pdo->prepare("SELECT m.*, s.name as supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id WHERE m.pharmacy_id = ? ORDER BY m.name ASC");
        $stmt->execute([$pharmacy_id]);
    }
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
    $error = "Error fetching medicines: " . $e->getMessage();
}

// Fetch Suppliers (Filtered by Pharmacy)
try {
    $stmt = $pdo->prepare("SELECT id, name FROM suppliers WHERE pharmacy_id = ? ORDER BY name ASC");
    $stmt->execute([$pharmacy_id]);
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
    $error = "Error fetching suppliers: " . $e->getMessage();
}

if ($error) {
    // If there's an error from fetching, it might override previous messages.
    // For now, we'll let it override or append if needed.
    // A more robust solution would be to manage an array of errors.
    if (strpos($error, "Database tables might not be ready") === false) {
        $error = "Database tables might not be ready. Please run <a href='install.php'>install.php</a> first.";
    }
}


include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2"><?php echo __('medicine_inventory'); ?></h1>
    <?php if (has_role(['admin', 'pharmacist', 'cashier'])): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medicineModal">
            <i class="fas fa-plus me-1"></i> <?php echo __('add_medicine'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Group medicines if Platform Admin
$grouped_inventory = [];
if (has_role('admin') && !$pharmacy_id) {
    foreach ($medicines as $m) {
        $grouped_inventory[$m['pharmacy_name']][] = $m;
    }
}
?>

<?php if (has_role('admin') && !$pharmacy_id): ?>
<div class="accordion border-0 shadow-sm rounded-4 overflow-hidden" id="inventoryAccordion">
    <?php if (empty($grouped_inventory)): ?>
        <div class="card border-0 p-5 text-center text-muted">No medicines found in the system.</div>
    <?php else: 
        $idx = 0;
        foreach ($grouped_inventory as $ph_name => $items): 
            $idx++;
            $acc_id = "invCollapse" . $idx;
    ?>
    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header">
            <button class="accordion-button <?php echo ($idx > 1) ? 'collapsed' : ''; ?> fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $acc_id; ?>">
                <i class="fas fa-hospital me-2 text-primary"></i> <?php echo $ph_name; ?> 
                <span class="badge bg-light text-dark border ms-2"><?php echo count($items); ?> Items</span>
            </button>
        </h2>
        <div id="<?php echo $acc_id; ?>" class="accordion-collapse collapse <?php echo ($idx == 1) ? 'show' : ''; ?>" data-bs-parent="#inventoryAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4"><?php echo __('medicine_name'); ?></th>
                                <th><?php echo __('category'); ?></th>
                                <th><?php echo __('supplier'); ?></th>
                                <th><?php echo __('price'); ?></th>
                                <th><?php echo __('quantity'); ?></th>
                                <th class="text-end pe-4"><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $m): ?>
                            <tr class="<?php echo ($m['quantity'] <= ($m['reorder_level'] ?? 0)) ? 'table-warning' : ''; ?>">
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $m['name']; ?></div>
                                    <div class="small text-muted"><?php echo $m['generic_name']; ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo $m['category']; ?></span></td>
                                <td><?php echo $m['supplier_name'] ?? 'N/A'; ?></td>
                                <td class="fw-bold"><?php echo format_currency($m['price']); ?></td>
                                <td>
                                    <?php echo $m['quantity']; ?> <?php echo $m['unit']; ?>
                                    <?php if (isset($m['reorder_level']) && $m['quantity'] <= $m['reorder_level']): ?>
                                        <i class="fas fa-exclamation-triangle text-warning ms-1" title="Low Stock"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info edit-medicine" data-json='<?php echo json_encode($m); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="inventory.php?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this medicine?')">
                                            <i class="fas fa-trash"></i>
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

<?php else: ?>
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4"><?php echo __('medicine_name'); ?></th>
                        <th><?php echo __('category'); ?></th>
                        <?php if (has_role('customer')): ?>
                        <th>Pharmacy</th>
                        <?php else: ?>
                        <th><?php echo __('supplier'); ?></th>
                        <?php endif; ?>
                        <th><?php echo __('price'); ?></th>
                        <th><?php echo __('quantity'); ?></th>
                        <th class="text-end pe-4"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No medicines found.</td></tr>
                    <?php else: foreach ($medicines as $m): ?>
                    <tr class="<?php echo ($m['quantity'] <= ($m['reorder_level'] ?? 0)) ? 'table-warning' : ''; ?>">
                        <td class="ps-4">
                            <div class="fw-bold"><?php echo $m['name']; ?></div>
                            <div class="small text-muted"><?php echo $m['generic_name']; ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?php echo $m['category']; ?></span></td>
                        <?php if (has_role('customer')): ?>
                        <td><span class="text-primary fw-bold"><?php echo $m['pharmacy_name'] ?? 'Unknown'; ?></span></td>
                        <?php else: ?>
                        <td><?php echo $m['supplier_name'] ?? 'N/A'; ?></td>
                        <?php endif; ?>
                        <td class="fw-bold"><?php echo format_currency($m['price']); ?></td>
                        <td>
                            <?php echo $m['quantity']; ?> <?php echo $m['unit']; ?>
                            <?php if (isset($m['reorder_level']) && $m['quantity'] <= $m['reorder_level']): ?>
                                <i class="fas fa-exclamation-triangle text-warning ms-1" title="Low Stock"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if (has_role('customer')): ?>
                                <button class="btn btn-primary btn-sm rounded-pill px-3 add-to-cart-btn" 
                                        data-id="<?php echo $m['id']; ?>" 
                                        data-name="<?php echo $m['name']; ?>" 
                                        data-price="<?php echo $m['price']; ?>" 
                                        data-pharma-id="<?php echo $m['pharmacy_id']; ?>"
                                        data-pharma-name="<?php echo $m['pharmacy_name']; ?>">
                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info edit-medicine" data-json='<?php echo json_encode($m); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="inventory.php?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this medicine?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add/Edit Medicine Modal -->
<div class="modal fade" id="medicineModal" tabindex="-1" aria-labelledby="medicineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title" id="medicineModalLabel">Medicine Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="medicine_id" id="medicine_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Medicine Name</label>
                            <input type="text" name="name" id="med-name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Generic Name</label>
                            <input type="text" name="generic_name" id="med-generic" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" id="med-cat" class="form-select">
                                <option value="Tablet">Tablet</option>
                                <option value="Capsule">Capsule</option>
                                <option value="Syrup">Syrup</option>
                                <option value="Injection">Injection</option>
                                <option value="Ointment">Ointment</option>
                                <option value="Drops">Drops</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Supplier</label>
                            <select name="supplier_id" id="med-sup" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php foreach($suppliers as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo $sup['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Barcode</label>
                            <input type="text" name="barcode" id="med-barcode" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cost Price</label>
                            <input type="number" step="0.01" name="cost_price" id="med-cost" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Sale Price</label>
                            <input type="number" step="0.01" name="price" id="med-price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock Qty</label>
                            <input type="number" name="quantity" id="med-qty" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Unit (e.g. Box)</label>
                            <input type="text" name="unit" id="med-unit" class="form-control" placeholder="Pcs/Box">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reorder Level</label>
                            <input type="number" name="reorder_level" id="med-reorder" class="form-control" value="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Expiry Date</label>
                            <input type="date" name="expiry_date" id="med-expiry" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="med-desc" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="save-btn">Save Medicine</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Order Modal (For Customers) -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title">Add to Cart</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="cart.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" id="cart-med-id">
                <input type="hidden" name="name" id="cart-med-name-val">
                <input type="hidden" name="price" id="cart-med-price-val">
                <input type="hidden" name="pharmacy_id" id="cart-pharma-id">
                <input type="hidden" name="pharmacy_name" id="cart-pharma-name">
                <div class="modal-body p-4">
                    <h6 id="order-med-name" class="fw-bold mb-3"></h6>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="text-muted small">
                        Total: <span id="order-total" class="fw-bold text-primary"></span>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Add to Cart</button>
                </div>
            </form>
        </div>
    </div>
</div>

?>

<?php if (has_role('customer')): ?>
<!-- Floating Cart Icon -->
<a href="cart.php" class="btn btn-primary rounded-circle shadow-lg position-fixed d-flex align-items-center justify-content-center" 
   style="bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1000; transition: transform 0.3s;">
    <i class="fas fa-shopping-cart fa-lg"></i>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
        <?php echo count($_SESSION['cart'] ?? []); ?>
    </span>
</a>
<style>
    .btn-primary:hover { transform: scale(1.1); }
</style>
<?php endif; ?>

<?php 
$extra_js = "
<script>
$(document).ready(function() {
    $('.edit-medicine').click(function() {
        const data = $(this).data('json');
        $('#form-action').val('edit');
        $('#medicine_id').val(data.id);
        $('#med-name').val(data.name);
        $('#med-generic').val(data.generic_name);
        $('#med-cat').val(data.category);
        $('#med-supplier').val(data.supplier_id);
        $('#med-price').val(data.price);
        $('#med-cost').val(data.cost_price);
        $('#med-qty').val(data.quantity);
        $('#med-unit').val(data.unit);
        $('#med-reorder').val(data.reorder_level);
        $('#med-expiry').val(data.expiry_date);
        $('#med-barcode').val(data.barcode);
        $('#med-desc').val(data.description);
        
        $('#medicineModalLabel').text('Edit Medicine: ' + data.name);
        $('#save-btn').text('Update Medicine');
        $('#medicineModal').modal('show');
    });

    $('.add-to-cart-btn').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const price = $(this).data('price');
        const pharma_id = $(this).data('pharma-id');
        const pharma_name = $(this).data('pharma-name');
        
        $('#cart-med-id').val(id);
        $('#cart-med-name-val').val(name);
        $('#cart-med-price-val').val(price);
        $('#cart-pharma-id').val(pharma_id);
        $('#cart-pharma-name').val(pharma_name);
        
        $('#order-med-name').text(name);
        $('#order-total').text(new Intl.NumberFormat().format(price) + ' FCFA');
        
        $('#orderModal').modal('show');
    });
});
</script>
";
include 'includes/templates/footer.php'; 
?>
