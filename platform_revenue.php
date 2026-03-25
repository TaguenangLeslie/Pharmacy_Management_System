<?php
/**
 * Platform Revenue Tracking (Global Admin Only)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

// Ensure only platform admins can access this
if ($_SESSION['pharmacy_id']) {
    redirect('dashboard.php');
}

$page_title = 'Platform Revenue';
$active_page = 'platform_revenue';

// Get Global Stats
try {
    // Total lifetime tax
    $stmt = $pdo->query("SELECT COALESCE(SUM(platform_tax), 0) FROM sales");
    $total_lifetime = $stmt->fetchColumn();

    // Today's tax
    $stmt = $pdo->query("SELECT COALESCE(SUM(platform_tax), 0) FROM sales WHERE DATE(sale_date) = CURDATE()");
    $total_today = $stmt->fetchColumn();

    // This Month's tax
    $stmt = $pdo->query("SELECT COALESCE(SUM(platform_tax), 0) FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $total_month = $stmt->fetchColumn();

    // Revenue per Pharmacy
    $stmt = $pdo->query("
        SELECT p.id, p.name AS pharmacy_name, p.status, 
               COUNT(s.id) AS total_sales, 
               COALESCE(SUM(s.grand_total), 0) AS total_sales_volume,
               COALESCE(SUM(s.platform_tax), 0) AS total_tax_collected
        FROM pharmacies p
        LEFT JOIN sales s ON p.id = s.pharmacy_id
        GROUP BY p.id
        ORDER BY total_tax_collected DESC
    ");
    $pharmacies_revenue = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12">
        <h1 class="h2">Platform Revenue</h1>
        <p class="text-muted">Track the tax revenue generated from all registered pharmacies on the platform.</p>
    </div>
</div>

<!-- Revenue Overviews -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card premium-card gradient-primary text-white h-100 shadow border-0">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">Today's Revenue</div>
                <div class="h3 mb-0 fw-bold"><?php echo format_currency($total_today); ?></div>
                <i class="fas fa-calendar-day position-absolute text-white-50" style="right: 20px; bottom: 20px; font-size: 3rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card premium-card gradient-success text-white h-100 shadow border-0">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">This Month</div>
                <div class="h3 mb-0 fw-bold"><?php echo format_currency($total_month); ?></div>
                <i class="fas fa-calendar-alt position-absolute text-white-50" style="right: 20px; bottom: 20px; font-size: 3rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card premium-card gradient-blue text-white h-100 shadow border-0">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">Lifetime Revenue</div>
                <div class="h3 mb-0 fw-bold"><?php echo format_currency($total_lifetime); ?></div>
                <i class="fas fa-hand-holding-usd position-absolute text-white-50" style="right: 20px; bottom: 20px; font-size: 3rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Revenue by Pharmacy Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-bold">Revenue by Pharmacy</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small">
                <tr>
                    <th class="ps-4">Pharmacy</th>
                    <th>Status</th>
                    <th class="text-center">Total Sales Count</th>
                    <th class="text-end">Total Gross Volume</th>
                    <th class="text-end pe-4">Platform Tax Collected</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pharmacies_revenue)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">No pharmacy revenue data available.</td>
                </tr>
                <?php else: foreach ($pharmacies_revenue as $pr): 
                    $badge_class = 'bg-secondary';
                    if ($pr['status'] == 'active') $badge_class = 'bg-success';
                    if ($pr['status'] == 'pending') $badge_class = 'bg-warning text-dark';
                    if ($pr['status'] == 'suspended') $badge_class = 'bg-danger';
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-primary">
                        <i class="fas fa-hospital me-2 text-muted"></i>
                        <?php echo htmlspecialchars($pr['pharmacy_name']); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?> rounded-pill">
                            <?php echo ucfirst($pr['status']); ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            <?php echo number_format($pr['total_sales']); ?> orders
                        </span>
                    </td>
                    <td class="text-end text-muted">
                        <?php echo format_currency($pr['total_sales_volume']); ?>
                    </td>
                    <td class="text-end pe-4 fw-bold text-success">
                        +<?php echo format_currency($pr['total_tax_collected']); ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot class="bg-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end pt-3">GRAND TOTAL COLLECTED:</td>
                    <td class="text-end pe-4 pt-3 text-primary fs-5"><?php echo format_currency($total_lifetime); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
