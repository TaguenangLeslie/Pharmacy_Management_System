<?php
/**
 * Mark Notifications as Read (AJAX)
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';

session_start();

if (is_logged_in()) {
    $now = date('Y-m-d H:i:s');
    $_SESSION['notifs_dismissed_at'] = time();
    $_SESSION['last_notif_dismissal'] = $now;

    try {
        $stmt = $pdo->prepare("UPDATE users SET last_notif_dismissal = ? WHERE id = ?");
        $stmt->execute([$now, $_SESSION['user_id']]);
    } catch (PDOException $e) {}

    // 2. If Platform Admin, mark all support messages as read in the DB
    if (has_role('admin') && !$_SESSION['pharmacy_id']) {
        try {
            $pdo->exec("UPDATE support_messages SET is_read = 1 WHERE is_read = 0");
        } catch (PDOException $e) {}
    }
}

echo json_encode(['success' => true]);
