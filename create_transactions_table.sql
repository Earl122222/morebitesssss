-- Create transactions table if not exists
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pos_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create transaction items table if not exists
CREATE TABLE IF NOT EXISTS transaction_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES pos_product(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_transaction_user ON transactions(user_id);
CREATE INDEX idx_transaction_date ON transactions(created_at);
CREATE INDEX idx_transaction_items_transaction ON transaction_items(transaction_id);
CREATE INDEX idx_transaction_items_product ON transaction_items(product_id); 