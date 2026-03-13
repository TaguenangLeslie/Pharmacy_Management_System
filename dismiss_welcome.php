<?php
session_start();
// Dismiss the welcome banner
$_SESSION['welcome_dismissed'] = true;
// Redirect back to referring page or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $redirect);
exit;
