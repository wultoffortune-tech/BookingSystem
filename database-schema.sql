-- Database schema for CamExpress bus reservation system
-- Run this in MySQL to create the required tables.

CREATE DATABASE IF NOT EXISTS `booking-system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `booking-system`;

CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `id_number` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone_number` VARCHAR(50) DEFAULT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `route` (
    `route_id` INT AUTO_INCREMENT PRIMARY KEY,
    `original_city` VARCHAR(255) NOT NULL,
    `destination` VARCHAR(255) NOT NULL,
    `base_fare` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expired` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bus` (
    `bus_id` INT AUTO_INCREMENT PRIMARY KEY,
    `bus_name` VARCHAR(255) NOT NULL,
    `bus_type` VARCHAR(100) NOT NULL,
    `total_seats` INT NOT NULL DEFAULT 40,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule` (
    `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
    `route_id` INT NOT NULL,
    `bus_id` INT NOT NULL,
    `departure_time` DATETIME NOT NULL,
    `arrival_time` DATETIME NOT NULL,
    `available_seats` INT NOT NULL DEFAULT 0,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `expired` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`route_id`) REFERENCES `route`(`route_id`) ON DELETE CASCADE,
    FOREIGN KEY (`bus_id`) REFERENCES `bus`(`bus_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reservation` (
    `reservation_id` INT AUTO_INCREMENT PRIMARY KEY,
    `passenger_id` INT NOT NULL,
    `schedule_id` INT NOT NULL,
    `seat_number` VARCHAR(50) NOT NULL,
    `fare_paid` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `reservation_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`passenger_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedule`(`schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment` (
    `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT NOT NULL,
    `payment_method` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_status` ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `transaction_reference` VARCHAR(255) DEFAULT NULL,
    `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservation`(`reservation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
