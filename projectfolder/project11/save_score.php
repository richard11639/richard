<?php
// save_score.php
// Accepts JSON POST { name, score } and stores into an SQLite DB (scores.sqlite).
header('Content-Type: application/json');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $name = trim($data['name'] ?? 'Anonymous');
    $score = (int)($data['score'] ?? 0);
    if ($score <= 0) {
        echo json_encode(['ok'=>false,'error'=>'Invalid score']); exit;
    }
    if ($name === '') $name = 'Anonymous';
    // open/create sqlite
    $db = new PDO('sqlite:'.__DIR__.'/scores.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // create table if missing
    $db->exec("CREATE TABLE IF NOT EXISTS highscores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        score INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    // insert
    $stmt = $db->prepare("INSERT INTO highscores (name, score) VALUES (:name, :score)");
    $stmt->execute([':name'=>substr($name,0,64), ':score'=>$score]);
    echo json_encode(['ok'=>true]);
    exit;
} catch (Exception $ex) {
    echo json_encode(['ok'=>false,'error'=>$ex->getMessage()]);
    exit;
}
