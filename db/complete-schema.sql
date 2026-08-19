-- Compléments au schéma de base (database-schema.sql)
-- Ajoute les tables et colonnes que le code utilise mais que le schéma d'origine ne créait pas.
-- A exécuter APRES database-schema.sql.

USE `booking-system`;

-- ==========================================================
-- Colonnes manquantes sur `reservation`
-- ==========================================================
ALTER TABLE `reservation`
    ADD COLUMN `booking_code` VARCHAR(20) DEFAULT NULL AFTER `reservation_id`,
    ADD COLUMN `seats_released` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
    ADD COLUMN `assisted_by_staff` INT DEFAULT NULL AFTER `seats_released`,
    ADD COLUMN `assistance_notes` TEXT DEFAULT NULL AFTER `assisted_by_staff`,
    ADD UNIQUE KEY `reservation_booking_code_unique` (`booking_code`);

-- ==========================================================
-- Table `staff`
-- (rôles staff : login/login-process.php vérifie le statut ici)
-- ==========================================================
CREATE TABLE IF NOT EXISTS `staff` (
    `staff_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `position` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- Table `counter` (comptoirs / agences)
-- ==========================================================
CREATE TABLE IF NOT EXISTS `counter` (
    `counter_id` INT AUTO_INCREMENT PRIMARY KEY,
    `counter_code` VARCHAR(50) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `city` VARCHAR(255) NOT NULL,
    `phone_number` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `admin_id` INT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
