<?php
require 'includes/auth.php';
require_login();

$me = current_user_id();

// Szív gomb: eltávolítás a kedvencek közül (a swipe törlődik, így az autó újra megjelenik a főoldalon)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare('DELETE FROM swipes WHERE user_id = ? AND car_id = ?');
    $stmt->execute([$me, (int) ($_POST['remove'] ?? 0)]);
    header('Location: favorites.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT c.* FROM swipes s
     JOIN cars c ON c.car_id = s.car_id
     WHERE s.user_id = ? AND s.liked = 1
     ORDER BY s.letrehozva DESC"
);
$stmt->execute([$me]);
$cars = $stmt->fetchAll();

$images = images_for_cars(array_column($cars, 'car_id'));

$page_title = 'Kedvencek – CarSwipe';
$active     = 'favorites.php';
require 'includes/header.php';
?>
<h1>Kedvencek <span class="muted" style="font-weight:400">(<?= count($cars) ?>)</span></h1>

<?php if (!$cars): ?>
    <p class="muted">Még nincs kedvenced. Húzz jobbra egy autót a <a href="index.php">Swipe</a> oldalon!</p>
<?php endif; ?>

<div class="card-grid">
    <?php foreach ($cars as $car): ?>
        <?php $cover = $images[$car['car_id']][0]['file_path'] ?? null; ?>
        <article class="car-card">
            <div class="photo">
                <?php if ($cover): ?>
                    <img src="<?= e($cover) ?>" alt="<?= e($car['brand'] . ' ' . $car['model']) ?>">
                <?php else: ?>
                    <div class="no-photo">Nincs kép</div>
                <?php endif; ?>
            </div>

            <form class="heart" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="remove" value="<?= (int) $car['car_id'] ?>">
                <button title="Eltávolítás a kedvencek közül" aria-label="Eltávolítás a kedvencek közül">♥</button>
            </form>

            <div class="body">
                <div class="title-row">
                    <span class="title"><?= e($car['brand'] . ' ' . $car['model']) ?>, <?= (int) $car['year'] ?></span>
                    <span class="price"><?= format_price($car['price']) ?></span>
                </div>
                <div class="tags">
                    <span><?= e($car['fuel']) ?></span>
                    <span><?= format_km($car['mileage']) ?></span>
                    <span><?= e($car['transmission']) ?></span>
                </div>
                <div class="actions">
                    <a class="btn btn-dark" href="messages.php?car=<?= (int) $car['car_id'] ?>">Üzenet</a>
                    <a class="btn" href="car.php?id=<?= (int) $car['car_id'] ?>">Részletek</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>
