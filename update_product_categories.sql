USE pos;

-- First, remove the foreign key constraint
ALTER TABLE pos_product DROP FOREIGN KEY pos_product_ibfk_1;

-- Update the category_id references to match the new product_categories table
UPDATE pos_product p
INNER JOIN pos_category pc ON p.category_id = pc.category_id
SET p.category_id = (
    SELECT id 
    FROM product_categories 
    WHERE category_name = pc.category_name
);

-- Now we can safely drop the old table
DROP TABLE IF EXISTS pos_category; 