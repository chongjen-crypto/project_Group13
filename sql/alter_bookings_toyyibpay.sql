-- =============================================================================
-- Scholar Hub — ToyyibPay columns for `bookings`
-- Run once in phpMyAdmin on database: facility_booking_system
-- =============================================================================

-- payment_status already exists from sql/alter_bookings_payment.sql
-- Values used: 'pending', 'paid', 'failed', 'refunded'

ALTER TABLE `bookings`
  ADD COLUMN IF NOT EXISTS `bill_code` VARCHAR(100) DEFAULT NULL AFTER `payment_status`,
  ADD COLUMN IF NOT EXISTS `transaction_id` VARCHAR(100) DEFAULT NULL AFTER `bill_code`,
  ADD COLUMN IF NOT EXISTS `paid_at` DATETIME DEFAULT NULL AFTER `transaction_id`;

-- MySQL 5.7 / MariaDB without IF NOT EXISTS — run individually if the above fails:
-- ALTER TABLE `bookings` ADD COLUMN `bill_code` VARCHAR(100) DEFAULT NULL AFTER `payment_status`;
-- ALTER TABLE `bookings` ADD COLUMN `transaction_id` VARCHAR(100) DEFAULT NULL AFTER `bill_code`;
-- ALTER TABLE `bookings` ADD COLUMN `paid_at` DATETIME DEFAULT NULL AFTER `transaction_id`;

ALTER TABLE `bookings` ADD INDEX `idx_bookings_bill_code` (`bill_code`);
