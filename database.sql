CREATE DATABASE messo_db;
USE messo_db;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2) DEFAULT NULL,
    image VARCHAR(500),
    stock INT DEFAULT 100,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) DEFAULT 'UPI',
    payment_status VARCHAR(30) DEFAULT 'Pending',
    utr_number VARCHAR(100) DEFAULT NULL,
    order_status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

INSERT INTO products
(name, category, description, price, old_price, image)
VALUES
('Premium Almonds', 'Dry Fruits', 'Premium quality almonds', 249, 299,
'https://images.unsplash.com/photo-1508061253366-f7da158b6d46?w=500'),

('Premium Cashews', 'Dry Fruits', 'Fresh crunchy cashews', 299, 349,
'https://images.unsplash.com/photo-1563412885-1397d6a9c6a1?w=500'),

('Golden Raisins', 'Dry Fruits', 'Natural golden raisins', 179, 220,
'https://images.unsplash.com/photo-1596591606975-97ee5cef3a1e?w=500'),

('Pistachio', 'Dry Fruits', 'Premium pistachio nuts', 399, 449,
'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=500'),

('Walnuts', 'Dry Fruits', 'Fresh walnut kernels', 349, 399,
'https://images.unsplash.com/photo-1515543904379-3d757afe72e4?w=500'),

('Organic Dates', 'Dry Fruits', 'Natural sweet dates', 199, 249,
'https://images.unsplash.com/photo-1590080877777-b7d6b7d1d9e1?w=500'),

('Premium Peanuts', 'Snacks', 'Roasted peanuts', 99, 129,
'https://images.unsplash.com/photo-1567892737950-30c4db37cd89?w=500'),

('Healthy Mixed Nuts', 'Dry Fruits', 'Healthy mixed nuts', 449, 499,
'https://images.unsplash.com/photo-1608797178974-15b35a64ede9?w=500');