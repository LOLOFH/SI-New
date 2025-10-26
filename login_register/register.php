<?php
require_once __DIR__ . '/../db_settings/db.php';
require_once __DIR__ . '/../session.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($name && $email && $pass) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare('INSERT INTO customers(name,email,address,password) VALUES (?,?,?,?)');
            $stmt->execute([$name, $email, $address, $hash]);
            $msg = 'Registration successful. Please log in.';
        } catch (PDOException $e) {
            $msg = 'Error: Email may already be assigned.';
        }
    } else {
        $msg = 'Please fill in all mandatory fields.';
    }
}

include __DIR__ . '/../header.php';
?>
<h2>Registration</h2>
<?php if ($msg): ?>
  <div class="flash <?= str_starts_with($msg, 'Error') ? 'flash-err' : 'flash-ok' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>
<form method="post" class="card">
    <?php csrf_field(); ?>
    <label>Name<input name="name" required></label>
    <label>E-Mail<input type="email" name="email" required></label>
    <label>Adress<textarea name="address"></textarea></label>
    <label>Password<input type="password" name="password" required></label>
    <button>Registration</button>
</form>
<?php include __DIR__ . '/../footer.php'; ?>
