<?php
// products/products.php

// Auth zuerst laden (stellt Session, current_user(), is_admin(), csrf_* bereit)
require_once __DIR__ . '/../db_settings/auth.php';
// DB + Config
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../db_settings/config.php';

include __DIR__ . '/../header.php';

// --- Helper: Import-Basisverzeichnis robust auflösen -------------------------
function resolve_import_base(): ?string {
    $candidates = [
        realpath(__DIR__ . '/../file_exchange_management'),
        realpath(__DIR__ . '/../file_excange_management'), // Fallback: Tippfehler-Ordner
    ];
    foreach ($candidates as $p) {
        if ($p !== false && is_dir($p)) return $p;
    }
    return null;
}

// --- Import / Pull anstoßen (nur Admin) ------------------------------------
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    if (!csrf_ok()) {
        $flash = 'CSRF ungültig.';
    } else {
        $action = $_POST['do'] ?? '';
        $base   = resolve_import_base();

        if (!$base) {
            $flash = 'Import-Verzeichnis nicht gefunden.';
        } else {
            if ($action === 'pull') {
                // ERP (CAP) -> incoming
                $cmd = 'php ' . escapeshellarg($base . '/pull_from_cap.php') . ' 2>&1';
                $out = []; $ret = 0;
                exec($cmd, $out, $ret);
                $flash = $ret === 0
                    ? 'ERP-Daten erfolgreich abgerufen.'
                    : ('ERP-Pull FEHLGESCHLAGEN: ' . htmlspecialchars(implode("\n", $out)));
            } elseif ($action === 'import') {
                // incoming -> DB
                $cmd = 'php ' . escapeshellarg($base . '/import_products.php') . ' 2>&1';
                $out = []; $ret = 0;
                exec($cmd, $out, $ret);
                $flash = $ret === 0
                    ? 'Produktdaten erfolgreich importiert.'
                    : ('Import FEHLGESCHLAGEN: ' . htmlspecialchars(implode("\n", $out)));
            }
        }
    }
}

// --- Produkte laden (nur aktive & verfügbare) -------------------------------
$pdo = getPDO();
$sql = 'SELECT * FROM products WHERE active = 1 AND stock > 0 ORDER BY product_id DESC';
$products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Letztes Import-Log ermitteln (optional Link anzeigen) ------------------
function latest_import_log_path(): ?string {
    $cand = [
        __DIR__ . '/../file_exchange_management/logs',
        __DIR__ . '/../file_excange_management/logs', // Fallback
    ];
    $logDir = null;
    foreach ($cand as $d) { if (is_dir($d)) { $logDir = $d; break; } }
    if (!$logDir) return null;

    $logs = glob($logDir . '/importer-*.log') ?: [];
    if (!$logs) return null;
    rsort($logs, SORT_NATURAL);
    return $logs[0] ?? null;
}
$latestLog = latest_import_log_path();

// Versuch, einen Web-Pfad fürs Log zu bauen (nur wenn Logs webzugänglich sind)
$latestLogWeb = null;
if ($latestLog && isset($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $normalized = str_replace('\\', '/', $latestLog);
    if (str_starts_with($normalized, $docRoot)) {
        $latestLogWeb = substr($normalized, strlen($docRoot)) ?: null;
    }
}
?>
<h2>Products</h2>

<?php if ($flash): ?>
    <div class="alert" style="background:#eef;border-left:4px solid #88f;padding:.5rem 1rem;margin:1rem 0;">
        <?= nl2br(htmlspecialchars($flash)) ?>
    </div>
<?php endif; ?>

<?php if (is_admin()): ?>
    <div class="toolbar" style="margin:1rem 0; display:flex; gap:.5rem; flex-wrap:wrap;">
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="do" value="pull">
            <button type="submit">Vom ERP ziehen (CAP → incoming)</button>
        </form>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="do" value="import">
            <button type="submit">Import starten (incoming → DB)</button>
        </form>
        <?php if ($latestLogWeb): ?>
            <a class="btn-link" href="<?= htmlspecialchars($latestLogWeb) ?>" target="_blank">Letztes Import-Log öffnen</a>
        <?php endif; ?>
        <a class="btn" href="product_add.php">+ New Product</a>
    </div>
<?php else: ?>
    <a class="btn" href="product_add.php">+ New Product</a>
<?php endif; ?>

<div class="grid">
<?php foreach ($products as $p): ?>
    <div class="card">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
        <p><strong><?= number_format((float)$p['price'], 2, ',', '.') ?> €</strong></p>

        <form method="post" action="../order_process/cart.php?action=add">
            <?php csrf_field(); ?>
            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
            <input type="number" name="quantity" min="1" value="1" required>
            <button>Add to Basket</button>
        </form>

        <?php if (is_admin()): ?>
            <form method="post" action="../order_process/cart.php?action=remove" style="margin-top:.5rem;display:inline;">
                <?php csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                <button type="submit" class="btn-link" style="background:none;border:none;padding:0;color:#06c;cursor:pointer;">
                    Remove from Basket
                </button>
            </form>
            ·
            <a class="btn-link" href="product_edit.php?id=<?= (int)$p['product_id'] ?>">Edit</a>
            ·
            <a class="btn-link" href="product_delete.php?id=<?= (int)$p['product_id'] ?>" onclick="return confirm('Produkt wirklich löschen?');">Delete</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
