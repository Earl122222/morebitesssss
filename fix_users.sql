-- Drop and recreate the user_type column
ALTER TABLE pos_user MODIFY COLUMN user_type ENUM('Admin', 'Cashier', 'Stockman') NOT NULL DEFAULT 'Cashier';

-- Update specific users
UPDATE pos_user SET user_type = 'Stockman' WHERE user_id = 10;
UPDATE pos_user SET user_type = 'Cashier' WHERE user_id IN (11, 12);

-- Set any remaining NULL values to their default
UPDATE pos_user SET user_type = 'Cashier' WHERE user_type IS NULL; 