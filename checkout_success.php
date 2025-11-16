<?php
// checkout_success.php - Bestätigungsseite nach erfolgreicher Bestellung

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/cart_handler.php';

// Prüfe, ob Bestellung erfolgreich war
if (empty($_SESSION['orderSuccess'])) {
    header('Location: /index.php');
    exit;
}

$orderTotal = $_SESSION['orderTotal'] ?? 0;
$orderItemsCount = $_SESSION['orderItemsCount'] ?? 0;

// Cleanup session
unset($_SESSION['orderSuccess']);
unset($_SESSION['orderTotal']);
unset($_SESSION['orderItemsCount']);

include 'header.php';
?>

<div class="card success-card" style="text-align:center;background:#ecfdf5;border:2px solid #86efac;padding:40px;">
    <h2 style="color:#16a34a;margin-top:0;font-size:32px;">✓ Bestellung erfolgreich!</h2>
    <p style="font-size:18px;color:#666;margin:20px 0;">
        Vielen Dank für Ihren Einkauf.
    </p>
    
    <div style="background:#f0fdf4;padding:24px;border-radius:8px;margin:24px 0;max-width:500px;margin-left:auto;margin-right:auto;">
        <div style="margin:16px 0;">
            <p style="color:#666;margin:0 0 8px 0;">Anzahl Artikel:</p>
            <p style="font-size:24px;font-weight:700;color:#16a34a;margin:0;"><?php echo $orderItemsCount; ?></p>
        </div>
        
        <div style="margin:16px 0;padding-top:16px;border-top:2px solid #d1fae5;">
            <p style="color:#666;margin:0 0 8px 0;">Gesamtbetrag:</p>
            <p style="font-size:28px;font-weight:700;color:#2563eb;margin:0;">
                <?php echo number_format($orderTotal, 2, ',', '.'); ?> EUR
            </p>
        </div>
    </div>
    
    <p style="font-size:14px;color:#666;margin:20px 0;">
        Eine Bestellbestätigung wird an Ihre E-Mail-Adresse gesendet.
    </p>
    
    <div style="display:flex;gap:12px;justify-content:center;margin-top:32px;">
        <a href="/index.php" class="btn btn-link" style="text-decoration:none;">
            Zur Startseite
        </a>
        <a href="/sap_main/products.php" class="btn btn-primary" style="text-decoration:none;">
            Weiter einkaufen
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>
