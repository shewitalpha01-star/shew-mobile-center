<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

require_once 'db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// Simple API authentication for protected routes
$protected_actions = ['add_product', 'update_product', 'delete_product', 
                       'add_accessory', 'delete_accessory', 'update_order_status',
                       'delete_order', 'add_staff', 'delete_staff', 'add_coupon', 
                       'toggle_coupon', 'delete_coupon', 'add_lesson', 'delete_lesson',
                       'get_all_lessons', 'get_enrollments_admin'];
                       
$is_protected = in_array($action, $protected_actions);

if ($is_protected) {
    $headers = getallheaders();
    $api_key = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
    // Simple API key check - change this to your secret key
    $valid_api_key = 'shewit_mobile_secret_2026';
    
    if ($api_key !== $valid_api_key) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized access', 'success' => false]);
        exit;
    }
}

// Helper function to generate order number
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Auto-create tables if they don't exist
$pdo->exec("
CREATE TABLE IF NOT EXISTS products (
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
);

CREATE TABLE IF NOT EXISTS accessories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT DEFAULT 0,
  image_url TEXT DEFAULT '',
  description TEXT DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
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
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200),
  phone VARCHAR(30) UNIQUE,
  email VARCHAR(200) DEFAULT '',
  total_orders INT DEFAULT 0,
  total_spent DECIMAL(10,2) DEFAULT 0,
  last_order TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'cashier',
  api_token VARCHAR(100) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NOT NULL,
  discount INT NOT NULL,
  min_purchase DECIMAL(10,2) DEFAULT 0,
  valid_until DATE NOT NULL,
  active TINYINT DEFAULT 1,
  uses INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  phone VARCHAR(30) UNIQUE NOT NULL,
  email VARCHAR(200) DEFAULT '',
  password VARCHAR(255) NOT NULL,
  token VARCHAR(100) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS course_lessons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id VARCHAR(20) NOT NULL,
  title VARCHAR(300) NOT NULL,
  youtube_url VARCHAR(500) NOT NULL,
  duration VARCHAR(20) DEFAULT '',
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id VARCHAR(20) NOT NULL,
  order_id INT DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'active',
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_enroll (student_id, course_id)
);

CREATE TABLE IF NOT EXISTS lesson_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  lesson_id INT NOT NULL,
  completed TINYINT DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_progress (student_id, lesson_id)
);

