<?php
if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')) {
    $host = 'localhost';
    $dbname = 'shewit_mobile';
    $username = 'root';
    $password = '';
} else {
    $host = 'sql306.infinityfree.com';
    $dbname = 'if0_41611048_shewit';
    $username = 'if0_41611048';
    $password = 'shewit2026';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}
?>
