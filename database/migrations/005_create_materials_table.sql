CREATE TABLE IF NOT EXISTS `materials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `materials` (`name`, `description`) VALUES
('Overburden', 'Waste material overlying mineral deposit'),
('Ore - Iron', 'Iron ore'),
('Ore - Manganese', 'Manganese ore'),
('Ore - Copper', 'Copper ore'),
('Ore - Bauxite', 'Bauxite ore'),
('Limestone', 'Limestone'),
('Coal', 'Coal'),
('Other', 'Other materials');
