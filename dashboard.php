<?php
/**
 * Dashboard Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

// If user is a customer, they should see a limited view or redirect
if (has_role('customer')) {
    redirect('explore.php');
}

// Handle Time Range Filtering
$range = $_GET['range'] ?? 'today';
$start_date = date('Y-m-d');
$range_label = __('today');

switch ($range) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $range_label = __('this_week');
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $range_label = __('this_month');
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        $range_label = __('this_year');
        break;
    default:
        $start_date = date('Y-m-d');
        $range_label = __('today');
}

$page_title = 'Dashboard';
$active_page = 'dashboard';

// Fetch stats with multi-tenant support
try {
    // Determine the pharmacy filter
    $pharmacy_id = (isset($_SESSION['pharmacy_id']) && $_SESSION['pharmacy_id'] > 0) ? intval($_SESSION['pharmacy_id']) : null;
    $ph_filter = $pharmacy_id ? " WHERE pharmacy_id = $pharmacy_id" : "";
    $ph_filter_and = $pharmacy_id ? " AND pharmacy_id = $pharmacy_id" : "";

    // 1. Sales in selected range
    $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? $ph_filter_and");
    $stmt->execute([$start_date, date('Y-m-d')]);
    $today_sales = $stmt->fetchColumn() ?: 0;

    // 1b. Platform Profit in selected range (Global Admin Only)
    $today_profit = 0;
    if (!$pharmacy_id) {
        $stmt = $pdo->prepare("SELECT SUM(platform_tax) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
        $stmt->execute([$start_date, date('Y-m-d')]);
        $today_profit = $stmt->fetchColumn() ?: 0;
    }

    // 2. Low Stock Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE quantity <= reorder_level $ph_filter_and");
    $stmt->execute();
    $low_stock_count = $stmt->fetchColumn() ?: 0;

    // 3. Expiring Soon (90 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date > CURDATE() $ph_filter_and");
    $stmt->execute();
    $expiring_soon = $stmt->fetchColumn() ?: 0;

    // 4. Total Medicines
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines $ph_filter");
    $stmt->execute();
    $total_medicines = $stmt->fetchColumn() ?: 0;

    // 5. Recent Sales
    $stmt = $pdo->prepare("SELECT * FROM sales $ph_filter ORDER BY sale_date DESC LIMIT 5");
    $stmt->execute();
    $recent_sales = $stmt->fetchAll();

} catch (PDOException $e) {
    // Fallback if DB doesn't have tables yet or error
    $today_sales = 0;
    $low_stock_count = 0;
    $expiring_soon = 0;
    $total_medicines = 0;
    $recent_sales = [];
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2"><?php echo __('dashboard_overview'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <?php if (!has_role('cashier')): ?>
                <a href="pos.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i> <?php echo __('new_sale'); ?></a>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-secondary"><?php echo __('export'); ?></button>
        </div>
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-calendar me-1"></i> <?php echo $range_label; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item <?php echo ($range == 'today') ? 'active' : ''; ?>" href="dashboard.php?range=today"><?php echo __('today'); ?></a></li>
                <li><a class="dropdown-item <?php echo ($range == 'week') ? 'active' : ''; ?>" href="dashboard.php?range=week"><?php echo __('this_week'); ?></a></li>
                <li><a class="dropdown-item <?php echo ($range == 'month') ? 'active' : ''; ?>" href="dashboard.php?range=month"><?php echo __('this_month'); ?></a></li>
                <li><a class="dropdown-item <?php echo ($range == 'year') ? 'active' : ''; ?>" href="dashboard.php?range=year"><?php echo __('this_year'); ?></a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card premium-card gradient-pink text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold">
                            <?php echo !$pharmacy_id ? "Tax Profit ($range_label)" : "Sales ($range_label)"; ?>
                        </div>
                        <div class="h3 mb-0 fw-bold">
                            <?php echo format_currency(!$pharmacy_id ? $today_profit : $today_sales); ?>
                        </div>
                        <?php if (!$pharmacy_id): ?>
                        <div class="small mt-1 opacity-75">Gross Sales: <?php echo format_currency($today_sales); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="stats-icon-box">
                        <i class="fas <?php echo !$pharmacy_id ? 'fa-hand-holding-usd' : 'fa-dollar-sign'; ?> fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card premium-card gradient-orange text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('low_stock'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo $low_stock_count; ?></div>
                    </div>
                    <div class="stats-icon-box">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card premium-card gradient-red text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('expiring_soon'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo $expiring_soon; ?></div>
                    </div>
                    <div class="stats-icon-box">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card premium-card gradient-blue text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('total_medicines'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo $total_medicines; ?></div>
                    </div>
                    <div class="stats-icon-box">
                        <i class="fas fa-pills fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card glass-card h-100 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <h5 class="mb-0"><?php echo __('sales_analytics'); ?></h5>
                <i class="fas fa-ellipsis-v text-muted"></i>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="col-lg-4 mb-4">
        <div class="card glass-card h-100 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <h5 class="mb-0"><?php echo __('recent_sales'); ?></h5>
                <a href="reports.php?type=sales" class="small text-primary text-decoration-none"><?php echo __('view_all'); ?></a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <?php if (empty($recent_sales)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-4"><?php echo __('no_recent_sales'); ?></td></tr>
                            <?php else: foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $sale['customer_name'] ?: __('walk_in_customer'); ?></div>
                                    <div class="small text-muted"><?php echo date('H:i A', strtotime($sale['sale_date'])); ?></div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-success">+<?php echo format_currency($sale['grand_total']); ?></div>
                                    <div class="badge bg-light text-dark small"><?php echo $sale['payment_method']; ?></div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($low_stock_count > 0 || $expiring_soon > 0): ?>
<div class="row">
    <?php if ($low_stock_count > 0): ?>
    <div class="col-lg-6 mb-4">
        <div class="card glass-card h-100 overflow-hidden border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 px-4">
                <h6 class="mb-0 fw-bold text-warning"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('low_stock_alerts'); ?></h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4"><?php echo __('medicine'); ?></th>
                                <th><?php echo __('in_stock'); ?></th>
                                <th class="text-end pe-4"><?php echo __('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("SELECT id, name, quantity, unit, reorder_level FROM medicines WHERE quantity <= reorder_level $ph_filter_and LIMIT 5");
                            $stmt->execute();
                            while($m = $stmt->fetch()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $m['name']; ?></div>
                                    <div class="small text-muted">Level: <?php echo $m['reorder_level']; ?></div>
                                </td>
                                <td><span class="badge bg-soft-warning text-warning"><?php echo $m['quantity']; ?> <?php echo $m['unit']; ?></span></td>
                                <td class="text-end pe-4">
                                    <a href="inventory.php?search=<?php echo urlencode($m['name']); ?>" class="btn btn-sm btn-light border"><?php echo __('restock'); ?></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($low_stock_count > 5): ?>
            <div class="card-footer bg-white border-0 text-center pb-3">
                <a href="inventory.php" class="small text-decoration-none"><?php echo __('view_all'); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($expiring_soon > 0): ?>
    <div class="col-lg-6 mb-4">
        <div class="card glass-card h-100 overflow-hidden border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 px-4">
                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-clock me-2"></i> <?php echo __('expiring_soon'); ?></h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4"><?php echo __('medicine'); ?></th>
                                <th><?php echo __('expiry_date'); ?></th>
                                <th class="text-end pe-4"><?php echo __('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("SELECT id, name, expiry_date FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date > CURDATE() $ph_filter_and LIMIT 5");
                            $stmt->execute();
                            while($m = $stmt->fetch()): 
                                $days = (strtotime($m['expiry_date']) - time()) / (60 * 60 * 24);
                                $color = $days < 30 ? 'danger' : 'warning';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><a href="inventory.php?search=<?php echo urlencode($m['name']); ?>" class="text-decoration-none text-dark"><?php echo $m['name']; ?></a></div>
                                </td>
                                <td><span class="text-<?php echo $color; ?>"><?php echo date('M d, Y', strtotime($m['expiry_date'])); ?></span></td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-light text-<?php echo $color; ?> border"><?php echo ceil($days); ?> <?php echo __('days_left'); ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($expiring_soon > 5): ?>
            <div class="card-footer bg-white border-0 text-center pb-3">
                <a href="inventory.php" class="small text-decoration-none"><?php echo __('view_all'); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
// Fetch chart data based on range
$chart_labels = [];
$chart_data = [];
$chart_ph_filter = $pharmacy_id ? " AND pharmacy_id = $pharmacy_id" : "";

if ($range === 'year') {
    // Show last 12 months
    for ($i = 11; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        $label = date('M Y', strtotime($month_start));
        $chart_labels[] = $label;
        
        $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? $chart_ph_filter");
        $stmt->execute([$month_start, $month_end]);
        $chart_data[] = $stmt->fetchColumn() ?: 0;
    }
} else {
    // Show daily trend (7 days for today/week, 30 days for month)
    $days = ($range === 'month') ? 29 : 6;
    for ($i = $days; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = ($range === 'month') ? date('d M', strtotime($date)) : date('D', strtotime($date));
        $chart_labels[] = $label;
        
        $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) = ? $chart_ph_filter");
        $stmt->execute([$date]);
        $chart_data[] = $stmt->fetchColumn() ?: 0;
    }
}
?>

<?php 
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById("salesChart").getContext("2d");
    new Chart(ctx, {
        type: "' . ($range === 'year' ? 'bar' : 'line') . '",
        data: {
            labels: ' . json_encode($chart_labels) . ',
            datasets: [{
                label: \'Sales (FCFA)\',
                data: ' . json_encode($chart_data) . ',
                borderColor: \'#FF1493\',
                backgroundColor: \'' . ($range === 'year' ? 'rgba(255, 20, 147, 0.6)' : 'rgba(255, 20, 147, 0.1)') . '\',
                borderWidth: ' . ($range === 'year' ? 0 : 4) . ',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: \'#fff\',
                pointBorderColor: \'#FF1493\',
                pointBorderWidth: 2,
                pointRadius: ' . ($range === 'month' ? 2 : 4) . ',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: "rgba(0,0,0,0.03)" } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
';
include 'includes/templates/footer.php'; 
?>
