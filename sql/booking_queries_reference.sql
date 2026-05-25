-- =============================================================================
-- Scholar Hub — Booking system SQL reference
-- Database: facility_booking_system
-- =============================================================================

-- -----------------------------------------------------------------------------
-- View booked slots for a court on a date (badminton example)
-- -----------------------------------------------------------------------------
SELECT booking_id, user_id, court_id, booking_date, start_time, end_time, booking_status
FROM bookings
WHERE facility_type = 'badminton'
  AND booking_date = '2026-05-25'
  AND court_id = 1
  AND booking_status IN ('pending', 'approved', 'completed')
ORDER BY start_time;

-- -----------------------------------------------------------------------------
-- View booked slots for an open facility (gym — court_id IS NULL)
-- -----------------------------------------------------------------------------
SELECT booking_id, user_id, booking_date, start_time, end_time, booking_status
FROM bookings
WHERE facility_type = 'gym'
  AND booking_date = '2026-05-25'
  AND court_id IS NULL
  AND booking_status IN ('pending', 'approved', 'completed')
ORDER BY start_time;

-- -----------------------------------------------------------------------------
-- List courts for a facility type
-- -----------------------------------------------------------------------------
SELECT court_id, court_name, status FROM badminton_court WHERE status = 'available';
SELECT table_id AS court_id, table_name AS court_name, status FROM snooker_room WHERE status = 'available';

-- -----------------------------------------------------------------------------
-- Manual test booking (1 hour) — replace user_id and date
-- -----------------------------------------------------------------------------
INSERT INTO bookings (user_id, facility_type, court_id, booking_date, start_time, end_time, purpose, booking_status)
VALUES (11, 'badminton', 1, '2026-05-25', '14:00:00', '15:00:00', 'Club practice', 'pending');

-- Open facility (no court)
INSERT INTO bookings (user_id, facility_type, court_id, booking_date, start_time, end_time, purpose, booking_status)
VALUES (11, 'gym', NULL, '2026-05-25', '09:00:00', '10:00:00', 'Workout', 'pending');

-- -----------------------------------------------------------------------------
-- Overlap check (same logic as booking_helpers.php)
-- -----------------------------------------------------------------------------
SELECT booking_id FROM bookings
WHERE facility_type = 'badminton'
  AND booking_date = '2026-05-25'
  AND court_id = 1
  AND booking_status IN ('pending', 'approved', 'completed')
  AND start_time < '15:00:00'
  AND end_time > '14:00:00'
LIMIT 1;
