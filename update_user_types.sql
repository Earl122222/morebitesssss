-- First, modify the user_type enum to include all types
ALTER TABLE pos_user MODIFY COLUMN user_type ENUM('Admin', 'Cashier', 'Stockman') NOT NULL DEFAULT 'Cashier';

-- Update any existing 'Staff' users to 'Cashier' (since Staff is being removed)
UPDATE pos_user SET user_type = 'Cashier' WHERE user_type = 'Staff'; 