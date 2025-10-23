<?php
// highscores.php - returns top 10 scores as JSON
header('Content-Type: application/json');
try {
    $db = new PDO('sqlite:'.__DIR__.'/scores.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS highscores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        score INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $db->query("SELECT name, score, datetime(created_at) AS when FROM highscores ORDER BY score DESC, created_at ASC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $ex) {
    echo json_encode([]);
}
