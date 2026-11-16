<?php
// Üzenetek lekérdezése és küldése. A js/messages.js hívja.
//   GET  ?car=ID&buyer=ID            -> a beszélgetés üzenetei (pár másodpercenként lekérdezve)
//   POST csrf, car, buyer, body      -> új üzenet; ha az érdeklődő írt, a chatbot válaszol
// Válasz: {"ok": true, "messages": [...], "unread": <összes olvasatlan>}

require '../includes/auth.php';
require '../includes/messages.php';
require '../includes/chatbot.php';

header('Content-Type: application/json; charset=utf-8');

const BOT_DELAY_SECONDS = 2;      // ennyi idő múlva "érkezik meg" a chatbot válasza
const MAX_MESSAGE_LENGTH = 1000;

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

$conversation = find_conversation((int) ($_REQUEST['car'] ?? 0), (int) ($_REQUEST['buyer'] ?? 0), $me);
if (!$conversation) {
    fail(404, 'A beszélgetés nem található.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        fail(400, 'Érvénytelen kérés.');
    }

    $body = trim((string) ($_POST['body'] ?? ''));
    if ($body === '' || mb_strlen($body) > MAX_MESSAGE_LENGTH) {
        fail(400, 'Az üzenet 1 és ' . MAX_MESSAGE_LENGTH . ' karakter közötti legyen.');
    }

    $key = [$conversation['car_id'], $conversation['buyer_id']];

    if ($conversation['i_am_buyer']) {
        // Hány üzenetet írt eddig az érdeklődő (a chatbot ettől függően váltogatja az általános válaszokat)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE car_id = ? AND buyer_id = ? AND from_buyer = 1');
        $stmt->execute($key);
        $earlier = (int) $stmt->fetchColumn();

        // Az érdeklődő üzenete (az eladónál olvasatlanként jelenik meg, amíg meg nem nézi)
        $pdo->prepare('INSERT INTO messages (car_id, buyer_id, from_buyer, body) VALUES (?, ?, 1, ?)')
            ->execute([...$key, $body]);

        // A chatbot válasza az eladó nevében, pár másodperccel későbbi időponttal
        $pdo->prepare(
            'INSERT INTO messages (car_id, buyer_id, from_buyer, body, letrehozva)
             VALUES (?, ?, 0, ?, NOW() + INTERVAL ' . BOT_DELAY_SECONDS . ' SECOND)'
        )->execute([...$key, bot_reply($conversation['car'], $body, $earlier)]);
    } else {
        // Az eladó kézzel is hozzászólhat a saját autójáról szóló beszélgetéshez
        $pdo->prepare('INSERT INTO messages (car_id, buyer_id, from_buyer, body) VALUES (?, ?, 0, ?)')
            ->execute([...$key, $body]);
    }
}

echo json_encode([
    'ok'       => true,
    'messages' => conversation_messages($conversation),
    'unread'   => unread_messages_count($me),
], JSON_UNESCAPED_UNICODE);
