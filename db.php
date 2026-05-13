<?php
// Try local XAMPP MySQL first, fall back to InfinityFree
$configs = [
    [
        'host'     => 'localhost',
        'dbname'   => 'shewit_mobile',
        'username' => 'root',
        'password' => ''
    ],
    [
        'host'     => 'sql306.infinityfree.com',
        'dbname'   => 'if0_41611048_shewit',
        'username' => 'if0_41611048',
        'password' => 'shewit2026'
    ]
];

$pdo = null;
foreach ($configs as $cfg) {
    try {
        $pdo = new PDO(
            "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset=utf8",
            $cfg['username'],
            $cfg['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
        );
        break; // connected successfully
    } catch (PDOException $e) {
        $pdo = null;
    }
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed. Make sure XAMPP MySQL is running or you are online.']);
    exit;
}
?>
