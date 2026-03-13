<?php
/**
 * Contact Support Page - saves messages to DB for admin review
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/helpers.php';

$page_title = 'Contact Support';
$success = false;
$error = '';

// Attempt to fetch System Name from settings
$system_name = APP_NAME;
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'system_name' AND pharmacy_id IS NULL");
    if ($row = $stmt->fetch()) {
        $system_name = $row['setting_value'];
    }
    // Ensure support_messages table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_name VARCHAR(150) NOT NULL,
        sender_email VARCHAR(150) NOT NULL,
        issue_type VARCHAR(100) DEFAULT 'General',
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_name  = sanitize_input($_POST['sender_name'] ?? '');
    $sender_email = sanitize_input($_POST['sender_email'] ?? '');
    $issue_type   = sanitize_input($_POST['issue_type'] ?? 'General');
    $message      = sanitize_input($_POST['message'] ?? '');

    if (empty($sender_name) || empty($sender_email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO support_messages (sender_name, sender_email, issue_type, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sender_name, $sender_email, $issue_type, $message]);
            $success = true;
        } catch (PDOException $e) {
            $error = 'Failed to send message. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - <?php echo $system_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark pink-gradient sticky-top shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <div class="bg-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                <i class="fas fa-hand-holding-medical text-primary fs-5"></i>
            </div>
            <?php echo $system_name; ?>
        </a>
        <div class="ms-auto d-flex align-items-center">
            <a href="login.php" class="btn btn-light rounded-pill px-4 shadow-sm text-primary fw-bold">Sign In <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if ($success): ?>
            <div class="alert alert-success rounded-4 shadow-sm border-0 text-center p-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="fw-bold">Message Sent!</h5>
                <p class="mb-0">Your message has been sent to our support team. We'll get back to you soon.</p>
                <a href="login.php" class="btn btn-success rounded-pill px-4 mt-3">Back to Login</a>
            </div>
            <?php else: ?>

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-5 pink-gradient text-white p-5 d-flex flex-column justify-content-center">
                        <h3 class="fw-bold mb-4">Get in Touch</h3>
                        <p class="mb-4">We're here to help! Whether you forgot your password or need assistance with your pharmacy account.</p>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-envelope fa-lg me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Email Support</h6>
                                <p class="small mb-0 opacity-75">support@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-phone-alt fa-lg me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Call directly</h6>
                                <p class="small mb-0 opacity-75">+1 (555) 123-4567</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock fa-lg me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Availability</h6>
                                <p class="small mb-0 opacity-75">Mon - Fri, 8am to 6pm</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 p-5 bg-white">
                        <h4 class="mb-4 fw-bold text-dark">Send a Message</h4>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form action="contact.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Your Name</label>
                                <input type="text" name="sender_name" class="form-control bg-light border-0 py-2" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Email Address</label>
                                <input type="email" name="sender_email" class="form-control bg-light border-0 py-2" placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['sender_email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Issue Type</label>
                                <select name="issue_type" class="form-select bg-light border-0 py-2">
                                    <option>Forgot Password / Account Recovery</option>
                                    <option>Pharmacy Verification Inquiry</option>
                                    <option>Technical Issue</option>
                                    <option>Billing Question</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase">Message</label>
                                <textarea name="message" class="form-control bg-light border-0" rows="4" placeholder="How can we help you today?" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                Send Message <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" class="text-muted text-decoration-none border-bottom border-secondary"><i class="fas fa-arrow-left me-1"></i> Return to Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
