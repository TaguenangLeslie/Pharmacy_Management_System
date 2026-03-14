<?php
/**
 * Header Template
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

// Load system settings
$app_settings = [];
try {
    $settings_pharmacy_id = $_SESSION['pharmacy_id'] ?? null;
    
    // Fetch global settings first
    $stmt = $pdo->query("SELECT * FROM settings WHERE pharmacy_id IS NULL");
    while ($row = $stmt->fetch()) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Fetch pharmacy-specific settings to override
    if ($settings_pharmacy_id) {
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE pharmacy_id = ?");
        $stmt->execute([$settings_pharmacy_id]);
        while ($row = $stmt->fetch()) {
            $app_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (PDOException $e) {}

$system_name = $app_settings['system_name'] ?? APP_NAME;
$_SESSION['lang'] = $app_settings['language'] ?? 'en';

// Clean up any stale stock reservations
cleanup_expired_reservations($pdo);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo $system_name; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500;600&family=Montserrat:wght@500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <?php echo $extra_css ?? ''; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php if (is_logged_in() && (!isset($hide_sidebar) || !$hide_sidebar)): ?>
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-white sidebar collapse border-end" style="position: relative; z-index: 200;">
                <div class="pt-3" style="position: sticky; top: 0; height: 100vh; overflow-y: auto; overflow-x: visible;">
                    <!-- Global Actions (Notifications & Theme Toggle) -->
                    <div class="global-actions">
                        <?php
                        // Fetch Notifications (Low Stock & Expiry) - Filtered by Pharmacy
                        $ph_id_nav = $_SESSION['pharmacy_id'] ?? null;
                        $ph_filter_nav = $ph_id_nav ? " AND pharmacy_id = $ph_id_nav" : "";
                        
                        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE quantity <= reorder_level $ph_filter_nav")->fetchColumn();
                        $expiry_count    = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) $ph_filter_nav")->fetchColumn();
                        
                        // Approval notification for new branch admins
                        $approval_notif = ($_SESSION['role'] === 'admin' && $_SESSION['pharmacy_id'] && !isset($_SESSION['welcome_dismissed']));
                        
                        // Unread support messages — only for global platform admin (no pharmacy_id)
                        $support_msg_count = 0;
                        if ($_SESSION['role'] === 'admin' && !$ph_id_nav) {
                            try {
                                $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (id INT PRIMARY KEY AUTO_INCREMENT, sender_name VARCHAR(150) NOT NULL, sender_email VARCHAR(150) NOT NULL, issue_type VARCHAR(100) DEFAULT 'General', message TEXT NOT NULL, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                                $support_msg_count = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE is_read = 0")->fetchColumn();
                            } catch (PDOException $e) {}
                        }
                                               $total_notifs = $low_stock_count + $expiry_count + ($approval_notif ? 1 : 0) + $support_msg_count;
                        
                        // Persistent dismissal logic
                        if (!isset($_SESSION['last_notif_dismissal'])) {
                            $stmt_user = $pdo->prepare("SELECT last_notif_dismissal FROM users WHERE id = ?");
                            $stmt_user->execute([$_SESSION['user_id']]);
                            $_SESSION['last_notif_dismissal'] = $stmt_user->fetchColumn();
                        }

                        $last_dismissed = $_SESSION['notifs_dismissed_at'] ?? strtotime($_SESSION['last_notif_dismissal'] ?? '1970-01-01');
                        $show_badge = ($total_notifs > 0);
                        
                        if ($last_dismissed > 0) {
                            $show_badge = false;
                            if ($support_msg_count > 0) $show_badge = true;
                        }
                        ?>
>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-white shadow-sm rounded-circle position-relative" id="notifDropdown" data-bs-toggle="dropdown" data-bs-strategy="fixed" data-bs-offset="0,8" aria-expanded="false" style="width: 35px; height: 35px;">
                                <i class="fas fa-bell text-primary"></i>
                                <?php if ($show_badge): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notif-badge" style="font-size: 0.5rem; border: 2px solid white;">
                                    <?php echo $total_notifs; ?>
                                </span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="width: 300px; z-index: 9999;">
                                <li><div class="dropdown-header d-flex align-items-center justify-content-between">System Notifications <span class="badge bg-primary rounded-pill"><?php echo $total_notifs; ?></span></div></li>
                                <?php if ($approval_notif): ?>
                                <li>
                                    <div class="dropdown-item py-2 d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                        <div class="small flex-grow-1">
                                            <div class="fw-bold text-success">Pharmacy Approved!</div>
                                            <div class="text-muted">You are now admin of your registered branch.</div>
                                            <form method="POST" action="dismiss_welcome.php" class="mt-1">
                                                <button type="submit" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" style="font-size:0.7rem;">Dismiss</button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider my-0"></li>
                                <?php endif; ?>
                                <?php if ($support_msg_count > 0): ?>
                                <li><a class="dropdown-item py-2" href="support_messages.php">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-headset text-info me-2"></i>
                                        <div class="small"><strong class="text-info"><?php echo $support_msg_count; ?> unread</strong> support message<?php echo ($support_msg_count > 1 ? 's' : ''); ?></div>
                                    </div>
                                </a></li>
                                <li><hr class="dropdown-divider my-0"></li>
                                <?php endif; ?>
                                <?php if ($low_stock_count > 0): ?>
                                <li><a class="dropdown-item py-2" href="reports.php?type=stock">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                        <div class="small"><?php echo $low_stock_count; ?> items are low on stock!</div>
                                    </div>
                                </a></li>
                                <?php endif; ?>
                                <?php if ($expiry_count > 0): ?>
                                <li><a class="dropdown-item py-2" href="reports.php?type=expiry">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-hourglass-end text-danger me-2"></i>
                                        <div class="small"><?php echo $expiry_count; ?> items near expiry!</div>
                                    </div>
                                </a></li>
                                <?php endif; ?>
                                <?php if ($total_notifs == 0): ?>
                                <li class="p-3 text-center text-muted small"><i class="fas fa-bell-slash me-1"></i> No new notifications</li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider my-0"></li>
                                <li class="text-center py-1">
                                    <button class="btn btn-xs text-muted" style="font-size:0.7rem;" onclick="dismissBadge()">Mark all as read</button>
                                </li>
                            </ul>
                        </div>
                        <script>
                        // Auto-hide badge when dropdown opens
                        document.getElementById('notifDropdown').addEventListener('show.bs.dropdown', function() {
                            dismissBadge();
                        });
                        function dismissBadge() {
                            var badge = document.getElementById('notif-badge');
                            if (badge) badge.style.display = 'none';
                            fetch('mark_notifs_read.php');
                        }
                        </script>

                        <button id="theme-toggle" class="btn btn-sm btn-white shadow-sm rounded-circle" onclick="toggleTheme()" style="width: 35px; height: 35px;">
                            <i class="fas fa-moon text-primary"></i>
                        </button>
                    </div>

                    <!-- User Profile Quick View -->
                    <div class="px-4 py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <a href="profile.php" class="text-decoration-none d-flex align-items-center">
                                <img src="<?php echo $_SESSION['avatar'] ? BASE_URL . $_SESSION['avatar'] : BASE_URL . 'assets/img/default-avatar.png'; ?>" class="rounded-circle me-2 border" width="45" height="45" style="object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark small mb-0"><?php echo $_SESSION['full_name']; ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        <?php echo strtoupper($_SESSION['role']); ?>
                                        <?php echo $_SESSION['pharmacy_id'] ? ' (Branch)' : ' (Global)'; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <?php /* Approval notification moved to bell dropdown */ ?>
                    
                    <div class="text-center mb-4 mt-4">
                        <h3 class="text-primary"><i class="fas fa-hand-holding-medical"></i> <?php echo $system_name; ?></h3>
                    </div>
                    <ul class="nav flex-column">
                        <?php if (has_role(['admin', 'pharmacist', 'cashier'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-th-large me-2"></i> <?php echo __('dashboard'); ?>
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'explore') ? 'active' : ''; ?>" href="explore.php">
                                <i class="fas fa-search me-2"></i> Explore
                            </a>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'inventory') ? 'active' : ''; ?>" href="inventory.php">
                                <i class="fas fa-pills me-2"></i> <?php echo __('inventory'); ?>
                            </a>
                        </li>

                        <?php if (has_role('customer')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'orders') ? 'active' : ''; ?>" href="orders.php">
                                <i class="fas fa-shopping-bag me-2"></i> My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'prescriptions') ? 'active' : ''; ?>" href="prescriptions.php">
                                <i class="fas fa-file-prescription me-2"></i> Prescriptions
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (has_role(['admin', 'pharmacist', 'cashier'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'customers') ? 'active' : ''; ?>" href="customers.php">
                                <i class="fas fa-user-friends me-2"></i> <?php echo __('customers'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'expenses') ? 'active' : ''; ?>" href="expenses.php">
                                <i class="fas fa-money-bill-wave me-2"></i> <?php echo __('expenses'); ?>
                            </a>
                        </li>
                        <?php if (!has_role('customer')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'prescriptions') ? 'active' : ''; ?>" href="prescriptions.php">
                                <i class="fas fa-file-medical me-2"></i> <?php echo __('prescriptions'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'pos') ? 'active' : ''; ?>" href="pos.php">
                                <i class="fas fa-shopping-cart me-2"></i> <?php echo __('pos'); ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (has_role('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'suppliers') ? 'active' : ''; ?>" href="suppliers.php">
                                <i class="fas fa-truck me-2"></i> <?php echo __('suppliers'); ?>
                            </a>
                        </li>
                        <?php if (has_role('admin') && !$_SESSION['pharmacy_id']): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'manage_orders') ? 'active' : ''; ?>" href="manage_orders.php">
                            <i class="fas fa-globe me-2"></i> Global Orders
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'reports') ? 'active' : ''; ?>" href="reports.php">
                                <i class="fas fa-chart-line me-2"></i> <?php echo __('reports'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'users') ? 'active' : ''; ?>" href="users.php">
                                <i class="fas fa-users-cog me-2"></i> <?php echo __('users'); ?>
                            </a>
                        </li>
                        <?php if (has_role('admin') && !$_SESSION['pharmacy_id']): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'pharmacies') ? 'active' : ''; ?>" href="pharmacies.php">
                                <i class="fas fa-hospital me-2"></i> Pharmacies
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'settings') ? 'active' : ''; ?>" href="settings.php">
                                <i class="fas fa-cogs me-2"></i> <?php echo __('settings'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'logs') ? 'active' : ''; ?>" href="audit_logs.php">
                                <i class="fas fa-history me-2"></i> <?php echo __('audit_logs'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'backup') ? 'active' : ''; ?>" href="backup.php">
                                <i class="fas fa-database me-2"></i> <?php echo __('backup'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo __('logout'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="col-md-9 col-lg-10 px-md-4 py-4">
            <?php else: ?>
            <main class="col-12 px-0">
            <?php endif; ?>
