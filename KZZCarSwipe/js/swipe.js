// CarSwipe – a főoldali kártya kezelése.
// A CARS (autók listája) és a CSRF (token) változót az index.php adja át.

const card = document.getElementById('card');
const emptyBox = document.getElementById('empty');
const photoImg = document.getElementById('photo-img');
const noPhoto = document.getElementById('no-photo');
const photoBars = document.getElementById('photo-bars');
const stampLike = document.getElementById('stamp-like');
const stampNope = document.getElementById('stamp-nope');

const SWIPE_LIMIT = 110; // ennyi pixel húzás után számít swipe-nak

let carIndex = 0;   // hányadik autónál tartunk
let photoIndex = 0; // az aktuális autó hányadik képe látszik
let busy = false;   // animáció közben ne lehessen újra swipe-olni

function currentCar() {
    return CARS[carIndex];
}

// ---------- Megjelenítés ----------

function showPhoto() {
    const images = currentCar().images;

    if (images.length === 0) {
        photoImg.hidden = true;
        noPhoto.hidden = false;
    } else {
        photoImg.src = images[photoIndex];
        photoImg.hidden = false;
        noPhoto.hidden = true;
    }

    // Lapozó nyilak és felső csíkok: csak akkor, ha több kép van
    document.getElementById('photo-prev').hidden = images.length < 2;
    document.getElementById('photo-next').hidden = images.length < 2;
    photoBars.innerHTML = '';
    if (images.length > 1) {
        images.forEach((_, index) => {
            const bar = document.createElement('i');
            if (index === photoIndex) bar.className = 'on';
            photoBars.append(bar);
        });
    }
}

function showCar() {
    const car = currentCar();

    if (!car) {
        card.hidden = true;
        emptyBox.hidden = false;
        document.getElementById('actions').hidden = true;
        document.getElementById('hint').hidden = true;
        return;
    }

    photoIndex = 0;
    showPhoto();

    document.getElementById('title').textContent = car.title;
    document.getElementById('price').textContent = car.price;
    document.getElementById('description').textContent = car.description || 'Nincs leírás.';
    document.getElementById('details').href = 'car.php?id=' + car.id;

    const tags = document.getElementById('tags');
    tags.innerHTML = '';
    car.tags.forEach(text => {
        const tag = document.createElement('span');
        tag.textContent = text;
        tags.append(tag);
    });

    moveCard(0, false);
    card.hidden = false;
}

// A kártya eltolása és elforgatása; a "TETSZIK" / "NEM" felirat halványan megjelenik
function moveCard(x, animate) {
    card.style.transition = animate ? 'transform .25s ease-out' : 'none';
    card.style.transform = x === 0 ? '' : `translateX(${x}px) rotate(${x / 22}deg)`;
    stampLike.style.opacity = Math.max(0, Math.min(1, x / SWIPE_LIMIT));
    stampNope.style.opacity = Math.max(0, Math.min(1, -x / SWIPE_LIMIT));
}

// Lapozás a képek között: irány +1 (következő) vagy -1 (előző), körbe forog
function changePhoto(direction) {
    const count = currentCar().images.length;
    photoIndex = (photoIndex + direction + count) % count;
    showPhoto();
}

document.getElementById('photo-prev').addEventListener('click', () => changePhoto(-1));
document.getElementById('photo-next').addEventListener('click', () => changePhoto(1));

// ---------- Swipe ----------

function swipe(liked) {
    const car = currentCar();
    if (!car || busy) return;
    busy = true;

    moveCard(liked ? 700 : -700, true);

    fetch('api/swipe.php', {
        method: 'POST',
        body: new URLSearchParams({ csrf: CSRF, car_id: car.id, liked: liked ? 1 : 0 }),
    })
        .then(response => response.json())
        .then(data => {
            if (!data.ok) throw new Error(data.error);
            const counter = document.getElementById('fav-count');
            if (counter) counter.textContent = data.favorites;
        })
        .catch(() => alert('Nem sikerült menteni a választásod. Töltsd újra az oldalt.'));

    setTimeout(() => {
        carIndex++;
        busy = false;
        showCar();
    }, 250);
}

document.getElementById('btn-like').addEventListener('click', () => swipe(true));
document.getElementById('btn-nope').addEventListener('click', () => swipe(false));

document.addEventListener('keydown', event => {
    // Szűrő mezőben gépelve / csúszkát állítva ne swipe-oljon
    if (event.target instanceof Element && event.target.matches('input, select, textarea')) return;
    if (event.key === 'ArrowRight') swipe(true);
    if (event.key === 'ArrowLeft') swipe(false);
});

// ---------- Húzás egérrel vagy ujjal ----------

let startX = null;
let dragX = 0;

card.addEventListener('pointerdown', event => {
    // Linkre vagy lapozó nyílra kattintva ne induljon húzás
    if (busy || event.target.closest('a, .photo-nav')) return;
    startX = event.clientX;
    dragX = 0;
    card.setPointerCapture(event.pointerId);
    card.classList.add('dragging');
});

card.addEventListener('pointermove', event => {
    if (startX === null) return;
    dragX = event.clientX - startX;
    moveCard(dragX, false);
});

function endDrag(event) {
    if (startX === null) return;
    startX = null;
    card.classList.remove('dragging');

    if (dragX > SWIPE_LIMIT) {
        swipe(true);
    } else if (dragX < -SWIPE_LIMIT) {
        swipe(false);
    } else {
        moveCard(0, true);
        // Húzás nélküli kattintás a képen: következő kép
        const car = currentCar();
        if (Math.abs(dragX) < 6 && car.images.length > 1 && event.target.closest('#photo')) {
            changePhoto(1);
        }
    }
}

card.addEventListener('pointerup', endDrag);
card.addEventListener('pointercancel', endDrag);

// ---------- Ár csúszka ----------

const minInput = document.getElementById('min');
const maxInput = document.getElementById('max');

function formatFt(value) {
    return Number(value).toLocaleString('hu-HU') + ' Ft';
}

function updatePriceLabels(changed) {
    // A két csúszka ne tudjon átmenni egymáson
    if (Number(minInput.value) > Number(maxInput.value)) {
        if (changed === minInput) maxInput.value = minInput.value;
        else minInput.value = maxInput.value;
    }
    document.getElementById('min-label').textContent = formatFt(minInput.value);
    document.getElementById('max-label').textContent = formatFt(maxInput.value);
}

minInput.addEventListener('input', () => updatePriceLabels(minInput));
maxInput.addEventListener('input', () => updatePriceLabels(maxInput));
updatePriceLabels();

showCar();
