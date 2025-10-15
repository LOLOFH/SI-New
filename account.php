<?php
require_once 'auth.php';
require_login();
require_once 'db.php';

$user = current_user();
$msg='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name && $email) {
        $stmt=$pdo->prepare('UPDATE customers SET name=?, email=?, address=? WHERE customer_id=?');
        try {
            $stmt->execute([$name,$email,$address,$user['customer_id']]);
            $_SESSION['user']['name']=$name;
            $_SESSION['user']['email']=$email;
            $_SESSION['user']['address']=$address;
            $msg='Daten aktualisiert.';
        } catch (PDOException $e) {
            $msg='Fehler: E-Mail evtl. bereits vergeben.';
        }
    }
}

// Bestellungen laden
$stmt = $pdo->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY order_id DESC');
$stmt->execute([$user['customer_id']]);
$orders = $stmt->fetchAll();

include 'header.php';
?>
<h2>My Konto</h2>
<?php if ($msg): ?>
  <div class="flash <?= str_starts_with($msg,'Fehler')?'flash-err':'flash-ok' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div class="card">
<form method="post">
    <?php csrf_field(); ?>
    <label>Name<input name="name" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>" required></label>
    <label>E-Mail<input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" required></label>
    <label>Adress<textarea name="address"><?= htmlspecialchars($_SESSION['user']['address'] ?? '') ?></textarea></label>
    <button>Save</button>
</form>
</div>

<div class="card">
    <h3>My Order</h3>
    <table class="table">
        <tr><th>#</th><th>Date</th><th>Status</th><th>Summ</th><th>Details</th></tr>
        <?php foreach($orders as $o): ?>
            <tr>
                <td><?= (int)$o['order_id'] ?></td>
                <td><?= htmlspecialchars($o['order_date']) ?></td>
                <td><?= htmlspecialchars($o['status']) ?></td>
                <td><?= number_format($o['total_price'],2,',','.') ?> €</td>
                <td><a href="order_view.php?id=<?= (int)$o['order_id'] ?>">Show</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php include 'footer.php'; ?>
