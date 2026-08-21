-- ============================================
-- RESERVATION SYSTEM - COMPLETE DATABASE
-- All tables match your PHP code
-- MySQL 5.6/5.7 Compatible
-- ============================================

-- ============================================
-- CREATE DATABASE
-- ============================================
DROP DATABASE IF EXISTS `reservation-system`;
CREATE DATABASE `reservation-system` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- ============================================
-- USE THE NEW DATABASE
-- ============================================
USE `reservation-system`;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE `users` (
    `user_id` int(11) NOT NULL AUTO_INCREMENT,
    `full_name` varchar(100) NOT NULL,
    `date_of_birth` date DEFAULT NULL,
    `id_number` varchar(50) DEFAULT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone_number` varchar(20) DEFAULT NULL,
    `role` enum('user','admin','staff') DEFAULT 'user',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 2. STAFF TABLE
-- ============================================
CREATE TABLE `staff` (
    `staff_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `staff_code` varchar(20) NOT NULL,
    `position` varchar(50) NOT NULL,
    `department` varchar(50) DEFAULT 'Operations',
    `hire_date` date NOT NULL,
    `status` enum('active','inactive','suspended') DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`staff_id`),
    UNIQUE KEY `staff_code` (`staff_code`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 3. AGENCY TABLE
-- ============================================
CREATE TABLE `agency` (
    `agency_id` int(11) NOT NULL AUTO_INCREMENT,
    `agency_code` varchar(20) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `location` varchar(100) DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `phone_number` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `status` enum('active','inactive','closed') DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`agency_id`),
    UNIQUE KEY `agency_code` (`agency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 4. COUNTER TABLE (Legacy)
-- ============================================
CREATE TABLE `counter` (
    `agency_id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) NOT NULL,
    `location` varchar(100) DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `name` varchar(100) DEFAULT NULL,
    `phone_number` varchar(20) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`agency_id`),
    KEY `counter_ibfk_1` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 5. BUS TABLE
