-- CarSwipe – teljes adatbázis séma + demo adatok
-- Többször is lefuttatható (nem töröl semmit, csak a hiányzó dolgokat hozza létre).
-- Importálás: phpMyAdmin -> Importálás, vagy:
--   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < sql\carswipe.sql

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS carswipe
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_hungarian_ci;

USE carswipe;

-- Felhasználók
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    letrehozva DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Hirdetések
CREATE TABLE IF NOT EXISTS cars (
    car_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    brand         VARCHAR(50) NOT NULL,
    model         VARCHAR(80) NOT NULL,
    year          INT NOT NULL,
    mileage       INT NOT NULL,
    fuel          VARCHAR(20) NOT NULL,
    transmission  VARCHAR(20) NOT NULL,
    body_type     VARCHAR(30) NOT NULL,
    power         INT NOT NULL,
    price         INT NOT NULL,
    description   TEXT,
    letrehozva    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Hirdetések képei (sort_order = 0 a borítókép)
CREATE TABLE IF NOT EXISTS car_images (
    image_id   INT AUTO_INCREMENT PRIMARY KEY,
    car_id     INT NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE
);

-- Swipe-ok: liked = 1 jobbra húzva (kedvenc), liked = 0 balra húzva
CREATE TABLE IF NOT EXISTS swipes (
    user_id    INT NOT NULL,
    car_id     INT NOT NULL,
    liked      TINYINT(1) NOT NULL,
    letrehozva DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, car_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE
);

-- Üzenetek. Egy beszélgetés = egy autó (car_id) + egy érdeklődő (buyer_id).
-- from_buyer = 1: az érdeklődő írta, 0: az eladó oldala (a chatbot válasza).
-- is_read: a címzett látta-e már. A chatbot válaszának letrehozva ideje pár
-- másodperccel későbbi, az üzenet csak akkortól látszik ("gépel" hatás).
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    car_id     INT NOT NULL,
    buyer_id   INT NOT NULL,
    from_buyer TINYINT(1) NOT NULL,
    body       TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    letrehozva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------------
-- Demo adatok: az Autok mappa 9 autója egy "CarSwipe Demo" felhasználó nevén.
-- A demo felhasználóval nem lehet belépni (a jelszó mező nem érvényes hash).
-- Az autók adatai kitaláltak, az Eladás menüben / phpMyAdminban átírhatók.
-- ---------------------------------------------------------------------------
INSERT INTO users (name, email, password)
SELECT 'CarSwipe Demo', 'demo@carswipe.local', '!nincs-belepes'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'demo@carswipe.local');

SET @demo := (SELECT user_id FROM users WHERE email = 'demo@carswipe.local');

