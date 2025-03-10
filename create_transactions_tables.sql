-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES pos_user(user_id)
) ENGINE=InnoDB;

-- Create transaction_items table
CREATE TABLE IF NOT EXISTS transaction_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id),
    FOREIGN KEY (product_id) REFERENCES pos_product(product_id)
) ENGINE=InnoDB; 