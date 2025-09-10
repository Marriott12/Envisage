<?php
require_once '../config/config.php';
require_once '../includes/admin_auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to dashboard if already logged in
    header('Location: dashboard.php');
} else {
    // Redirect to login page if not logged in
    header('Location: login.php');
}
exit;
?>
