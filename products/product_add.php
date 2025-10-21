<?php
// SI-NEW/products/product_add.php

declare(strict_types=1);

require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../db_settings/auth.php'; // stellt current_user(), is_admin() bereit
require_once __DIR__ . '/../session.php';

$pdo = getPDO();
$msg = '';
$err = '';

// ------- Zugriff: erlaubt wenn (nicht eingeloggt) ODER (Admin) -------
$cu = function_exists('current_user') ? current_user() : null;
$isAdmin = function_exists('is_admin') ? is_admin() : false;

// Wenn niemand eingeloggt ist, gilt man als Admin (wie gefordert)
$allowed = ($cu === null) || $isAdmin;

if (!$allowed) {
    http_response_code(403);
    die('Nur Admins dürfen Produkte hinzufügen.');
}

// ------- POST: anlegen -------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }

    // Eingaben einsammeln & säubern
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $erpId = trim($_POST['erp_id'] ?? '');
    $stock = $_POST['stock'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;

    // Basale Validierung
    if ($name === '') {
        $err = 'Bitte einen Namen angeben.';
    } elseif ($price === '' || !is_numeric(str_replace(',', '.', $price))) {
        $err = 'Bitte einen gültigen Preis angeben.';
    } elseif ($stock !== '' && !preg_match('/^-?\d+$/', (string)$stock)) {
        $err = 'Bitte einen gültigen Lagerbestand (Ganzzahl) angeben.';
    }

    if ($err === '') {
        // Preis vereinheitlichen (Komma -> Punkt) und auf 2 Nachkommastellen begrenzen
        $price = number_format((float)str_replace(',', '.', $price), 2, '.', '');

        // Stock normalisieren
        $stockVal = ($stock === '' ? 0 : (int)$stock);

        try {
            // INSERT mit optionalem erp_id
            $sql = "INSERT INTO products (erp_id, name, description, price, stock, active)
                    VALUES (:erp_id, :name, :description, :price, :stock, :active)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':erp_id'      => ($erpId !== '' ? $erpId : null),
                ':name'        => $name,
                ':description' => $desc,
                ':price'       => $price,
                ':stock'       => $stockVal,
                ':active'      => $active,
            ]);

            $msg = 'Produkt wurde angelegt.';
            // Formularfelder leeren für erneute Eingabe
            $name = $desc = $price = $erpId = '';
            $stock = '0';
            $active = 1;
        } catch (PDOException $e) {
            // Eindeutigkeit von erp_id abfangen
            if ($e->getCode() === '23000') {
                $err = 'Die angegebene ERP-ID existiert bereits.';
            } else {
                $err = 'Fehler beim Speichern: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

include __DIR__ . '/../header.php';
?>
<h2>Add Product</h2>

<?php if ($msg): ?>
    <div class="flash flash-ok"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="flash flash-error" style="background:#fee;border-left:4px solid #f66;padding:.5rem 1rem;margin:.75rem 0;">
        <?= nl2br(htmlspecialchars($err)) ?>
    </div>
<?php endif; ?>

<form method="post" class="card" style="max-width:650px;">
    <?php csrf_field(); ?>

    <div class="grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <label style="grid-column:1 / -1;">
            Name
            <input name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
        </label>

        <label style="grid-column:1 / -1;">
            Description
            <textarea name="description" rows="4"><?= htmlspecialchars($desc ?? '') ?></textarea>
        </label>

        <label>
            Price (€)
            <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($price ?? '') ?>" required>
        </label>

        <label>
            ERP-ID (optional)
            <input name="erp_id" value="<?= htmlspecialchars($erpId ?? '') ?>" maxlength="64">
        </label>

        <label>
            Stock
            <input type="number" name="stock" step="1" value="<?= htmlspecialchars(($stock === '' ? '0' : (string)$stock)) ?>">
        </label>

        <label style="display:flex;align-items:center;gap:.5rem;margin-top:1.7rem;">
            <input type="checkbox" name="active" value="1" <?= (isset($active) ? ($active ? 'checked' : '') : 'checked') ?>>
            Active
        </label>
    </div>

    <div style="margin-top:1rem;">
        <button>Save</button>
        <a class="btn-link" href="./products.php" style="margin-left:.5rem;">Zurück zur Übersicht</a>
    </div>
</form>

<?php include __DIR__ . '/../footer.php'; ?>
