CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  api_token VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  shipping_address TEXT NOT NULL,
  phone VARCHAR(40) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(180) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO categories (name, slug) VALUES
('Crochet Earrings', 'crochet-earrings'),
('Headbands', 'headbands'),
('Keychains & Plush', 'keychains-plush')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (category_id, name, slug, description, price, stock, image_url, featured, is_active) VALUES
(1, 'Blue Crochet Flower Earrings', 'blue-crochet-flower-earrings', 'Soft handmade floral earrings inspired by Loomi posts.', 12.99, 24, 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=900&q=60', 1, 1),
(2, 'Cozy Ribbed Crochet Headband', 'cozy-ribbed-crochet-headband', 'Warm textured headband in dreamy Loomi-inspired shades.', 16.50, 18, 'https://images.unsplash.com/photo-1543076447-215ad9ba6923?auto=format&fit=crop&w=900&q=60', 1, 1),
(3, 'Cloud Plush Keychain', 'cloud-plush-keychain', 'Cute smiling cloud charm for bags and gift boxes.', 14.00, 32, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=900&q=60', 1, 1),
(3, 'Sunflower Crochet Keyring', 'sunflower-crochet-keyring', 'Detailed sunflower keyring handcrafted with care.', 13.50, 22, 'https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?auto=format&fit=crop&w=900&q=60', 0, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), stock = VALUES(stock), image_url = VALUES(image_url);

-- Default admin account password: Admin@123
INSERT INTO users (name, email, password_hash, role)
VALUES ('Store Admin', 'admin@store.com', '$2y$10$ubjAAEDduesqG6zVnLfM1OesjQW9Sd2wa8k8jQInxSxRJXj.K2N7W', 'admin')
ON DUPLICATE KEY UPDATE role = VALUES(role);
