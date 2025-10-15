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

$stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id=?');
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { die('Kunde nicht gefunden.'); }

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }

    // Tabs unterscheiden: 'profile' oder 'password'
    $tab = $_POST['_tab'] ?? 'profile';

    if ($tab === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name && $email) {
            try {
                $upd = $pdo->prepare('UPDATE customers SET name=?, email=?, address=? WHERE customer_id=?');
                $upd->execute([$name, $email, $address, $id]);
                $msg = 'Kundendaten aktualisiert.';
                // neu laden
                $stmt->execute([$id]);
                $customer = $stmt->fetch();
            } catch (PDOException $e) {
                $err = 'Fehler: E-Mail evtl. bereits vergeben.';
            }
        } else {
            $err = 'Bitte Name und E-Mail angeben.';
        }
    } elseif ($tab === 'password') {
        $new = $_POST['new_password'] ?? '';
        $new2 = $_POST['new_password2'] ?? '';
        if ($new && $new === $new2 && strlen($new) >= 6) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE customers SET password=? WHERE customer_id=?');
            $upd->execute([$hash, $id]);
            $msg = 'Passwort aktualisiert.';
        } else {
            $err = 'Passwort ungültig oder stimmt nicht überein (min. 6 Zeichen).';
        }
    }
}

include 'header.php';
?>
<h2>Admin: Edit Customer #<?= (int)$customer['customer_id'] ?></h2>

<?php if ($msg): ?><div class="flash flash-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash flash-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
  <h3>Profil</h3>
  <form method="post">
    <?php csrf_field(); ?>
    <input type="hidden" name="_tab" value="profile">
    <label>Name
      <input name="name" value="<?= htmlspecialchars($customer['name']) ?>" required>
    </label>
    <label>E-Mail
      <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>
    </label>
    <label>Adress
      <textarea name="address"><?= htmlspecialchars($customer['address']) ?></textarea>
    </label>
    <button class="btn">Save</button>
    <a class="btn-link" href="customers_admin.php">Back</a>
  </form>
</div>

<div class="card">
  <h3>Set back Password</h3>
  <form method="post">
    <?php csrf_field(); ?>
    <input type="hidden" name="_tab" value="password">
    <label>New Password
      <input type="password" name="new_password" required>
    </label>
    <label>New Password (repeate)
      <input type="password" name="new_password2" required>
    </label>
    <button class="btn">Set Password </button>
  </form>
</div>

<?php include 'footer.php'; ?>
