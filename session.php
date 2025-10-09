<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// CSRF-Helfer
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
function csrf_field() {
    $t = $_SESSION['csrf'] ?? '';
    echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($t).'">';
}
function csrf_ok() {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}
?>
