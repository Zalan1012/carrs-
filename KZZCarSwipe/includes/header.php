<?php
// Közös fejléc. Betöltés előtt beállítható:
//   $page_title – a böngészőfül címe
//   $active     – az aktív menüpont fájlneve (pl. 'index.php')
//   $auth_page  – true a bejelentkezés / regisztráció oldalon (egyszerűsített fejléc)

$page_title = $page_title ?? 'CarSwipe';
$active     = $active ?? '';
$auth_page  = $auth_page ?? false;
$user       = current_user();

$menu = [
    'index.php'     => 'Swipe',
    'favorites.php' => 'Kedvencek',
    'messages.php'  => 'Üzenetek',
    'sell.php'      => 'Eladom az autóm',
];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="img/logo.webp">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">
        <img class="logo" src="img/logo.webp" alt="CarSwipe">
    </a>

    <?php if ($auth_page): ?>
        <span class="spacer"></span>
        <a class="link" href="index.php">← Vissza a swipe-hoz</a>
    <?php else: ?>
        <nav class="main-nav">
            <?php foreach ($menu as $file => $label): ?>
                <a href="<?= $file ?>" class="<?= $active === $file ? 'active' : '' ?>">
                    <?= $label ?>
                    <?php if ($file === 'favorites.php' && $user): ?>
                        (<span id="fav-count"><?= favorites_count((int) $user['user_id']) ?></span>)
                    <?php endif; ?>
                    <?php if ($file === 'messages.php' && $user): ?>
                        <?php $unread_total = unread_messages_count((int) $user['user_id']); ?>
                        <span class="badge" id="msg-count" <?= $unread_total ? '' : 'hidden' ?>><?= $unread_total ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <span class="spacer"></span>
        <?php if ($user): ?>
            <span class="user-name"><?= e($user['name']) ?></span>
            <a class="btn" href="logout.php">Kijelentkezés</a>
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
        <?php else: ?>
            <a class="btn" href="login.php">Bejelentkezés</a>
            <span class="avatar"></span>
        <?php endif; ?>
    <?php endif; ?>
</header>
<main class="<?= $auth_page ? 'auth-main' : 'page' ?>">
