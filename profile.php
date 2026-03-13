<?php
/**
 * User Profile Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_login();

$page_title = 'My Profile';
$active_page = 'profile';

$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    
    try {
        // Update basic info
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $user_id]);
        
        // Handle Avatar Upload
        if (!empty($_FILES['avatar']['name'])) {
            $target_dir = "uploads/avatars/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
            $file_name = "user_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$target_file, $user_id]);
                $_SESSION['avatar'] = $target_file;
            }
        }
        
        // Handle Password Change
        if (!empty($_POST['new_password'])) {
            if (password_verify($_POST['current_password'], $pdo->query("SELECT password_hash FROM users WHERE id = $user_id")->fetchColumn())) {
                $hashed_pass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hashed_pass, $user_id]);
                $message = "Profile and password updated successfully!";
            } else {
                $error = "Incorrect current password. Profile updated except password.";
            }
        } else {
            $message = "Profile updated successfully!";
        }
        
        $_SESSION['full_name'] = $full_name;
        log_activity($pdo, $user_id, 'UPDATE_PROFILE', 'users', $user_id);

    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include 'includes/templates/header.php';
?>

<div class="row pt-4">
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm text-center p-4 h-100">
            <div class="card-body">
                <div class="position-relative d-inline-block mb-4">
                    <img src="<?php echo $user['avatar'] ? BASE_URL . $user['avatar'] : 'https://via.placeholder.com/150'; ?>" class="rounded-circle shadow-sm border border-4 border-white" style="width: 150px; height: 150px; object-fit: cover;" alt="Avatar">
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-primary p-2">
                        <i class="fas fa-camera"></i>
                    </span>
                </div>
                <h4 class="fw-bold mb-1"><?php echo $user['full_name']; ?></h4>
                <p class="text-muted small mb-3"><?php echo ucfirst($user['role']); ?></p>
                <div class="badge bg-light text-dark border p-2 px-3 rounded-pill">
                    Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                </div>
                
                <hr class="my-4">
                
                <div class="text-start">
                    <div class="mb-3">
                        <div class="small text-muted">Username</div>
                        <div class="fw-bold"><?php echo $user['username']; ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted">Email Address</div>
                        <div class="fw-bold"><?php echo $user['email']; ?></div>
                    </div>
                    <div class="mb-0">
                        <div class="small text-muted">Last Login</div>
                        <div class="fw-bold"><?php echo $user['last_login'] ? date('M d, H:i', strtotime($user['last_login'])) : 'Never'; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Profile Details</h5>
                <i class="fas fa-user-edit text-primary"></i>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <h6 class="text-uppercase small fw-bold text-muted mb-3">Basic Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Change Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                    </div>
                    
                    <h6 class="text-uppercase small fw-bold text-muted mb-3 mt-5">Security & Password</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Current Password <small class="text-muted">(Required to change password)</small></label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mt-5 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-5 shadow-sm rounded-pill">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
