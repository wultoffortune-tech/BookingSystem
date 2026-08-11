-- Fix foreign key on reservation.schedule_id
-- Run this in MySQL (phpMyAdmin or MySQL CLI) after backing up your database.

-- 1) Check existing foreign keys:
-- SHOW CREATE TABLE reservation\G

-- 2) Drop the incorrect foreign key (adjust name if different):
ALTER TABLE `reservation` DROP FOREIGN KEY `reservation_ibfk_2`;

-- 3) Add correct foreign key referencing local `schedule(schedule_id)`:
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_fk_schedule`
  FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- 4) Verify:
-- SHOW CREATE TABLE reservation\G
-- 5) Add unique constraint to prevent double-booking a seat on the same schedule
ALTER TABLE `reservation`
  ADD UNIQUE KEY `reservation_unique_schedule_seat` (`schedule_id`, `seat_number`);

-- NOTE: If the FK name is different on your DB, run SHOW CREATE TABLE reservation and replace the name above.
-- Alternatively run db/fix_reservation_fk.php from the server to programmatically fix FK and add the unique index.
