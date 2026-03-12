<?php
/**
 * Expense Management Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = 'Expense Tracking';
$active_page = 'expenses';

$message = '';
$error = '';

// Handle Add/Edit Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = sanitize_input($_POST['description']);
    $date = $_POST['date'];
    
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            $id = $_POST['expense_id'];
            $stmt = $pdo->prepare("UPDATE expenses SET category=?, amount=?, description=?, date=? WHERE id=?");
            $stmt->execute([$category, $amount, $description, $date, $id]);
            log_activity($pdo, $_SESSION['user_id'], 'UPDATE_EXPENSE', 'expenses', $id);
            $message = "Expense updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO expenses (category, amount, description, date, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category, $amount, $description, $date, $_SESSION['user_id']]);
            log_activity($pdo, $_SESSION['user_id'], 'ADD_EXPENSE', 'expenses', $pdo->lastInsertId());
            $message = "Expense recorded successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, $_SESSION['user_id'], 'DELETE_EXPENSE', 'expenses', $id);
        $message = "Expense deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Expenses
try {
    $stmt = $pdo->query("SELECT e.*, u.full_name as recorder FROM expenses e LEFT JOIN users u ON e.user_id = u.id ORDER BY date DESC");
    $expenses = $stmt->fetchAll();
} catch (PDOException $e) {
    $expenses = [];
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">Expense Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">
            <i class="fas fa-receipt me-1"></i> Record New Expense
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Expenses (This Month)</div>
                <div class="h3 mb-0 fw-bold">
                    <?php 
                    $month_total = 0;
                    $current_month = date('Y-m');
                    foreach($expenses as $e) {
                        if (str_starts_with($e['date'], $current_month)) $month_total += $e['amount'];
                    }
                    echo format_currency($month_total);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Recorder</th>
                        <th>Amount</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No expenses recorded yet.</td></tr>
                    <?php else: foreach ($expenses as $e): ?>
                    <tr>
                        <td class="ps-4"><?php echo date('M d, Y', strtotime($e['date'])); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo ucfirst($e['category']); ?></span></td>
                        <td><?php echo $e['description']; ?></td>
                        <td><small class="text-muted"><?php echo $e['recorder']; ?></small></td>
                        <td class="fw-bold text-danger"><?php echo format_currency($e['amount']); ?></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info edit-expense" data-json='<?php echo json_encode($e); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="expenses.php?delete=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this record?')">
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

<!-- Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title" id="eTitle">Record Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="expenses.php" method="POST">
                <input type="hidden" name="action" id="eAction" value="add">
                <input type="hidden" name="expense_id" id="eId">
                <div class="modal-body p-4">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="date" id="eDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="amount" id="eAmount" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" id="eCategory" class="form-select" required>
                            <option value="rent">Rent</option>
                            <option value="utilities">Utilities</option>
                            <option value="salary">Salary</option>
                            <option value="supplies">Supplies</option>
                            <option value="marketing">Marketing</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" id="eDesc" class="form-control" rows="2" placeholder="What was this for?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="eBtn">Record Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = "
<script>
$(document).ready(function() {
    $('.edit-expense').click(function() {
        const data = $(this).data('json');
        $('#eAction').val('edit');
        $('#eId').val(data.id);
        $('#eDate').val(data.date);
        $('#eAmount').val(data.amount);
        $('#eCategory').val(data.category);
        $('#eDesc').val(data.description);
        
        $('#eTitle').text('Edit Expense');
        $('#eBtn').text('Update Record');
        $('#expenseModal').modal('show');
    });
    
    $('#expenseModal').on('hidden.bs.modal', function () {
        $('#eAction').val('add');
        $('#eTitle').text('Record Expense');
        $('#eBtn').text('Record Expense');
        $('form')[0].reset();
    });
});
</script>
";
include 'includes/templates/footer.php'; 
?>
