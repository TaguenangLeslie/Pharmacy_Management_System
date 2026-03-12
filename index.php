<?php
/**
 * Professional Landing Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

// Fetch Dynamic Stats for the Landing Page
try {
    $total_medicines = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
    $total_sales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
} catch (PDOException $e) {
    $total_medicines = 0;
    $total_sales = 0;
    $total_customers = 0;
}

$page_title = 'Welcome to PharmaCare';
include 'includes/templates/header.php';
?>

<div class="col-12 px-0">
<!-- Hero Section -->
<div class="landing-hero pink-gradient text-white py-5 mb-5 rounded-4 shadow">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-3"><?php echo $app_settings['landing_hero_title'] ?? 'Your Health, Our Priority'; ?></h1>
                <p class="lead mb-4 opacity-75"><?php echo $app_settings['landing_hero_subtext'] ?? 'Welcome to PharmaCare, the most advanced Pharmacy Management System designed to handle prescriptions, inventory, and point-of-sale with ease and security.'; ?></p>
                <div class="d-grid d-md-flex gap-3">
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="btn btn-white btn-lg px-5">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-white btn-lg px-5">Get Started</a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-5">Learn More</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block text-center">
                <i class="fas fa-hand-holding-medical fa-10x opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<!-- Stats Counter -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card p-4 border-0 shadow-sm h-100">
                <div class="h1 fw-bold text-primary mb-0"><?php echo number_format($total_medicines); ?>+</div>
                <div class="text-muted small uppercase">Medicines in Stock</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-0 shadow-sm h-100">
                <div class="h1 fw-bold text-primary mb-0"><?php echo number_format($total_customers); ?>+</div>
                <div class="text-muted small uppercase">Happy Customers</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-0 shadow-sm h-100">
                <div class="h1 fw-bold text-primary mb-0"><?php echo number_format($total_sales); ?>+</div>
                <div class="text-muted small uppercase">Transactions Completed</div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div id="features" class="container py-5">
    <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">Why Choose <?php echo $system_name; ?>?</h2>
        <p class="text-muted">The ultimate tool for modern pharmacy management.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="text-center p-4">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-shield-alt fa-2x text-primary"></i>
                </div>
                <h4><?php echo $app_settings['landing_f1_title'] ?? 'Secure & Reliable'; ?></h4>
                <p class="text-muted"><?php echo $app_settings['landing_f1_desc'] ?? 'Your data is protected with high-level encryption and role-based access control.'; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-4">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-bolt fa-2x text-primary"></i>
                </div>
                <h4><?php echo $app_settings['landing_f2_title'] ?? 'Real-time Tracking'; ?></h4>
                <p class="text-muted"><?php echo $app_settings['landing_f2_desc'] ?? 'Monitor stock levels, expiry dates, and sales in real-time from any device.'; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center p-4">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-chart-pie fa-2x text-primary"></i>
                </div>
                <h4><?php echo $app_settings['landing_f3_title'] ?? 'Advanced Analytics'; ?></h4>
                <p class="text-muted"><?php echo $app_settings['landing_f3_desc'] ?? 'Get detailed reports and insights into your pharmacy\'s financial performance.'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="bg-white py-5 border-top border-bottom">
    <div class="container text-center py-4">
        <h2 class="fw-bold mb-4">Ready to Transform Your Pharmacy?</h2>
        <a href="login.php" class="btn btn-primary btn-lg px-5 shadow">Login Now</a>
    </div>
</div>

<style>
.landing-hero {
    min-height: 400px;
    background-size: cover;
    background-position: center;
}
.btn-outline-light {
    border-color: rgba(255,255,255,0.5);
}
.btn-outline-light:hover {
    background-color: white;
    color: var(--primary-color);
}
</style>
</div>

<?php include 'includes/templates/footer.php'; ?>
