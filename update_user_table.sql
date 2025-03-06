-- Add user_email column if it doesn't exist
ALTER TABLE pos_user ADD COLUMN IF NOT EXISTS user_email VARCHAR(255) NOT NULL AFTER user_name;

-- Add user_status column if it doesn't exist
ALTER TABLE pos_user ADD COLUMN IF NOT EXISTS user_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active' AFTER user_type;

-- Update the admin user with an email
UPDATE pos_user SET user_email = 'admin@example.com' WHERE user_name = 'admin'; 