-- Minden demo autó csak akkor kerül be, ha még nincs ilyen modell a demo felhasználónál
INSERT INTO cars (user_id, brand, model, year, mileage, fuel, transmission, body_type, power, price, description)
SELECT * FROM (
    SELECT @demo AS user_id, 'BMW' AS brand, 'M4 (F82)' AS model, 2016 AS year, 86000 AS mileage, 'Benzin' AS fuel, 'Automata' AS transmission, 'Kupé' AS body_type, 431 AS power, 16900000 AS price,
           'Szép állapotú BMW M4 F82, végig márkaszervizben szervizelve. M Sport ülések, Harman Kardon hangrendszer, adaptív futómű, karbon tető.' AS description
    UNION ALL
    SELECT @demo, 'BMW', 'M3 Competition (G80)', 2022, 34000, 'Benzin', 'Automata', 'Szedán', 510, 33500000,
           'BMW M3 Competition G80, karbon tető, lézer fényszóró, head-up display. Garázsban tartott, sérülésmentes autó.'
    UNION ALL
    SELECT @demo, 'BMW', '320d (E46)', 2003, 284000, 'Dízel', 'Manuális', 'Szedán', 150, 1000000,
           'Megbízható E46 320d, friss műszakival. Klíma, tempomat, ülésfűtés. Korának megfelelő állapotban, rozsdamentes küszöbökkel.'
    UNION ALL
    SELECT @demo, 'BMW', '535d (E60)', 2006, 312000, 'Dízel', 'Automata', 'Szedán', 272, 2650000,
           'E60 535d M-csomaggal, bőr belső, navigáció, xenon. Vezérlés és turbók cserélve, számlákkal igazolt szerviztörténet.'
    UNION ALL
    SELECT @demo, 'Porsche', 'Taycan 4S', 2021, 42000, 'Elektromos', 'Automata', 'Szedán', 530, 31900000,
           'Porsche Taycan 4S nagyobb akkumulátorral, légrugóval és panorámatetővel. Gyári garanciás akkumulátor, 800 voltos villámtöltés.'
    UNION ALL
    SELECT @demo, 'Tesla', 'Model S', 2018, 96000, 'Elektromos', 'Automata', 'Szedán', 422, 15500000,
           'Tesla Model S összkerékhajtással, Autopilottal és prémium belsővel. Az akkumulátor jó állapotú, a hatótáv továbbra is 450 km körüli.'
    UNION ALL
    SELECT @demo, 'BMW', '530e (G30)', 2019, 118000, 'Hibrid', 'Automata', 'Szedán', 252, 12900000,
           'BMW 530e plug-in hibrid, tisztán elektromosan kb. 40 km-t megy. Bőr belső, adaptív LED, digitális műszerfal, vezetett szervizkönyv.'
    UNION ALL
    SELECT @demo, 'Ferrari', '458 Spider', 2013, 31000, 'Benzin', 'Automata', 'Kabrió', 570, 89900000,
           'Ferrari 458 Spider szívó V8-as motorral és nyitható keménytetővel. Karbon-kerámia fékek, sportkipufogó, teljes márkaszerviz-történet.'
    UNION ALL
    SELECT @demo, 'Ferrari', '488 Pista', 2019, 12000, 'Benzin', 'Automata', 'Kupé', 720, 149000000,
           'Ferrari 488 Pista, a 488 pályára hangolt változata. Karbon ülések és karbon belső, versenycsíkos fényezés, garázsban tartott gyűjtői darab.'
) AS demo_autok
WHERE NOT EXISTS (SELECT 1 FROM cars x WHERE x.user_id = @demo AND x.model = demo_autok.model);

INSERT INTO car_images (car_id, file_path, sort_order)
SELECT c.car_id, k.file_path, 0
FROM cars c
JOIN (
    SELECT 'M4 (F82)' AS model, 'Autok/bmwm4f82.jpg' AS file_path
    UNION ALL SELECT 'Taycan 4S', 'Autok/porschetaycan.jpg'
    UNION ALL SELECT 'Model S', 'Autok/teslamodels.jpg'
    UNION ALL SELECT '530e (G30)', 'Autok/bmw530eg30.jpg'
    UNION ALL SELECT '458 Spider', 'Autok/ferrari458spider.jpg'
    UNION ALL SELECT '488 Pista', 'Autok/ferrari488pista.jpg'
    UNION ALL SELECT 'M3 Competition (G80)', 'Autok/bmwm3g80.jpg'
    UNION ALL SELECT '320d (E46)', 'Autok/e46320d.jpg'
    UNION ALL SELECT '535d (E60)', 'Autok/e60535d.jpg'
) AS k ON k.model = c.model
WHERE c.user_id = @demo
  AND NOT EXISTS (SELECT 1 FROM car_images i WHERE i.car_id = c.car_id);

-- Második kép: belső tér (ahol van hozzá fotó az Autok mappában)
INSERT INTO car_images (car_id, file_path, sort_order)
SELECT c.car_id, k.file_path, 1
FROM cars c
JOIN (
    SELECT 'M4 (F82)' AS model, 'Autok/bmwm4f82interior.jpg' AS file_path
    UNION ALL SELECT 'M3 Competition (G80)', 'Autok/bmwm3g80interior.webp'
    UNION ALL SELECT '320d (E46)', 'Autok/e46320dinterior.jpg'
    UNION ALL SELECT 'Taycan 4S', 'Autok/taycaninterior.avif'
    UNION ALL SELECT '458 Spider', 'Autok/ferrari458spiderinterior.webp'
    UNION ALL SELECT '488 Pista', 'Autok/ferrari488pistainterior.jpg'
) AS k ON k.model = c.model
WHERE c.user_id = @demo
  AND NOT EXISTS (SELECT 1 FROM car_images i WHERE i.car_id = c.car_id AND i.file_path = k.file_path);
