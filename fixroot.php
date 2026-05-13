<?php
// Fix root permissions and create database - run once via Apache
// Delete this file after running!

$errors = [];
$done = [];

$attempts = [
    ['dsn' => 'mysql:host=localhost;port=3306;charset=utf8', 'user' => 'root', 'pass' => ''],
    ['dsn' => 'mysql:host=127.0.0.1;port=3306;charset=utf8', 'user' => 'root', 'pass' => ''],
    ['dsn' => 'mysql:unix_socket=C:/xampp/mysql/mysql.sock;charset=utf8', 'user' => 'root', 'pass' => ''],
    ['dsn' => 'mysql:host=::1;port=3306;charset=utf8', 'user' => 'root', 'pass' => ''],
];

$pdo = null;
foreach ($attempts as $a) {
    try {
        $pdo = new PDO($a['dsn'], $a['user'], $a['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        $done[] = "✅ Connected via: " . $a['dsn'];
        break;
    } catch (PDOException $e) {
        $errors[] = "❌ " . $a['dsn'] . " → " . $e->getMessage();
    }
}

if ($pdo) {
    $sqls = [
        "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION",
        "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY '' WITH GRANT OPTION",
        "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '' WITH GRANT OPTION",
        "FLUSH PRIVILEGES",
        "CREATE DATABASE IF NOT EXISTS shewit_mobile CHARACTER SET utf8 COLLATE utf8_general_ci",
    ];
    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            $done[] = "✅ " . $sql;
        } catch (PDOException $e) {
            $errors[] = "⚠️ " . $sql . " → " . $e->getMessage();
        }
    }
    $done[] = "🎉 All done! <a href='setup.php'>Click here to run setup.php</a>";
}
?><!DOCTYPE html>
<html><head><title>Fix Root</title>
<style>body{font-family:monospace;padding:30px;background:#1a1a2e;color:#eee;line-height:2}
.ok{color:#2ecc71}.err{color:#e94560}a{color:#3498db;font-size:1.2rem}</style>
</head><body>
<h2 style="color:#e94560">🔧 MySQL Root Fix</h2>
<?php foreach($done as $d) echo "<div class='ok'>$d</div>"; ?>
<?php foreach($errors as $e) echo "<div class='err'>$e</div>"; ?>
<?php if(!$pdo) echo "<div class='err' style='font-size:1.1rem;margin-top:20px'>❌ Could not connect. Make sure MySQL is started in XAMPP Control Panel.</div>"; ?>
</body></html>
