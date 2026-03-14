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
$hide_sidebar = true;
include 'includes/templates/header.php';
?>

<div class="col-12 px-0">
<!-- Premium Hero Section -->
<div class="landing-hero premium-gradient text-white py-5 position-relative overflow-hidden" style="min-height: 550px; background: linear-gradient(135deg, #1A1A1A 0%, #2D1B36 100%);">
    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
    <div class="container py-5 position-relative">
        <div class="row align-items-center py-5">
            <div class="col-lg-7 text-center text-lg-start">
                <div class="glass-card p-4 p-md-5 mb-4 border-0">
                    <h1 class="display-3 fw-bold mb-3">
                        <span class="text-white"><?php echo $app_settings['landing_hero_title'] ?? 'Digital Health'; ?></span>
                        <span class="text-gradient">Redefined.</span>
                    </h1>
                    <p class="lead mb-4 text-white-50"><?php echo $app_settings['landing_hero_subtext'] ?? 'The ultimate multi-tenant pharmacy ecosystem. Manage global inventory, digital prescriptions, and cross-branch sales with professional precision.'; ?></p>
                    <div class="d-flex flex-column flex-md-row justify-content-center justify-content-lg-start gap-3">
                        <?php if (is_logged_in()): ?>
                            <a href="dashboard.php" class="btn btn-primary btn-lg px-5 shadow-lg w-100 w-md-auto">Enter Console</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-lg px-5 shadow-lg w-100 w-md-auto">Get Started Free</a>
                            <a href="register.php" class="btn btn-outline-light btn-lg px-5 w-100 w-md-auto">Join Network</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hover-lift glass-card p-4 text-center">
                    <i class="fas fa-hand-holding-medical fa-10x text-gradient py-4"></i>
                    <div class="h4 text-white mt-3">PharmaCare v3.0</div>
                    <div class="text-white-50">Global Management Active</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Stats Counter -->
<div class="container" style="margin-top: -50px; position: relative; z-index: 5;">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="glass-card p-4 hover-lift h-100 bg-white">
                <div class="h1 fw-bold text-gradient mb-0"><?php echo number_format($total_medicines); ?>+</div>
                <div class="text-muted small fw-bold text-uppercase ls-1">Live Inventory</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 hover-lift h-100 bg-white">
                <div class="h1 fw-bold text-gradient mb-0"><?php echo number_format($total_customers); ?>+</div>
                <div class="text-muted small fw-bold text-uppercase ls-1">Empowered Users</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 hover-lift h-100 bg-white">
                <div class="h1 fw-bold text-gradient mb-0"><?php echo number_format($total_sales); ?>+</div>
                <div class="text-muted small fw-bold text-uppercase ls-1">Secured Orders</div>
            </div>
        </div>
    </div>
</div>

<!-- Integrated Ecosystem Section -->
<div id="features" class="container py-5 mt-5">
    <div class="text-center mb-5">
        <h2 class="display-5 fw-bold text-dark">One Platform. <span class="text-gradient">Every Pharmacy.</span></h2>
        <p class="text-muted lead mx-auto" style="max-width: 700px;">PharmaCare connects patients and providers through a seamless, secure, and transparent pharmaceutical network.</p>
    </div>
    
    <div class="row g-5">
        <div class="col-md-4 text-center px-4">
            <div class="glass-card p-5 hover-lift h-100 border-0 shadow-lg bg-white">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                    <i class="fas fa-shopping-basket fa-3x text-primary"></i>
                </div>
                <h4 class="fw-bold text-primary">Global Marketplace</h4>
                <p class="text-muted">Browse and order medicines from a network of verified pharmacies. Real-time availability at your fingertips.</p>
            </div>
        </div>
        <div class="col-md-4 text-center px-4">
            <div class="glass-card p-5 hover-lift h-100 border-0 shadow-lg" style="background: linear-gradient(180deg, rgba(255,255,255,1) 0%, rgba(255,105,180,0.1) 100%);">
                <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                    <i class="fas fa-file-prescription fa-3x text-info"></i>
                </div>
                <h4 class="fw-bold text-info">Smart Prescriptions</h4>
                <p class="text-muted">Upload your medical prescriptions digitally and choose the nearest pharmacy for instant fulfillment.</p>
            </div>
        </div>
        <div class="col-md-4 text-center px-4">
            <div class="glass-card p-5 hover-lift h-100 border-0 shadow-lg bg-white">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                    <i class="fas fa-shield-virus fa-3x text-success"></i>
                </div>
                <h4 class="fw-bold text-success">Branch Isolation</h4>
                <p class="text-muted">Strict multi-tenancy ensures data privacy. Every branch operates in its own secure, isolated environment.</p>
            </div>
        </div>
    </div>
</div>

<!-- Global Oversight Preview -->
<div class="bg-white py-5 mt-5 border-top border-bottom overflow-hidden position-relative shadow-sm">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0 pe-lg-5 text-center text-lg-start">
                <h2 class="display-6 fw-bold mb-4">Enterprise-Grade <span class="text-gradient">Administration</span></h2>
                <ul class="list-unstyled text-start d-inline-block d-lg-block">
                    <li class="mb-4 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="fas fa-desktop text-primary fa-fw"></i>
                        </div>
                        <span class="fs-5 text-dark">Global Order Monitoring & Control</span>
                    </li>
                    <li class="mb-4 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="fas fa-certificate text-primary fa-fw"></i>
                        </div>
                        <span class="fs-5 text-dark">Pharmacy License Verification Suite</span>
                    </li>
                    <li class="mb-4 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="fas fa-chart-line text-primary fa-fw"></i>
                        </div>
                        <span class="fs-5 text-dark">Unified Expense & Financial Mapping</span>
                    </li>
                    <li class="mb-4 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="fas fa-list-ul text-primary fa-fw"></i>
                        </div>
                        <span class="fs-5 text-dark">Immutable System-Wide Audit Logging</span>
                    </li>
                </ul>
            </div>
            <div class="col-lg-6 px-4 px-lg-2">
                <div class="glass-card p-2 shadow-lg rotate-lg-negative-3 bg-light border-0">
                    <img src="assets/images/dashboard_preview.png" alt="Dashboard Preview" class="img-fluid rounded-4 shadow">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="container py-5 mt-4 text-center">
    <div class="glass-card p-4 p-md-5 shadow-lg pink-gradient text-white border-0">
        <h2 class="display-5 fw-bold mb-4">Ready to Modernize Your Network?</h2>
        <p class="lead mb-5 opacity-75">Connect your pharmacy to the digital future today.</p>
        <div class="d-flex flex-column flex-md-row justify-content-center gap-4">
            <a href="register.php" class="btn btn-white btn-lg px-5 py-3 rounded-pill fw-bold w-100 w-md-auto text-primary shadow">Sign Up Now</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill fw-bold w-100 w-md-auto">Try Demo</a>
        </div>
    </div>
</div>

<style>
.ls-1 { letter-spacing: 1px; }
.rotate-lg-negative-3 { transform: rotate(-3deg); transition: transform 0.3s; }
.rotate-lg-negative-3:hover { transform: rotate(0deg); }
@media (max-width: 991px) {
    .rotate-lg-negative-3 { transform: none; }
}
</style>
</div>

<?php include 'includes/templates/footer.php'; ?>
