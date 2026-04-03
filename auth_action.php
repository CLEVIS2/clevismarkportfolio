<?php

session_start();

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = trim($_POST['action'] ?? '');

if ($action === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start();
    session_regenerate_id(true);
    clevis_set_auth_flash('success', 'You have been signed out.');
    header('Location: admin-login');
    exit;
}
clevis_set_auth_flash('error', 'Unsupported request.');
header('Location: admin-login');
exit;
