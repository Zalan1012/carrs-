<?php
// Adatbázis kapcsolat (XAMPP alapbeállítás: root felhasználó, jelszó nélkül)

$db_host = 'localhost';
$db_name = 'carswipe';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Nem sikerült csatlakozni az adatbázishoz. Fut a MySQL a XAMPP-ban, és létezik a "carswipe" adatbázis?');
}
