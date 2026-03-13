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
    $ph_id = $_SESSION['pharmacy_id'] ?? null;
    $ph_filter = $ph_id ? " AND s.pharmacy_id = $ph_id" : "";
    $ph_filter_bare = $ph_id ? " AND pharmacy_id = $ph_id" : "";

    if ($type === 'sales') {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as cashier_name, ph.name as pharmacy_name 
                               FROM sales s 
                               JOIN pharmacies ph ON s.pharmacy_id = ph.id
                               LEFT JOIN users u ON s.user_id = u.id 
                               WHERE DATE(s.sale_date) BETWEEN ? AND ? $ph_filter
                               ORDER BY ph.name ASC, s.sale_date DESC");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll();
        
        $stmt_total = $pdo->prepare("SELECT SUM(grand_total) as total, COUNT(*) as count FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? $ph_filter_bare");
        $stmt_total->execute([$start_date, $end_date]);
        $summary = $stmt_total->fetch();
    } elseif ($type === 'expenses') {
        $stmt = $pdo->prepare("SELECT e.*, ph.name as pharmacy_name 
                               FROM expenses e 
                               JOIN pharmacies ph ON e.pharmacy_id = ph.id
                               WHERE e.date BETWEEN ? AND ? $ph_filter
                               ORDER BY ph.name ASC, e.date DESC");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll();
    } elseif ($type === 'finance') {
        // Platform Admin - Get breakdown
        if (!$ph_id) {
            $stmt = $pdo->prepare("SELECT ph.name as pharmacy_name, 
                                   SUM(s.grand_total) as revenue,
                                   (SELECT SUM(e.amount) FROM expenses e WHERE e.pharmacy_id = ph.id AND e.date BETWEEN ? AND ?) as expenses
                                   FROM pharmacies ph
                                   LEFT JOIN sales s ON s.pharmacy_id = ph.id AND DATE(s.sale_date) BETWEEN ? AND ?
                                   GROUP BY ph.id
                                   ORDER BY ph.name ASC");
            $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
            $report_data = $stmt->fetchAll();
        } else {
            // Single Pharmacy
            $stmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? AND pharmacy_id = ?");
            $stmt->execute([$start_date, $end_date, $ph_id]);
            $rev = $stmt->fetchColumn() ?: 0;
            
            $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE date BETWEEN ? AND ? AND pharmacy_id = ?");
            $stmt->execute([$start_date, $end_date, $ph_id]);
            $exp = $stmt->fetchColumn() ?: 0;
            
            $report_data = [['pharmacy_name' => 'Current', 'revenue' => $rev, 'expenses' => $exp]];
        }
    } elseif ($type === 'stock') {
        $where_ph = $ph_id ? " AND m.pharmacy_id = $ph_id" : "";
        $stmt = $pdo->query("SELECT m.*, s.name as supplier_name, ph.name as pharmacy_name 
                             FROM medicines m 
                             JOIN pharmacies ph ON m.pharmacy_id = ph.id
                             LEFT JOIN suppliers s ON m.supplier_id = s.id 
                             WHERE m.quantity <= m.reorder_level $where_ph
                             ORDER BY ph.name ASC, m.quantity ASC");
        $report_data = $stmt->fetchAll();
    } elseif ($type === 'expiry') {
        $where_ph = $ph_id ? " AND m.pharmacy_id = $ph_id" : "";
        $stmt = $pdo->query("SELECT m.*, s.name as supplier_name, ph.name as pharmacy_name 
                             FROM medicines m 
                             JOIN pharmacies ph ON m.pharmacy_id = ph.id
                             LEFT JOIN suppliers s ON m.supplier_id = s.id 
                             WHERE m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) $where_ph
                             ORDER BY ph.name ASC, m.expiry_date ASC");
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
        <?php if ($type === 'finance'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Pharmacy</th>
                            <th>Revenue</th>
                            <th>Expenses</th>
                            <th class="text-end pe-4">Profit / Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_rev = 0; $total_exp = 0;
                        foreach ($report_data as $row): 
                            $profit = $row['revenue'] - $row['expenses'];
                            $total_rev += $row['revenue'];
                            $total_exp += $row['expenses'];
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo $row['pharmacy_name']; ?></td>
                            <td class="text-success"><?php echo format_currency($row['revenue']); ?></td>
                            <td class="text-danger"><?php echo format_currency($row['expenses'] ?: 0); ?></td>
                            <td class="text-end pe-4 fw-bold <?php echo ($profit >= 0) ? 'text-success' : 'text-danger'; ?>">
                                <?php echo format_currency($profit); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="ps-4 text-primary">TOTAL OVERVIEW</td>
                            <td class="text-success"><?php echo format_currency($total_rev); ?></td>
                            <td class="text-danger"><?php echo format_currency($total_exp); ?></td>
                            <td class="text-end pe-4 <?php echo ($total_rev - $total_exp >= 0) ? 'text-primary' : 'text-danger'; ?>">
                                <?php echo format_currency($total_rev - $total_exp); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php elseif (empty($report_data)): ?>
            <div class="p-5 text-center text-muted">No data found for this report.</div>
        <?php else: 
            // Group by pharmacy if Platform Admin, else flat list
            $grouped_report = [];
            if (!$ph_id) {
                foreach ($report_data as $row) {
                    $grouped_report[$row['pharmacy_name']][] = $row;
                }
            } else {
                $grouped_report['Local Report'] = $report_data;
            }

            foreach ($grouped_report as $ph_name => $rows):
        ?>
            <?php if (!$ph_id): ?>
            <div class="bg-light p-3 border-bottom fw-bold text-primary">
                <i class="fas fa-hospital me-2"></i> <?php echo $ph_name; ?>
            </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small">
                        <?php if ($type === 'sales'): ?>
                        <tr>
                            <th class="ps-4">Invoice #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Cashier</th>
                            <th class="text-end pe-4">Total</th>
                        </tr>
                        <?php elseif ($type === 'expenses'): ?>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th class="ps-4">Medicine Name</th>
                            <th>Supplier</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php if ($type === 'sales'): ?>
                            <td class="ps-4 fw-bold text-primary small"><?php echo $row['invoice_no']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></td>
                            <td><?php echo $row['customer_name']; ?></td>
                            <td><?php echo $row['cashier_name'] ?: 'Admin'; ?></td>
                            <td class="text-end pe-4 fw-bold"><?php echo format_currency($row['grand_total']); ?></td>
                            <?php elseif ($type === 'expenses'): ?>
                            <td class="ps-4"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo ucfirst($row['category']); ?></span></td>
                            <td><?php echo $row['description']; ?></td>
                            <td class="text-end pe-4 fw-bold text-danger"><?php echo format_currency($row['amount']); ?></td>
                            <?php else: ?>
                            <td class="ps-4 fw-bold"><?php echo $row['name']; ?></td>
                            <td><?php echo $row['supplier_name'] ?: 'None'; ?></td>
                            <td><?php echo format_currency($row['price']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td class="text-center pe-4">
                                <?php if ($type == 'stock'): ?>
                                    <span class="badge bg-danger">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Expiring: <?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; endif; ?>
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
