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

// Handle Add/Edit Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, category, supplier_id, price, cost_price, quantity, unit, reorder_level, expiry_date, barcode, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description]);
                
                log_activity($pdo, $_SESSION['user_id'], 'ADD_MEDICINE', 'medicines', $pdo->lastInsertId());
                $message = "Medicine added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding medicine: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['medicine_id'];
            try {
                $stmt = $pdo->prepare("UPDATE medicines SET name=?, generic_name=?, category=?, supplier_id=?, price=?, cost_price=?, quantity=?, unit=?, reorder_level=?, expiry_date=?, barcode=?, description=? WHERE id=?");
                $stmt->execute([$name, $generic_name, $category, $supplier_id, $price, $cost_price, $quantity, $unit, $reorder_level, $expiry_date, $barcode, $description, $id]);
                
                log_activity($pdo, $_SESSION['user_id'], 'UPDATE_MEDICINE', 'medicines', $id);
                $message = "Medicine updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating medicine: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_MEDICINE', 'medicines', $id);
        $message = "Medicine deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting medicine: " . $e->getMessage();
    }
}

// Fetch Medicines
try {
    $stmt = $pdo->query("SELECT m.*, s.name as supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id ORDER BY m.name ASC");
    $medicines = $stmt->fetchAll();
    
    // Fetch Suppliers for dropdown
    $stmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC");
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
    $suppliers = [];
    $error = "Database tables might not be ready. Please run <a href='install.php'>install.php</a> first.";
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2"><?php echo __('medicine_inventory'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medicineModal">
            <i class="fas fa-plus me-1"></i> <?php echo __('add_medicine'); ?>
        </button>
    </div>
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

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Medicine Name</th>
                        <th>Generic Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Expiry</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No medicines found in inventory.</td></tr>
                    <?php else: foreach ($medicines as $med): 
                        $stock_class = ($med['quantity'] <= $med['reorder_level']) ? 'badge bg-danger' : 'badge bg-success';
                        $expiry_date = strtotime($med['expiry_date']);
                        $expiry_class = ($expiry_date < time()) ? 'text-danger fw-bold' : '';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?php echo $med['name']; ?></div>
                            <div class="small text-muted"><?php echo $med['barcode']; ?></div>
                        </td>
                        <td><?php echo $med['generic_name']; ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo $med['category']; ?></span></td>
                        <td><?php echo $med['supplier_name'] ?: 'None'; ?></td>
                        <td><?php echo format_currency($med['price']); ?></td>
                        <td><span class="<?php echo $stock_class; ?>"><?php echo $med['quantity']; ?> <?php echo $med['unit']; ?></span></td>
                        <td class="<?php echo $expiry_class; ?>"><?php echo date('M d, Y', $expiry_date); ?></td>
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-outline-info me-1 edit-med" data-json='<?php echo json_encode($med); ?>'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="inventory.php?delete=<?php echo $med['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this medicine?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

<?php 
$extra_js = '
<script>
    $(document).ready(function() {
        $(".edit-med").click(function() {
            const data = $(this).data("json");
            $("#form-action").val("edit");
            $("#medicine_id").val(data.id);
            $("#med-name").val(data.name);
            $("#med-generic").val(data.generic_name);
            $("#med-cat").val(data.category);
            $("#med-sup").val(data.supplier_id);
            $("#med-barcode").val(data.barcode);
            $("#med-cost").val(data.cost_price);
            $("#med-price").val(data.price);
            $("#med-qty").val(data.quantity);
            $("#med-unit").val(data.unit);
            $("#med-reorder").val(data.reorder_level);
            $("#med-expiry").val(data.expiry_date);
            $("#med-desc").val(data.description);
            
            $("#medicineModalLabel").text("Edit Medicine: " + data.name);
            $("#save-btn").text("Update Medicine");
            $("#medicineModal").modal("show");
        });
        
        // Modal reset on close
        $("#medicineModal").on("hidden.bs.modal", function () {
            $("#form-action").val("add");
            $("#medicineModalLabel").text("Medicine Information");
            $("#save-btn").text("Save Medicine");
            $("form")[0].reset();
        });
    });
</script>
';
include 'includes/templates/footer.php'; 
?>
