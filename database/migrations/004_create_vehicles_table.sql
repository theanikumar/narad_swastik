CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_number` VARCHAR(50) NOT NULL UNIQUE,
    `vehicle_type` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('active', 'breakdown', 'maintenance', 'retired') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
