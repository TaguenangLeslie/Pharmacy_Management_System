<?php
/**
 * Register a New Pharmacy
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pharmacy_name = sanitize_input($_POST['pharmacy_name']);
    $address = sanitize_input($_POST['address']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $license_no = sanitize_input($_POST['license_no']);
    $pharmacy_type = sanitize_input($_POST['pharmacy_type']);
    
    // Legal fields
    $owner_full_name = sanitize_input($_POST['owner_full_name']);
    $business_reg_no = sanitize_input($_POST['business_reg_no']);
    $pharmacist_name = sanitize_input($_POST['pharmacist_name']);
    $pharmacist_license_no = sanitize_input($_POST['pharmacist_license_no']);

    $required_docs = ['owner_id_doc', 'pharmacy_license_doc', 'business_reg_doc', 'pharmacist_doc'];
    $uploaded_docs = [];
    $upload_error = false;

    if (empty($pharmacy_name) || empty($license_no) || empty($owner_full_name) || empty($business_reg_no)) {
        $error = "All business name, license numbers, and owner details are required.";
    } else {
        try {
            // Handle File Uploads
            $target_dir = "uploads/legal/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            foreach ($required_docs as $doc_key) {
                if (!isset($_FILES[$doc_key]) || $_FILES[$doc_key]['error'] !== UPLOAD_ERR_OK) {
                    $error = "Please upload all required legal documents for verification.";
                    $upload_error = true;
                    break;
                }
                
                // Security Validation
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $file_extension = strtolower(pathinfo($_FILES[$doc_key]["name"], PATHINFO_EXTENSION));
                $file_size = $_FILES[$doc_key]["size"];

                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = "Invalid file type for " . str_replace('_', ' ', $doc_key) . ". Only PDF, JPG, and PNG allowed.";
                    $upload_error = true;
                    break;
                }

                if ($file_size > 5 * 1024 * 1024) { // 5MB limit
                    $error = "File too large: " . str_replace('_', ' ', $doc_key) . " must be under 5MB.";
                    $upload_error = true;
                    break;
                }

                $new_filename = $doc_key . "_" . $_SESSION['user_id'] . "_" . time() . "." . $file_extension;
                $target_path = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES[$doc_key]["tmp_name"], $target_path)) {
                    $uploaded_docs[$doc_key] = $target_path;
                } else {
                    $error = "Failed to upload $doc_key. Please try again.";
                    $upload_error = true;
                    break;
                }
            }

            if (!$upload_error) {
                // Insert new pharmacy as pending with ALL legal data
                $stmt = $pdo->prepare("INSERT INTO pharmacies 
                    (name, address, phone, email, license_no, pharmacy_type, owner_id, owner_full_name, owner_id_doc, pharmacy_license_doc, business_reg_no, business_reg_doc, pharmacist_name, pharmacist_license_no, pharmacist_doc, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                
                $stmt->execute([
                    $pharmacy_name, $address, $phone, $email, $license_no, $pharmacy_type, $_SESSION['user_id'],
                    $owner_full_name, $uploaded_docs['owner_id_doc'], $uploaded_docs['pharmacy_license_doc'],
                    $business_reg_no, $uploaded_docs['business_reg_doc'],
                    $pharmacist_name, $pharmacist_license_no, $uploaded_docs['pharmacist_doc']
                ]);
                
                $pharmacy_id = $pdo->lastInsertId();
                $message = "Your high-security pharmacy registration request for '<b>$pharmacy_name</b>' has been submitted! Our team will verify your legal documents soon.";
                log_activity($pdo, $_SESSION['user_id'], 'REGISTER_PHARMACY', 'pharmacies', $pharmacy_id, null, "Submitted full legal registration for $pharmacy_name");
            }
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

$page_title = 'Register My Pharmacy';
include 'includes/templates/header.php';
?>

<div class="col-12 py-5">
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="pink-gradient p-5 text-center text-white">
                <h2 class="fw-bold mb-0">Launch Your Pharmacy</h2>
                <p class="mb-0 opacity-75">Fill the form below to join our platform.</p>
            </div>
            <div class="card-body p-4 p-md-5">
                
                <?php if ($message): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4 text-center py-4 mb-4" role="alert">
                    <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                    <h4>Request Submitted</h4>
                    <p class="mb-3"><?php echo $message; ?></p>
                    <a href="explore.php" class="btn btn-primary rounded-pill px-4">Back to Marketplace</a>
                </div>
                <?php else: ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form action="register_pharmacy.php" method="POST" enctype="multipart/form-data">
                    <h5 class="mb-4 text-primary border-bottom pb-2"><i class="fas fa-building me-2"></i> Basic Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Pharmacy Name <span class="text-danger">*</span></label>
                            <input type="text" name="pharmacy_name" class="form-control bg-light border-0" placeholder="e.g. City Health Pharmacy" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Pharmacy License No <span class="text-danger">*</span></label>
                            <input type="text" name="license_no" class="form-control bg-light border-0" placeholder="PH-123456" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Pharmacy Type</label>
                            <select name="pharmacy_type" class="form-select bg-light border-0">
                                <option value="Retail">Retail Pharmacy</option>
                                <option value="Wholesale">Wholesale/Distributor</option>
                                <option value="Hospital">Hospital Pharmacy</option>
                                <option value="Clinic">Clinic/Health Center</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Business Phone</label>
                            <input type="text" name="phone" class="form-control bg-light border-0" placeholder="+237 ...">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Full Address</label>
                        <textarea name="address" class="form-control bg-light border-0" rows="2" placeholder="Where is your pharmacy located?"></textarea>
                    </div>

                    <h5 class="mb-4 text-primary border-bottom pb-2 mt-5"><i class="fas fa-user-shield me-2"></i> Ownership & Identity</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Owner Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="owner_full_name" class="form-control bg-light border-0" placeholder="As seen on ID" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Upload ID Card (PDF/Image) <span class="text-danger">*</span></label>
                            <input type="file" name="owner_id_doc" class="form-control bg-light border-0" accept="image/*,.pdf" required>
                        </div>
                    </div>

                    <h5 class="mb-4 text-primary border-bottom pb-2 mt-5"><i class="fas fa-file-contract me-2"></i> Business & Legal</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Business Reg. No <span class="text-danger">*</span></label>
                            <input type="text" name="business_reg_no" class="form-control bg-light border-0" placeholder="RC/..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Reg. Certificate <span class="text-danger">*</span></label>
                            <input type="file" name="business_reg_doc" class="form-control bg-light border-0" accept="image/*,.pdf" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Operations License Document <span class="text-danger">*</span></label>
                        <input type="file" name="pharmacy_license_doc" class="form-control bg-light border-0" accept="image/*,.pdf" required>
                    </div>

                    <h5 class="mb-4 text-primary border-bottom pb-2 mt-5"><i class="fas fa-user-md me-2"></i> Technical/Lead Pharmacist</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Pharmacist Name</label>
                            <input type="text" name="pharmacist_name" class="form-control bg-light border-0" placeholder="Lead pharmacist name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Pharmacist License No</label>
                            <input type="text" name="pharmacist_license_no" class="form-control bg-light border-0" placeholder="License code">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Pharmacist Cert/License Doc <span class="text-danger">*</span></label>
                        <input type="file" name="pharmacist_doc" class="form-control bg-light border-0" accept="image/*,.pdf" required>
                    </div>

                    <div class="d-grid mt-5">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow">Submit Registration for Admin Review</button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <a href="explore.php" class="small text-decoration-none text-muted"><i class="fas fa-arrow-left me-1"></i> I'll do this later</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'includes/templates/footer.php'; ?>
