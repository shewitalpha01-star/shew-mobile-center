-- Run this in XAMPP phpMyAdmin
-- Go to http://localhost/phpmyadmin → select shewit_mobile → SQL tab → paste this → Go

CREATE DATABASE IF NOT EXISTS shewit_mobile;
USE shewit_mobile;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  old_price DECIMAL(10,2) DEFAULT 0,
  stock INT DEFAULT 0,
  badge VARCHAR(20) DEFAULT '',
  battery VARCHAR(50) DEFAULT '',
  camera VARCHAR(50) DEFAULT '',
  storage VARCHAR(50) DEFAULT '',
  colors VARCHAR(255) DEFAULT '',
  image_url TEXT DEFAULT '',
  description TEXT DEFAULT '',
  brand VARCHAR(100) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(50) UNIQUE,
  customer_name VARCHAR(255),
  phone VARCHAR(20),
  email VARCHAR(255),
  product_id INT,
  product_name VARCHAR(255),
  price DECIMAL(10,2),
  original_price DECIMAL(10,2),
  discount INT DEFAULT 0,
  payment_method VARCHAR(50),
  transaction_ref VARCHAR(100),
  delivery_address TEXT,
  note TEXT,
  coupon_code VARCHAR(50),
  tracking_number VARCHAR(100),
  status VARCHAR(20) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  phone VARCHAR(20) UNIQUE,
  email VARCHAR(255),
  password VARCHAR(255),
  total_orders INT DEFAULT 0,
  total_spent DECIMAL(10,2) DEFAULT 0,
  last_order TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role VARCHAR(50) DEFAULT 'cashier',
  api_token VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  discount INT,
  min_purchase DECIMAL(10,2) DEFAULT 0,
  valid_until DATE,
  uses INT DEFAULT 0,
  active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS accessories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  price DECIMAL(10,2),
  stock INT DEFAULT 0,
  image_url TEXT,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin account (password: shewit2026)
