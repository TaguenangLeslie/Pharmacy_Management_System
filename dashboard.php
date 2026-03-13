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

$pharmacy_id = $_SESSION['pharmacy_id'] ?? null;

$page_title = 'Dashboard';
$active_page = 'dashboard';

// Fetch some stats with multi-tenant support
try {
    $pharma_id = $_SESSION['pharmacy_id'] ?? null;
    $ph_filter = $pharma_id ? " WHERE pharmacy_id = $pharma_id" : "";
    $ph_filter_and = $pharma_id ? " AND pharmacy_id = $pharma_id" : "";

    // 1. Today's Sales
    $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) = CURDATE() $ph_filter_and");
    $stmt->execute();
    $today_sales = $stmt->fetchColumn() ?: 0;

    // 2. Low Stock Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE quantity <= reorder_level $ph_filter_and");
    $stmt->execute();
    $low_stock_count = $stmt->fetchColumn() ?: 0;

    // 3. Expiring Soon (90 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date > CURDATE() $ph_filter_and");
    $stmt->execute();
    $expiring_soon = $stmt->fetchColumn() ?: 0;

    // 4. Total Medicines
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicines" . ($pharma_id ? " WHERE pharmacy_id = $pharma_id" : ""));
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
    <h1 class="h2">Dashboard Overview</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="pos.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i> New Sale</a>
            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
            <i class="fas fa-calendar me-1"></i> This week
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 pink-gradient text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('today_sales'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo format_currency($today_sales); ?></div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 bg-warning text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('low_stock'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo $low_stock_count; ?></div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 bg-danger text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('expiring_soon'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo $expiring_soon; ?></div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 bg-info text-white h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold"><?php echo __('total_medicines'); ?></div>
                        <div class="h3 mb-0 fw-bold"><?php echo $total_medicines; ?></div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="fas fa-pills fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <h5 class="mb-0">Sales Analytics</h5>
                <i class="fas fa-ellipsis-v text-muted"></i>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <h5 class="mb-0"><?php echo __('recent_sales'); ?></h5>
                <a href="reports.php?type=sales" class="small text-primary text-decoration-none">View All</a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <?php if (empty($recent_sales)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-4">No recent sales found.</td></tr>
                            <?php else: foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></div>
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

<?php
// Fetch chart data (Last 7 days)
$chart_labels = [];
$chart_data = [];
$ph_filter_and = ($_SESSION['pharmacy_id'] ?? null) ? " AND pharmacy_id = " . intval($_SESSION['pharmacy_id']) : "";

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $chart_labels[] = $label;
    
    $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) = ? $ph_filter_and");
    $stmt->execute([$date]);
    $chart_data[] = $stmt->fetchColumn() ?: 0;
}
?>

<?php 
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById("salesChart").getContext("2d");
    new Chart(ctx, {
        type: "line",
        data: {
            labels: ' . json_encode($chart_labels) . ',
            datasets: [{
                label: "Daily Sales (' . ($app_settings['currency'] ?? '$') . ')",
                data: ' . json_encode($chart_data) . ',
                backgroundColor: "rgba(255, 105, 180, 0.2)",
                borderColor: "rgba(255, 105, 180, 1)",
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: "rgba(255, 105, 180, 1)",
                pointBorderColor: "#fff",
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
