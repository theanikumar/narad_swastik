CREATE TABLE IF NOT EXISTS `shifts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shifts` (`name`, `start_time`, `end_time`) VALUES
('Morning', '06:00:00', '14:00:00'),
('Evening', '14:00:00', '22:00:00'),
('Night', '22:00:00', '06:00:00');
