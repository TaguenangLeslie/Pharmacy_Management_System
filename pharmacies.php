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

// Handle Status Updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $status = ($action === 'approve') ? 'active' : (($action === 'suspend') ? 'suspended' : 'pending');
    
    try {
        $stmt = $pdo->prepare("UPDATE pharmacies SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = "Pharmacy status updated to " . ucfirst($status) . ".";
        log_activity($pdo, $_SESSION['user_id'], 'UPDATE_PHARMACY_STATUS', 'pharmacies', $id, null, "Status changed to $status");
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Fetch all pharmacies
try {
    $stmt = $pdo->query("SELECT p.*, u.username as owner_name FROM pharmacies p LEFT JOIN users u ON p.owner_id = u.id ORDER BY p.name ASC");
    $pharmacies = $stmt->fetchAll();
} catch (PDOException $e) {
    $pharmacies = [];
}

$modals_html = ""; // Buffer for modals
include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12">
        <h1 class="h2">Pharmacy Management</h1>
        <p class="text-muted">Review and manage all pharmacies registered on the platform.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Pharmacy Details</th>
                    <th>Business & Legal</th>
                    <th>Verification Proofs</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pharmacies)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">No pharmacies registered yet.</td></tr>
                <?php else: foreach ($pharmacies as $p): 
                    $status_class = 'bg-secondary';
                    switch($p['status']) {
                        case 'active': $status_class = 'bg-success'; break;
                        case 'pending': $status_class = 'bg-warning text-dark'; break;
                        case 'suspended': $status_class = 'bg-danger'; break;
                    }
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-primary"><?php echo $p['name']; ?></div>
                        <div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $p['address']; ?></div>
                        <div class="small text-muted mt-1">Joined: <?php echo date('M d, Y', strtotime($p['created_at'])); ?></div>
                    </td>
                    <td>
                        <div class="fw-bold small"><?php echo $p['pharmacy_type']; ?></div>
                        <div class="small text-muted">Lic: <?php echo $p['license_no']; ?></div>
                        <div class="small text-muted">Reg: <?php echo $p['business_reg_no'] ?? 'N/A'; ?></div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill" data-bs-toggle="modal" data-bs-target="#docsModal<?php echo $p['id']; ?>">
                            <i class="fas fa-file-contract me-1"></i> View Documents
                        </button>

                        <?php 
                        // Capture modal HTML to be rendered at the bottom
                        ob_start(); 
                        ?>
                        <!-- Docs Modal -->
                        <div class="modal fade" id="docsModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <div class="modal-header border-0 bg-light p-4">
                                        <h5 class="modal-title fw-bold">Legal Verification: <?php echo $p['name']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row g-4">
                                            <div class="col-md-6 border-end">
                                                <h6 class="text-uppercase small fw-bold text-primary mb-3">Ownership & Identity</h6>
                                                <p class="mb-1"><strong>Owner Name:</strong> <?php echo $p['owner_full_name'] ?? 'N/A'; ?></p>
                                                <p class="mb-3"><strong>Account User:</strong> <?php echo $p['owner_name'] ?? 'N/A'; ?></p>
                                                <a href="<?php echo BASE_URL . $p['owner_id_doc']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="fas fa-id-card me-1"></i> View Owner ID
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-uppercase small fw-bold text-primary mb-3">Professional Staff</h6>
                                                <p class="mb-1"><strong>Lead Pharmacist:</strong> <?php echo $p['pharmacist_name'] ?? 'N/A'; ?></p>
                                                <p class="mb-3"><strong>License No:</strong> <?php echo $p['pharmacist_license_no'] ?? 'N/A'; ?></p>
                                                <a href="<?php echo BASE_URL . $p['pharmacist_doc']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="fas fa-user-md me-1"></i> View Pharmacist License
                                                </a>
                                            </div>
                                            <div class="col-12 mt-4 pt-4 border-top">
                                                <h6 class="text-uppercase small fw-bold text-primary mb-3">Business Licenses</h6>
                                                <div class="d-flex gap-3">
                                                    <a href="<?php echo BASE_URL . $p['business_reg_doc']; ?>" target="_blank" class="btn btn-outline-dark rounded-pill">
                                                        <i class="fas fa-building me-1"></i> Business Registration Certificate
                                                    </a>
                                                    <a href="<?php echo BASE_URL . $p['pharmacy_license_doc']; ?>" target="_blank" class="btn btn-outline-dark rounded-pill">
                                                        <i class="fas fa-file-medical me-1"></i> Operations License
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 p-4 pt-0">
                                        <?php if ($p['status'] === 'pending'): ?>
                                        <a href="pharmacies.php?action=approve&id=<?php echo $p['id']; ?>" class="btn btn-success rounded-pill px-4">Approve Pharmacy</a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                        $modals_html .= ob_get_clean(); 
                        ?>
                    </td>
                    <td><span class="badge <?php echo $status_class; ?> rounded-pill px-3"><?php echo ucfirst($p['status']); ?></span></td>
                    <td class="text-end pe-4">
                        <div class="btn-group">
                            <?php if ($p['status'] !== 'active'): ?>
                            <a href="pharmacies.php?action=approve&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <?php endif; ?>
                            <?php if ($p['status'] === 'active'): ?>
                            <a href="pharmacies.php?action=suspend&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Suspend this business?')">
                                <i class="fas fa-ban"></i> Suspend
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

<?php 
echo $modals_html;
include 'includes/templates/footer.php'; 
?>