CREATE TABLE IF NOT EXISTS courses (
  id VARCHAR(20) PRIMARY KEY,
  cat VARCHAR(50) NOT NULL,
  emoji VARCHAR(15) DEFAULT '',
  bg VARCHAR(400) DEFAULT '',
  name VARCHAR(300) NOT NULL,
  instructor VARCHAR(200) NOT NULL,
  level VARCHAR(30) DEFAULT 'beginner',
  duration VARCHAR(50) DEFAULT '',
  students_count VARCHAR(40) DEFAULT '',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  old_price DECIMAL(10,2) DEFAULT 0,
  tags_json TEXT,
  includes_json TEXT,
  description TEXT,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
");

// Seed catalog courses once (matches storefront / admin lesson course_id values)
if ((int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn() === 0) {
    $seedCourses = [
        ['cr1','Programming','💻','linear-gradient(135deg,#1a1a2e,#0f3460)','Python Programming for Beginners','Dawit Tesfaye','beginner','40 hrs','1,240',1500,2500,['Python','Coding','Automation'],['40 video lessons','Source code files','Certificate of completion','Lifetime access','WhatsApp support group'],'Learn Python from scratch. Variables, loops, functions, OOP and real projects. Perfect for absolute beginners.'],
        ['cr2','Programming','🌐','linear-gradient(135deg,#e94560,#c0392b)','Full Stack Web Development','Yonas Haile','intermediate','80 hrs','890',3500,5000,['HTML','CSS','JavaScript','PHP','MySQL'],['80 video lessons','5 real projects','Certificate','Lifetime access','Code review sessions'],'Build complete websites from front to back. HTML, CSS, JavaScript, PHP, MySQL and deployment.'],
        ['cr3','Networking','🌐','linear-gradient(135deg,#27ae60,#1e8449)','CCNA Network Fundamentals','Bereket Alemu','intermediate','60 hrs','650',2800,4000,['Cisco','CCNA','Networking','TCP/IP'],['60 video lessons','Packet Tracer labs','Practice exams','Certificate','Study guide PDF'],'Prepare for Cisco CCNA certification. Routing, switching, VLANs, subnetting and network troubleshooting.'],
        ['cr4','Security','🔒','linear-gradient(135deg,#2c3e50,#e74c3c)','Ethical Hacking & Cybersecurity','Henok Girma','advanced','70 hrs','420',4000,6000,['Kali Linux','Penetration Testing','Security'],['70 video lessons','Virtual lab environment','CTF challenges','Certificate','Private Discord group'],'Learn ethical hacking, penetration testing, vulnerability assessment and how to protect systems.'],
        ['cr5','Design','🎨','linear-gradient(135deg,#fd79a8,#e84393)','Graphic Design with Photoshop','Meron Tadesse','beginner','35 hrs','980',1200,2000,['Photoshop','Design','Branding'],['35 video lessons','Design assets pack','Certificate','Lifetime access','Project feedback'],'Master Adobe Photoshop for logo design, photo editing, social media graphics and print design.'],
        ['cr6','Mobile','📱','linear-gradient(135deg,#3498db,#2980b9)','Android App Development','Kibrom Tekle','intermediate','55 hrs','560',3000,4500,['Android','Java','Kotlin','Mobile'],['55 video lessons','3 complete apps','Google Play guide','Certificate','Code review'],'Build Android apps from scratch using Java and Kotlin. Publish your app to Google Play Store.'],
        ['cr7','CCTV','📷','linear-gradient(135deg,#1a1a2e,#16213e)','CCTV Installation & Configuration','Shewit Tech Team','beginner','20 hrs','340',800,1500,['CCTV','Security','Installation','DVR/NVR'],['20 video lessons','Installation manual PDF','Certificate','WhatsApp support','Practical demo videos'],'Learn to install, configure and maintain CCTV systems. Covers analog, IP cameras, DVR/NVR setup and remote viewing.'],
        ['cr8','Programming','🐍','linear-gradient(135deg,#f39c12,#e67e22)','Data Science with Python','Tigist Bekele','advanced','65 hrs','310',4500,6500,['Python','Data Science','Machine Learning','Pandas'],['65 video lessons','Datasets & notebooks','Kaggle projects','Certificate','Mentorship sessions'],'Analyze data, build ML models and create visualizations using Python, Pandas, NumPy and Scikit-learn.'],
        ['cr9','Networking','🔧','linear-gradient(135deg,#16a085,#1abc9c)','Network Troubleshooting & Support','Amanuel Desta','beginner','25 hrs','720',1000,0,['Networking','IT Support','Troubleshooting'],['25 video lessons','Troubleshooting checklists','Certificate','Lifetime access','Q&A sessions'],'Practical IT support skills. Diagnose and fix common network issues, configure routers and provide helpdesk support.'],
        ['cr10','Design','🎬','linear-gradient(135deg,#8e44ad,#9b59b6)','Video Editing with Premiere Pro','Selam Hailu','beginner','30 hrs','850',1500,2200,['Premiere Pro','Video Editing','YouTube'],['30 video lessons','Project files','Certificate','Lifetime access','YouTube growth tips'],'Edit professional videos for YouTube, social media and business. Transitions, color grading, audio mixing.'],
    ];
    $ins = $pdo->prepare("INSERT INTO courses (id, cat, emoji, bg, name, instructor, level, duration, students_count, price, old_price, tags_json, includes_json, description) VALUES (:id,:cat,:emoji,:bg,:name,:instructor,:level,:duration,:students_count,:price,:old_price,:tags_json,:includes_json,:description)");
    foreach ($seedCourses as $sc) {
        $ins->execute([
            ':id' => $sc[0], ':cat' => $sc[1], ':emoji' => $sc[2], ':bg' => $sc[3], ':name' => $sc[4], ':instructor' => $sc[5], ':level' => $sc[6], ':duration' => $sc[7], ':students_count' => $sc[8],
            ':price' => $sc[9], ':old_price' => $sc[10], ':tags_json' => json_encode($sc[11]), ':includes_json' => json_encode($sc[12]), ':description' => $sc[13],
        ]);
    }
}

// Insert default admin if not exists
$check = $pdo->query("SELECT COUNT(*) FROM staff WHERE username='admin'")->fetchColumn();
if ($check == 0) {
    $pdo->prepare("INSERT INTO staff (username, password, role) VALUES ('admin', :p, 'manager')")
        ->execute([':p' => password_hash('admin123', PASSWORD_DEFAULT)]);
}

switch ($action) {

    // ── PRODUCTS ──────────────────────────────────────────
    case 'get_products':
        $brand = $_GET['brand'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        if ($brand) {
            $sql .= " AND brand = :brand";
            $params[':brand'] = $brand;
        }
        if ($sort === 'low') $sql .= " ORDER BY price ASC";
        elseif ($sort === 'high') $sql .= " ORDER BY price DESC";
        else $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_product':
        $brand = $data['brand'] ?? explode(' ', $data['name'])[0];
        $stmt = $pdo->prepare("INSERT INTO products (name, price, old_price, stock, badge, battery, camera, storage, colors, image_url, description, brand) VALUES (:name,:price,:old_price,:stock,:badge,:battery,:camera,:storage,:colors,:image_url,:description,:brand)");
        $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':old_price' => $data['old_price'] ?? 0,
            ':stock' => $data['stock'] ?? 0,
            ':badge' => $data['badge'] ?? '',
            ':battery' => $data['battery'] ?? '',
            ':camera' => $data['camera'] ?? '',
            ':storage' => $data['storage'] ?? '',
            ':colors' => $data['colors'] ?? '',
            ':image_url' => $data['image_url'] ?? '',
            ':description' => $data['description'] ?? '',
            ':brand' => $brand
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update_product':
        $stmt = $pdo->prepare("UPDATE products SET name=:name, price=:price, old_price=:old_price, stock=:stock, badge=:badge, battery=:battery, camera=:camera, storage=:storage, colors=:colors, image_url=:image_url, description=:description, brand=:brand WHERE id=:id");
        $stmt->execute([
            ':id' => $data['id'],
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':old_price' => $data['old_price'] ?? 0,
            ':stock' => $data['stock'],
            ':badge' => $data['badge'] ?? '',
            ':battery' => $data['battery'] ?? '',
            ':camera' => $data['camera'] ?? '',
            ':storage' => $data['storage'] ?? '',
            ':colors' => $data['colors'] ?? '',
            ':image_url' => $data['image_url'] ?? '',
            ':description' => $data['description'] ?? '',
            ':brand' => $data['brand'] ?? ''
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_product':
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=:id");
        $stmt->execute([':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    // ── ACCESSORIES ───────────────────────────────────────
    case 'get_accessories':
        $stmt = $pdo->query("SELECT * FROM accessories ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_accessory':
        $stmt = $pdo->prepare("INSERT INTO accessories (name, price, stock, image_url, description) VALUES (:name,:price,:stock,:image_url,:description)");
        $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':stock' => $data['stock'] ?? 0,
            ':image_url' => $data['image_url'] ?? '',
            ':description' => $data['description'] ?? ''
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'delete_accessory':
        $stmt = $pdo->prepare("DELETE FROM accessories WHERE id=:id");
        $stmt->execute([':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    // ── ORDERS ────────────────────────────────────────────
    case 'place_order':
        try {
            $pdo->beginTransaction();
            
            // Validate product stock if product_id provided
            if (!empty($data['product_id'])) {
                $stock_check = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
                $stock_check->execute([':id' => $data['product_id']]);
                $current_stock = $stock_check->fetchColumn();
                
                if ($current_stock <= 0) {
                    throw new Exception('Product is out of stock');
                }
            }
            
            // Validate coupon if provided
            $discount = 0;
            $final_price = $data['price'];
            $original_price = $data['price'];
            
            if (!empty($data['coupon_code'])) {
                $cs = $pdo->prepare("SELECT * FROM coupons WHERE code=:code AND active=1 AND valid_until >= CURDATE()");
                $cs->execute([':code' => $data['coupon_code']]);
                $coupon = $cs->fetch(PDO::FETCH_ASSOC);
                if ($coupon && $original_price >= $coupon['min_purchase']) {
                    $discount = $coupon['discount'];
                    $final_price = $original_price * (1 - $discount / 100);
                    $pdo->prepare("UPDATE coupons SET uses=uses+1 WHERE id=:id")->execute([':id' => $coupon['id']]);
                }
            }
            
            // Reduce product stock
            if (!empty($data['product_id'])) {
                $update_stock = $pdo->prepare("UPDATE products SET stock = stock - 1 WHERE id = :id AND stock > 0");
                $update_stock->execute([':id' => $data['product_id']]);
            }
            
            $order_number = generateOrderNumber();
            $tracking = 'TRK' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_name, phone, email, product_id, product_name, price, original_price, discount, payment_method, transaction_ref, delivery_address, note, coupon_code, tracking_number, status) VALUES (:order_number, :name, :phone, :email, :product_id, :product_name, :price, :original_price, :discount, :method, :ref, :address, :note, :coupon, :tracking, 'pending')");
            $stmt->execute([
                ':order_number' => $order_number,
                ':name' => $data['customer_name'],
                ':phone' => $data['phone'],
                ':email' => $data['email'] ?? '',
                ':product_id' => $data['product_id'] ?? null,
                ':product_name' => $data['product_name'],
                ':price' => round($final_price, 2),
                ':original_price' => $original_price,
                ':discount' => $discount,
                ':method' => $data['payment_method'],
                ':ref' => $data['transaction_ref'],
                ':address' => $data['delivery_address'],
                ':note' => $data['note'] ?? '',
                ':coupon' => $data['coupon_code'] ?? '',
                ':tracking' => $tracking
            ]);
            
            // Update or insert customer
            $pdo->prepare("INSERT INTO customers (name, phone, email, total_orders, total_spent, last_order) VALUES (:name, :phone, :email, 1, :price, NOW()) ON DUPLICATE KEY UPDATE total_orders = total_orders + 1, total_spent = total_spent + :price2, last_order = NOW(), name = :name2, email = :email2")->execute([
                ':name' => $data['customer_name'],
                ':phone' => $data['phone'],
                ':email' => $data['email'] ?? '',
                ':price' => round($final_price, 2),
                ':price2' => round($final_price, 2),
                ':name2' => $data['customer_name'],
                ':email2' => $data['email'] ?? ''
            ]);
            
            $newOrderId = (int)$pdo->lastInsertId();
            $pdo->commit();
            echo json_encode(['success' => true, 'order_number' => $order_number, 'order_id' => $newOrderId, 'tracking' => $tracking, 'discount' => $discount]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_orders':
        $status = $_GET['status'] ?? '';
        $sql = "SELECT * FROM orders";
        if ($status) $sql .= " WHERE status = :status";
        $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        if ($status) $stmt->bindValue(':status', $status);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'update_order_status':
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => strtolower($data['status']), ':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_order':
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
        $stmt->execute([':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    // ── CUSTOMERS ─────────────────────────────────────────
    case 'get_customers':
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY total_orders DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // ── STAFF ─────────────────────────────────────────────
    case 'login':
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = :u");
        $stmt->execute([':u' => $data['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify hashed password
        if ($user && password_verify($data['password'], $user['password'])) {
            // Generate API token for session
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE staff SET api_token = :token WHERE id = :id")->execute([
                ':token' => $token,
                ':id' => $user['id']
            ]);
            echo json_encode(['success' => true, 'role' => $user['role'], 'username' => $user['username'], 'token' => $token]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
        break;

    case 'add_staff':
        try {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO staff (username, password, role) VALUES (:u, :p, :r)");
            $stmt->execute([':u' => $data['username'], ':p' => $hashed_password, ':r' => $data['role']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
        }
        break;

    case 'get_staff':
        $stmt = $pdo->query("SELECT id, username, role, created_at FROM staff");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'delete_staff':
        $stmt = $pdo->prepare("DELETE FROM staff WHERE id = :id AND username != 'admin'");
        $stmt->execute([':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    // ── COUPONS ───────────────────────────────────────────
    case 'get_coupons':
        $stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_coupon':
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount, min_purchase, valid_until) VALUES (:code, :discount, :min, :until)");
            $stmt->execute([
                ':code' => strtoupper($data['code']),
                ':discount' => $data['discount'],
                ':min' => $data['min_purchase'] ?? 0,
                ':until' => $data['valid_until']
            ]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Coupon code already exists']);
        }
        break;

    case 'toggle_coupon':
        $stmt = $pdo->prepare("UPDATE coupons SET active = NOT active WHERE id = :id");
        $stmt->execute([':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_coupon':
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = :id");
        $stmt->execute([':id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    // ── DASHBOARD ─────────────────────────────────────────
    case 'get_dashboard':
        $stats = [];
        $stats['total_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stats['total_revenue'] = (float)$pdo->query("SELECT COALESCE(SUM(price), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
        $stats['total_customers'] = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $stats['pending_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        
        $status_query = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
        $stats['order_status'] = $status_query->fetchAll(PDO::FETCH_ASSOC);
        
        $top_query = $pdo->query("SELECT product_name, COUNT(*) as sales FROM orders WHERE product_name IS NOT NULL GROUP BY product_name ORDER BY sales DESC LIMIT 5");
        $stats['top_products'] = $top_query->fetchAll(PDO::FETCH_ASSOC);
        
        $recent_query = $pdo->query("SELECT id, order_number, customer_name, product_name, price, status, tracking_number, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
        $stats['recent_orders'] = $recent_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($stats);
        break;

    case 'get_courses':
        $stmt = $pdo->query("SELECT id, cat, emoji, bg, name, instructor, level, duration, students_count, price, old_price, tags_json, includes_json, description FROM courses WHERE active = 1 ORDER BY id ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_course':
        $cid = $_GET['id'] ?? '';
        if ($cid === '') {
            echo json_encode(['error' => 'Missing id', 'success' => false]);
            break;
        }
        $stmt = $pdo->prepare("SELECT c.id, c.cat, c.emoji, c.bg, c.name, c.instructor, c.level, c.duration, c.students_count, c.price, c.old_price, c.tags_json, c.includes_json, c.description,
            (SELECT COUNT(*) FROM course_lessons cl WHERE cl.course_id = c.id) AS lesson_count
            FROM courses c WHERE c.id = :id AND c.active = 1");
        $stmt->execute([':id' => $cid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['error' => 'Course not found', 'success' => false]);
            break;
        }
        echo json_encode($row);
        break;

    // ── STUDENTS ──────────────────────────────────────────
    case 'student_register':
        try {
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (name, phone, email, password) VALUES (:name, :phone, :email, :password)");
            $stmt->execute([
                ':name' => $data['name'],
                ':phone' => $data['phone'],
                ':email' => $data['email'] ?? '',
                ':password' => $hashed
            ]);
            $student_id = $pdo->lastInsertId();
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE students SET token=:token WHERE id=:id")->execute([':token'=>$token,':id'=>$student_id]);
            echo json_encode(['success' => true, 'token' => $token, 'name' => $data['name'], 'id' => $student_id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Phone number already registered']);
        }
        break;

    case 'student_login':
        $stmt = $pdo->prepare("SELECT * FROM students WHERE phone = :phone");
        $stmt->execute([':phone' => $data['phone']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student && password_verify($data['password'], $student['password'])) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE students SET token=:token WHERE id=:id")->execute([':token'=>$token,':id'=>$student['id']]);
            echo json_encode(['success' => true, 'token' => $token, 'name' => $student['name'], 'id' => $student['id']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid phone or password']);
        }
        break;

    case 'get_my_enrollments':
        $headers = getallheaders();
        $stoken = $headers['X-Student-Token'] ?? $headers['x-student-token'] ?? '';
        $stu = $pdo->prepare("SELECT id FROM students WHERE token=:t");
        $stu->execute([':t' => $stoken]);
        $sid = $stu->fetchColumn();
        if (!$sid) { echo json_encode(['success'=>false,'error'=>'Not logged in']); break; }
        $stmt = $pdo->prepare("SELECT course_id, enrolled_at FROM enrollments WHERE student_id=:sid AND status='active'");
        $stmt->execute([':sid' => $sid]);
        echo json_encode(['success'=>true,'enrollments'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_course_lessons':
        $cid = $_GET['course_id'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE course_id=:cid ORDER BY sort_order ASC, id ASC");
        $stmt->execute([':cid' => $cid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_lesson_progress':
        $headers = getallheaders();
        $stoken = $headers['X-Student-Token'] ?? $headers['x-student-token'] ?? '';
        $stu = $pdo->prepare("SELECT id FROM students WHERE token=:t");
        $stu->execute([':t' => $stoken]);
        $sid = $stu->fetchColumn();
        if (!$sid) { echo json_encode([]); break; }
        $cid = $_GET['course_id'] ?? '';
        $stmt = $pdo->prepare("SELECT lp.lesson_id, lp.completed FROM lesson_progress lp JOIN course_lessons cl ON cl.id=lp.lesson_id WHERE lp.student_id=:sid AND cl.course_id=:cid");
        $stmt->execute([':sid'=>$sid,':cid'=>$cid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'mark_lesson':
        $headers = getallheaders();
        $stoken = $headers['X-Student-Token'] ?? $headers['x-student-token'] ?? '';
        $stu = $pdo->prepare("SELECT id FROM students WHERE token=:t");
        $stu->execute([':t' => $stoken]);
        $sid = $stu->fetchColumn();
        if (!$sid) { echo json_encode(['success'=>false]); break; }
        $stmt = $pdo->prepare("INSERT INTO lesson_progress (student_id, lesson_id, completed) VALUES (:sid,:lid,1) ON DUPLICATE KEY UPDATE completed=:c");
        $stmt->execute([':sid'=>$sid,':lid'=>$data['lesson_id'],':c'=>$data['completed']?1:0]);
        echo json_encode(['success'=>true]);
        break;

    case 'enroll_student':
        $headers = getallheaders();
        $stoken = $headers['X-Student-Token'] ?? $headers['x-student-token'] ?? '';
        $stu = $pdo->prepare("SELECT id FROM students WHERE token=:t");
        $stu->execute([':t' => $stoken]);
        $sid = $stu->fetchColumn();
        if (!$sid) { echo json_encode(['success'=>false,'error'=>'Not logged in']); break; }
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO enrollments (student_id, course_id, order_id) VALUES (:sid,:cid,:oid)");
            $stmt->execute([':sid'=>$sid,':cid'=>$data['course_id'],':oid'=>$data['order_id']??null]);
            echo json_encode(['success'=>true]);
        } catch(Exception $e) {
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        }
        break;

    // ── COURSE LESSONS (ADMIN) ─────────────────────────────
    case 'add_lesson':
        $stmt = $pdo->prepare("INSERT INTO course_lessons (course_id, title, youtube_url, duration, sort_order) VALUES (:cid,:title,:url,:dur,:sort)");
        $stmt->execute([
            ':cid' => $data['course_id'],
            ':title' => $data['title'],
            ':url' => $data['youtube_url'],
            ':dur' => $data['duration'] ?? '',
            ':sort' => $data['sort_order'] ?? 0
        ]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;

    case 'delete_lesson':
        $stmt = $pdo->prepare("DELETE FROM course_lessons WHERE id=:id");
        $stmt->execute([':id'=>$data['id']]);
        echo json_encode(['success'=>true]);
        break;

    case 'get_all_lessons':
        $stmt = $pdo->query("SELECT * FROM course_lessons ORDER BY course_id, sort_order ASC, id ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_enrollments_admin':
        $stmt = $pdo->query("SELECT e.*, s.name as student_name, s.phone FROM enrollments e JOIN students s ON s.id=e.student_id ORDER BY e.enrolled_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action', 'success' => false]);
}
?>