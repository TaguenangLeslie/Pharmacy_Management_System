<?php
/**
 * Customer Registration Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username or email already exists.";
            } else {
                $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, is_active) VALUES (?, ?, ?, 'customer', ?, 1)");
                $stmt->execute([$username, $email, $hashed_pass, $full_name]);
                
                // Also add to customers table for tracking
                $stmt_cust = $pdo->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
                $stmt_cust->execute([$full_name, $email]);

                $message = "Registration successful! You can now login.";
            }
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

$page_title = 'Create Account';
include 'includes/templates/header.php';
?>

<div class="col-12">
<div class="row justify-content-center align-items-center min-vh-100 py-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="pink-gradient p-4 text-center text-white">
                <h2 class="fw-bold mb-0">Join <?php echo $system_name; ?></h2>
                <p class="mb-0 opacity-75">Create your account to start ordering.</p>
            </div>
            <div class="card-body p-4 p-md-5">
                
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                    <a href="login.php" class="alert-link">Login here</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control bg-light border-0" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="username" class="form-control bg-light border-0" placeholder="Choose username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control bg-light border-0" placeholder="your@email.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Password</label>
                            <input type="password" name="password" class="form-control bg-light border-0" placeholder="Min 6 chars" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control bg-light border-0" placeholder="Repeat password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid shadow-sm">
                        <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login here</a></p>
                    <a href="index.php" class="small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'includes/templates/footer.php'; ?>
