CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `slug`, `description`) VALUES
('Supervisor', 'supervisor', 'Ground-level operational data entry and breakdown reporting'),
('Dumper Operator', 'operator', 'Vehicle operation and live GPS tracking'),
('Mechanic', 'mechanic', 'Equipment maintenance and breakdown ticket resolution'),
('Owner', 'owner', 'Management dashboard with KPIs and fleet monitoring');
