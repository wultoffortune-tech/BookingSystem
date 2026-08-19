-- Seed test data for the booking-system database
-- Mot de passe commun pour tous les comptes de test : Test@1234
-- Hash bcrypt (password_hash(PASSWORD_DEFAULT)) : $2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm

USE `booking-system`;

-- ==========================================================
-- Prérequis : la valeur 'staff' n'existe pas encore dans
-- l'ENUM users.role, alors que tout le code de l'appli
-- (login, staff/*.php, admin/dashboard.php) en dépend.
-- ==========================================================
ALTER TABLE `users` MODIFY `role` ENUM('user','admin','staff') NOT NULL DEFAULT 'user';

-- ==========================================================
-- users (10) : 5 passagers, 3 staff, 2 admin
-- ==========================================================
INSERT INTO `users` (`user_id`, `full_name`, `date_of_birth`, `id_number`, `email`, `phone_number`, `role`, `password`, `created_at`) VALUES
(1, 'Jean Fotso',       '1992-03-14', 'CM-ID-001001', 'jean.fotso@example.com',       '+237670010001', 'user',  '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-08-10 09:12:00'),
(2, 'Marie Ngo',        '1995-07-22', 'CM-ID-001002', 'marie.ngo@example.com',        '+237670010002', 'user',  '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-08-10 09:20:00'),
(3, 'Paul Kamdem',      '1988-11-02', 'CM-ID-001003', 'paul.kamdem@example.com',      '+237670010003', 'user',  '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-08-11 10:05:00'),
(4, 'Alice Mballa',     '1998-01-30', 'CM-ID-001004', 'alice.mballa@example.com',     '+237670010004', 'user',  '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-08-12 14:45:00'),
(5, 'Eric Tchoumi',     '1990-05-18', 'CM-ID-001005', 'eric.tchoumi@example.com',     '+237670010005', 'user',  '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-08-12 16:30:00'),
(6, 'Sandrine Njoya',   '1994-02-09', 'CM-ID-002001', 'sandrine.njoya@camexpress.cm', '+237670020001', 'staff', '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-06-01 08:00:00'),
(7, 'Bertrand Fokou',   '1991-09-25', 'CM-ID-002002', 'bertrand.fokou@camexpress.cm', '+237670020002', 'staff', '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-06-01 08:00:00'),
(8, 'Clarisse Etoga',   '1993-12-17', 'CM-ID-002003', 'clarisse.etoga@camexpress.cm', '+237670020003', 'staff', '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-06-01 08:00:00'),
(9, 'Admin Principal',  '1985-04-04', 'CM-ID-003001', 'admin@camexpress.cm',          '+237670030001', 'admin', '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-01-05 08:00:00'),
(10, 'Super Admin',     '1983-10-11', 'CM-ID-003002', 'superadmin@camexpress.cm',     '+237670030002', 'admin', '$2y$12$lxUEdzl71KzuuQYj127BqOZu.MkHl1ehglZba1fGzZ/c0R7sP.pDm', '2026-01-05 08:00:00');

-- ==========================================================
-- staff (3) : une ligne par utilisateur role='staff'
-- ==========================================================
INSERT INTO `staff` (`staff_id`, `user_id`, `position`, `status`, `created_at`) VALUES
(1, 6, 'Agent de guichet',      'active', '2026-06-01 08:05:00'),
(2, 7, 'Agent de guichet',      'active', '2026-06-01 08:05:00'),
(3, 8, 'Superviseur guichet',   'active', '2026-06-01 08:05:00');

-- ==========================================================
-- route (10)
-- ==========================================================
INSERT INTO `route` (`route_id`, `original_city`, `destination`, `base_fare`, `description`, `created_at`, `expired`) VALUES
(1,  'Dschang',   'Yaoundé',     5000.00,  'Liaison directe Dschang - Yaoundé',      '2026-01-10 08:00:00', 0),
(2,  'Yaoundé',   'Dschang',     5000.00,  'Liaison directe Yaoundé - Dschang',      '2026-01-10 08:00:00', 0),
(3,  'Douala',    'Yaoundé',     4000.00,  'Liaison directe Douala - Yaoundé',       '2026-01-10 08:00:00', 0),
(4,  'Yaoundé',   'Douala',      4000.00,  'Liaison directe Yaoundé - Douala',       '2026-01-10 08:00:00', 0),
(5,  'Bafoussam', 'Douala',      4500.00,  'Liaison directe Bafoussam - Douala',     '2026-01-10 08:00:00', 0),
(6,  'Douala',    'Bafoussam',   4500.00,  'Liaison directe Douala - Bafoussam',     '2026-01-10 08:00:00', 0),
(7,  'Bamenda',   'Douala',      6000.00,  'Liaison directe Bamenda - Douala',       '2026-01-10 08:00:00', 0),
(8,  'Yaoundé',   'Bamenda',     7000.00,  'Liaison directe Yaoundé - Bamenda',      '2026-01-10 08:00:00', 0),
(9,  'Douala',    'Kribi',       3000.00,  'Liaison directe Douala - Kribi',         '2026-01-10 08:00:00', 0),
(10, 'Yaoundé',   'Ngaoundéré',  12000.00, 'Liaison directe Yaoundé - Ngaoundéré',   '2026-01-10 08:00:00', 0);

-- ==========================================================
-- bus (10)
-- ==========================================================
INSERT INTO `bus` (`bus_id`, `bus_name`, `bus_type`, `total_seats`, `status`, `created_at`) VALUES
(1,  'GEV-01', 'VIP',     30, 'active',   '2026-01-05 08:00:00'),
(2,  'GEV-02', 'Classic', 45, 'active',   '2026-01-05 08:00:00'),
(3,  'GEV-03', 'VIP',     30, 'active',   '2026-01-05 08:00:00'),
(4,  'GEV-04', 'Classic', 45, 'active',   '2026-01-05 08:00:00'),
(5,  'GEV-05', 'VIP',     28, 'active',   '2026-01-05 08:00:00'),
(6,  'GEV-06', 'Classic', 50, 'active',   '2026-01-05 08:00:00'),
(7,  'GEV-07', 'VIP',     30, 'inactive', '2026-01-05 08:00:00'),
(8,  'GEV-08', 'Classic', 45, 'active',   '2026-01-05 08:00:00'),
(9,  'GEV-09', 'VIP',     32, 'active',   '2026-01-05 08:00:00'),
(10, 'GEV-10', 'Classic', 45, 'active',   '2026-01-05 08:00:00');

-- ==========================================================
-- schedule (10) : available_seats = total_seats - reservations actives
-- ==========================================================
INSERT INTO `schedule` (`schedule_id`, `route_id`, `bus_id`, `departure_time`, `arrival_time`, `available_seats`, `price`, `expired`, `created_at`) VALUES
(1,  1,  1, '2026-08-20 08:00:00', '2026-08-20 13:00:00', 27, 5000.00,  0, '2026-08-01 08:00:00'),
(2,  2,  2, '2026-08-20 14:00:00', '2026-08-20 19:00:00', 44, 5000.00,  0, '2026-08-01 08:00:00'),
(3,  3,  3, '2026-08-21 09:00:00', '2026-08-21 13:00:00', 29, 4000.00,  0, '2026-08-01 08:00:00'),
(4,  4,  4, '2026-08-21 15:00:00', '2026-08-21 19:00:00', 44, 4000.00,  0, '2026-08-01 08:00:00'),
(5,  5,  5, '2026-08-22 07:00:00', '2026-08-22 11:00:00', 27, 4500.00,  0, '2026-08-01 08:00:00'),
(6,  6,  6, '2026-08-22 16:00:00', '2026-08-22 20:00:00', 50, 4500.00,  0, '2026-08-01 08:00:00'),
(7,  7,  1, '2026-08-23 08:00:00', '2026-08-23 15:00:00', 30, 6000.00,  0, '2026-08-01 08:00:00'),
(8,  8,  2, '2026-08-23 10:00:00', '2026-08-23 17:00:00', 45, 7000.00,  0, '2026-08-01 08:00:00'),
(9,  9,  3, '2026-08-15 08:00:00', '2026-08-15 11:00:00', 29, 3000.00,  1, '2026-07-20 08:00:00'),
(10, 10, 6, '2026-08-24 06:00:00', '2026-08-24 14:00:00', 49, 12000.00, 0, '2026-08-01 08:00:00');

-- ==========================================================
-- reservation (10) : les 4 statuts sont représentés
-- ==========================================================
INSERT INTO `reservation` (`reservation_id`, `booking_code`, `passenger_id`, `schedule_id`, `seat_number`, `fare_paid`, `status`, `seats_released`, `assisted_by_staff`, `assistance_notes`, `reservation_date`) VALUES
(1,  '90010001', 1, 1,  'A1', 5000.00,  'confirmed', 0, NULL, NULL, '2026-08-17 09:00:00'),
(2,  '90010002', 2, 1,  'A2', 5000.00,  'used',      0, NULL, NULL, '2026-08-17 09:05:00'),
(3,  '90010003', 3, 1,  'A3', 5000.00,  'pending',   0, NULL, NULL, '2026-08-18 11:00:00'),
(4,  '90010004', 4, 2,  'B1', 5000.00,  'cancelled', 1, NULL, NULL, '2026-08-16 10:00:00'),
(5,  '90010005', 5, 2,  'B2', 5000.00,  'confirmed', 0, NULL, NULL, '2026-08-17 12:00:00'),
(6,  '90010006', 1, 3,  'C1', 4000.00,  'confirmed', 0, NULL, NULL, '2026-08-17 15:00:00'),
(7,  '90010007', 2, 4,  'D1', 4000.00,  'pending',   0, NULL, NULL, '2026-08-18 08:30:00'),
(8,  '90010008', 3, 5,  'E1', 4500.00,  'confirmed', 0, NULL, NULL, '2026-08-17 17:00:00'),
(9,  '90010009', 4, 9,  'F1', 3000.00,  'used',      0, NULL, NULL, '2026-07-21 09:00:00'),
(10, '90010010', 5, 10, 'G1', 12000.00, 'confirmed', 0, NULL, NULL, '2026-08-18 09:30:00');

-- ==========================================================
-- payment (10) : couvre completed / pending / failed
-- ==========================================================
INSERT INTO `payment` (`payment_id`, `reservation_id`, `payment_method`, `amount`, `payment_status`, `transaction_reference`, `payment_date`) VALUES
(1,  1,  'mobile_money', 5000.00,  'completed', 'TXN-CMX-0001', '2026-08-17 09:02:00'),
(2,  2,  'cash',         5000.00,  'completed', 'TXN-CMX-0002', '2026-08-17 09:07:00'),
(3,  4,  'mobile_money', 5000.00,  'completed', 'TXN-CMX-0003', '2026-08-16 10:03:00'),
(4,  5,  'mobile_money', 5000.00,  'completed', 'TXN-CMX-0004', '2026-08-17 12:03:00'),
(5,  6,  'cash',         4000.00,  'completed', 'TXN-CMX-0005', '2026-08-17 15:02:00'),
(6,  8,  'cash',         4500.00,  'completed', 'TXN-CMX-0006', '2026-08-17 17:03:00'),
(7,  9,  'cash',         3000.00,  'completed', 'TXN-CMX-0007', '2026-07-21 09:02:00'),
(8,  10, 'mobile_money', 12000.00, 'completed', 'TXN-CMX-0008', '2026-08-18 09:33:00'),
(9,  3,  'mobile_money', 5000.00,  'pending',   NULL,           '2026-08-18 11:02:00'),
(10, 7,  'mobile_money', 4000.00,  'failed',    'TXN-CMX-0009', '2026-08-18 08:32:00');

-- ==========================================================
-- counter (10)
-- ==========================================================
INSERT INTO `counter` (`counter_id`, `counter_code`, `name`, `location`, `city`, `phone_number`, `email`, `address`, `admin_id`, `status`, `created_at`) VALUES
(1,  'CTR-DSC-101', 'Guichet Dschang Centre',      'Marché A',            'Dschang',    '+237690001001', 'dschang@camexpress.cm',    'Face marché A, Dschang',           9,  'active', '2026-01-06 08:00:00'),
(2,  'CTR-YAO-102', 'Guichet Yaoundé Mvan',        'Carrefour Mvan',      'Yaoundé',    '+237690001002', 'mvan@camexpress.cm',       'Carrefour Mvan, Yaoundé',          9,  'active', '2026-01-06 08:00:00'),
(3,  'CTR-DLA-103', 'Guichet Douala Bonabéri',     'Rond-point Bonabéri', 'Douala',     '+237690001003', 'bonaberi@camexpress.cm',   'Rond-point Bonabéri, Douala',      10, 'active', '2026-01-06 08:00:00'),
(4,  'CTR-BFM-104', 'Guichet Bafoussam',           'Marché Central',      'Bafoussam',  '+237690001004', 'bafoussam@camexpress.cm',  'Marché Central, Bafoussam',        10, 'active', '2026-01-06 08:00:00'),
(5,  'CTR-BDA-105', 'Guichet Bamenda',             'Commercial Avenue',   'Bamenda',    '+237690001005', 'bamenda@camexpress.cm',    'Commercial Avenue, Bamenda',       9,  'active', '2026-01-06 08:00:00'),
(6,  'CTR-BUE-106', 'Guichet Buea',                'Molyko',              'Buea',       '+237690001006', 'buea@camexpress.cm',       'Molyko, Buea',                     10, 'active', '2026-01-06 08:00:00'),
(7,  'CTR-KRI-107', 'Guichet Kribi',               'Plage',               'Kribi',      '+237690001007', 'kribi@camexpress.cm',      'Front de mer, Kribi',              9,  'active', '2026-01-06 08:00:00'),
(8,  'CTR-EBO-108', 'Guichet Ebolowa',             'Centre-ville',        'Ebolowa',    '+237690001008', 'ebolowa@camexpress.cm',    'Centre-ville, Ebolowa',            10, 'inactive', '2026-01-06 08:00:00'),
(9,  'CTR-NGD-109', 'Guichet Ngaoundéré',          'Gare Routière',       'Ngaoundéré', '+237690001009', 'ngaoundere@camexpress.cm', 'Gare Routière, Ngaoundéré',        9,  'active', '2026-01-06 08:00:00'),
(10, 'CTR-MRO-110', 'Guichet Maroua',              'Marché Central',      'Maroua',     '+237690001010', 'maroua@camexpress.cm',     'Marché Central, Maroua',           10, 'active', '2026-01-06 08:00:00');
