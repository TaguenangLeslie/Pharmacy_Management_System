<?php
/**
 * Login Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $login_result = login_user($pdo, $username, $password);
        if ($login_result === true) {
            redirect('dashboard.php');
        } else {
            $error = $login_result;
        }
    }
}

$page_title = 'Login';
include 'includes/templates/header.php';
?>

<div class="col-12">
<div class="row justify-content-center align-items-center vh-100">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="pink-gradient p-5 text-center text-white">
                <h1 class="display-6 fw-bold mb-0"><?php echo $system_name; ?></h1>
                <p class="mb-0 opacity-75">Pharmacy Management System</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <h3 class="mb-4 text-center"><?php echo __('welcome'); ?>!</h3>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" class="form-control bg-light border-0" id="username" name="username" placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-primary"></i></span>
                            <input type="password" class="form-control bg-light border-0" id="password" name="password" placeholder="Enter password" required>
                        </div>
                    </div>
                    <div class="d-grid shadow-sm">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">Forgot password? <a href="contact.php" class="text-danger fw-bold text-decoration-none">Contact Support</a></p>
                    <p class="text-muted mt-2 border-top pt-3">Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Sign up here</a></p>
                    <a href="index.php" class="small text-decoration-none mt-2 d-block"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <p class="text-muted small">&copy; <?php echo date('Y'); ?> PharmaCare. All rights reserved.</p>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
