USE pos;

-- Drop the existing ingredients table if it exists
DROP TABLE IF EXISTS ingredients;

-- Create the ingredients table with standardized columns
CREATE TABLE ingredients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    quantity DECIMAL(10,2) DEFAULT 0.00,
    unit VARCHAR(20) DEFAULT 'pcs',
    min_stock DECIMAL(10,2) DEFAULT 0.00,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('Available', 'Low Stock', 'Out of Stock') DEFAULT 'Available',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Insert sample data
INSERT INTO ingredients (name, category_id, quantity, unit, min_stock, unit_cost) VALUES
('Coffee Beans', 1, 50.00, 'kg', 10.00, 500.00),
('Milk', 2, 100.00, 'liters', 20.00, 65.00),
('Chicken', 3, 75.00, 'kg', 15.00, 180.00),
('Tomatoes', 4, 30.00, 'kg', 10.00, 45.00),
('Rice', 5, 200.00, 'kg', 50.00, 50.00),
('Sugar', 2, 0.00, 'kg', 25.00, 45.00),
('Cheese', 2, 0.00, 'kg', 10.00, 350.00),
('Bombay', 2, 0.00, 'kg', 5.00, 120.00),
('Ahos', 1, 0.00, 'kg', 8.00, 80.00); 