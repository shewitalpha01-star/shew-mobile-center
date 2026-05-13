<?php
// ═══════════════════════════════════════════════════════
//  Shewit Mobile Center — Auto Setup Script
//  Open this once at: http://localhost/shewit_mobile/setup.php
// ═══════════════════════════════════════════════════════

$configs = [
    ['host'=>'localhost','dbname'=>'shewit_mobile','username'=>'root','password'=>'','label'=>'XAMPP Local (socket)'],
    ['host'=>'127.0.0.1','dbname'=>'shewit_mobile','username'=>'root','password'=>'','label'=>'XAMPP Local (TCP)'],
    ['host'=>'sql306.infinityfree.com','dbname'=>'if0_41611048_shewit','username'=>'if0_41611048','password'=>'shewit2026','label'=>'InfinityFree Remote'],
];

$pdo = null; $usedLabel = '';
foreach ($configs as $cfg) {
    try {
        $dsn_nodb = "mysql:host={$cfg['host']};charset=utf8";
        $opts = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT=>4];
        $tmp = new PDO($dsn_nodb, $cfg['username'], $cfg['password'], $opts);
        $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['dbname']}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $pdo = new PDO("mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset=utf8", $cfg['username'], $cfg['password'], $opts);
        $usedLabel = $cfg['label'];
        break;
    } catch (PDOException $e) { $pdo = null; }
}

$results = [];
$ok = true;

