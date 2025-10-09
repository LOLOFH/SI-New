<?php
require_once 'db.php';
require_once 'session.php';
$msg='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok()) { die('CSRF ungültig'); }
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE email=?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password'])) {
        unset($u['password']);
        $_SESSION['user'] = $u;
        header('Location: account.php');
        exit;
    } else {
        $msg='Login fehlgeschlagen.';
    }
}

include 'header.php';
?>
<h2>Login</h2>
<?php if ($msg): ?><div class="flash flash-err"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card">
    <?php csrf_field(); ?>
    <label>E-Mail<input type="email" name="email" required></label>
    <label>Passwort<input type="password" name="password" required></label>
    <button>Login</button>
</form>
<?php include 'footer.php'; ?>
