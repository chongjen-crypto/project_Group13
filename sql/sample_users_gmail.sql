-- =============================================================================
-- Scholar Hub — Sample users (Gmail addresses)
-- Database: facility_booking_system
-- Password for ALL rows below (login + registration): ScholarHub123
-- Hash generated with PHP: password_hash('ScholarHub123', PASSWORD_DEFAULT)
-- =============================================================================
-- Run in phpMyAdmin → SQL. If an email already exists, change it or skip that row.
-- =============================================================================

-- Base schema (columns match database/facility_booking_system.sql):
INSERT INTO `users` (`role`, `full_name`, `user_id`, `email`, `phone`, `password`) VALUES
('student', 'Hana Yusof', 'STU2001', 'hana.yusof2001@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm'),
('student', 'Marcus Tan', 'STU2002', 'marcus.tan2002@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm'),
('student', 'Siti Aminah', 'STU2003', 'siti.aminah2003@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm'),
('staff', 'Facility Supervisor', 'STAFF9001', 'facility.supervisor9001@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm'),
('staff', 'Booking Coordinator', 'STAFF9002', 'booking.coordinator9002@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm');

-- -----------------------------------------------------------------------------
-- OPTIONAL: if your `users` table has email_verified / verification columns
-- (see register.php / staff_registration.php), use this style instead:
--
-- INSERT INTO `users` (`role`, `full_name`, `user_id`, `email`, `phone`, `password`, `email_verified`, `verification_code`, `verification_expiry`) VALUES
-- ('student', 'Hana Yusof', 'STU2001', 'hana.yusof2001@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm', 1, NULL, NULL),
-- ('staff', 'Facility Supervisor', 'STAFF9001', 'facility.supervisor9001@gmail.com', NULL, '$2y$10$0m/FpxvhegDAdDX7wEQQmOhAHM08KRAo3oQ6894H4iIMih/yo1lzm', 1, NULL, NULL);
-- -----------------------------------------------------------------------------
