<?php
/**
 * Explore Pharmacies & Medicines
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = 'Explore Marketplace';
$active_page = 'explore';

// Fetch all active pharmacies
try {
    $stmt = $pdo->query("SELECT * FROM pharmacies WHERE status = 'active' ORDER BY name ASC");
    $pharmacies = $stmt->fetchAll();
} catch (PDOException $e) {
    $pharmacies = [];
}

include 'includes/templates/header.php';
?>

<div class="row pt-3 pb-2 mb-4 align-items-center">
    <div class="col-12 col-md-6">
        <h1 class="h2">Explore Marketplace</h1>
        <p class="text-muted">Browse partner pharmacies and discover available health products.</p>
    </div>
    <div class="col-12 col-md-6">
        <div class="input-group">
            <span class="input-group-text bg-white border-0 shadow-sm"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control border-0 shadow-sm" placeholder="Search for medicines or pharmacies...">
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php if (empty($pharmacies)): ?>
    <div class="col-12 text-center py-5">
        <i class="fas fa-store-slash fa-4x text-muted mb-3"></i>
        <h3>No pharmacies registered yet</h3>
        <p>Stay tuned! Our marketplace is growing.</p>
    </div>
    <?php else: foreach ($pharmacies as $p): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative hover-lift">
            <div class="pink-gradient p-4 text-center text-white" style="height: 120px;">
                <i class="fas fa-hospital-alt fa-4x opacity-25"></i>
            </div>
            <div class="card-body p-4 pt-1 text-center" style="margin-top: -50px;">
                <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow mb-3" style="width: 100px; height: 100px;">
                    <?php if ($p['logo']): ?>
                        <img src="uploads/logos/<?php echo $p['logo']; ?>" class="rounded-circle w-100 h-100 object-fit-cover">
                    <?php else: ?>
                        <i class="fas fa-mortar-pestle fa-3x text-primary"></i>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold"><?php echo $p['name']; ?></h4>
                <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $p['address'] ?: 'Online Pharmacy'; ?></p>
                <div class="d-grid">
                    <a href="inventory.php?pharma=<?php echo $p['id']; ?>" class="btn btn-outline-primary rounded-pill">
                        Browse Medicines
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 pink-gradient text-white p-5 text-center mb-5">
    <h2 class="fw-bold">Own a Pharmacy?</h2>
    <p class="lead mb-4">Join our platform to manage your stock, sales, and reach more customers.</p>
    <div class="d-flex justify-content-center gap-3">
        <a href="register_pharmacy.php" class="btn btn-white btn-lg px-5 rounded-pill">Register My Pharmacy</a>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
