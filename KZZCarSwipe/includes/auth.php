<?php
// Közös függvények: session, bejelentkezés, CSRF védelem, segédfüggvények.
// Minden oldal ezt tölti be legelőször.

require_once __DIR__ . '/db.php';

const REMEMBER_SECONDS = 60 * 60 * 24 * 30; // "Maradjak bejelentkezve": 30 nap

const BRANDS = ['Alfa Romeo', 'Audi', 'BMW', 'Chevrolet', 'Citroën', 'Dacia', 'Ferrari', 'Fiat', 'Ford', 'Honda', 'Hyundai',
    'Kia', 'Lexus', 'Mazda', 'Mercedes-Benz', 'Mini', 'Mitsubishi', 'Nissan', 'Opel', 'Peugeot', 'Porsche',
    'Renault', 'Seat', 'Skoda', 'Subaru', 'Suzuki', 'Tesla', 'Toyota', 'Volkswagen', 'Volvo', 'Egyéb'];
const FUELS = ['Benzin', 'Dízel', 'Hibrid', 'Elektromos'];
const TRANSMISSIONS = ['Manuális', 'Automata'];
const BODY_TYPES = ['Szedán', 'Kombi', 'Ferdehátú', 'SUV', 'Kupé', 'Kabrió', 'Egyterű', 'Pickup', 'Egyéb'];
const MAX_IMAGES = 4;

ini_set('session.gc_maxlifetime', (string) REMEMBER_SECONDS);
session_name('KZZCARSWIPE');
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

/** HTML-be írás előtt minden adatot ezzel kell "ártalmatlanítani". */
function e($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/** A bejelentkezett felhasználó sora (user_id, name, email), vagy null. */
function current_user(): ?array
{
    global $pdo;
    static $user = false;

    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = $pdo->prepare('SELECT user_id, name, email FROM users WHERE user_id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if ($user === null) {
                unset($_SESSION['user_id']); // a fiók időközben megszűnt
            }
        }
    }
    return $user;
}

function current_user_id(): int
{
    $user = current_user();
    return $user ? (int) $user['user_id'] : 0;
}

/** Védett oldalak elején: ha nincs belépve, a bejelentkezésre irányít. */
function require_login(): void
{
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

/** Sikeres belépés / regisztráció után hívjuk. */
function log_in(int $user_id, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;

    if ($remember) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + REMEMBER_SECONDS,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Rejtett űrlapmező a CSRF tokennel. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/** Minden POST kérés elején ellenőrizzük, hogy a saját űrlapunkról jött-e. */
function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Érvénytelen kérés. Töltsd újra az oldalt, és próbáld meg ismét.');
    }
}

function format_price($price): string
{
    return number_format((int) $price, 0, ',', '.') . ' Ft';
}

function format_km($km): string
{
    return number_format((int) $km, 0, ',', ' ') . ' km';
}

/** Hány autót húzott jobbra a felhasználó. */
function favorites_count(int $user_id): int
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM swipes WHERE user_id = ? AND liked = 1'
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/** Hány olvasatlan üzenete van a felhasználónak (érdeklődőként és eladóként együtt). */
function unread_messages_count(int $user_id): int
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM messages m
         JOIN cars c ON c.car_id = m.car_id
         WHERE m.is_read = 0 AND m.letrehozva <= NOW()
           AND ((m.buyer_id = ? AND m.from_buyer = 0) OR (c.user_id = ? AND m.from_buyer = 1))'
    );
    $stmt->execute([$user_id, $user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Több autó képeinek lekérdezése egyszerre.
 * Visszaad: [car_id => [ ['image_id' => .., 'file_path' => ..], ... ]], borítókép elöl.
 */
function images_for_cars(array $car_ids): array
{
    global $pdo;
    if (!$car_ids) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($car_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT image_id, car_id, file_path FROM car_images
         WHERE car_id IN ($marks) ORDER BY sort_order, image_id"
    );
    $stmt->execute(array_values($car_ids));

    $images = [];
    foreach ($stmt as $row) {
        $images[$row['car_id']][] = $row;
    }
    return $images;
}
