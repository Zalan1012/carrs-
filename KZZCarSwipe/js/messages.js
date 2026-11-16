// CarSwipe – a megnyitott beszélgetés kezelése.
// A CHAT változót (car, buyer, iAmBuyer, csrf, messages) a messages.php adja át.

const chatBody = document.getElementById('chat-body');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatHint = document.getElementById('chat-hint');

const POLL_MS = 2000;          // ilyen gyakran kérdezzük le az új üzeneteket
const TYPING_TIMEOUT_MS = 8000; // legfeljebb eddig látszik a "gépel" jelzés

const seenIds = new Set(); // a már kirajzolt üzenetek azonosítói
let lastDay = null;        // az utoljára kiírt nap ("Ma", "Tegnap", dátum)
let typingBubble = null;
let typingTimer = null;

// ---------- Megjelenítés ----------

function addMessage(message) {
    if (seenIds.has(message.id)) return false;
    seenIds.add(message.id);

    if (message.day !== lastDay) {
        const day = document.createElement('div');
        day.className = 'day';
        day.textContent = message.day;
        chatBody.append(day);
        lastDay = message.day;
    }

    const bubble = document.createElement('div');
    bubble.className = 'bubble' + (message.mine ? ' mine' : '');
    bubble.textContent = message.body; // textContent: a beírt HTML nem fut le

    const time = document.createElement('span');
    time.className = 'bubble-time';
    time.textContent = message.time;
    bubble.append(time);

    chatBody.append(bubble);
    return true;
}

function showMessages(messages) {
    let added = false;
    let incoming = false;

    messages.forEach(message => {
        if (addMessage(message)) {
            added = true;
            if (!message.mine) incoming = true;
        }
    });

    if (incoming) hideTyping();
    if (typingBubble) chatBody.append(typingBubble); // a "gépel" jelzés maradjon legalul

    if (added) {
        chatHint.hidden = true;
        chatBody.scrollTop = chatBody.scrollHeight;

        // A bal oldali listában a kiválasztott beszélgetés előnézete is frissül
        const preview = document.querySelector('.conv.selected .preview');
        if (preview) preview.textContent = messages[messages.length - 1].body;
    }
}

function showTyping() {
    hideTyping();
    typingBubble = document.createElement('div');
    typingBubble.className = 'bubble typing';
    typingBubble.innerHTML = '<i></i><i></i><i></i>';
    chatBody.append(typingBubble);
    chatBody.scrollTop = chatBody.scrollHeight;
    typingTimer = setTimeout(hideTyping, TYPING_TIMEOUT_MS);
}

function hideTyping() {
    clearTimeout(typingTimer);
    if (typingBubble) typingBubble.remove();
    typingBubble = null;
}

function updateUnreadBadge(count) {
    const badge = document.getElementById('msg-count');
    if (!badge) return;
    badge.textContent = count;
    badge.hidden = count === 0;
}

// ---------- Kommunikáció a szerverrel ----------

const apiUrl = `api/messages.php?car=${CHAT.car}&buyer=${CHAT.buyer}`;

async function loadMessages() {
    try {
        const data = await (await fetch(apiUrl)).json();
        if (data.ok) {
            showMessages(data.messages);
            updateUnreadBadge(data.unread);
        }
    } catch (error) {
        // Átmeneti hálózati hiba: a következő lekérdezés újrapróbálja
    }
}

chatForm.addEventListener('submit', async event => {
    event.preventDefault();
    const body = chatInput.value.trim();
    if (!body) return;

    chatInput.value = '';
    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: new URLSearchParams({ csrf: CHAT.csrf, body }),
        });
        const data = await response.json();
        if (!data.ok) throw new Error(data.error);

        showMessages(data.messages);
        if (CHAT.iAmBuyer) showTyping(); // az eladó (chatbot) pár másodperc múlva válaszol
    } catch (error) {
        chatInput.value = body; // ne vesszen el, amit beírt
        alert('Nem sikerült elküldeni az üzenetet. Próbáld újra.');
    }
    chatInput.focus();
});

showMessages(CHAT.messages);
setInterval(loadMessages, POLL_MS);
