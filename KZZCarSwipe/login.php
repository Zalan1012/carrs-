<?php
require 'includes/auth.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT user_id, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password'])) {
        log_in((int) $row['user_id'], !empty($_POST['remember']));
        header('Location: index.php');
        exit;
    }
    $error = 'Hibás e-mail cím vagy jelszó.';
}

$page_title = 'Bejelentkezés – CarSwipe';
$auth_page  = true;
require 'includes/header.php';
?>
<h1>Bejelentkezés</h1>
<p class="muted">Üdv újra! Folytasd a swipe-olást.</p>

<?php if ($error): ?>
    <div class="alert"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
    <?= csrf_field() ?>

    <label for="email">E-mail cím</label>
    <input type="email" id="email" name="email" placeholder="nev@email.hu" value="<?= e($email) ?>" required autofocus>

    <label for="password">Jelszó</label>
    <input type="password" id="password" name="password" required>

    <label class="check">
        <input type="checkbox" name="remember" value="1">
        Maradjak bejelentkezve
    </label>

    <button class="btn-dark btn-block">Bejelentkezés</button>
</form>

<p class="auth-foot">Nincs még fiókod? <a href="register.php">Regisztráció</a></p>

<?php require 'includes/footer.php'; ?>
