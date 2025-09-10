<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireAuth() {
    if (!isset($_SESSION['admin_user']) || !$_SESSION['admin_user']) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_user']) && $_SESSION['admin_user'];
}

function getCurrentUser() {
    return $_SESSION['admin_user'] ?? null;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
