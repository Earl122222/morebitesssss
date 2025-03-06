-- Create database if not exists
CREATE DATABASE IF NOT EXISTS pos;
USE pos;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create ingredients table
CREATE TABLE IF NOT EXISTS ingredients (
    ingredient_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(100) NOT NULL,
    category_id INT,
    quantity DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20),
    min_stock DECIMAL(10,2) DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Insert some sample categories
INSERT INTO categories (category_name, description) VALUES
('Beverages', 'Drinks and liquid refreshments'),
('Dairy', 'Milk and dairy products'),
('Meat', 'Fresh and processed meats'),
('Produce', 'Fresh fruits and vegetables'),
('Dry Goods', 'Non-perishable food items');

-- Insert some sample ingredients
INSERT INTO ingredients (ingredient_name, category_id, quantity, unit, min_stock, unit_cost) VALUES
('Coffee Beans', 1, 50, 'kg', 10, 500),
('Milk', 2, 100, 'liters', 20, 65),
('Chicken', 3, 75, 'kg', 15, 180),
('Tomatoes', 4, 30, 'kg', 10, 45),
('Rice', 5, 200, 'kg', 50, 50); 