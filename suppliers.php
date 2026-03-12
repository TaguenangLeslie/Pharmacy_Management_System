<?php
/**
 * Supplier Management Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$page_title = 'Supplier Management';
$active_page = 'suppliers';

$message = '';
$error = '';

// Handle Add/Edit Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = sanitize_input($_POST['name']);
        $contact_person = sanitize_input($_POST['contact_person']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $address = sanitize_input($_POST['address']);
        $payment_terms = sanitize_input($_POST['payment_terms']);

        if ($_POST['action'] === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, payment_terms) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $contact_person, $phone, $email, $address, $payment_terms]);
                log_activity($pdo, $_SESSION['user_id'], 'ADD_SUPPLIER', 'suppliers', $pdo->lastInsertId());
                $message = "Supplier added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding supplier: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['supplier_id'];
            try {
                $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, payment_terms=? WHERE id=?");
                $stmt->execute([$name, $contact_person, $phone, $email, $address, $payment_terms, $id]);
                log_activity($pdo, $_SESSION['user_id'], 'UPDATE_SUPPLIER', 'suppliers', $id);
                $message = "Supplier updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating supplier: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_SUPPLIER', 'suppliers', $id);
        $message = "Supplier deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting supplier: " . $e->getMessage();
    }
}

// Fetch Suppliers
try {
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
    $error = "Database error: " . $e->getMessage();
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">Suppliers</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">
            <i class="fas fa-plus me-1"></i> Add New Supplier
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
                        <th class="ps-4">Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Payment Terms</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No suppliers found.</td></tr>
                    <?php else: foreach ($suppliers as $sup): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?php echo $sup['name']; ?></td>
                        <td><?php echo $sup['contact_person']; ?></td>
                        <td><?php echo $sup['phone']; ?></td>
                        <td><?php echo $sup['email']; ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo $sup['payment_terms']; ?></span></td>
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-outline-info me-1 edit-sup" data-json='<?php echo json_encode($sup); ?>'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="suppliers.php?delete=<?php echo $sup['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this supplier?')">
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

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title" id="supplierModalLabel">Supplier Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="suppliers.php" method="POST">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="supplier_id" id="supplier_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company Name</label>
                        <input type="text" name="name" id="sup-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Person</label>
                        <input type="text" name="contact_person" id="sup-contact" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" name="phone" id="sup-phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="sup-email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" id="sup-addr" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Terms</label>
                        <input type="text" name="payment_terms" id="sup-terms" class="form-control" placeholder="e.g. Net 30">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-modal="hide">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="save-btn">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = '
<script>
    $(document).ready(function() {
        $(".edit-sup").click(function() {
            const data = $(this).data("json");
            $("#form-action").val("edit");
            $("#supplier_id").val(data.id);
            $("#sup-name").val(data.name);
            $("#sup-contact").val(data.contact_person);
            $("#sup-phone").val(data.phone);
            $("#sup-email").val(data.email);
            $("#sup-addr").val(data.address);
            $("#sup-terms").val(data.payment_terms);
            
            $("#supplierModalLabel").text("Edit Supplier: " + data.name);
            $("#save-btn").text("Update Supplier");
            $("#supplierModal").modal("show");
        });
        
        $("#supplierModal").on("hidden.bs.modal", function () {
            $("#form-action").val("add");
            $("#supplierModalLabel").text("Supplier Information");
            $("#save-btn").text("Save Supplier");
            $("form")[0].reset();
        });
    });
</script>
';
include 'includes/templates/footer.php'; 
?>
