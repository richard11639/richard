<?php
// db.php - update credentials
$DB_HOST = '127.0.0.1';
$DB_NAME = 'your_database';
$DB_USER = 'your_db_user';
$DB_PASS = 'your_db_pass';
$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

