<?php
require 'includes/auth.php';
require_login();

$me = current_user_id();

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // képenként legfeljebb 5 MB
const UPLOAD_DIR = 'uploads/';

/** Csak a saját, uploads mappába feltöltött képfájlt töröljük (a demo "Autok" képeket soha). */
function delete_image_file(string $file_path): void
{
    if (str_starts_with($file_path, UPLOAD_DIR) && is_file(__DIR__ . '/' . $file_path)) {
        unlink(__DIR__ . '/' . $file_path);
    }
}

/** A saját hirdetés lekérdezése; null, ha nem létezik vagy nem a miénk. */
function find_my_car(int $car_id, int $user_id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM cars WHERE car_id = ? AND user_id = ?');
    $stmt->execute([$car_id, $user_id]);
    return $stmt->fetch() ?: null;
}

/**
 * A feltöltött képek ellenőrzése és mentése az uploads mappába.
 * Visszaadja az elmentett fájlok útvonalait; a hibákat az $errors tömbbe írja.
 */
function save_uploaded_images(array $files, int $limit, array &$errors): array
{
    $saved = [];
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    foreach ($files['tmp_name'] ?? [] as $i => $tmp) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $name = $files['name'][$i];

        if (count($saved) >= $limit) {
            $errors[] = 'Legfeljebb ' . MAX_IMAGES . ' kép tartozhat egy hirdetéshez, a többit kihagytam.';
            break;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || $files['size'][$i] > MAX_UPLOAD_BYTES) {
            $errors[] = "A(z) „{$name}” kép feltöltése nem sikerült (legfeljebb 5 MB lehet).";
            continue;
        }
        // A fájl tényleges tartalmát nézzük, nem a nevét
        $info = @getimagesize($tmp);
        if (!$info || !isset($types[$info[2]])) {
            $errors[] = "A(z) „{$name}” nem JPG, PNG vagy WEBP kép.";
            continue;
        }

        $file_path = UPLOAD_DIR . bin2hex(random_bytes(12)) . '.' . $types[$info[2]];
        if (move_uploaded_file($tmp, __DIR__ . '/' . $file_path)) {
            $saved[] = $file_path;
        } else {
            $errors[] = "A(z) „{$name}” képet nem sikerült elmenteni.";
        }
    }
    return $saved;
}

$errors = [];
$form   = [
    'car_id' => 0, 'brand' => '', 'model' => '', 'year' => '', 'mileage' => '', 'fuel' => 'Benzin',
    'transmission' => 'Manuális', 'body_type' => 'Szedán', 'power' => '', 'price' => '',
    'description' => '',
];

