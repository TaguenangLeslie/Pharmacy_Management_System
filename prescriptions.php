<?php
/**
 * Prescription Management Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = 'Prescription Management';
$active_page = 'prescriptions';

$message = '';
$error = '';

// Handle Prescription Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['prescription_image'])) {
    $patient_name = sanitize_input($_POST['patient_name']);
    $patient_age = $_POST['patient_age'];
    $doctor_name = sanitize_input($_POST['doctor_name']);
    $prescription_date = $_POST['prescription_date'];
    
    $target_dir = "uploads/prescriptions/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["prescription_image"]["name"], PATHINFO_EXTENSION));
    $file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    if (in_array($file_extension, $allowed_types)) {
        if (move_uploaded_file($_FILES["prescription_image"]["tmp_name"], $target_file)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_name, patient_age, doctor_name, prescription_date, image_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$patient_name, $patient_age, $doctor_name, $prescription_date, $target_file]);
                $message = "Prescription uploaded successfully!";
                log_activity($pdo, $_SESSION['user_id'], 'UPLOAD_PRESCRIPTION', 'prescriptions', $pdo->lastInsertId());
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Error uploading file.";
        }
    } else {
        $error = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
    }
}

// Handle Status Update
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['update_status'];
    try {
        $stmt = $pdo->prepare("UPDATE prescriptions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = "Prescription status updated to " . ucfirst($status);
        log_activity($pdo, $_SESSION['user_id'], 'UPDATE_PRESCRIPTION_STATUS', 'prescriptions', $id, null, $status);
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Fetch Prescriptions
try {
    $stmt = $pdo->query("SELECT * FROM prescriptions ORDER BY created_at DESC");
    $prescriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $prescriptions = [];
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">Prescriptions</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i> Upload New Prescription
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($prescriptions)): ?>
    <div class="col-12 text-center py-5 text-muted">
        <i class="fas fa-file-medical fa-4x mb-3 opacity-25"></i>
        <p>No prescriptions found. Use the upload button to add one.</p>
    </div>
    <?php else: foreach ($prescriptions as $p): 
        $status_color = ($p['status'] == 'filled') ? 'success' : (($p['status'] == 'pending') ? 'warning' : 'danger');
    ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-img-top position-relative overflow-hidden" style="height: 200px; background: #eee;">
                <?php if (str_ends_with($p['image_path'], '.pdf')): ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <i class="fas fa-file-pdf fa-4x text-danger"></i>
                    </div>
                <?php else: ?>
                    <img src="<?php echo $p['image_path']; ?>" class="w-100 h-100 object-fit-cover" alt="Prescription Image" onerror="this.src='https://via.placeholder.com/400x200?text=No+Preview'">
                <?php endif; ?>
                <span class="badge bg-<?php echo $status_color; ?> position-absolute top-0 end-0 m-3 shadow-sm">
                    <?php echo ucfirst($p['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <h5 class="card-title fw-bold mb-1"><?php echo $p['patient_name']; ?></h5>
                <p class="text-muted small mb-3">
                    <i class="fas fa-user-md me-1"></i> Dr. <?php echo $p['doctor_name']; ?> | 
                    <i class="fas fa-calendar-alt ms-2 me-1"></i> <?php echo date('M d, Y', strtotime($p['prescription_date'])); ?>
                </p>
                
                <div class="d-flex justify-content-between align-items-center mt-auto">
                    <div class="btn-group">
                        <a href="<?php echo $p['image_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php if ($p['status'] == 'pending'): ?>
                        <a href="prescriptions.php?update_status=filled&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-check"></i> Fill
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Status
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="prescriptions.php?update_status=pending&id=<?php echo $p['id']; ?>">Pending</a></li>
                            <li><a class="dropdown-item" href="prescriptions.php?update_status=filled&id=<?php echo $p['id']; ?>">Filled</a></li>
                            <li><a class="dropdown-item" href="prescriptions.php?update_status=cancelled&id=<?php echo $p['id']; ?>">Cancelled</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Upload Prescription Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pink-gradient text-white">
                <h5 class="modal-title">Upload Prescription</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="prescriptions.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Patient Name</label>
                        <input type="text" name="patient_name" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Patient Age</label>
                            <input type="number" name="patient_age" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Prescription Date</label>
                            <input type="date" name="prescription_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Doctor Name</label>
                        <input type="text" name="doctor_name" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Upload Image/PDF</label>
                        <input type="file" name="prescription_image" class="form-control" accept="image/*,application/pdf" required>
                        <div class="form-text small">Accepted: JPG, PNG, PDF (Max 5MB)</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm">Upload & Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
