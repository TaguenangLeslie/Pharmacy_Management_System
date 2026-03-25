<?php
session_start();
require_once 'includes/config/database.php';
require_once 'includes/functions/helpers.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;

    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$lang, $_SESSION['user_id']]);
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}

// Redirect back to referring page or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: " . $redirect);
exit();
