<?php
// Swipe mentése. A js/swipe.js hívja POST kéréssel:
//   csrf, car_id, liked (1 = jobbra / kedvenc, 0 = balra)
// Válasz: {"ok": true, "favorites": <kedvencek száma>}

require '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$me = current_user_id();
if (!$me) {
    fail(401, 'Nem vagy bejelentkezve.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
    fail(400, 'Érvénytelen kérés.');
}

$car_id = (int) ($_POST['car_id'] ?? 0);
$liked  = !empty($_POST['liked']) ? 1 : 0;

// Csak létező, nem saját hirdetésre lehet swipe-olni
$stmt = $pdo->prepare('SELECT 1 FROM cars WHERE car_id = ? AND user_id <> ?');
$stmt->execute([$car_id, $me]);
if (!$stmt->fetch()) {
    fail(404, 'A hirdetés nem található.');
}

$stmt = $pdo->prepare(
    'INSERT INTO swipes (user_id, car_id, liked) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE liked = VALUES(liked), letrehozva = CURRENT_TIMESTAMP'
);
$stmt->execute([$me, $car_id, $liked]);

echo json_encode(['ok' => true, 'favorites' => favorites_count($me)]);
