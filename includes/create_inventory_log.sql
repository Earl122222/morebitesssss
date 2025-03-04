CREATE TABLE IF NOT EXISTS inventory_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient_id INT NOT NULL,
    action_type ENUM('restock', 'deduct', 'adjust') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL,
    action_date DATETIME NOT NULL,
    notes TEXT,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(ingredient_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 