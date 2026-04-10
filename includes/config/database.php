<?php
/**
 * Database Configuration and Constants
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_db');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', '');     // Default XAMPP password

// Dynamic Port Detection (3307 or 3306)
$ports = ['3307', '3306'];
$pdo = null;
$db_port = '3306';

foreach ($ports as $port) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=$port;dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db_port = $port;
        break; // Success!
    } catch (PDOException $e) {
        if ($port === end($ports)) {
            // Both failed
            die("Database Connection Error: Could not connect to " . DB_HOST . " on ports " . implode(', ', $ports) . ". " . $e->getMessage());
        }
        continue;
    }
}

define('DB_PORT', $db_port);



// Global Constants
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

define('APP_NAME', 'PharmaCare');
define('COPYRIGHT_HOLDER', 'Taguenang Leslie');
define('COPYRIGHT_YEAR', '2026');

// Robust Dynamic BASE_URL Detection
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Calculate the project's base directory relative to the document root safely
$doc_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
$proj_root = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$base_dir = str_ireplace($doc_root, '', $proj_root);
$base_url = $protocol . "://" . $host . rtrim($base_dir, '/') . "/";
define('BASE_URL', $base_url);

define('UPLOAD_DIR', __DIR__ . '/../../uploads/');

// Color Palette (Pink Theme)
define('COLOR_PRIMARY', '#FF69B4');   // Hot Pink
define('COLOR_SECONDARY', '#FFB6C1'); // Light Pink
define('COLOR_ACCENT', '#FF1493');    // Deep Pink
define('COLOR_BG', '#FFF0F5');        // Lavender Blush
