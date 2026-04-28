-- PrintPro Database Schema

CREATE DATABASE IF NOT EXISTS printpro_db;
USE printpro_db;

-- Table: Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    subscription_plan ENUM('basic', 'premium', 'business') DEFAULT 'basic',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_details TEXT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'printing', 'completed', 'shipped') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: Subscription Plans
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    features JSON
);

-- Seed data for plans
INSERT IGNORE INTO plans (name, price, description, features) VALUES
('Basic', 0.00, 'Free forever', '["5 orders/mo", "Standard Support"]'),
('Premium', 29.99, 'For individuals', '["Unlimited orders", "Priority Support", "Email notifications"]'),
('Business', 89.99, 'For teams', '["Unlimited orders", "24/7 Support", "API Access", "Custom Branding"]');
