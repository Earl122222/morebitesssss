DROP TABLE IF EXISTS ingredients_log;

CREATE TABLE ingredients_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    initial DECIMAL(10,2),
    adjustment DECIMAL(10,2),
    remaining DECIMAL(10,2),
    usage_cost DECIMAL(10,2) DEFAULT NULL,
    requested DECIMAL(10,2) DEFAULT NULL,
    fulfilled DECIMAL(10,2) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES pos_user(id)
); 