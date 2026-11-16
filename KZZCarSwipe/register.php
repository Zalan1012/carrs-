<?php
require 'includes/auth.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Add meg a neved (legfeljebb 100 karakter).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Adj meg egy érvényes e-mail címet.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'A jelszónak legalább 8 karakterből kell állnia.';
    }
    if ($password !== $password2) {
        $errors[] = 'A két jelszó nem egyezik.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Ezzel az e-mail címmel már van fiók.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

        log_in((int) $pdo->lastInsertId());
        header('Location: index.php');
        exit;
    }
}

$page_title = 'Regisztráció – CarSwipe';
$auth_page  = true;
require 'includes/header.php';
?>
<h1>Regisztráció</h1>
<p class="muted">Hozz létre fiókot, és találd meg a következő autód.</p>

<?php if ($errors): ?>
    <div class="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post">
    <?= csrf_field() ?>

    <label for="name">Név</label>
    <input type="text" id="name" name="name" placeholder="Teljes név" maxlength="100" value="<?= e($name) ?>" required autofocus>

    <label for="email">E-mail cím</label>
    <input type="email" id="email" name="email" placeholder="nev@email.hu" maxlength="150" value="<?= e($email) ?>" required>

    <label for="password">Jelszó</label>
    <input type="password" id="password" name="password" placeholder="Legalább 8 karakter" minlength="8" required>
    <div class="strength" id="strength"><i></i><i></i><i></i><i></i></div>

    <label for="password2">Jelszó újra</label>
    <input type="password" id="password2" name="password2" minlength="8" required>

    <button class="btn-dark btn-block" style="margin-top:24px">Fiók létrehozása</button>
</form>

<p class="auth-foot">Van már fiókod? <a href="login.php">Bejelentkezés</a></p>

<script>
// Jelszóerősség-sáv: hossz, kis+nagybetű, szám, különleges karakter
const password = document.getElementById('password');
const bars = document.querySelectorAll('#strength i');

password.addEventListener('input', () => {
    const value = password.value;
    let score = 0;
    if (value.length >= 8) score++;
    if (/[a-zá-ű]/.test(value) && /[A-ZÁ-Ű]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^\wá-űÁ-Ű]/.test(value) || value.length >= 14) score++;
    bars.forEach((bar, index) => bar.classList.toggle('on', index < score));
});
</script>

<?php require 'includes/footer.php'; ?>
