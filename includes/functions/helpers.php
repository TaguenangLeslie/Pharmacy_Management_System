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
    function log_activity($pdo, $user_id, $action, $table_name = null, $record_id = null, $old_val = null, $new_val = null, $pharmacy_id = null) {
        try {
            $pharm_id = $pharmacy_id ?? $_SESSION['pharmacy_id'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, pharmacy_id, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $action,
                $table_name,
                $record_id,
                $old_val,
                $new_val,
                $pharm_id,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (PDOException $e) {
            // Silently fail or log to file
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}
if (!function_exists('is_ajax')) {
    function is_ajax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * Clean up expired stock reservations and return stock to medicines
 */
if (!function_exists('cleanup_expired_reservations')) {
    function cleanup_expired_reservations($pdo) {
        try {
            // Find expired reservations
            $stmt = $pdo->query("SELECT id, medicine_id, quantity FROM cart_reservations WHERE expires_at < NOW()");
            $expired = $stmt->fetchAll();
            
            if (!empty($expired)) {
                $pdo->beginTransaction();
                foreach ($expired as $res) {
                    // 1. Return stock
                    $pdo->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?")->execute([$res['quantity'], $res['medicine_id']]);
                    // 2. Delete reservation
                    $pdo->prepare("DELETE FROM cart_reservations WHERE id = ?")->execute([$res['id']]);
                }
                $pdo->commit();
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Reservation cleanup failed: " . $e->getMessage());
        }
    }
}

/**
 * Clear reservations for a specific session (e.g. after checkout or manual clear)
 */
if (!function_exists('clear_session_reservations')) {
    function clear_session_reservations($pdo, $session_id) {
        try {
            $pdo->prepare("DELETE FROM cart_reservations WHERE session_id = ?")->execute([$session_id]);
        } catch (PDOException $e) {
            error_log("Session reservation clear failed: " . $e->getMessage());
        }
    }
}

/**
 * Debug logging for cart operations
 */
if (!function_exists('cart_log')) {
    function cart_log($msg) {
        $log_dir = dirname(dirname(__DIR__)) . '/tmp';
        if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
        $log_file = $log_dir . '/cart_debug.log';
        $log = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
        file_put_contents($log_file, $log, FILE_APPEND);
    }
}
