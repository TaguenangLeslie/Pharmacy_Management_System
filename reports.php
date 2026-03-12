<?php
/**
 * Reports & Analytics Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$page_title = 'Reports & Analytics';
$active_page = 'reports';

$type = $_GET['type'] ?? 'sales';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    if ($type === 'sales') {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE DATE(s.sale_date) BETWEEN ? AND ? ORDER BY s.sale_date DESC");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll();
        
        $stmt_total = $pdo->prepare("SELECT SUM(grand_total) as total, COUNT(*) as count FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
        $stmt_total->execute([$start_date, $end_date]);
        $summary = $stmt_total->fetch();
    } elseif ($type === 'expenses') {
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll();
    } elseif ($type === 'finance') {
        // Revenue
        $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $revenue = $stmt->fetchColumn() ?: 0;
        
        // Expenses
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE date BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $expenses_total = $stmt->fetchColumn() ?: 0;
        
        // COGS (Rough estimate based on current cost prices)
        $stmt = $pdo->prepare("SELECT SUM(si.quantity * m.cost_price) FROM sale_items si JOIN sales s ON si.sale_id = s.id JOIN medicines m ON si.medicine_id = m.id WHERE DATE(s.sale_date) BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $cogs = $stmt->fetchColumn() ?: 0;
        
        $finance = [
            'revenue' => $revenue,
            'expenses' => $expenses_total,
            'cogs' => $cogs,
            'profit' => $revenue - $expenses_total - $cogs
        ];
    } elseif ($type === 'stock') {
        $stmt = $pdo->query("SELECT m.*, s.name as supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id WHERE m.quantity <= m.reorder_level ORDER BY m.quantity ASC");
        $report_data = $stmt->fetchAll();
    } elseif ($type === 'expiry') {
        $stmt = $pdo->query("SELECT m.*, s.name as supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id WHERE m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY m.expiry_date ASC");
        $report_data = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $report_data = [];
    $error = "Database error: " . $e->getMessage();
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">System Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary me-2 no-print">
            <i class="fas fa-print me-1"></i> Print Report
        </button>
        <a href="export.php?type=<?php echo $type; ?>&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="btn btn-primary no-print">
            <i class="fas fa-file-export me-1"></i> Export CSV
        </a>
    </div>
</div>

<!-- Report Navigation -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm d-inline-flex no-print">
    <li class="nav-item">
        <a class="nav-link <?php echo ($type == 'sales') ? 'active' : ''; ?>" href="reports.php?type=sales">Sales Report</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($type == 'expenses') ? 'active' : ''; ?>" href="reports.php?type=expenses">Expenses</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($type == 'finance') ? 'active' : ''; ?>" href="reports.php?type=finance">Profit & Loss</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($type == 'stock') ? 'active' : ''; ?>" href="reports.php?type=stock">Low Stock</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($type == 'expiry') ? 'active' : ''; ?>" href="reports.php?type=expiry">Expiry Alerts</a>
    </li>
</ul>

<?php if ($type === 'sales'): ?>
<!-- Sales Filter -->
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body">
        <form action="reports.php" method="GET" class="row g-3">
            <input type="hidden" name="type" value="sales">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter Results</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm pink-gradient text-white">
            <div class="card-body p-4">
                <div class="small opacity-75 text-uppercase">Total Revenue</div>
                <h2 class="display-6 fw-bold mb-0"><?php echo format_currency($summary['total'] ?? 0); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body p-4">
                <div class="small text-muted text-uppercase">Total Transactions</div>
                <h2 class="display-6 fw-bold mb-0 text-primary"><?php echo $summary['count'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <?php if ($type === 'sales'): ?>
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Method</th>
                        <th class="text-end pe-4">Total</th>
                    </tr>
                    <?php elseif ($type === 'expenses'): ?>
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end pe-4">Amount</th>
                    </tr>
                    <?php elseif ($type === 'finance'): ?>
                    <!-- No table header needed for finance overview cards -->
                    <?php else: ?>
                    <tr>
                        <th class="ps-4">Medicine Name</th>
                        <th>Supplier</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Expiry Date</th>
                        <th class="text-center pe-4">Status</th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if ($type === 'finance'): ?>
                    <tr>
                        <td colspan="6" class="p-4">
                            <div class="row g-4 text-center">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded shadow-sm">
                                        <div class="text-muted small mb-1">Total Revenue</div>
                                        <div class="h4 fw-bold text-success"><?php echo format_currency($finance['revenue']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded shadow-sm">
                                        <div class="text-muted small mb-1">Cost of Goods Sold</div>
                                        <div class="h4 fw-bold text-danger"><?php echo format_currency($finance['cogs']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded shadow-sm">
                                        <div class="text-muted small mb-1">Operational Expenses</div>
                                        <div class="h4 fw-bold text-danger"><?php echo format_currency($finance['expenses']); ?></div>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <div class="card pink-gradient text-white border-0 shadow-sm p-4">
                                        <div class="h6 text-uppercase fw-bold opacity-75">Net Profit / Loss for Period</div>
                                        <div class="display-5 fw-bold"><?php echo format_currency($finance['profit']); ?></div>
                                        <p class="mb-0 mt-2 small opacity-75">Generated based on sales activity and recorded business expenses.</p>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php elseif (empty($report_data)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No data found for this report.</td></tr>
                    <?php else: foreach ($report_data as $row): ?>
                    <tr>
                        <?php if ($type === 'sales'): ?>
                        <td class="ps-4 fw-bold text-primary"><?php echo $row['invoice_no']; ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($row['sale_date'])); ?></td>
                        <td><?php echo $row['customer_name']; ?></td>
                        <td><?php echo $row['cashier_name'] ?: 'Admin'; ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo $row['payment_method']; ?></span></td>
                        <td class="text-end pe-4 fw-bold"><?php echo format_currency($row['grand_total']); ?></td>
                        <?php elseif ($type === 'expenses'): ?>
                        <td class="ps-4"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo ucfirst($row['category']); ?></span></td>
                        <td><?php echo $row['description']; ?></td>
                        <td class="text-end pe-4 fw-bold text-danger"><?php echo format_currency($row['amount']); ?></td>
                        <?php else: 
                            $status_badge = ($type == 'stock') ? '<span class="badge bg-danger">Low Stock</span>' : '<span class="badge bg-warning">Expiring Soon</span>';
                        ?>
                        <td class="ps-4 fw-bold"><?php echo $row['name']; ?></td>
                        <td><?php echo $row['supplier_name'] ?: 'None'; ?></td>
                        <td><?php echo $row['quantity']; ?> <?php echo $row['unit']; ?></td>
                        <td><?php echo format_currency($row['price']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></td>
                        <td class="text-center pe-4"><?php echo $status_badge; ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, #sidebar, .nav-pills, .btn-toolbar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; }
}
</style>

<?php include 'includes/templates/footer.php'; ?>