DELETE FROM staff WHERE username = 'admin';
INSERT INTO staff (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');

-- Sample coupons
INSERT IGNORE INTO coupons (code, discount, min_purchase, valid_until) VALUES
('WELCOME10', 10, 0, '2026-12-31'),
('SAVE20', 20, 15000, '2026-12-31');

-- Sample products
INSERT IGNORE INTO products (name, price, old_price, stock, badge, battery, camera, storage, colors, image_url, description, brand) VALUES
('Samsung Galaxy A14', 12000, 14000, 8, 'sale', '5000mAh', '50MP', '64GB', 'Black,White,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a14-5g.jpg', '6.6" Display, 50MP Camera', 'Samsung'),
('iPhone 11', 35000, 0, 5, 'hot', '3110mAh', '12MP', '64GB', 'Black,White,Red', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-11.jpg', '6.1" Retina, A13 Bionic', 'iPhone'),
('Infinix Smart 7', 9000, 10000, 12, 'sale', '5000mAh', '13MP', '64GB', 'Black,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/infinix-smart-7.jpg', '6.6" HD+, 13MP Camera', 'Infinix'),
('Tecno Spark 10', 11000, 0, 3, 'new', '5000mAh', '16MP', '128GB', 'Black,Gold,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/tecno-spark-10.jpg', '6.6" Display, 16MP Camera', 'Tecno'),
('Samsung Galaxy A05s', 14000, 16000, 6, 'sale', '5000mAh', '50MP', '128GB', 'Black,White,Purple', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a05s.jpg', '6.7" Display, 50MP Triple Camera', 'Samsung'),
('Xiaomi Redmi 12', 16000, 0, 4, 'new', '5000mAh', '50MP', '128GB', 'Black,Blue,Silver', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-12.jpg', '6.79" Display, 50MP Camera', 'Xiaomi'),
('Tecno Camon 20', 18000, 20000, 2, 'hot', '5000mAh', '64MP', '256GB', 'Black,Gold', 'https://fdn2.gsmarena.com/vv/bigpic/tecno-camon-20.jpg', '6.67" AMOLED, 64MP Camera', 'Tecno'),
('Samsung Galaxy A34', 28000, 0, 7, 'new', '5000mAh', '48MP', '128GB', 'Black,White,Silver', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a34-5g.jpg', '6.6" Super AMOLED, 48MP', 'Samsung'),
('iPhone 12', 42000, 45000, 4, 'hot', '2815mAh', '12MP', '64GB', 'Black,White,Blue,Red', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-12.jpg', '6.1" Super Retina XDR', 'iPhone'),
('Xiaomi Redmi Note 12', 20000, 0, 9, 'new', '5000mAh', '50MP', '128GB', 'Black,Blue,Gold', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-note-12-pro-5g.jpg', '6.67" AMOLED, 50MP', 'Xiaomi'),
('iPhone 13', 55000, 60000, 3, 'hot', '3227mAh', '12MP Dual', '128GB', 'Black,White,Blue,Pink,Red', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-13.jpg', '6.1" Super Retina XDR, A15 Bionic', 'iPhone'),
('iPhone 14', 72000, 0, 2, 'new', '3279mAh', '12MP Dual', '128GB', 'Black,White,Blue,Purple,Yellow', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-14.jpg', '6.1" Super Retina XDR, A15 Bionic', 'iPhone'),
('Samsung Galaxy S23', 65000, 70000, 3, 'hot', '3900mAh', '50MP Triple', '128GB', 'Black,White,Green,Lavender', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s23.jpg', '6.1" Dynamic AMOLED, Snapdragon 8 Gen 2', 'Samsung'),
('Samsung Galaxy A54', 32000, 35000, 5, 'sale', '5000mAh', '50MP Triple', '128GB', 'Black,White,Violet,Lime', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a54.jpg', '6.4" Super AMOLED, 50MP OIS Camera', 'Samsung'),
('Xiaomi 13 Lite', 38000, 42000, 4, 'new', '4500mAh', '50MP Triple', '256GB', 'Black,White,Pink', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-13-lite.jpg', '6.55" AMOLED 120Hz, Snapdragon 7 Gen 1', 'Xiaomi'),
('Tecno Phantom X2', 45000, 48000, 2, 'hot', '5160mAh', '64MP Triple', '256GB', 'Black,Gold', 'https://fdn2.gsmarena.com/vv/bigpic/tecno-phantom-x2.jpg', '6.8" AMOLED 120Hz, Dimensity 9000', 'Tecno'),
('Infinix Note 30', 15000, 17000, 8, 'sale', '5000mAh', '108MP', '128GB', 'Black,Blue,Gold', 'https://fdn2.gsmarena.com/vv/bigpic/infinix-note-30.jpg', '6.78" FHD+ 120Hz, 108MP Camera', 'Infinix'),
('Infinix Hot 30', 10500, 12000, 10, 'sale', '5000mAh', '13MP', '128GB', 'Black,Blue,White', 'https://fdn2.gsmarena.com/vv/bigpic/infinix-hot-30.jpg', '6.78" HD+ 90Hz, Gaming Mode', 'Infinix'),
('Samsung Galaxy A15', 13500, 15000, 7, 'new', '5000mAh', '50MP Triple', '128GB', 'Black,Blue,Yellow', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a15.jpg', '6.5" Super AMOLED, 50MP Triple Camera', 'Samsung'),
('Xiaomi Redmi 13C', 11500, 0, 6, 'new', '5000mAh', '50MP', '128GB', 'Black,Blue,Green', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-13c.jpg', '6.74" HD+ 90Hz, MediaTek Helio G85', 'Xiaomi'),
('Tecno Spark 20', 13000, 14500, 5, 'new', '5000mAh', '50MP', '128GB', 'Black,Gold,Blue', 'https://fdn2.gsmarena.com/vv/bigpic/tecno-spark-20.jpg', '6.56" HD+ 90Hz, 50MP AI Camera', 'Tecno'),
('iPhone SE 2022', 40000, 44000, 3, 'sale', '2018mAh', '12MP', '64GB', 'Black,White,Red', 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-se-2022.jpg', '4.7" Retina HD, A15 Bionic Chip', 'iPhone'),
('Samsung Galaxy M34', 22000, 25000, 6, 'sale', '6000mAh', '50MP Triple', '128GB', 'Black,Blue,Silver', 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-m34.jpg', '6.5" Super AMOLED 120Hz, 6000mAh', 'Samsung'),
('Xiaomi Poco X5', 25000, 28000, 4, 'hot', '5000mAh', '48MP Triple', '128GB', 'Black,Blue,Yellow', 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-poco-x5.jpg', '6.67" AMOLED 120Hz, Snapdragon 695', 'Xiaomi');
