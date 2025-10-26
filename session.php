<?php
// session.php — central session and CSRF management

// Secure cookie parameters (before session_start()):
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_SESSION_ACTIVE !== session_status()) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax', // if necessary 'Strict'
    ]);
    session_start();
}

// ----- CSRF -----

/** Returns (and generates if required) the CSRF token of the session */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        // 32 Bytes → 64 hex chars
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Echoes a hidden field for the HTML form */
function csrf_field(): void {
    echo '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Checks CSRF token from POST field or header (X-CSRF-Token) */
function csrf_ok(): bool {
    if (PHP_SESSION_ACTIVE !== session_status()) session_start();
    $provided = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $stored   = $_SESSION['csrf'] ?? '';
    return is_string($provided) && is_string($stored) && $stored !== '' && hash_equals($stored, $provided);
}
