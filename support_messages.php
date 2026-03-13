<?php
/**
 * Support Messages Inbox - Platform Admin Only
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();
if (!has_role('admin') || $_SESSION['pharmacy_id']) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Support Inbox';
$active_page = 'support_messages';

// Mark a single message as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare("UPDATE support_messages SET is_read = 1 WHERE id = ?")->execute([$_GET['read']]);
}

// Mark all as read
if (isset($_GET['mark_all'])) {
    $pdo->exec("UPDATE support_messages SET is_read = 1");
    header("Location: support_messages.php");
    exit;
}

// Delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM support_messages WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: support_messages.php");
    exit;
}

// Fetch all messages
try {
    $messages = $pdo->query("SELECT * FROM support_messages ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (id INT PRIMARY KEY AUTO_INCREMENT, sender_name VARCHAR(150) NOT NULL, sender_email VARCHAR(150) NOT NULL, issue_type VARCHAR(100) DEFAULT 'General', message TEXT NOT NULL, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $messages = [];
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2"><i class="fas fa-headset me-2"></i>Support Inbox</h1>
    <div class="btn-toolbar">
        <?php $unread = count(array_filter($messages, fn($m) => !$m['is_read'])); ?>
        <?php if ($unread > 0): ?>
        <a href="support_messages.php?mark_all=1" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fas fa-check-double me-1"></i> Mark All as Read
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($messages)): ?>
<div class="card border-0 shadow-sm text-center py-5">
    <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-25"></i>
    <p class="text-muted">No support messages yet.</p>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4" style="width:5%"></th>
                    <th>From</th>
                    <th>Issue Type</th>
                    <th>Message Preview</th>
                    <th>Received</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                <tr class="<?php echo $msg['is_read'] ? '' : 'table-warning fw-semibold'; ?>">
                    <td class="ps-4 text-center">
                        <?php if (!$msg['is_read']): ?>
                        <span class="badge bg-danger rounded-pill" style="font-size:0.55rem;">NEW</span>
                        <?php else: ?>
                        <i class="fas fa-envelope-open text-muted small"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($msg['sender_email']); ?></div>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($msg['issue_type']); ?></span></td>
                    <td class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($msg['message'], 0, 80, '...')); ?></td>
                    <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                    <td class="text-end pe-4">
                        <button type="button" class="btn btn-sm btn-outline-info view-msg-btn"
                            data-name="<?php echo htmlspecialchars($msg['sender_name']); ?>"
                            data-email="<?php echo htmlspecialchars($msg['sender_email']); ?>"
                            data-type="<?php echo htmlspecialchars($msg['issue_type']); ?>"
                            data-msg="<?php echo htmlspecialchars($msg['message']); ?>"
                            data-date="<?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>"
                            data-id="<?php echo $msg['id']; ?>"
                            title="View Message">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if (!$msg['is_read']): ?>
                        <a href="support_messages.php?read=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-success" title="Mark as Read">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                        <a href="support_messages.php?delete=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message?')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- View Message Modal -->
<div class="modal fade" id="viewMsgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Support Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 d-flex align-items-center">
                    <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                    <div>
                        <div class="fw-bold" id="vm-name"></div>
                        <div class="text-muted small" id="vm-email"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="badge bg-light text-dark border" id="vm-type"></span>
                    <span class="text-muted small ms-2" id="vm-date"></span>
                </div>
                <hr>
                <p class="mb-0" id="vm-message" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer border-0">
                <a id="vm-reply-btn" href="#" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-reply me-1"></i> Reply via Email
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = "
<script>
$(document).ready(function() {
    $('.view-msg-btn').click(function() {
        $('#vm-name').text($(this).data('name'));
        $('#vm-email').text($(this).data('email'));
        $('#vm-type').text($(this).data('type'));
        $('#vm-date').text($(this).data('date'));
        $('#vm-message').text($(this).data('msg'));
        $('#vm-reply-btn').attr('href', 'mailto:' + $(this).data('email'));
        // Auto-mark as read
        var id = $(this).data('id');
        $.get('support_messages.php?read=' + id);
        $(this).closest('tr').removeClass('table-warning fw-semibold');
        $(this).closest('tr').find('.badge.bg-danger').hide();
        $('#viewMsgModal').modal('show');
    });
});
</script>
";
include 'includes/templates/footer.php';
?>
