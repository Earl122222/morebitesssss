DROP TABLE IF EXISTS ingredients;

CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) NOT NULL,
  `threshold` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO `ingredients` (`name`, `quantity`, `unit`, `threshold`) VALUES
('Ahos', 100.00, 'kg', 20),
('Rice', 500.00, 'kg', 100),
('Sugar', 200.00, 'kg', 50); 