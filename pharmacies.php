<?php
/**
 * Pharmacy Management (Platform Admin Only)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

// Ensure only platform admins (no pharmacy_id) can access this
if ($_SESSION['pharmacy_id']) {
    redirect('dashboard.php');
}

$page_title = 'Pharmacy Management';
$active_page = 'pharmacies';

$message = '';
$error = '';

// -------------------------------------------------------
// Handle: ADD New Pharmacy (Admin-created, auto approved)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pharmacy') {
    $name         = sanitize_input($_POST['name'] ?? '');
    $email        = sanitize_input($_POST['email'] ?? '');
    $phone        = sanitize_input($_POST['phone'] ?? '');
    $address      = sanitize_input($_POST['address'] ?? '');
    $license_no   = sanitize_input($_POST['license_no'] ?? '');
    $pharmacy_type = sanitize_input($_POST['pharmacy_type'] ?? 'Retail');

    if (empty($name)) {
        $error = "Pharmacy name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO pharmacies (name, address, phone, email, license_no, pharmacy_type, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $address, $phone, $email, $license_no, $pharmacy_type]);
            $new_id = $pdo->lastInsertId();
            log_activity($pdo, $_SESSION['user_id'], 'ADD_PHARMACY', 'pharmacies', $new_id, null, "Admin created pharmacy: $name");
            $message = "Pharmacy \"$name\" has been created and is now active.";
        } catch (PDOException $e) {
            $error = "Error creating pharmacy: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------
// Handle: Status Update (approve / suspend) + Delete
// -------------------------------------------------------
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    // --- DELETE ---
    if ($action === 'delete') {
        try {
            // Disassociate users from this pharmacy first (set to NULL / customer)
            $pdo->prepare("UPDATE users SET pharmacy_id = NULL, role = 'customer' WHERE pharmacy_id = ?")->execute([$id]);
            // Then delete the pharmacy (cascade will remove medicines, sales, etc.)
            $pdo->prepare("DELETE FROM pharmacies WHERE id = ?")->execute([$id]);
            log_activity($pdo, $_SESSION['user_id'], 'DELETE_PHARMACY', 'pharmacies', $id, null, "Pharmacy ID $id removed from platform");
            $message = "Pharmacy has been permanently removed from the platform. All associated staff accounts have been converted to customers.";
        } catch (PDOException $e) {
            $error = "Error deleting pharmacy: " . $e->getMessage();
        }
    } else {
        // --- APPROVE / SUSPEND ---
        $status = ($action === 'approve') ? 'active' : (($action === 'suspend') ? 'suspended' : 'pending');

        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE pharmacies SET status = ? WHERE id = ?")->execute([$status, $id]);

            // If Approved: auto-upgrade the applicant to admin of their pharmacy
            if ($status === 'active') {
                $stmtOwner = $pdo->prepare("SELECT owner_id, name FROM pharmacies WHERE id = ?");
                $stmtOwner->execute([$id]);
                $ownerData = $stmtOwner->fetch();

                if ($ownerData && $ownerData['owner_id']) {
                    $pdo->prepare("UPDATE users SET role = 'admin', pharmacy_id = ? WHERE id = ?")->execute([$id, $ownerData['owner_id']]);
                    log_activity($pdo, $_SESSION['user_id'], 'UPGRADE_OWNER', 'users', $ownerData['owner_id'], null, "Auto-upgraded to admin for " . $ownerData['name']);
                    $message = "Pharmacy approved! The applicant's account has been automatically upgraded to Branch Admin.";
                } else {
                    $message = "Pharmacy status updated to " . ucfirst($status) . ".";
                }
            } else {
                $message = "Pharmacy status updated to " . ucfirst($status) . ".";
            }

            $pdo->commit();
            log_activity($pdo, $_SESSION['user_id'], 'UPDATE_PHARMACY_STATUS', 'pharmacies', $id, null, "Status changed to $status");
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating status: " . $e->getMessage();
        }
    }
}

// Fetch all pharmacies
try {
    $stmt = $pdo->query("SELECT p.*, u.username as owner_name, u.full_name as owner_full_name_user FROM pharmacies p LEFT JOIN users u ON p.owner_id = u.id ORDER BY p.status ASC, p.name ASC");
    $pharmacies = $stmt->fetchAll();
} catch (PDOException $e) {
    $pharmacies = [];
}

$modals_html = "";
include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col">
        <h1 class="h2">Pharmacy Management</h1>
        <p class="text-muted mb-0">Review, create, and manage all pharmacies on the platform.</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPharmacyModal">
            <i class="fas fa-plus me-2"></i> Add Pharmacy
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Pharmacy Details</th>
                    <th>Business &amp; Legal</th>
                    <th>Owner / Applicant</th>
                    <th>Documents</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pharmacies)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">No pharmacies registered yet.</td></tr>
                <?php else: foreach ($pharmacies as $p):
                    $badge = match($p['status']) {
                        'active'    => 'bg-success',
                        'pending'   => 'bg-warning text-dark',
                        'suspended' => 'bg-danger',
                        default     => 'bg-secondary'
                    };
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($p['address'] ?? 'N/A'); ?></div>
                        <div class="small text-muted mt-1">Joined: <?php echo date('M d, Y', strtotime($p['created_at'])); ?></div>
                    </td>
                    <td>
                        <div class="fw-bold small"><?php echo $p['pharmacy_type'] ?? 'N/A'; ?></div>
                        <div class="small text-muted">Lic: <?php echo $p['license_no'] ?? 'N/A'; ?></div>
                        <div class="small text-muted">Reg: <?php echo $p['business_reg_no'] ?? 'N/A'; ?></div>
                    </td>
                    <td>
                        <?php if ($p['owner_id']): ?>
                            <div class="fw-bold small"><?php echo htmlspecialchars($p['owner_full_name_user'] ?? $p['owner_name'] ?? 'N/A'); ?></div>
                            <div class="small text-muted">@<?php echo htmlspecialchars($p['owner_name'] ?? ''); ?></div>
                        <?php else: ?>
                            <span class="text-muted small">Admin-created</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['owner_id_doc'] || $p['pharmacy_license_doc']): ?>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill" data-bs-toggle="modal" data-bs-target="#docsModal<?php echo $p['id']; ?>">
                            <i class="fas fa-file-contract me-1"></i> View Docs
                        </button>
                        <?php ob_start(); ?>
                        <!-- Docs Modal -->
                        <div class="modal fade" id="docsModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <div class="modal-header border-0 bg-light p-4">
                                        <h5 class="modal-title fw-bold">Legal Verification: <?php echo htmlspecialchars($p['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row g-4">
                                            <div class="col-md-6 border-end">
                                                <h6 class="text-uppercase small fw-bold text-primary mb-3">Ownership &amp; Identity</h6>
                                                <p class="mb-1"><strong>Owner Name:</strong> <?php echo $p['owner_full_name'] ?? 'N/A'; ?></p>
                                                <p class="mb-3"><strong>Account User:</strong> <?php echo $p['owner_name'] ?? 'N/A'; ?></p>
                                                <?php if ($p['owner_id_doc']): ?>
                                                <a href="<?php echo BASE_URL . $p['owner_id_doc']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="fas fa-id-card me-1"></i> View Owner ID
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-uppercase small fw-bold text-primary mb-3">Professional Staff</h6>
                                                <p class="mb-1"><strong>Lead Pharmacist:</strong> <?php echo $p['pharmacist_name'] ?? 'N/A'; ?></p>
                                                <p class="mb-3"><strong>License No:</strong> <?php echo $p['pharmacist_license_no'] ?? 'N/A'; ?></p>
                                                <?php if ($p['pharmacist_doc']): ?>
                                                <a href="<?php echo BASE_URL . $p['pharmacist_doc']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="fas fa-user-md me-1"></i> View Pharmacist License
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($p['business_reg_doc'] || $p['pharmacy_license_doc']): ?>
                                            <div class="col-12 mt-2 pt-3 border-top">
                                                <h6 class="text-uppercase small fw-bold text-primary mb-3">Business Licenses</h6>
                                                <div class="d-flex gap-3 flex-wrap">
                                                    <?php if ($p['business_reg_doc']): ?>
                                                    <a href="<?php echo BASE_URL . $p['business_reg_doc']; ?>" target="_blank" class="btn btn-outline-dark rounded-pill">
                                                        <i class="fas fa-building me-1"></i> Business Registration
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if ($p['pharmacy_license_doc']): ?>
                                                    <a href="<?php echo BASE_URL . $p['pharmacy_license_doc']; ?>" target="_blank" class="btn btn-outline-dark rounded-pill">
                                                        <i class="fas fa-file-medical me-1"></i> Operations License
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 p-4 pt-0">
                                        <?php if ($p['status'] === 'pending'): ?>
                                        <a href="pharmacies.php?action=approve&id=<?php echo $p['id']; ?>" class="btn btn-success rounded-pill px-4">
                                            <i class="fas fa-check me-1"></i> Approve & Upgrade Owner
                                        </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $modals_html .= ob_get_clean(); ?>
                        <?php else: ?>
                        <span class="text-muted small">No docs uploaded</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo ucfirst($p['status']); ?></span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group">
                            <?php if ($p['status'] !== 'active'): ?>
                            <a href="pharmacies.php?action=approve&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-success" title="Approve">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <?php endif; ?>
                            <?php if ($p['status'] === 'active'): ?>
                            <a href="pharmacies.php?action=suspend&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Suspend this pharmacy? Staff will lose access.')" title="Suspend">
                                <i class="fas fa-ban"></i> Suspend
                            </a>
                            <?php endif; ?>
                            <a href="pharmacies.php?action=delete&id=<?php echo $p['id']; ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('⚠️ PERMANENT DELETE: This will remove the pharmacy and convert all its staff to customer accounts. This cannot be undone. Continue?')"
                               title="Delete Permanently">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Pharmacy Modal -->
<div class="modal fade" id="addPharmacyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 bg-light p-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-hospital me-2 text-primary"></i>Add New Pharmacy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="pharmacies.php">
                <input type="hidden" name="action" value="add_pharmacy">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Pharmacy Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Sunrise Pharmacy" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Type</label>
                            <select name="pharmacy_type" class="form-select">
                                <option value="Retail">Retail</option>
                                <option value="Wholesale">Wholesale</option>
                                <option value="Hospital">Hospital</option>
                                <option value="Clinic">Clinic</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="pharmacy@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+237 6XX XXX XXX">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Street, City, Region">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">License Number</label>
                            <input type="text" name="license_no" class="form-control" placeholder="License #">
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info border-0 mb-0 py-2 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Pharmacies added here are <strong>immediately active</strong>. You can assign a Branch Admin from the <a href="users.php">Users</a> page.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-plus me-1"></i> Create Pharmacy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
echo $modals_html;
include 'includes/templates/footer.php';
?>
