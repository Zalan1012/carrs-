<?php
// Üzenetek közös függvényei (a messages.php oldal és az api/messages.php is ezeket használja).
// Egy beszélgetés = egy autó (car_id) + egy érdeklődő (buyer_id).

/**
 * A beszélgetés adatai, ha a bejelentkezett felhasználó ($me) részt vehet benne:
 * vagy ő az érdeklődő, vagy ő az autó eladója. Különben null.
 */
function find_conversation(int $car_id, int $buyer_id, int $me): ?array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT c.*, s.name AS seller_name, b.name AS buyer_name
         FROM cars c
         JOIN users s ON s.user_id = c.user_id
         JOIN users b ON b.user_id = ?
         WHERE c.car_id = ?'
    );
    $stmt->execute([$buyer_id, $car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        return null;
    }
    $seller_id = (int) $car['user_id'];
    if ($buyer_id === $seller_id || ($me !== $buyer_id && $me !== $seller_id)) {
        return null; // saját autóra nem lehet írni, más beszélgetésébe nem lehet belenézni
    }

    $i_am_buyer = $me === $buyer_id;
    return [
        'car'        => $car,
        'car_id'     => $car_id,
        'buyer_id'   => $buyer_id,
        'i_am_buyer' => $i_am_buyer,
        'other_name' => $i_am_buyer ? $car['seller_name'] : $car['buyer_name'],
        'cover'      => images_for_cars([$car_id])[$car_id][0]['file_path'] ?? null,
    ];
}

/**
 * A beszélgetés már látható üzenetei időrendben, megjelenítésre kész formában.
 * Közben olvasottnak jelöli azokat, amiket a másik fél írt.
 */
function conversation_messages(array $conversation): array
{
    global $pdo;
    $key = [$conversation['car_id'], $conversation['buyer_id']];

    // A másik fél üzenetei: ha én vagyok az érdeklődő, akkor a from_buyer = 0 sorok
    $pdo->prepare(
        'UPDATE messages SET is_read = 1
         WHERE car_id = ? AND buyer_id = ? AND from_buyer = ? AND is_read = 0 AND letrehozva <= NOW()'
    )->execute([...$key, $conversation['i_am_buyer'] ? 0 : 1]);

    $stmt = $pdo->prepare(
        "SELECT message_id, from_buyer, body,
                DATE_FORMAT(letrehozva, '%H:%i') AS time,
                DATE_FORMAT(letrehozva, '%Y. %m. %d.') AS date,
                DATEDIFF(CURDATE(), DATE(letrehozva)) AS days_ago
         FROM messages
         WHERE car_id = ? AND buyer_id = ? AND letrehozva <= NOW()
         ORDER BY letrehozva, message_id"
    );
    $stmt->execute($key);

    $messages = [];
    foreach ($stmt as $row) {
        $messages[] = [
            'id'   => (int) $row['message_id'],
            'mine' => (bool) $row['from_buyer'] === $conversation['i_am_buyer'],
            'body' => $row['body'],
            'time' => $row['time'],
            'day'  => match ((int) $row['days_ago']) {
                0       => 'Ma',
                1       => 'Tegnap',
                default => $row['date'],
            },
        ];
    }
    return $messages;
}

/**
 * A felhasználó összes beszélgetése (érdeklődőként és eladóként is), a legfrissebb elöl.
 */
function conversation_list(int $me): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT t.car_id, t.buyer_id, t.unread,
                last.body AS last_body,
                IF(DATE(last.letrehozva) = CURDATE(),
                   DATE_FORMAT(last.letrehozva, '%H:%i'),
                   DATE_FORMAT(last.letrehozva, '%m. %d.')) AS last_time,
                c.brand, c.model, c.user_id AS seller_id,
                s.name AS seller_name, b.name AS buyer_name
         FROM (
             SELECT m.car_id, m.buyer_id, MAX(m.message_id) AS last_id,
                    SUM(m.is_read = 0 AND m.from_buyer = IF(m.buyer_id = ?, 0, 1)) AS unread
             FROM messages m
             JOIN cars c ON c.car_id = m.car_id
             WHERE (m.buyer_id = ? OR c.user_id = ?) AND m.letrehozva <= NOW()
             GROUP BY m.car_id, m.buyer_id
         ) AS t
         JOIN messages last ON last.message_id = t.last_id
         JOIN cars c ON c.car_id = t.car_id
         JOIN users s ON s.user_id = c.user_id
         JOIN users b ON b.user_id = t.buyer_id
         ORDER BY t.last_id DESC"
    );
    $stmt->execute([$me, $me, $me]);

    $list = [];
    foreach ($stmt as $row) {
        $list[] = [
            'car_id'     => (int) $row['car_id'],
            'buyer_id'   => (int) $row['buyer_id'],
            'other_name' => (int) $row['buyer_id'] === $me ? $row['seller_name'] : $row['buyer_name'],
            'car_title'  => $row['brand'] . ' ' . $row['model'],
            'preview'    => $row['last_body'],
            'time'       => $row['last_time'],
            'unread'     => (int) $row['unread'],
        ];
    }
    return $list;
}
