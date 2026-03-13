<?php
/**
 * Contact Support Page
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/helpers.php';

$page_title = 'Contact Support';

// Attempt to fetch System Name from settings
$system_name = APP_NAME;
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'system_name' AND pharmacy_id IS NULL");
    if ($row = $stmt->fetch()) {
        $system_name = $row['setting_value'];
    }
} catch (PDOException $e) {}

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
                        <form action="contact.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Your Name</label>
                                <input type="text" class="form-control bg-light border-0 py-2" placeholder="John Doe" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Email Address</label>
                                <input type="email" class="form-control bg-light border-0 py-2" placeholder="john@example.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Issue Type</label>
                                <select class="form-select bg-light border-0 py-2">
                                    <option>Forgot Password / Account Recovery</option>
                                    <option>Pharmacy Verification Inquiry</option>
                                    <option>Technical Issue</option>
                                    <option>Billing Question</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase">Message</label>
                                <textarea class="form-control bg-light border-0" rows="4" placeholder="How can we help you today?" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm" onclick="event.preventDefault(); alert('Message sent successfully! Our team will contact you soon.'); window.location.href='index.php';">
                                Send Message <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="login.php" class="text-muted text-decoration-none border-bottom border-secondary"><i class="fas fa-arrow-left me-1"></i> Return to Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
