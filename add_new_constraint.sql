USE pos;

-- Add new foreign key constraint to reference product_categories
ALTER TABLE pos_product
ADD CONSTRAINT fk_product_category
FOREIGN KEY (category_id) REFERENCES product_categories(id)
ON DELETE RESTRICT
ON UPDATE CASCADE; 