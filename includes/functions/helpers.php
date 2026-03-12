<?php
/**
 * Generic Helper Functions
 */
require_once __DIR__ . '/lang.php';

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount) {
        global $app_settings;
        $currency = $app_settings['currency'] ?? 'FCFA';
        return number_format($amount, 0, '.', ' ') . ' ' . $currency;
    }
}

if (!function_exists('generate_invoice_no')) {
    function generate_invoice_no() {
        return 'INV-' . strtoupper(substr(uniqid(), -8));
    }
}

if (!function_exists('log_activity')) {
    function log_activity($pdo, $user_id, $action, $table_name = null, $record_id = null, $old_val = null, $new_val = null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $action,
                $table_name,
                $record_id,
                $old_val,
                $new_val,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (PDOException $e) {
            // Silently fail or log to file
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}
