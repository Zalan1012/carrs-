<?php
// Üzenetek. Bal oldalt a beszélgetések, jobb oldalt a kiválasztott beszélgetés.
//   messages.php                    – csak a lista (a legfrissebb beszélgetés nyílik meg)
//   messages.php?car=ID             – érdeklődőként írok az autó eladójának
//   messages.php?car=ID&buyer=ID    – egy meglévő beszélgetés (eladóként is)
// Az érdeklődő üzenetére az eladó nevében a chatbot válaszol (includes/chatbot.php).

require 'includes/auth.php';
require 'includes/messages.php';
require_login();

$me   = current_user_id();
$list = conversation_list($me);

$car_id   = (int) ($_GET['car'] ?? 0);
$buyer_id = (int) ($_GET['buyer'] ?? $me);
$notice   = '';

// Ha nincs megadva beszélgetés, a legfrissebbet nyitjuk meg
if (!$car_id && $list) {
    $car_id   = $list[0]['car_id'];
    $buyer_id = $list[0]['buyer_id'];
}

$conversation = $car_id ? find_conversation($car_id, $buyer_id, $me) : null;
if ($car_id && !$conversation) {
    $notice = 'Ez a beszélgetés nem nyitható meg. A saját hirdetésedre nem tudsz üzenetet írni.';
}

$messages = $conversation ? conversation_messages($conversation) : [];

// Új beszélgetés (még nincs benne üzenet): ideiglenesen a lista elejére tesszük
if ($conversation) {
    $in_list = false;
    foreach ($list as &$item) {
        if ($item['car_id'] === $car_id && $item['buyer_id'] === $buyer_id) {
            $item['unread'] = 0; // épp most nyitottuk meg
            $in_list = true;
        }
    }
    unset($item);

    if (!$in_list) {
        array_unshift($list, [
            'car_id'     => $car_id,
            'buyer_id'   => $buyer_id,
            'other_name' => $conversation['other_name'],
            'car_title'  => $conversation['car']['brand'] . ' ' . $conversation['car']['model'],
            'preview'    => 'Új beszélgetés',
            'time'       => '',
            'unread'     => 0,
        ]);
    }
}

$page_title = 'Üzenetek – CarSwipe';
$active     = 'messages.php';
require 'includes/header.php';
?>
<div class="messages-layout">
    <div>
        <h1>Üzenetek</h1>
        <input type="search" id="conv-search" placeholder="Keresés az üzenetekben" aria-label="Keresés az üzenetekben">

        <?php if ($notice): ?>
            <div class="alert"><?= e($notice) ?></div>
        <?php endif; ?>

        <?php if (!$list): ?>
            <p class="muted" style="margin-top:16px">Még nincs beszélgetésed. A <a href="favorites.php">Kedvencek</a> között az „Üzenet” gombbal írhatsz egy eladónak.</p>
        <?php endif; ?>

        <div id="conv-list">
            <?php foreach ($list as $item): ?>
                <?php $selected = $conversation && $item['car_id'] === $car_id && $item['buyer_id'] === $buyer_id; ?>
                <a class="conv <?= $selected ? 'selected' : '' ?>"
                   href="messages.php?car=<?= $item['car_id'] ?>&amp;buyer=<?= $item['buyer_id'] ?>">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($item['other_name'], 0, 1))) ?></span>
                    <span class="who">
                        <b><?= e($item['other_name']) ?></b>
                        <small><?= e($item['car_title']) ?></small>
                        <span class="preview"><?= e($item['preview']) ?></span>
                    </span>
                    <span class="time">
                        <?= e($item['time']) ?>
                        <?php if ($item['unread'] > 0): ?><span class="dot" title="Olvasatlan"></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($conversation): ?>
        <?php $car = $conversation['car']; ?>
        <div class="chat">
            <div class="chat-head">
                <?php if ($conversation['cover']): ?>
                    <img class="thumb" src="<?= e($conversation['cover']) ?>" alt="">
                <?php else: ?>
                    <div class="thumb no-photo"></div>
                <?php endif; ?>
                <div>
                    <b><?= e($conversation['other_name']) ?></b><br>
                    <small class="muted"><?= e($car['brand'] . ' ' . $car['model']) ?> · <?= format_price($car['price']) ?></small>
                </div>
                <span class="spacer"></span>
                <a class="btn" href="car.php?id=<?= (int) $car['car_id'] ?>">Hirdetés megnyitása</a>
            </div>

            <div class="chat-body" id="chat-body">
                <p class="chat-hint" id="chat-hint">
                    <?= $conversation['i_am_buyer']
                        ? 'Írj az eladónak! Kérdezhetsz az árról, a futott km-ről, az állapotról, vagy egyeztethetsz megtekintést.'
                        : 'Ebben a beszélgetésben még nincs üzenet.' ?>
                </p>
            </div>

            <form class="chat-form" id="chat-form">
                <input type="text" id="chat-input" maxlength="1000" placeholder="Írj üzenetet..." aria-label="Üzenet" autocomplete="off" autofocus>
                <button class="btn-dark">Küldés</button>
            </form>
        </div>

        <script>
            const CHAT = <?= json_encode([
                'car'      => $car_id,
                'buyer'    => $buyer_id,
                'iAmBuyer' => $conversation['i_am_buyer'],
                'csrf'     => csrf_token(),
                'messages' => $messages,
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        </script>
        <script src="js/messages.js"></script>
    <?php else: ?>
        <div class="chat chat-empty">
            <p class="muted">Válassz egy beszélgetést a listából.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Keresés a beszélgetések között (név, autó és az utolsó üzenet alapján)
document.getElementById('conv-search').addEventListener('input', event => {
    const query = event.target.value.trim().toLowerCase();
    document.querySelectorAll('#conv-list .conv').forEach(conv => {
        conv.hidden = !conv.textContent.toLowerCase().includes(query);
    });
});
</script>

<?php require 'includes/footer.php'; ?>
