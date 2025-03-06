-- First, delete existing test users
DELETE FROM pos_user WHERE user_type IN ('Stockman', 'Cashier');

-- Reset auto increment to allow specific IDs
ALTER TABLE pos_user AUTO_INCREMENT = 10;

-- Insert users with specific IDs
INSERT INTO pos_user (user_id, user_name, user_email, password, user_type, user_status) VALUES
(10, 'Stockman', 'stock@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Stockman', 'Active'),
(11, 'Cashier', 'cashier@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier', 'Active'); 