if (!$pdo) {
    $ok = false;
    $results[] = ['❌', 'Database connection', 'FAILED — Make sure XAMPP MySQL is running or you are online'];
} else {
    $results[] = ['✅', 'Database connection', 'Connected via ' . $usedLabel];

    $tables = [
        "products" => "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            old_price DECIMAL(10,2) DEFAULT 0,
            stock INT DEFAULT 0,
            badge VARCHAR(20) DEFAULT '',
            battery VARCHAR(50) DEFAULT '',
            camera VARCHAR(50) DEFAULT '',
            storage VARCHAR(50) DEFAULT '',
            colors VARCHAR(200) DEFAULT '',
            image_url TEXT DEFAULT '',
            description TEXT DEFAULT '',
            brand VARCHAR(100) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "accessories" => "CREATE TABLE IF NOT EXISTS accessories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            image_url TEXT DEFAULT '',
            description TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "orders" => "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50),
            customer_name VARCHAR(200),
            phone VARCHAR(30),
            email VARCHAR(200) DEFAULT '',
            product_id INT DEFAULT NULL,
            product_name VARCHAR(200),
            price DECIMAL(10,2),
            original_price DECIMAL(10,2) DEFAULT 0,
            discount INT DEFAULT 0,
            payment_method VARCHAR(50),
            transaction_ref VARCHAR(100),
            delivery_address TEXT DEFAULT '',
            note TEXT DEFAULT '',
            coupon_code VARCHAR(50) DEFAULT '',
            tracking_number VARCHAR(50) DEFAULT '',
            status VARCHAR(30) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "customers" => "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200),
            phone VARCHAR(30) UNIQUE,
            email VARCHAR(200) DEFAULT '',
            total_orders INT DEFAULT 0,
            total_spent DECIMAL(10,2) DEFAULT 0,
            last_order TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "staff" => "CREATE TABLE IF NOT EXISTS staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'cashier',
            api_token VARCHAR(100) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "coupons" => "CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            discount INT NOT NULL,
            min_purchase DECIMAL(10,2) DEFAULT 0,
            valid_until DATE NOT NULL,
            active TINYINT DEFAULT 1,
            uses INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "students" => "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            phone VARCHAR(30) UNIQUE NOT NULL,
            email VARCHAR(200) DEFAULT '',
            password VARCHAR(255) NOT NULL,
            token VARCHAR(100) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "course_lessons" => "CREATE TABLE IF NOT EXISTS course_lessons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id VARCHAR(20) NOT NULL,
            title VARCHAR(300) NOT NULL,
            youtube_url VARCHAR(500) NOT NULL,
            duration VARCHAR(20) DEFAULT '',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "enrollments" => "CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id VARCHAR(20) NOT NULL,
            order_id INT DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_enroll (student_id, course_id)
        )",
        "lesson_progress" => "CREATE TABLE IF NOT EXISTS lesson_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            lesson_id INT NOT NULL,
            completed TINYINT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_progress (student_id, lesson_id)
        )",
    ];

    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            $results[] = ['✅', "Table: $name", 'Created / already exists'];
        } catch (Exception $e) {
            $ok = false;
            $results[] = ['❌', "Table: $name", $e->getMessage()];
        }
    }

    // Insert default admin
    try {
        $check = $pdo->query("SELECT COUNT(*) FROM staff WHERE username='admin'")->fetchColumn();
        if ($check == 0) {
            $pdo->prepare("INSERT INTO staff (username, password, role) VALUES ('admin', :p, 'manager')")
                ->execute([':p' => password_hash('admin123', PASSWORD_DEFAULT)]);
            $results[] = ['✅', 'Default admin account', 'Created — username: admin / password: admin123'];
        } else {
            $results[] = ['ℹ️', 'Default admin account', 'Already exists'];
        }
    } catch (Exception $e) {
        $results[] = ['⚠️', 'Default admin account', $e->getMessage()];
    }

    // Insert sample products if empty
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($count == 0) {
            $samples = [
                ['Samsung Galaxy A14', 12000, 14000, 8, 'sale', '5000mAh', '50MP', '64GB', 'Black,White,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a14-5g.jpg', '6.6" Display, 50MP Camera, 5000mAh Battery', 'Samsung'],
                ['iPhone 11', 35000, 0, 5, 'hot', '3110mAh', '12MP', '64GB', 'Black,White,Red', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-11.jpg', '6.1" Retina, A13 Bionic, Dual Camera', 'iPhone'],
                ['Infinix Smart 7', 9000, 10000, 12, 'sale', '5000mAh', '13MP', '64GB', 'Black,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/infinix-smart-7.jpg', '6.6" HD+, 13MP Camera, 5000mAh Battery', 'Infinix'],
                ['Tecno Spark 10', 11000, 0, 3, 'new', '5000mAh', '16MP', '128GB', 'Black,Gold,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/tecno-spark-10.jpg', '6.6" Display, 16MP Camera, 5000mAh', 'Tecno'],
                ['Samsung Galaxy A05s', 14000, 16000, 6, 'sale', '5000mAh', '50MP', '128GB', 'Black,White,Purple', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a05s.jpg', '6.7" Display, 50MP Triple Camera', 'Samsung'],
                ['Xiaomi Redmi 12', 16000, 0, 4, 'new', '5000mAh', '50MP', '128GB', 'Black,Blue,Silver', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-12.jpg', '6.79" Display, 50MP Camera, 5000mAh', 'Xiaomi'],
                ['Tecno Camon 20', 18000, 20000, 2, 'hot', '5000mAh', '64MP', '256GB', 'Black,Gold', 'https://fdn2.gsmarena.com/vv/bigpic/tecno-camon-20.jpg', '6.67" AMOLED, 64MP Camera, 256GB', 'Tecno'],
                ['Samsung Galaxy A34', 28000, 0, 7, 'new', '5000mAh', '48MP', '128GB', 'Black,White,Silver', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a34-5g.jpg', '6.6" Super AMOLED, 48MP, 5000mAh', 'Samsung'],
                ['iPhone 12', 42000, 45000, 4, 'hot', '2815mAh', '12MP', '64GB', 'Black,White,Blue,Red', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-12.jpg', '6.1" Super Retina XDR, A14 Bionic', 'iPhone'],
                ['Xiaomi Redmi Note 12', 20000, 0, 9, 'new', '5000mAh', '50MP', '128GB', 'Black,Blue,Gold', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-note-12-pro-5g.jpg', '6.67" AMOLED, 50MP, 33W Fast Charge', 'Xiaomi'],
            ];
            $stmt = $pdo->prepare("INSERT INTO products (name,price,old_price,stock,badge,battery,camera,storage,colors,image_url,description,brand) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($samples as $p) $stmt->execute($p);
            $results[] = ['✅', 'Sample products', '10 products inserted'];
        } else {
            $results[] = ['ℹ️', 'Sample products', "$count products already in database"];
        }
    } catch (Exception $e) {
        $results[] = ['⚠️', 'Sample products', $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shewit Mobile — Setup</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{background:#fff;border-radius:20px;padding:40px;max-width:680px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.1)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-size:1.8rem;color:#1a1a2e}
.logo h1 span{color:#e94560}
.logo p{color:#888;font-size:.9rem;margin-top:4px}
table{width:100%;border-collapse:collapse;margin-bottom:28px}
th{background:#1a1a2e;color:#fff;padding:10px 14px;text-align:left;font-size:.82rem}
td{padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:.84rem;vertical-align:top}
td:first-child{font-size:1.1rem;width:36px}
td:last-child{color:#555}
tr:last-child td{border-bottom:none}
.success-box{background:#d4edda;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px}
.success-box h2{color:#155724;margin-bottom:6px}
.success-box p{color:#155724;font-size:.88rem}
.error-box{background:#f8d7da;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px}
.error-box h2{color:#721c24;margin-bottom:6px}
.error-box p{color:#721c24;font-size:.88rem}
.btn{display:inline-block;background:#e94560;color:#fff;padding:13px 32px;border-radius:12px;text-decoration:none;font-weight:600;font-size:1rem;margin:6px}
.btn.purple{background:#9b59b6}
.btn.green{background:#27ae60}
.creds{background:#f8f9fa;border-radius:10px;padding:14px 18px;font-size:.85rem;color:#555;margin-bottom:20px}
.creds strong{color:#1a1a2e}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <h1>📱 Shewit <span>Mobile</span></h1>
    <p>Database Setup — Shire Branch</p>
  </div>

  <?php if($ok): ?>
  <div class="success-box">
    <h2>✅ Setup Complete!</h2>
    <p>Database and all tables created successfully via <strong><?= htmlspecialchars($usedLabel) ?></strong></p>
  </div>
  <?php else: ?>
  <div class="error-box">
    <h2>❌ Setup Failed</h2>
    <p>Could not connect to any database. Start XAMPP MySQL or check your internet connection.</p>
  </div>
  <?php endif; ?>

  <table>
    <thead><tr><th></th><th>Item</th><th>Result</th></tr></thead>
    <tbody>
    <?php foreach($results as $r): ?>
      <tr><td><?= $r[0] ?></td><td><strong><?= htmlspecialchars($r[1]) ?></strong></td><td><?= htmlspecialchars($r[2]) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if($ok): ?>
  <div class="creds">
    <strong>Admin Login:</strong> username: <code>admin</code> &nbsp;|&nbsp; password: <code>admin123</code><br>
    <strong>Change your password</strong> after first login from the Staff tab in Admin Panel.
  </div>
  <div style="text-align:center">
    <a href="index.html" class="btn">🚀 Open the Site</a>
    <a href="index.html#" onclick="localStorage.clear()" class="btn purple">🔄 Fresh Start</a>
  </div>
  <?php else: ?>
  <div style="text-align:center">
    <a href="setup.php" class="btn green">🔄 Try Again</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
