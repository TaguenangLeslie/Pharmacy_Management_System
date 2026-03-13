<?php
/**
 * Customer Management Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = 'Customer Management';
$active_page = 'customers';

$message = '';
$error = '';

// Handle Add/Edit Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $address = sanitize_input($_POST['address']);
    $pharma_id = $_SESSION['pharmacy_id'];
    
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            $id = $_POST['customer_id'];
            $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=? AND (pharmacy_id = ? OR pharmacy_id IS NULL)");
            $stmt->execute([$name, $phone, $email, $address, $id, $pharma_id]);
            log_activity($pdo, $_SESSION['user_id'], 'UPDATE_CUSTOMER', 'customers', $id);
            $message = "Customer updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, pharmacy_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $address, $pharma_id]);
            log_activity($pdo, $_SESSION['user_id'], 'ADD_CUSTOMER', 'customers', $pdo->lastInsertId());
            $message = "Customer added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pharma_id = $_SESSION['pharmacy_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ? AND (pharmacy_id = ? OR pharmacy_id IS NULL)");
        $stmt->execute([$id, $pharma_id]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_CUSTOMER', 'customers', $id);
        $message = "Customer deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error: This customer cannot be deleted because they have sale records.";
    }
}

// Fetch Customers
try {
    $pharma_id = $_SESSION['pharmacy_id'] ?? null;
    if ($pharma_id) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE pharmacy_id = ? OR pharmacy_id IS NULL ORDER BY name ASC");
        $stmt->execute([$pharma_id]);
    } else {
        // Platform Admin - Fetch all
        $stmt = $pdo->query("SELECT c.*, p.name as pharmacy_name 
                             FROM customers c 
                             LEFT JOIN pharmacies p ON c.pharmacy_id = p.id 
                             ORDER BY p.name ASC, c.name ASC");
    }
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">Customer Database</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
            <i class="fas fa-plus me-1"></i> Add New Customer
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Group customers if Platform Admin
$grouped_customers = [];
if (!$pharma_id) {
    foreach ($customers as $c) {
        $ph_key = $c['pharmacy_name'] ?: 'System-wide';
        $grouped_customers[$ph_key][] = $c;
    }
}
?>

<?php if (!$pharma_id): ?>
<div class="accordion border-0 shadow-sm rounded-4 overflow-hidden" id="customersAccordion">
    <?php if (empty($grouped_customers)): ?>
        <div class="card border-0 p-5 text-center text-muted">No customers found in the system.</div>
    <?php else: 
        $cidx = 0;
        foreach ($grouped_customers as $ph_name => $items): 
            $cidx++;
            $cacc_id = "custCollapse" . $cidx;
    ?>
    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header">
            <button class="accordion-button <?php echo ($cidx > 1) ? 'collapsed' : ''; ?> fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $cacc_id; ?>">
                <i class="fas fa-hospital me-2 text-primary"></i> <?php echo $ph_name; ?> 
                <span class="badge bg-light text-dark border ms-2"><?php echo count($items); ?> Records</span>
            </button>
        </h2>
        <div id="<?php echo $cacc_id; ?>" class="accordion-collapse collapse <?php echo ($cidx == 1) ? 'show' : ''; ?>" data-bs-parent="#customersAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Customer Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $c['name']; ?></div>
                                    <div class="small text-muted">Joined <?php echo date('M Y', strtotime($c['created_at'])); ?></div>
                                </td>
                                <td><a href="tel:<?php echo $c['phone']; ?>" class="text-decoration-none"><?php echo $c['phone']; ?></a></td>
                                <td><?php echo $c['email']; ?></td>
                                <td><span class="text-truncate d-inline-block" style="max-width: 200px;"><?php echo $c['address']; ?></span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info edit-customer" data-json='<?php echo json_encode($c); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="customers.php?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this customer?')">
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
                        <th class="ps-4">Customer Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No customers found.</td></tr>
                    <?php else: foreach ($customers as $c): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?php echo $c['name']; ?></div>
                            <div class="small text-muted">Joined <?php echo date('M Y', strtotime($c['created_at'])); ?></div>
                        </td>
                        <td><a href="tel:<?php echo $c['phone']; ?>" class="text-decoration-none"><?php echo $c['phone']; ?></a></td>
                        <td><?php echo $c['email']; ?></td>
                        <td><span class="text-truncate d-inline-block" style="max-width: 200px;"><?php echo $c['address']; ?></span></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info edit-customer" data-json='<?php echo json_encode($c); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="customers.php?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this customer?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title" id="modalTitle">Add Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="customers.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="customer_id" id="customerId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" id="cName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" id="cPhone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" name="email" id="cEmail" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" id="cAddress" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="saveBtn">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = "
<script>
$(document).ready(function() {
    $('.edit-customer').click(function() {
        const data = $(this).data('json');
        $('#formAction').val('edit');
        $('#customerId').val(data.id);
        $('#cName').val(data.name);
        $('#cPhone').val(data.phone);
        $('#cEmail').val(data.email);
        $('#cAddress').val(data.address);
        
        $('#modalTitle').text('Edit Customer');
        $('#saveBtn').text('Update Customer');
        $('#customerModal').modal('show');
    });
    
    $('#customerModal').on('hidden.bs.modal', function () {
        $('#formAction').val('add');
        $('#modalTitle').text('Add Customer');
        $('#saveBtn').text('Save Customer');
        $('form')[0].reset();
    });
});
</script>
";
include 'includes/templates/footer.php'; 
?>
