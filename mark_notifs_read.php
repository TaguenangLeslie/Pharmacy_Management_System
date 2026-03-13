<?php
/**
 * Mark Notifications as Read (AJAX)
 */
session_start();
$_SESSION['notifs_dismissed_at'] = time();
echo json_encode(['success' => true]);
