<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'session.php';
require_once 'config.php';

if (!is_admin()) {
    http_response_code(403);
    die('Zugriff verweigert: Adminrechte erforderlich.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die('Kein Kunde angegeben.'); }

// Prüfen, ob Bestellungen existieren
$cnt = $pdo->prepare('SELECT COUNT(*) AS c FROM orders WHERE customer_id=?');
$cnt->execute([$id]);
$has = (int)$cnt->fetch()['c'];

if ($has > 0) {
    // Löschen blockieren
    header('Location: customers_admin.php?err=delete_has_orders');
    exit;
}

// Wenn keine Orders -> löschen
$del = $pdo->prepare('DELETE FROM customers WHERE customer_id=?');
$del->execute([$id]);

header('Location: customers_admin.php?ok=deleted');
exit;
