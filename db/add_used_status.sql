-- Add 'used' status to reservation.status enum
-- Allows staff to mark a confirmed ticket as redeemed/boarded at the counter.
-- Run this AFTER db/complete-schema.sql has already been applied.

ALTER TABLE `reservation`
    MODIFY `status` ENUM('pending','confirmed','cancelled','used') NOT NULL DEFAULT 'pending';
