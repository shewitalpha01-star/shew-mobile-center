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
                       'add_staff', 'delete_staff', 'add_coupon', 'toggle_coupon', 
                       'delete_coupon', 'get_dashboard'];
                       
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
            
            $pdo->commit();
            echo json_encode(['success' => true, 'order_number' => $order_number, 'tracking' => $tracking, 'discount' => $discount]);
            
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

    // ── CUSTOMER AUTH ─────────────────────────────────────
    case 'register_customer':
        try {
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, password) VALUES (:name,:phone,:email,:pass)");
            $stmt->execute([':name'=>$data['name'],':phone'=>$data['phone'],':email'=>$data['email']??'',':pass'=>$hashed]);
            echo json_encode(['success'=>true]);
        } catch(Exception $e) {
            echo json_encode(['success'=>false,'error'=>'Phone already registered']);
        }
        break;

    case 'customer_login':
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = :phone");
        $stmt->execute([':phone'=>$data['phone']]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($c && password_verify($data['password'], $c['password'])) {
            echo json_encode(['success'=>true,'customer'=>['name'=>$c['name'],'phone'=>$c['phone'],'email'=>$c['email']]]);
        } else {
            echo json_encode(['success'=>false,'error'=>'Invalid credentials']);
        }
        break;

    case 'validate_coupon':
        $cs = $pdo->prepare("SELECT * FROM coupons WHERE code=:code AND active=1 AND valid_until >= CURDATE()");
        $cs->execute([':code'=>strtoupper($data['code'])]);
        $coupon = $cs->fetch(PDO::FETCH_ASSOC);
        if ($coupon && ($data['price']??0) >= $coupon['min_purchase']) {
            echo json_encode(['valid'=>true,'discount'=>$coupon['discount']]);
        } else {
            echo json_encode(['valid'=>false]);
        }
        break;

    case 'update_tracking':
        $stmt = $pdo->prepare("UPDATE orders SET tracking_number = :tracking WHERE id = :id");
        $stmt->execute([':tracking'=>$data['tracking'],':id'=>$data['id']]);
        echo json_encode(['success'=>true]);
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

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action', 'success' => false]);
}
?>