// ---------------------------------------------------------------------------
// POST: törlés vagy mentés
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ha a feltöltés nagyobb, mint amit a PHP enged, a $_POST üresen érkezik
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = 'A feltöltött képek együtt túl nagyok. Próbáld kevesebb vagy kisebb képpel.';
    } else {
        csrf_check();
    }

    // ----- Hirdetés törlése -----
    if (!$errors && isset($_POST['delete'])) {
        $car = find_my_car((int) $_POST['delete'], $me);
        if ($car) {
            foreach (images_for_cars([$car['car_id']])[$car['car_id']] ?? [] as $image) {
                delete_image_file($image['file_path']);
            }
            // A képek és a swipe-ok sorai a külső kulcsok miatt (ON DELETE CASCADE) maguktól törlődnek
            $pdo->prepare('DELETE FROM cars WHERE car_id = ? AND user_id = ?')->execute([$car['car_id'], $me]);
        }
        header('Location: sell.php?deleted=1');
        exit;
    }

    // ----- Hirdetés mentése (új vagy módosítás) -----
    if (!$errors) {
        foreach ($form as $key => $default) {
            $form[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        $form['car_id'] = (int) $form['car_id'];

        $existing = $form['car_id'] ? find_my_car($form['car_id'], $me) : null;
        if ($form['car_id'] && !$existing) {
            $errors[] = 'Ez a hirdetés nem található.';
        }

        $next_year = (int) date('Y') + 1;
        if (!in_array($form['brand'], BRANDS, true)) {
            $errors[] = 'Válassz márkát.';
        }
        if ($form['model'] === '' || mb_strlen($form['model']) > 80) {
            $errors[] = 'Add meg a modellt (legfeljebb 80 karakter).';
        }
        if (!ctype_digit($form['year']) || $form['year'] < 1900 || $form['year'] > $next_year) {
            $errors[] = "Az évjárat 1900 és $next_year közötti szám legyen.";
        }
        if (!ctype_digit($form['mileage']) || $form['mileage'] > 2000000) {
            $errors[] = 'Add meg a futásteljesítményt km-ben (csak szám).';
        }
        if (!in_array($form['fuel'], FUELS, true)) {
            $errors[] = 'Válassz üzemanyagot.';
        }
        if (!in_array($form['transmission'], TRANSMISSIONS, true)) {
            $errors[] = 'Válassz váltót.';
        }
        if (!in_array($form['body_type'], BODY_TYPES, true)) {
            $errors[] = 'Válassz karosszériát.';
        }
        if (!ctype_digit($form['power']) || $form['power'] < 1 || $form['power'] > 2500) {
            $errors[] = 'Add meg a teljesítményt lóerőben (csak szám).';
        }
        if (!ctype_digit($form['price']) || $form['price'] < 1 || $form['price'] > 2000000000) {
            $errors[] = 'Add meg az árat forintban (csak szám).';
        }
        if (mb_strlen($form['description']) > 5000) {
            $errors[] = 'A leírás legfeljebb 5000 karakter lehet.';
        }

        if (!$errors) {
            $values = [
                $form['brand'], $form['model'], (int) $form['year'], (int) $form['mileage'], $form['fuel'],
                $form['transmission'], $form['body_type'], (int) $form['power'], (int) $form['price'],
                $form['description'],
            ];

            if ($existing) {
                $car_id = (int) $existing['car_id'];
                $pdo->prepare(
                    'UPDATE cars SET brand = ?, model = ?, year = ?, mileage = ?, fuel = ?, transmission = ?, body_type = ?,
                            power = ?, price = ?, description = ?
                     WHERE car_id = ? AND user_id = ?'
                )->execute([...$values, $car_id, $me]);
            } else {
                $pdo->prepare(
                    'INSERT INTO cars (brand, model, year, mileage, fuel, transmission, body_type,
                            power, price, description, user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([...$values, $me]);
                $car_id = (int) $pdo->lastInsertId();
            }

            // Törlésre jelölt régi képek eltávolítása
            $current   = images_for_cars([$car_id])[$car_id] ?? [];
            $to_delete = array_map('intval', (array) ($_POST['delete_images'] ?? []));
            $kept      = 0;
            foreach ($current as $image) {
                if (in_array((int) $image['image_id'], $to_delete, true)) {
                    $pdo->prepare('DELETE FROM car_images WHERE image_id = ? AND car_id = ?')->execute([$image['image_id'], $car_id]);
                    delete_image_file($image['file_path']);
                } else {
                    $kept++;
                }
            }

            // Új képek mentése a megmaradtak mögé
            $upload_errors = [];
            $new_files = save_uploaded_images($_FILES['photos'] ?? [], MAX_IMAGES - $kept, $upload_errors);
            $order = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM car_images WHERE car_id = ' . $car_id)->fetchColumn();
            foreach ($new_files as $file_path) {
                $pdo->prepare('INSERT INTO car_images (car_id, file_path, sort_order) VALUES (?, ?, ?)')
                    ->execute([$car_id, $file_path, ++$order]);
            }

            if ($upload_errors) {
                // A hirdetés mentve, de valamelyik képpel gond volt: a szerkesztőben mutatjuk meg
                $_SESSION['flash_errors'] = $upload_errors;
                header('Location: sell.php?edit=' . $car_id);
            } else {
                header('Location: sell.php?saved=1');
            }
            exit;
        }
    }
}

// ---------------------------------------------------------------------------
// GET: szerkesztés előtöltése
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $car = find_my_car((int) $_GET['edit'], $me);
    if ($car) {
        $form = array_intersect_key($car, $form);
    } else {
        $errors[] = 'Ez a hirdetés nem található.';
    }
}

if (!empty($_SESSION['flash_errors'])) {
    $errors = array_merge($errors, $_SESSION['flash_errors']);
    unset($_SESSION['flash_errors']);
}

$editing        = (int) $form['car_id'] > 0;
$editing_images = $editing ? (images_for_cars([(int) $form['car_id']])[(int) $form['car_id']] ?? []) : [];

// Saját hirdetések a jobb oldali oszlophoz
$stmt = $pdo->prepare('SELECT * FROM cars WHERE user_id = ? ORDER BY letrehozva DESC, car_id DESC');
$stmt->execute([$me]);
$my_cars   = $stmt->fetchAll();
$my_images = images_for_cars(array_column($my_cars, 'car_id'));

$page_title = 'Eladom az autóm – CarSwipe';
$active     = 'sell.php';
require 'includes/header.php';
?>
<h1><?= $editing ? 'Hirdetés módosítása' : 'Eladom az autóm' ?></h1>
<p class="muted">
    <?= $editing
        ? 'Írd át, amit szeretnél, majd mentsd el. <a href="sell.php">Mégse, új hirdetést adok fel</a>'
        : 'Töltsd ki az adatokat, és az autód megjelenik a swipe-ban.' ?>
</p>

<?php if (isset($_GET['saved'])): ?>
    <div class="notice">A hirdetés mentve, és megjelent a swipe-ban a többi felhasználónak.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="notice">A hirdetést töröltem.</div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="sell-layout">
    <form method="post" enctype="multipart/form-data" id="sell-form">
        <?= csrf_field() ?>
        <input type="hidden" name="car_id" value="<?= (int) $form['car_id'] ?>">

        <section class="box">
            <h2>Alapadatok</h2>
            <div class="cols-3">
                <div>
                    <label for="brand">Márka</label>
                    <select id="brand" name="brand" required>
                        <option value="">Válassz márkát</option>
                        <?php foreach (BRANDS as $brand): ?>
                            <option <?= $brand === $form['brand'] ? 'selected' : '' ?>><?= e($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="model">Modell</label>
                    <input type="text" id="model" name="model" maxlength="80" placeholder="Modell" value="<?= e($form['model']) ?>" required>
                </div>
                <div>
                    <label for="year">Évjárat</label>
                    <input type="number" id="year" name="year" min="1900" max="<?= date('Y') + 1 ?>" placeholder="2019" value="<?= e($form['year']) ?>" required>
                </div>
            </div>
            <div class="cols-3">
                <div>
                    <label for="mileage">Futásteljesítmény (km)</label>
                    <input type="number" id="mileage" name="mileage" min="0" placeholder="84000" value="<?= e($form['mileage']) ?>" required>
                </div>
            </div>
        </section>

        <section class="box">
            <h2>Műszaki adatok</h2>

            <label>Üzemanyag</label>
            <div class="segments">
                <?php foreach (FUELS as $fuel): ?>
                    <label>
                        <input type="radio" name="fuel" value="<?= e($fuel) ?>" <?= $fuel === $form['fuel'] ? 'checked' : '' ?>>
                        <span><?= e($fuel) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Váltó</label>
            <div class="segments">
                <?php foreach (TRANSMISSIONS as $transmission): ?>
                    <label>
                        <input type="radio" name="transmission" value="<?= e($transmission) ?>" <?= $transmission === $form['transmission'] ? 'checked' : '' ?>>
                        <span><?= e($transmission) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="cols-2">
                <div>
                    <label for="body_type" style="margin-top:16px">Karosszéria</label>
                    <select id="body_type" name="body_type">
                        <?php foreach (BODY_TYPES as $body_type): ?>
                            <option <?= $body_type === $form['body_type'] ? 'selected' : '' ?>><?= e($body_type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="power" style="margin-top:16px">Teljesítmény (LE)</label>
                    <input type="number" id="power" name="power" min="1" placeholder="150" value="<?= e($form['power']) ?>" required>
                </div>
            </div>
        </section>

        <section class="box">
            <h2>Ár és leírás</h2>
            <label for="price">Ár (Ft)</label>
            <input type="number" id="price" name="price" min="1" placeholder="Ár" value="<?= e($form['price']) ?>" required>

            <label for="description">Leírás</label>
            <textarea id="description" name="description" maxlength="5000" placeholder="Írd le az autó állapotát, felszereltségét..."><?= e($form['description']) ?></textarea>
        </section>

        <section class="box">
            <h2>Fotók</h2>
            <div class="dropzone" id="dropzone">
                <p>Húzd ide a képeket</p>
                <button type="button" id="browse">Tallózás</button>
                <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple hidden>
            </div>
            <p class="muted" style="margin:12px 0 0;font-size:13px">Legfeljebb <?= MAX_IMAGES ?> kép (JPG, PNG vagy WEBP, egyenként max. 5 MB). Az első kép lesz a borítókép.</p>
            <div class="photo-slots" id="photo-slots"></div>
            <div id="delete-inputs"></div>
        </section>

        <div class="form-actions">
            <button class="btn-dark"><?= $editing ? 'Módosítások mentése' : 'Hirdetés közzététele' ?></button>
        </div>
    </form>

    <aside class="my-ads">
        <h2>Hirdetéseim (<?= count($my_cars) ?>)</h2>

        <?php if (!$my_cars): ?>
            <p class="muted">Még nincs feltöltött hirdetésed.</p>
        <?php endif; ?>

        <?php foreach ($my_cars as $car): ?>
            <?php $cover = $my_images[$car['car_id']][0]['file_path'] ?? null; ?>
            <div class="my-ad <?= (int) $car['car_id'] === (int) $form['car_id'] ? 'editing' : '' ?>">
                <div class="photo">
                    <?php if ($cover): ?>
                        <img src="<?= e($cover) ?>" alt="">
                    <?php else: ?>
                        <div class="no-photo">Nincs kép</div>
                    <?php endif; ?>
                </div>
                <div class="body">
                    <div class="name"><?= e($car['brand'] . ' ' . $car['model']) ?>, <?= (int) $car['year'] ?></div>
                    <div class="meta">
                        <?= format_price($car['price']) ?> · <?= format_km($car['mileage']) ?>
                    </div>
                    <div class="actions">
                        <a class="btn btn-small" href="sell.php?edit=<?= (int) $car['car_id'] ?>">Módosítás</a>
                        <form method="post" onsubmit="return confirm('Biztosan törlöd ezt a hirdetést? Ez nem vonható vissza.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete" value="<?= (int) $car['car_id'] ?>">
                            <button class="btn-small">Törlés</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </aside>
</div>

<script>
// ---------- Fotók: tallózás, behúzás, előnézet, eltávolítás ----------
const MAX_IMAGES = <?= MAX_IMAGES ?>;
const MAX_BYTES = <?= MAX_UPLOAD_BYTES ?>;

// Szerkesztéskor a már feltöltött képek: {id, src, remove}
const savedImages = <?= json_encode(array_map(
    fn($image) => ['id' => (int) $image['image_id'], 'src' => $image['file_path'], 'remove' => false],
    $editing_images
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

let newFiles = []; // most kiválasztott, még fel nem töltött fájlok

const fileInput = document.getElementById('photos');
const dropzone = document.getElementById('dropzone');
const slots = document.getElementById('photo-slots');
const deleteInputs = document.getElementById('delete-inputs');

function keptCount() {
    return savedImages.filter(image => !image.remove).length;
}

function addFiles(fileList) {
    let skipped = false;
    for (const file of fileList) {
        const okType = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
        if (!okType || file.size > MAX_BYTES || keptCount() + newFiles.length >= MAX_IMAGES) {
            skipped = true;
            continue;
        }
        newFiles.push(file);
    }
    if (skipped) {
        alert('Néhány képet kihagytam: legfeljebb ' + MAX_IMAGES + ' kép tölthető fel (JPG, PNG vagy WEBP, egyenként max. 5 MB).');
    }
    render();
}

// A kiválasztott fájlokat visszaírjuk a fájlmezőbe, mert az űrlap azt küldi el
function syncFileInput() {
    const transfer = new DataTransfer();
    newFiles.forEach(file => transfer.items.add(file));
    fileInput.files = transfer.files;
}

function makeSlot(src, caption, onRemove, marked) {
    const slot = document.createElement('div');
    slot.className = 'photo-slot' + (marked ? ' marked' : '');
    if (src) {
        const img = document.createElement('img');
        img.src = src;
        img.alt = '';
        slot.append(img);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'remove';
        button.textContent = marked ? '↺' : '✕';
        button.title = marked ? 'Mégis megtartom' : 'Kép eltávolítása';
        button.addEventListener('click', onRemove);
        slot.append(button);
    }
    if (caption) {
        const cap = document.createElement('span');
        cap.className = 'cap';
        cap.textContent = caption;
        slot.append(cap);
    }
    return slot;
}

function render() {
    syncFileInput();
    slots.innerHTML = '';
    deleteInputs.innerHTML = '';
    let coverDone = false;
    let used = 0;

    savedImages.forEach(image => {
        let caption = '';
        if (image.remove) {
            caption = 'Törlésre jelölve';
            deleteInputs.insertAdjacentHTML('beforeend', `<input type="hidden" name="delete_images[]" value="${image.id}">`);
        } else if (!coverDone) {
            caption = 'Borítókép';
            coverDone = true;
        }
        slots.append(makeSlot(image.src, caption, () => {
            // Visszavonni csak akkor lehet, ha még van hely
            if (image.remove && keptCount() + newFiles.length >= MAX_IMAGES) return;
            image.remove = !image.remove;
            render();
        }, image.remove));
        used++;
    });

    newFiles.forEach((file, index) => {
        const caption = coverDone ? 'Új kép' : 'Borítókép';
        coverDone = true;
        slots.append(makeSlot(URL.createObjectURL(file), caption, () => {
            newFiles.splice(index, 1);
            render();
        }, false));
        used++;
    });

    for (; used < MAX_IMAGES; used++) {
        slots.append(makeSlot(null, coverDone ? '' : 'Borítókép', null, false));
        coverDone = true;
    }
}

document.getElementById('browse').addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => addFiles(fileInput.files));

['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => {
    event.preventDefault();
    dropzone.classList.add('over');
}));
['dragleave', 'drop'].forEach(type => dropzone.addEventListener(type, event => {
    event.preventDefault();
    dropzone.classList.remove('over');
}));
dropzone.addEventListener('drop', event => addFiles(event.dataTransfer.files));

render();
</script>

<?php require 'includes/footer.php'; ?>
