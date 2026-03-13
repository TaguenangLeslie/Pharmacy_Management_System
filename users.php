<?php
/**
 * User Management Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$page_title = 'User Management';
$active_page = 'users';

$message = '';
$error = '';

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $full_name = sanitize_input($_POST['full_name']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $pharmacy_id = !empty($_POST['pharmacy_id']) ? $_POST['pharmacy_id'] : $_SESSION['pharmacy_id'];

        if ($_POST['action'] === 'add') {
            $password = $_POST['password'];
            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, is_active, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_pass, $role, $full_name, $is_active, $pharmacy_id]);
                
                log_activity($pdo, $_SESSION['user_id'], 'ADD_USER', 'users', $pdo->lastInsertId());
                $message = "User added successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Username or email already exists.";
                } else {
                    $error = "Error adding user: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['user_id'];
            $pharma_id = $_SESSION['pharmacy_id'];
            try {
                // Determine if we restricted to own pharmacy (staff) or all (platform admin)
                $where_clause = $pharma_id ? " WHERE id=? AND pharmacy_id=?" : " WHERE id=?";
                $params_base = $pharma_id ? [$id, $pharma_id] : [$id];

                if (!empty($_POST['password'])) {
                    $hashed_pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password_hash=?, role=?, full_name=?, is_active=?, pharmacy_id=? " . $where_clause);
                    $stmt->execute([$username, $email, $hashed_pass, $role, $full_name, $is_active, $pharmacy_id, ...$params_base]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=?, full_name=?, is_active=?, pharmacy_id=? " . $where_clause);
                    $stmt->execute([$username, $email, $role, $full_name, $is_active, $pharmacy_id, ...$params_base]);
                }
                
                log_activity($pdo, $_SESSION['user_id'], 'UPDATE_USER', 'users', $id);
                $message = "User updated successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Username or email already exists.";
                } else {
                    $error = "Error updating user: " . $e->getMessage();
                }
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pharma_id = $_SESSION['pharmacy_id'];
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            $where_clause = $pharma_id ? " WHERE id=? AND pharmacy_id=?" : " WHERE id=?";
            $stmt = $pdo->prepare("DELETE FROM users " . $where_clause);
            $stmt->execute($pharma_id ? [$id, $pharma_id] : [$id]);
            log_activity($pdo, $_SESSION['user_id'], 'DELETE_USER', 'users', $id);
            $message = "User deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
}

try {
    $pharmacy_id = $_SESSION['pharmacy_id'];
    if ($pharmacy_id) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.*, p.name as pharmacy_name 
            FROM users u 
            LEFT JOIN pharmacies p ON u.pharmacy_id = p.id 
            LEFT JOIN prescriptions rx ON u.id = rx.user_id AND rx.pharmacy_id = ?
            WHERE u.pharmacy_id = ? 
               OR (u.role = 'customer' AND rx.id IS NOT NULL)
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$pharmacy_id, $pharmacy_id]);
    } else {
        $stmt = $pdo->query("SELECT u.*, p.name as pharmacy_name FROM users u LEFT JOIN pharmacies p ON u.pharmacy_id = p.id ORDER BY p.name ASC, u.created_at DESC");
    }
    $users = $stmt->fetchAll();
    
    // Fetch all pharmacies for dropdown
    $stmt = $pdo->query("SELECT id, name FROM pharmacies ORDER BY name ASC");
    $all_pharmacies = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $all_pharmacies = [];
    $error = "Database error: " . $e->getMessage();
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">System Users</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fas fa-plus me-1"></i> Add New User
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

<?php
// Group users if Platform Admin
$grouped_users = [];
$my_pharma = $_SESSION['pharmacy_id'] ?? null;
if (!$my_pharma) {
    foreach ($users as $u) {
        $ph_key = $u['pharmacy_name'] ?: 'Platform Administration';
        $grouped_users[$ph_key][] = $u;
    }
}
?>

<?php if (!$my_pharma): ?>
<div class="accordion border-0 shadow-sm rounded-4 overflow-hidden" id="usersAccordion">
    <?php if (empty($grouped_users)): ?>
        <div class="card border-0 p-5 text-center text-muted">No users found in the system.</div>
    <?php else: 
        $uidx = 0;
        foreach ($grouped_users as $ph_name => $items): 
            $uidx++;
            $uacc_id = "userCollapse" . $uidx;
    ?>
    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header">
            <button class="accordion-button <?php echo ($uidx > 1) ? 'collapsed' : ''; ?> fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $uacc_id; ?>">
                <i class="fas fa-hospital me-2 text-primary"></i> <?php echo $ph_name; ?> 
                <span class="badge bg-light text-dark border ms-2"><?php echo count($items); ?> Users</span>
            </button>
        </h2>
        <div id="<?php echo $uacc_id; ?>" class="accordion-collapse collapse <?php echo ($uidx == 1) ? 'show' : ''; ?>" data-bs-parent="#usersAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $u['username']; ?></div>
                                            <div class="small text-muted"><?php echo $u['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo ucfirst($u['role']); ?></span></td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info edit-user" data-json='<?php echo json_encode($u); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
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
                        <th class="ps-4">User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No users found.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo $u['username']; ?></div>
                                    <div class="small text-muted"><?php echo $u['email']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?php echo ucfirst($u['role']); ?></span></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info edit-user" data-json='<?php echo json_encode($u); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
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

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title" id="userModalLabel">User Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="user_id" id="user_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" id="user-fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" id="user-username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" id="user-email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" id="user-password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role</label>
                            <select name="role" id="user-role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="pharmacist">Pharmacist</option>
                                <option value="cashier">Cashier</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        <?php 
                        // Safety Valve: Master 'admin' always sees the selector
                        // Regular Platform Admins (!my_pharma) also see the selector
                        if (!$my_pharma || $_SESSION['username'] === 'admin'): 
                        ?>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Assign Pharmacy</label>
                            <select name="pharmacy_id" id="user-pharma" class="form-select">
                                <option value="">Platform (None)</option>
                                <?php foreach ($all_pharmacies as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="user-active" checked>
                                <label class="form-check-label fw-bold" for="user-active">Is Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="save-btn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = '
<script>
    $(document).ready(function() {
        $(".edit-user").click(function() {
            const data = $(this).data("json");
            $("#form-action").val("edit");
            $("#user_id").val(data.id);
            $("#user-fullname").val(data.full_name);
            $("#user-username").val(data.username);
            $("#user-email").val(data.email);
            $("#user-role").val(data.role);
            $("#user-active").prop("checked", data.is_active == 1);
            $("#user-password").attr("required", false);
            
            $("#userModalLabel").text("Edit User: " + data.username);
            $("#save-btn").text("Update User");
            if ($("#user-pharma").length) {
                $("#user-pharma").val(data.pharmacy_id);
            }
            $("#userModal").modal("show");
        });
        
        $("#userModal").on("hidden.bs.modal", function () {
            $("#form-action").val("add");
            $("#userModalLabel").text("User Information");
            $("#save-btn").text("Save User");
            $("#user-password").attr("required", true);
            $("form")[0].reset();
        });
    });
</script>
';
include 'includes/templates/footer.php'; 
?>
