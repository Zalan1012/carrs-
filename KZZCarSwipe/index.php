<?php
require 'includes/auth.php';
require_login();

$me = current_user_id();

// ----- "Újrakezdés" gomb: a balra húzott autók újra megjelennek (a kedvencek megmaradnak) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare('DELETE FROM swipes WHERE user_id = ? AND liked = 0')->execute([$me]);
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM swipes WHERE user_id = ? AND liked = 0');
$stmt->execute([$me]);
$rejected_count = (int) $stmt->fetchColumn();

// ----- Szűrők beolvasása -----
$brand = trim($_GET['brand'] ?? '');
$fuels = array_values(array_intersect(FUELS, (array) ($_GET['fuel'] ?? [])));

// Az ár csúszka felső határa: a legdrágább hirdetés, millióra felkerekítve
$slider_max = (int) $pdo->query('SELECT COALESCE(MAX(price), 0) FROM cars')->fetchColumn();
$slider_max = max(1000000, (int) ceil($slider_max / 1000000) * 1000000);

$min = isset($_GET['min']) ? max(0, (int) $_GET['min']) : 0;
$max = isset($_GET['max']) ? min($slider_max, (int) $_GET['max']) : $slider_max;
if ($min > $max) {
    [$min, $max] = [$max, $min];
}

// ----- Autók lekérdezése: nem saját, még nem swipe-olt -----
$where  = ['c.user_id <> ?', 'c.car_id NOT IN (SELECT car_id FROM swipes WHERE user_id = ?)'];
$params = [$me, $me];

if ($brand !== '') {
    $where[]  = 'c.brand = ?';
    $params[] = $brand;
}
if ($min > 0) {
    $where[]  = 'c.price >= ?';
    $params[] = $min;
}
if ($max < $slider_max) {
    $where[]  = 'c.price <= ?';
    $params[] = $max;
}
if ($fuels) {
    $where[] = 'c.fuel IN (' . implode(',', array_fill(0, count($fuels), '?')) . ')';
    $params  = array_merge($params, $fuels);
}

$stmt = $pdo->prepare('SELECT c.* FROM cars c WHERE ' . implode(' AND ', $where) . ' ORDER BY c.letrehozva DESC, c.car_id DESC LIMIT 200');
$stmt->execute($params);
$cars = $stmt->fetchAll();

$images = images_for_cars(array_column($cars, 'car_id'));

// A JavaScriptnek átadott, megjelenítésre kész adatok
$cards = [];
foreach ($cars as $car) {
    $cards[] = [
        'id'          => (int) $car['car_id'],
        'title'       => $car['brand'] . ' ' . $car['model'],
        'price'       => format_price($car['price']),
        'tags'        => array_values(array_filter([
            $car['fuel'], $car['transmission'], format_km($car['mileage']), $car['year'] . '', $car['power'] . ' LE',
        ])),
        'description' => mb_strimwidth((string) $car['description'], 0, 140, '…'),
        'images'      => array_column($images[$car['car_id']] ?? [], 'file_path'),
    ];
}

$brands = $pdo->query('SELECT DISTINCT brand FROM cars ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'CarSwipe';
$active     = 'index.php';
require 'includes/header.php';
?>
<div class="swipe-layout">
    <form class="filters" method="get">
        <h2>Szűrők</h2>

        <label for="brand">Márka</label>
        <select id="brand" name="brand">
            <option value="">Összes márka</option>
            <?php foreach ($brands as $option): ?>
                <option <?= $option === $brand ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Ár (Ft)</label>
        <div class="price-range">
            <input type="range" id="min" name="min" min="0" max="<?= $slider_max ?>" step="100000" value="<?= $min ?>" aria-label="Legkisebb ár">
            <input type="range" id="max" name="max" min="0" max="<?= $slider_max ?>" step="100000" value="<?= $max ?>" aria-label="Legnagyobb ár">
        </div>
        <div class="price-labels"><span id="min-label"></span><span id="max-label"></span></div>

        <label>Üzemanyag</label>
        <div class="segments">
            <?php foreach (FUELS as $fuel): ?>
                <label>
                    <input type="checkbox" name="fuel[]" value="<?= e($fuel) ?>" <?= in_array($fuel, $fuels, true) ? 'checked' : '' ?>>
                    <span><?= e($fuel) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <button class="btn-dark btn-block">Szűrés</button>
        <?php if ($_GET): ?>
            <p style="text-align:center;margin:12px 0 0"><a href="index.php">Szűrők törlése</a></p>
        <?php endif; ?>
    </form>

    <div class="swipe-stage">
        <div class="swipe-card" id="card" hidden>
            <div class="swipe-photo" id="photo">
                <img id="photo-img" alt="" draggable="false">
                <div class="no-photo" id="no-photo">Nincs kép</div>
                <div class="photo-bars" id="photo-bars"></div>
                <button type="button" class="photo-nav prev" id="photo-prev" aria-label="Előző kép" hidden>‹</button>
                <button type="button" class="photo-nav next" id="photo-next" aria-label="Következő kép" hidden>›</button>
                <span class="stamp like" id="stamp-like">TETSZIK</span>
                <span class="stamp nope" id="stamp-nope">NEM</span>
            </div>
            <div class="swipe-info">
                <div class="title-row">
                    <span class="title" id="title"></span>
                    <span class="price" id="price"></span>
                </div>
                <div class="tags" id="tags"></div>
                <p id="description"></p>
                <a id="details" href="#">Részletek megnyitása →</a>
            </div>
        </div>

        <div class="swipe-empty" id="empty" hidden>
            <h2>Nincs több autó</h2>
            <p class="muted">Minden hirdetést megnéztél, ami a szűrőknek megfelel.<br>Módosítsd a szűrőket, vagy nézz vissza később.</p>
            <?php if ($rejected_count > 0): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <button class="btn-dark">Újrakezdés: balra húzott autók (<?= $rejected_count ?>) újra</button>
                </form>
                <p class="muted" style="font-size:13px;margin-bottom:0">A kedvenceid megmaradnak.</p>
            <?php endif; ?>
        </div>

        <div class="swipe-actions" id="actions">
            <button type="button" id="btn-nope" aria-label="Nem tetszik">✕</button>
            <button type="button" id="btn-like" class="btn-dark" aria-label="Tetszik">♥</button>
        </div>
        <p class="swipe-hint" id="hint">Húzd a kártyát, vagy billentyűk: ← Nem · → Tetszik</p>
    </div>
</div>

<script>
    const CARS = <?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const CSRF = <?= json_encode(csrf_token()) ?>;
</script>
<script src="js/swipe.js"></script>

<?php require 'includes/footer.php'; ?>
