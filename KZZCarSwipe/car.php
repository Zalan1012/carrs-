<?php
require 'includes/auth.php';
require_login();

$me = current_user_id();

$stmt = $pdo->prepare(
    'SELECT c.*, u.name AS seller_name FROM cars c
     JOIN users u ON u.user_id = c.user_id
     WHERE c.car_id = ?'
);
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$car = $stmt->fetch();

if (!$car) {
    http_response_code(404);
    $page_title = 'Nincs ilyen hirdetés – CarSwipe';
    require 'includes/header.php';
    echo '<h1>Nincs ilyen hirdetés</h1><p class="muted">Lehet, hogy az eladó időközben törölte.</p><p><a href="index.php">← Vissza a swipe-hoz</a></p>';
    require 'includes/footer.php';
    exit;
}

$images = images_for_cars([$car['car_id']])[$car['car_id']] ?? [];
$title  = $car['brand'] . ' ' . $car['model'];
$mine   = (int) $car['user_id'] === $me;

$page_title = $title . ' – CarSwipe';
require 'includes/header.php';
?>
<p><a href="javascript:history.back()">← Vissza</a></p>

<div class="detail">
    <div>
        <div class="main-photo">
            <?php if ($images): ?>
                <img id="main-photo" src="<?= e($images[0]['file_path']) ?>" alt="<?= e($title) ?>">
                <?php if (count($images) > 1): ?>
                    <button type="button" class="photo-nav prev" id="photo-prev" aria-label="Előző kép">‹</button>
                    <button type="button" class="photo-nav next" id="photo-next" aria-label="Következő kép">›</button>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-photo">Nincs kép</div>
            <?php endif; ?>
        </div>

        <?php if (count($images) > 1): ?>
            <div class="thumbs">
                <?php foreach ($images as $image): ?>
                    <button type="button" data-src="<?= e($image['file_path']) ?>">
                        <img src="<?= e($image['file_path']) ?>" alt="">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <h1><?= e($title) ?></h1>
        <div class="price"><?= format_price($car['price']) ?></div>

        <table class="specs">
            <tr><th>Évjárat</th><td><?= (int) $car['year'] ?></td></tr>
            <tr><th>Futásteljesítmény</th><td><?= format_km($car['mileage']) ?></td></tr>
            <tr><th>Üzemanyag</th><td><?= e($car['fuel']) ?></td></tr>
            <tr><th>Váltó</th><td><?= e($car['transmission']) ?></td></tr>
            <tr><th>Karosszéria</th><td><?= e($car['body_type']) ?></td></tr>
            <tr><th>Teljesítmény</th><td><?= (int) $car['power'] ?> LE</td></tr>
            <tr><th>Eladó</th><td><?= e($car['seller_name']) ?></td></tr>
        </table>

        <?php if ($mine): ?>
            <a class="btn btn-dark" href="sell.php?edit=<?= (int) $car['car_id'] ?>">Hirdetés módosítása</a>
        <?php else: ?>
            <a class="btn btn-dark" href="messages.php?car=<?= (int) $car['car_id'] ?>">Üzenet az eladónak</a>
        <?php endif; ?>
    </div>
</div>

<h2 style="margin-top:32px">Leírás</h2>
<p class="description"><?= e($car['description'] ?: 'Nincs leírás.') ?></p>

<script>
// Képek lapozása: a nyilakkal vagy a kis képre kattintva
const photos = <?= json_encode(array_column($images, 'file_path'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let photoIndex = 0;

function showPhoto(index) {
    photoIndex = (index + photos.length) % photos.length; // körbe forog
    document.getElementById('main-photo').src = photos[photoIndex];
}

document.getElementById('photo-prev')?.addEventListener('click', () => showPhoto(photoIndex - 1));
document.getElementById('photo-next')?.addEventListener('click', () => showPhoto(photoIndex + 1));

document.querySelectorAll('.thumbs button').forEach((button, index) => {
    button.addEventListener('click', () => showPhoto(index));
});
</script>

<?php require 'includes/footer.php'; ?>
