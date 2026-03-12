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
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$system_name = $app_settings['system_name'] ?? APP_NAME;
$_SESSION['lang'] = $app_settings['language'] ?? 'en';
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
    <link rel="stylesheet" href="assets/css/style.css">
    
    <?php echo $extra_css ?? ''; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php if (is_logged_in()): ?>
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-white sidebar collapse border-end">
                <div class="position-sticky pt-3">
                    <!-- Global Actions (Notifications & Theme Toggle) -->
                    <div class="global-actions">
                        <?php
                        // Fetch Notifications (Low Stock & Expiry)
                        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE quantity <= reorder_level")->fetchColumn();
                        $expiry_count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
                        $total_notifs = $low_stock_count + $expiry_count;
                        ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-white shadow-sm rounded-circle position-relative" id="notifDropdown" data-bs-toggle="dropdown" style="width: 35px; height: 35px;">
                                <i class="fas fa-bell text-primary"></i>
                                <?php if ($total_notifs > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5rem; border: 2px solid white;">
                                    <?php echo $total_notifs; ?>
                                </span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="width: 250px;">
                                <li class="dropdown-header">System Notifications</li>
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
                                <li class="p-3 text-center text-muted small">No new notifications</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <button id="theme-toggle" class="btn btn-sm btn-white shadow-sm rounded-circle" onclick="toggleTheme()" style="width: 35px; height: 35px;">
                            <i class="fas fa-moon text-primary"></i>
                        </button>
                    </div>

                    <!-- User Profile Quick View -->
                    <div class="px-4 py-3 mb-4 border-bottom">
                        <div class="d-flex align-items-center">
                            <a href="profile.php" class="text-decoration-none d-flex align-items-center">
                                <img src="<?php echo $_SESSION['avatar'] ?? 'assets/img/default-avatar.png'; ?>" class="rounded-circle me-2 border" width="45" height="45" style="object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark small mb-0"><?php echo $_SESSION['full_name']; ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><?php echo strtoupper($_SESSION['role']); ?></div>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <div class="text-center mb-4">
                        <h3 class="text-primary"><i class="fas fa-hand-holding-medical"></i> <?php echo $system_name; ?></h3>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-th-large me-2"></i> <?php echo __('dashboard'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'inventory') ? 'active' : ''; ?>" href="inventory.php">
                                <i class="fas fa-pills me-2"></i> <?php echo __('inventory'); ?>
                            </a>
                        </li>
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
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'prescriptions') ? 'active' : ''; ?>" href="prescriptions.php">
                                <i class="fas fa-file-medical me-2"></i> <?php echo __('prescriptions'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'pos') ? 'active' : ''; ?>" href="pos.php">
                                <i class="fas fa-shopping-cart me-2"></i> <?php echo __('pos'); ?>
                            </a>
                        </li>
                        <?php if (has_role('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'suppliers') ? 'active' : ''; ?>" href="suppliers.php">
                                <i class="fas fa-truck me-2"></i> <?php echo __('suppliers'); ?>
                            </a>
                        </li>
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
            <?php endif; ?>
