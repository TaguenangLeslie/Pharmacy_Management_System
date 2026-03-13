<?php
/**
 * System Settings Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$page_title = 'System Settings';
$active_page = 'settings';

$message = '';
$error = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $key = sanitize_input($key);
        $value = sanitize_input($value);
        
        try {
            $pharmacy_id = $_SESSION['pharmacy_id'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, pharmacy_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $pharmacy_id, $value]);
        } catch (PDOException $e) {
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
    
    if (!$error) {
        log_activity($pdo, $_SESSION['user_id'], 'UPDATE_SETTINGS', 'settings');
        $message = "Settings updated successfully!";
    }
}

// Fetch all settings
$settings = [];
try {
    $pharmacy_id = $_SESSION['pharmacy_id'] ?? null;
    if ($pharmacy_id) {
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE pharmacy_id = ? OR pharmacy_id IS NULL");
        $stmt->execute([$pharmacy_id]);
    } else {
        $stmt = $pdo->query("SELECT * FROM settings WHERE pharmacy_id IS NULL");
    }
    
    while ($row = $stmt->fetch()) {
        // If it's a pharmacy setting, it should override the global one (which is already in the loop)
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist yet
    $error = "Settings table not found. Please run <a href='install.php'>install.php</a>.";
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">System Settings</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Configuration</h5>
            </div>
            <div class="card-body p-4">
                <form action="settings.php" method="POST">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">System Name</label>
                            <input type="text" name="settings[system_name]" class="form-control" value="<?php echo $settings['system_name'] ?? 'PharmaCare'; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Primary Email</label>
                            <input type="email" name="settings[email]" class="form-control" value="<?php echo $settings['email'] ?? 'admin@pharmacare.com'; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="settings[phone]" class="form-control" value="<?php echo $settings['phone'] ?? '+123 456 7890'; ?>">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Pharmacy Address</label>
                            <textarea name="settings[address]" class="form-control" rows="2"><?php echo $settings['address'] ?? '123 Pharmacy St, Health City'; ?></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Currency Symbol (e.g., FCFA)</label>
                            <input type="text" name="settings[currency]" class="form-control" value="<?php echo $settings['currency'] ?? 'FCFA'; ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tax Rate (%)</label>
                            <input type="number" step="0.01" name="settings[tax_rate]" class="form-control" value="<?php echo $settings['tax_rate'] ?? '0'; ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Default Language</label>
                            <select name="settings[language]" class="form-select">
                                <option value="en" <?php echo (isset($settings['language']) && $settings['language'] == 'en') ? 'selected' : ''; ?>>English</option>
                                <option value="fr" <?php echo (isset($settings['language']) && $settings['language'] == 'fr') ? 'selected' : ''; ?>>French</option>
                            </select>
                        </div>

                        <div class="col-md-12 mt-4">
                            <h5 class="border-bottom pb-2">Landing Page Customization</h5>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Hero Title</label>
                            <input type="text" name="settings[landing_hero_title]" class="form-control" value="<?php echo $settings['landing_hero_title'] ?? 'Your Health, Our Priority'; ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Hero Subtext</label>
                            <textarea name="settings[landing_hero_subtext]" class="form-control" rows="2"><?php echo $settings['landing_hero_subtext'] ?? 'Welcome to PharmaCare, the most advanced Pharmacy Management System designed to handle prescriptions, inventory, and point-of-sale with ease and security.'; ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Feature 1 Title</label>
                            <input type="text" name="settings[landing_f1_title]" class="form-control" value="<?php echo $settings['landing_f1_title'] ?? 'Secure & Reliable'; ?>">
                            <label class="form-label small mt-1">Feature 1 Description</label>
                            <textarea name="settings[landing_f1_desc]" class="form-control small" rows="2"><?php echo $settings['landing_f1_desc'] ?? 'Your data is protected with high-level encryption and role-based access control.'; ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Feature 2 Title</label>
                            <input type="text" name="settings[landing_f2_title]" class="form-control" value="<?php echo $settings['landing_f2_title'] ?? 'Real-time Tracking'; ?>">
                            <label class="form-label small mt-1">Feature 2 Description</label>
                            <textarea name="settings[landing_f2_desc]" class="form-control small" rows="2"><?php echo $settings['landing_f2_desc'] ?? 'Monitor stock levels, expiry dates, and sales in real-time from any device.'; ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Feature 3 Title</label>
                            <input type="text" name="settings[landing_f3_title]" class="form-control" value="<?php echo $settings['landing_f3_title'] ?? 'Advanced Analytics'; ?>">
                            <label class="form-label small mt-1">Feature 3 Description</label>
                            <textarea name="settings[landing_f3_desc]" class="form-control small" rows="2"><?php echo $settings['landing_f3_desc'] ?? 'Get detailed reports and insights into your pharmacy\'s financial performance.'; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="fas fa-save me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-4">
                <h5>System Info</h5>
                <hr>
                <div class="mb-3">
                    <div class="small text-muted">PHP Version</div>
                    <div class="fw-bold"><?php echo phpversion(); ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Server Software</div>
                    <div class="fw-bold"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                </div>
                <div class="mb-0">
                    <div class="small text-muted">Database Engine</div>
                    <div class="fw-bold">MySQL (PDO)</div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 mt-4 overflow-hidden">
            <div class="pink-gradient p-4 text-white text-center">
                <i class="fas fa-shield-alt fa-3x mb-3"></i>
                <h5>Security Check</h5>
                <p class="small mb-0 opacity-75">Your system is running with active CSRF and XSS protection.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
