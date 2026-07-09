CREATE TABLE IF NOT EXISTS `trips` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `supervisor_id` INT UNSIGNED NOT NULL,
    `vehicle_id` INT UNSIGNED NOT NULL,
    `shift_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `trip_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `remarks` TEXT DEFAULT NULL,
    `trip_date` DATE NOT NULL,
    `recorded_at` TIMESTAMP NOT NULL,
    `sync_status` ENUM('pending', 'synced', 'conflict') NOT NULL DEFAULT 'synced',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