-- ============================================
CREATE TABLE `bus` (
    `bus_id` int(11) NOT NULL AUTO_INCREMENT,
    `plate_number` varchar(20) NOT NULL,
    `bus_name` varchar(100) DEFAULT NULL,
    `bus_type` varchar(50) DEFAULT NULL,
    `total_seats` int(11) NOT NULL,
    `status` enum('active','inactive','maintenance') DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`bus_id`),
    UNIQUE KEY `plate_number` (`plate_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 6. ROUTE TABLE
-- ============================================
CREATE TABLE `route` (
    `route_id` int(11) NOT NULL AUTO_INCREMENT,
    `original_city` varchar(100) NOT NULL,
    `destination` varchar(100) NOT NULL,
    `distance` decimal(10,2) DEFAULT NULL,
    `base_fare` decimal(10,2) DEFAULT NULL,
    `estimated_duration` int(11) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`route_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 7. SCHEDULE TABLE
-- ============================================
CREATE TABLE `schedule` (
    `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
    `route_id` int(11) NOT NULL,
    `bus_id` int(11) NOT NULL,
    `departure_time` datetime NOT NULL,
    `arrival_time` datetime NOT NULL,
    `available_seats` int(11) NOT NULL DEFAULT '0',
    `price` decimal(10,2) NOT NULL DEFAULT '0.00',
    `expired` tinyint(1) NOT NULL DEFAULT '0',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`schedule_id`),
    KEY `fk_schedule_route` (`route_id`),
    KEY `fk_schedule_bus` (`bus_id`),
    CONSTRAINT `fk_schedule_bus` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`bus_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_schedule_route` FOREIGN KEY (`route_id`) REFERENCES `route` (`route_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 8. RESERVATION TABLE
-- ============================================
CREATE TABLE `reservation` (
    `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
    `booking_code` varchar(50) DEFAULT NULL,
    `passenger_id` int(11) NOT NULL,
    `staff_id` int(11) DEFAULT NULL,
    `schedule_id` int(11) NOT NULL,
    `seat_number` varchar(50) NOT NULL,
    `fare_paid` decimal(10,2) NOT NULL,
    `status` enum('pending','confirmed','cancelled','used') NOT NULL DEFAULT 'pending',
    `seats_released` tinyint(1) DEFAULT '0',
    `assisted_by_staff` tinyint(1) DEFAULT '0',
    `assistance_notes` text DEFAULT NULL,
    `boarded` tinyint(1) DEFAULT '0',
    `boarded_at` datetime DEFAULT NULL,
    `boarded_by_staff_id` int(11) DEFAULT NULL,
    `checked_in` tinyint(1) DEFAULT '0',
    `checked_in_at` datetime DEFAULT NULL,
    `reservation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reservation_id`),
    UNIQUE KEY `booking_code` (`booking_code`),
    UNIQUE KEY `unique_seat_schedule` (`schedule_id`,`seat_number`),
    KEY `fk_reservation_user` (`passenger_id`),
    KEY `fk_reservation_schedule` (`schedule_id`),
    KEY `fk_reservation_staff` (`staff_id`),
    KEY `idx_boarded` (`boarded`),
    KEY `idx_checked_in` (`checked_in`),
    CONSTRAINT `fk_reservation_passenger` FOREIGN KEY (`passenger_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reservation_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reservation_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
    CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`boarded_by_staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 9. PAYMENT TABLE
-- ============================================
CREATE TABLE `payment` (
    `payment_id` int(11) NOT NULL AUTO_INCREMENT,
    `reservation_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `payment_method` enum('cash','card','mobile_money','simulated') NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `transaction_reference` varchar(255) DEFAULT NULL,
    `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`payment_id`),
    KEY `fk_payment_reservation` (`reservation_id`),
    KEY `fk_payment_user` (`user_id`),
    CONSTRAINT `fk_payment_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 10. STAFF ACTIVITIES TABLE
-- ============================================
CREATE TABLE `staff_activities` (
    `activity_id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `action_type` varchar(50) NOT NULL,
    `description` text DEFAULT NULL,
    `reservation_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`activity_id`),
    KEY `staff_id` (`staff_id`),
    KEY `reservation_id` (`reservation_id`),
    CONSTRAINT `staff_activities_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
    CONSTRAINT `staff_activities_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- 11. DELETED TICKETS TABLE
-- ============================================
CREATE TABLE `deleted_tickets` (
    `delete_id` int(11) NOT NULL AUTO_INCREMENT,
    `original_reservation_id` int(11) NOT NULL,
    `booking_code` varchar(20) DEFAULT NULL,
    `passenger_name` varchar(100) DEFAULT NULL,
    `seat_number` varchar(10) DEFAULT NULL,
    `fare_paid` decimal(10,2) DEFAULT NULL,
    `route_info` varchar(200) DEFAULT NULL,
    `deleted_by_staff_id` int(11) NOT NULL,
    `deleted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `deletion_reason` text DEFAULT NULL,
    PRIMARY KEY (`delete_id`),
    KEY `deleted_by_staff_id` (`deleted_by_staff_id`),
    CONSTRAINT `deleted_tickets_ibfk_1` FOREIGN KEY (`deleted_by_staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_reservation_status ON reservation(status);
CREATE INDEX idx_reservation_passenger ON reservation(passenger_id);
CREATE INDEX idx_reservation_schedule ON reservation(schedule_id);
CREATE INDEX idx_reservation_booking_code ON reservation(booking_code);
CREATE INDEX idx_schedule_departure ON schedule(departure_time);
CREATE INDEX idx_schedule_route ON schedule(route_id);
CREATE INDEX idx_schedule_bus ON schedule(bus_id);
CREATE INDEX idx_payment_status ON payment(payment_status);
CREATE INDEX idx_payment_date ON payment(payment_date);
CREATE INDEX idx_payment_reservation ON payment(reservation_id);
CREATE INDEX idx_payment_user ON payment(user_id);
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_role ON users(role);

-- ============================================
-- VIEWS
-- ============================================
CREATE OR REPLACE VIEW v_active_schedules AS
SELECT 
    s.schedule_id,
    s.departure_time,
    s.arrival_time,
    s.available_seats,
    s.price,
    r.original_city,
    r.destination,
    r.base_fare,
    b.bus_name,
    b.bus_type,
    b.total_seats
FROM schedule s
JOIN route r ON s.route_id = r.route_id
JOIN bus b ON s.bus_id = b.bus_id
WHERE s.expired = 0 
  AND s.available_seats > 0
  AND s.departure_time > NOW();

CREATE OR REPLACE VIEW v_booking_summary AS
SELECT 
    r.reservation_id,
    r.booking_code,
    r.seat_number,
    r.fare_paid,
    r.status,
    r.reservation_date,
    u.full_name as passenger_name,
    u.email as passenger_email,
    u.phone_number as passenger_phone,
    rt.original_city,
    rt.destination,
    s.departure_time,
    s.arrival_time,
    b.bus_name,
    b.bus_type
FROM reservation r
JOIN users u ON r.passenger_id = u.user_id
JOIN schedule s ON r.schedule_id = s.schedule_id
JOIN route rt ON s.route_id = rt.route_id
JOIN bus b ON s.bus_id = b.bus_id;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Admin user (password: Admin123)
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@camexpress.cm', '$2y$10$gigtHVc9QySRtnalaJ8oXuVLwIN6ezLjz7ulX9Ke1edTEcPYT3sZi', 'admin');

-- Staff user (password: Staff123)
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Staff Member', 'staff@camexpress.cm', '$2y$10$cemOtqg2D8YPxCwkFhKY.OkPIT192q6cZEPbYuug3kwHzDCWhqb3q', 'staff');

-- Staff record
INSERT INTO `staff` (`user_id`, `staff_code`, `position`, `department`, `hire_date`) VALUES
(2, 'STF001', 'Booking Assistant', 'Operations', CURDATE());

-- Sample buses
INSERT INTO `bus` (`plate_number`, `bus_name`, `bus_type`, `total_seats`) VALUES
('CE 001 AB', 'CamExpress Luxury', 'Luxury', 40),
('CE 002 CD', 'CamExpress Comfort', 'Comfort', 40),
('CE 003 EF', 'CamExpress Classic', 'Classic', 40);

-- Sample routes
INSERT INTO `route` (`original_city`, `destination`, `distance`, `base_fare`, `estimated_duration`) VALUES
('Yaoundé', 'Douala', 200.00, 4500.00, 4),
('Douala', 'Yaoundé', 210.00, 5000.00, 3),
('Yaoundé', 'Bafoussam', 180.00, 4000.00, 3);

-- ============================================
-- SAMPLE SCHEDULE - FIXED SYNTAX
-- ============================================

-- Option 1: Use specific dates
INSERT INTO `schedule` (`route_id`, `bus_id`, `departure_time`, `arrival_time`, `available_seats`, `price`) VALUES
(1, 1, '2026-08-21 08:00:00', '2026-08-21 12:00:00', 40, 5000.00);

-- Option 2: Use DATE_ADD correctly
INSERT INTO `schedule` (`route_id`, `bus_id`, `departure_time`, `arrival_time`, `available_seats`, `price`) VALUES
(1, 1, DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 1 DAY), INTERVAL 4 HOUR), 40, 5000.00);

-- Option 3: Use ADDDATE
INSERT INTO `schedule` (`route_id`, `bus_id`, `departure_time`, `arrival_time`, `available_seats`, `price`) VALUES
(1, 1, ADDDATE(NOW(), 1), ADDDATE(ADDDATE(NOW(), 1), INTERVAL 4 HOUR), 40, 5000.00);

-- Sample reservation
INSERT INTO `reservation` (`booking_code`, `passenger_id`, `schedule_id`, `seat_number`, `fare_paid`, `status`) VALUES
('14736579', 1, 1, 'S12', 5000.00, 'confirmed');

-- Sample payment
INSERT INTO `payment` (`reservation_id`, `user_id`, `payment_method`, `amount`, `payment_status`, `transaction_reference`) VALUES
(1, 1, 'cash', 5000.00, 'completed', 'PAY-5F8D3C2B1A-20260118143025');

-- ============================================
-- VERIFY ALL TABLES
-- ============================================
SELECT '✅ Users' as Table_Name, COUNT(*) as Records FROM users
UNION ALL
SELECT '✅ Staff', COUNT(*) FROM staff
UNION ALL
SELECT '✅ Bus', COUNT(*) FROM bus
UNION ALL
SELECT '✅ Route', COUNT(*) FROM route
UNION ALL
SELECT '✅ Schedule', COUNT(*) FROM schedule
UNION ALL
SELECT '✅ Reservation', COUNT(*) FROM reservation
UNION ALL
SELECT '✅ Payment', COUNT(*) FROM payment
UNION ALL
SELECT '✅ Staff Activities', COUNT(*) FROM staff_activities;

-- ============================================
-- SHOW ALL TABLES
-- ============================================
SHOW TABLES;