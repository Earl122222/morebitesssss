-- Add test users with different roles
INSERT INTO pos_user (user_name, user_email, password, user_type, user_status) VALUES
('stockman1', 'stock@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Stockman', 'Active'),
('cashier1', 'cashier@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier', 'Active'); 