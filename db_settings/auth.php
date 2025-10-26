<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        // Always use root path
        $loginPath = '/login_register/login.php';
        // Absolute URL inkl. Host/Port (funktioniert auch mit php -S localhost:8000)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url    = $scheme . '://' . $host . $loginPath;
        header('Location: ' . $url, true, 302);
        exit;
    }
}

function is_logged_in() { return !!current_user(); }

/**
* Admin logic:
* - If no one is logged in and DEV_ALLOW_GUEST_ADMIN === true -> Admin
* - Otherwise, Admin if email is in ADMIN_EMAILS
*/
function is_admin(): bool {
    $u = current_user();
    $allowGuest = $GLOBALS['DEV_ALLOW_GUEST_ADMIN'] ?? false;
    $emails = array_map('strtolower', $GLOBALS['ADMIN_EMAILS'] ?? []);
    if (!$u) return (bool)$allowGuest; // Gast = Admin (nur für DEV!)
    return in_array(strtolower($u['email'] ?? ''), $emails, true);
}
