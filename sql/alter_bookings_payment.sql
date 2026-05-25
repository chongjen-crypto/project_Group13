-- =============================================================================
-- Scholar Hub — Add payment columns to `bookings`
-- Run once in phpMyAdmin on database: facility_booking_system
-- =============================================================================

ALTER TABLE `bookings`
  ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `booking_status`,
  ADD COLUMN `payment_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_method`,
  ADD COLUMN `payment_status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending' AFTER `payment_amount`;

-- Optional index for reporting
ALTER TABLE `bookings` ADD INDEX `idx_bookings_payment_status` (`payment_status`);
