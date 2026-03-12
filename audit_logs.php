<?php
/**
 * Audit Logs Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$page_title = 'System Audit Logs';
$active_page = 'logs';

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Count total
    $total = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    $pages = ceil($total / $limit);
    
    // Fetch logs
    $stmt = $pdo->prepare("SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $logs = [];
    $pages = 0;
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">Audit Logs</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-danger" onclick="alert('Manual clearing is disabled for security reasons.')">
            <i class="fas fa-eraser me-1"></i> Clear Logs
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Record ID</th>
                        <th>Value Changes</th>
                        <th class="pe-4">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No audit logs found.</td></tr>
                    <?php else: foreach ($logs as $l): ?>
                    <tr>
                        <td class="ps-4 small"><?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?></td>
                        <td><span class="fw-bold"><?php echo $l['username'] ?: 'System'; ?></span></td>
                        <td>
                            <?php 
                            $badge_class = 'bg-light text-dark';
                            if (str_contains($l['action'], 'DELETE')) $badge_class = 'bg-danger';
                            if (str_contains($l['action'], 'ADD') || str_contains($l['action'], 'CREATE')) $badge_class = 'bg-success';
                            if (str_contains($l['action'], 'UPDATE')) $badge_class = 'bg-info';
                            ?>
                            <span class="badge <?php echo $badge_class; ?> small text-uppercase" style="font-size: 0.65rem;"><?php echo $l['action']; ?></span>
                        </td>
                        <td><small><?php echo strtoupper($l['table_name']); ?></small></td>
                        <td>#<?php echo $l['record_id'] ?: '-'; ?></td>
                        <td class="small">
                            <?php if ($l['old_value'] || $l['new_value']): ?>
                                <div class="text-muted text-truncate" style="max-width: 200px;">
                                    <?php echo $l['old_value'] ? '<span class="text-danger">From: ' . $l['old_value'] . '</span>' : ''; ?>
                                    <?php echo $l['new_value'] ? '<span class="text-success ms-1">To: ' . $l['new_value'] . '</span>' : ''; ?>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 small text-muted"><?php echo $l['ip_address']; ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white py-3 border-0">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="audit_logs.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<div class="alert alert-warning border-0 shadow-sm mt-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-shield-alt fa-2x me-3"></i>
        <div>
            <h6 class="fw-bold mb-1">Security Enforcement</h6>
            <p class="small mb-0 opacity-75">Audit logs are immutable and cannot be deleted through the interface to maintain system integrity for compliance audits.</p>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
