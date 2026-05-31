-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2026 at 08:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `facility_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `badminton_court`
--

CREATE TABLE `badminton_court` (
  `court_id` int(11) NOT NULL,
  `court_name` varchar(50) NOT NULL,
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badminton_court`
--

INSERT INTO `badminton_court` (`court_id`, `court_name`, `status`, `created_at`) VALUES
(1, 'Court 1', 'available', '2026-05-22 03:42:52'),
(2, 'Court 2', 'available', '2026-05-22 03:42:52'),
(3, 'Court 3', 'available', '2026-05-22 03:42:52'),
(4, 'Court 4', 'available', '2026-05-22 03:42:52'),
(5, 'Court 5', 'available', '2026-05-22 03:42:52'),
(6, 'Court 6', 'available', '2026-05-22 03:42:52'),
(7, 'Court 7', 'available', '2026-05-22 03:42:52'),
(8, 'Court 8', 'available', '2026-05-22 03:42:52');

-- --------------------------------------------------------

--
-- Table structure for table `basketball_court`
--

CREATE TABLE `basketball_court` (
  `court_id` int(11) NOT NULL,
  `court_name` varchar(50) NOT NULL,
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `basketball_court`
--

INSERT INTO `basketball_court` (`court_id`, `court_name`, `status`, `created_at`) VALUES
(1, 'Court 1', 'available', '2026-05-22 03:43:17'),
(2, 'Court 2', 'available', '2026-05-22 03:43:17'),
(3, 'Court 3', 'available', '2026-05-22 03:43:17'),
(4, 'Court 4', 'available', '2026-05-22 03:43:17');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_type` varchar(50) NOT NULL,
  `court_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text DEFAULT NULL,
  `booking_status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reject_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `facility_type`, `court_id`, `booking_date`, `start_time`, `end_time`, `purpose`, `booking_status`, `payment_method`, `payment_amount`, `payment_status`, `created_at`, `reject_reason`) VALUES
(1, 11, 'badminton', 1, '2026-05-22', '09:00:00', '10:00:00', '', 'cancelled', NULL, NULL, 'pending', '2026-05-22 04:26:26', NULL),
(2, 11, 'badminton', 1, '2026-05-23', '08:00:00', '09:00:00', '', 'cancelled', NULL, NULL, 'pending', '2026-05-23 06:06:47', NULL),
(3, 11, 'badminton', 1, '2026-05-23', '09:00:00', '10:00:00', '', 'cancelled', NULL, NULL, 'pending', '2026-05-23 06:06:47', NULL),
(4, 11, 'badminton', 1, '2026-05-23', '10:00:00', '11:00:00', '', 'cancelled', NULL, NULL, 'pending', '2026-05-23 06:06:47', NULL),
(5, 11, 'badminton', 1, '2026-05-23', '11:00:00', '12:00:00', '', 'cancelled', 'tng', 5.00, 'paid', '2026-05-23 06:35:14', NULL),
(6, 11, 'badminton', 1, '2026-05-23', '08:00:00', '09:00:00', '', 'pending', 'in_app', 5.00, 'paid', '2026-05-23 07:12:51', NULL),
(7, 11, 'badminton', 1, '2026-05-23', '09:00:00', '10:00:00', '', 'pending', 'in_app', 5.00, 'paid', '2026-05-23 07:12:51', NULL),
(8, 11, 'badminton', 1, '2026-05-23', '10:00:00', '11:00:00', '', 'pending', 'in_app', 5.00, 'paid', '2026-05-23 07:12:51', NULL),
(9, 11, 'badminton', 1, '2026-05-28', '21:00:00', '22:00:00', '', 'cancelled', 'tng', 5.00, 'paid', '2026-05-27 14:49:59', NULL),
(10, 11, 'tennis', 1, '2026-05-28', '09:00:00', '10:00:00', '', 'rejected', 'in_app', 8.00, 'paid', '2026-05-27 15:04:35', '1234');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(100) NOT NULL,
  `facility_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `status` enum('active','maintenance','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price_amount` decimal(10,2) NOT NULL DEFAULT 5.00,
  `price_mode` enum('hourly','entry') NOT NULL DEFAULT 'hourly',
  `rules` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `facility_name`, `facility_type`, `description`, `image`, `location`, `opening_time`, `closing_time`, `status`, `created_at`, `price_amount`, `price_mode`, `rules`) VALUES
(1, 'Badminton Court', 'badminton', 'A well-maintained indoor badminton facility suitable for recreational play, training sessions, and organized matches.', 'assets/badmintoncourt.webp', 'Sports Complex', '08:00:00', '22:00:00', 'active', '2026-05-22 03:44:25', 9.10, 'hourly', 'Non-marking indoor sports shoes only.\r\nMaximum session length follows your booking slot.\r\nFood and drinks (except sealed water) are not allowed on court.'),
(2, 'Basketball Court', 'basketball', 'A full-size basketball facility suitable for individual practice, team training, and competitive matches.', 'assets/basketballcourt.jpeg', 'Sports Complex', '08:00:00', '22:00:00', 'active', '2026-05-22 03:44:25', 5.00, 'hourly', 'Indoor basketball shoes only.\nShare court fairly during open slots.\nNo dunking on portable hoops unless allowed.'),
(3, 'Futsal Court', 'futsal', 'An indoor futsal facility suitable for team practice, recreational matches, and organized tournaments.', 'assets/futsalcourt.jpg', 'Sports Complex', '08:00:00', '22:00:00', 'active', '2026-05-22 03:44:25', 5.00, 'hourly', 'Indoor futsal shoes only.\nRespect booked slot end times.\nReport damaged turf to staff.'),
(4, 'Tennis Court', 'tennis', 'An outdoor tennis facility designed to support recreational activities, skill development, and competitive play.', 'assets/tenniscourt.jpg', 'Sports Complex', '08:00:00', '22:00:00', 'closed', '2026-05-22 03:44:25', 5.00, 'hourly', 'Proper tennis shoes required.\r\nRespect booked court times.\r\nReport equipment issues to staff.'),
(5, 'Volleyball Court', 'volleyball', 'An indoor volleyball facility designed for team training, recreational games, and organized competitions.', 'assets/volleyballcourt.webp', 'Sports Complex', '08:00:00', '22:00:00', 'active', '2026-05-22 03:44:25', 5.00, 'hourly', 'Indoor court shoes only.\nMaximum players per court as posted.\nVacate on time for the next booking.'),
(6, 'Gym Room', 'gym', 'A fitness facility equipped to support strength training, cardiovascular exercise, and general wellness activities.', 'assets/gymroom.jpg', 'Block A', '08:00:00', '22:00:00', 'active', '2026-05-22 03:44:25', 5.00, 'hourly', 'Wipe equipment after use.\nProper athletic attire required.\nRe-rack weights after use.'),
(7, 'Swimming Pool', 'swimming', 'An Olympic-size swimming pool suitable for swimming practice, fitness training, and recreational use.', 'assets/swimmingpool.jpg', 'Aquatic Center', '08:00:00', '20:00:00', 'closed', '2026-05-22 03:44:25', 5.00, 'hourly', 'Shower before entering the pool.\nNo running on pool deck.\nFollow lifeguard instructions at all times.'),
(8, 'Track Field', 'track', 'An outdoor track and field facility suitable for running, athletic training, and sports-related events.', 'assets/trackfield.webp', 'Stadium', '06:00:00', '22:00:00', 'active', '2026-05-22 03:44:25', 5.00, 'hourly', 'Stay in your assigned lane when busy.\nNo spikes on synthetic surface unless permitted.\nYield to official events.'),
(9, 'Snooker Room', 'snooker', 'An indoor recreational facility equipped with snooker tables for leisure and social activities.', 'assets/snookerroom.jpg', 'Sports Complex', '08:00:00', '22:00:00', 'active', '2026-05-22 03:52:41', 5.00, 'hourly', 'Keep noise to a minimum.\nReturn cues and balls after play.\nNo food at the tables.'),
(10, 'Gym Room', 'gym', 'A fitness facility equipped to support strength training, cardiovascular exercise, and general wellness activities.', 'assets/gymroom.jpg', 'Block A', '08:00:00', '22:00:00', 'active', '2026-05-22 04:02:56', 5.00, 'hourly', 'Wipe equipment after use.\nProper athletic attire required.\nRe-rack weights after use.'),
(11, 'Swimming Pool', 'swimming', 'An Olympic-size swimming pool suitable for swimming practice, fitness training, and recreational use.', 'assets/swimmingpool.jpg', 'Aquatic Center', '08:00:00', '20:00:00', 'closed', '2026-05-22 04:02:56', 5.00, 'hourly', 'Shower before entering the pool.\nNo running on pool deck.\nFollow lifeguard instructions at all times.'),
(12, 'Track Field', 'track', 'An outdoor track and field facility suitable for running, athletic training, and sports-related events.', 'assets/trackfield.webp', 'Stadium', '06:00:00', '22:00:00', 'active', '2026-05-22 04:02:56', 5.00, 'hourly', 'Stay in your assigned lane when busy.\nNo spikes on synthetic surface unless permitted.\nYield to official events.');

-- --------------------------------------------------------

--
-- Table structure for table `futsal_court`
--

CREATE TABLE `futsal_court` (
  `court_id` int(11) NOT NULL,
  `court_name` varchar(50) NOT NULL,
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `futsal_court`
--

INSERT INTO `futsal_court` (`court_id`, `court_name`, `status`, `created_at`) VALUES
(1, 'Court 1', 'available', '2026-05-22 03:43:25'),
(2, 'Court 2', 'available', '2026-05-22 03:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(14, 11, 'Booking Request Accepted', 'Your booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00) has been accepted.', 1, '2026-05-27 14:51:33'),
(15, 5, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(16, 12, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(17, 13, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(18, 18, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(19, 19, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(20, 20, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(21, 21, 'Student Booking Cancelled', 'student cancelled booking #9 for Badminton Court on 2026-05-28 (21:00 - 22:00).', 0, '2026-05-27 14:52:03'),
(22, 7, 'Facility Unavailable', 'Badminton Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-27 15:03:49'),
(23, 11, 'Facility Unavailable', 'Badminton Court is now unavailable for booking. Please choose another facility.', 1, '2026-05-27 15:03:49'),
(24, 14, 'Facility Unavailable', 'Badminton Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-27 15:03:49'),
(25, 15, 'Facility Unavailable', 'Badminton Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-27 15:03:49'),
(26, 16, 'Facility Unavailable', 'Badminton Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-27 15:03:49'),
(27, 17, 'Facility Unavailable', 'Badminton Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-27 15:03:49'),
(29, 11, 'Booking Request Rejected', 'Your booking #10 for Tennis Court on 2026-05-28 (09:00 - 10:00) was rejected. Reason: 1234', 1, '2026-05-27 15:05:15'),
(30, 7, 'Facility Unavailable', 'Tennis Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:56'),
(31, 11, 'Facility Unavailable', 'Tennis Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:56'),
(32, 14, 'Facility Unavailable', 'Tennis Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:56'),
(33, 15, 'Facility Unavailable', 'Tennis Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:56'),
(34, 16, 'Facility Unavailable', 'Tennis Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:56'),
(35, 17, 'Facility Unavailable', 'Tennis Court is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:56'),
(37, 7, 'Facility Unavailable', 'Swimming Pool is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:57'),
(38, 11, 'Facility Unavailable', 'Swimming Pool is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:57'),
(39, 14, 'Facility Unavailable', 'Swimming Pool is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:57'),
(40, 15, 'Facility Unavailable', 'Swimming Pool is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:57'),
(41, 16, 'Facility Unavailable', 'Swimming Pool is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:57'),
(42, 17, 'Facility Unavailable', 'Swimming Pool is now unavailable for booking. Please choose another facility.', 0, '2026-05-31 17:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `snooker_room`
--

CREATE TABLE `snooker_room` (
  `table_id` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snooker_room`
--

INSERT INTO `snooker_room` (`table_id`, `table_name`, `status`, `created_at`) VALUES
(1, 'Table 1', 'available', '2026-05-22 03:52:29'),
(2, 'Table 2', 'available', '2026-05-22 03:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `tennis_court`
--

CREATE TABLE `tennis_court` (
  `court_id` int(11) NOT NULL,
  `court_name` varchar(50) NOT NULL,
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tennis_court`
--

INSERT INTO `tennis_court` (`court_id`, `court_name`, `status`, `created_at`) VALUES
(1, 'Court 1', 'available', '2026-05-22 03:43:34'),
(2, 'Court 2', 'available', '2026-05-22 03:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` varchar(20) DEFAULT 'student',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(32) DEFAULT NULL,
  `verification_expiry` datetime DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `email_verified`, `verification_code`, `verification_expiry`, `full_name`, `user_id`, `email`, `phone`, `password`, `created_at`) VALUES
(5, 'staff', 1, NULL, NULL, 'zzz', 'zzz', 'zzz@gmail.com', '', '$2y$10$kzHjVKrZnE5eHD3MDUI8/uaO.nLxvo2.LjO40VEUoIWIhXGyuO3yy', '2026-05-11 06:53:28'),
(7, 'student', 1, NULL, NULL, 'zzzz', 'zzzz', 'kaizekuang@gmail.com', '', '$2y$10$m7WXnZVpTz/5zQKQCua04O6tHUZMjYBkdWk.0NBiy8Dd05eTn2lt6', '2026-05-11 08:15:00'),
(11, 'student', 1, NULL, NULL, 'student', 'default', 'student@gmail.com', '', '$2y$10$XKiBe7M39LWJyxCc/cVDJeySAdpMmz00JuepE9CauDHQK.Xds22Oq', '2026-05-14 04:07:52'),
(12, 'staff', 0, NULL, NULL, 'default', 'staff', 'staff@gmail.com', '', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 08:48:05'),
(13, 'admin', 0, NULL, NULL, 'System Administrator', 'admin', 'admin@gmail.com', '0123456789', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 13:43:17'),
(14, 'student', 0, NULL, NULL, 'Jason Lim', 'STU001', 'jasonlim@gmail.com', '0121111111', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(15, 'student', 0, NULL, NULL, 'Emily Tan', 'STU002', 'emilytan@gmail.com', '0122222222', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(16, 'student', 0, NULL, NULL, 'Ryan Wong', 'STU003', 'ryanwong@gmail.com', '0123333333', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(17, 'student', 0, NULL, NULL, 'Sophia Lee', 'STU004', 'sophialee@gmail.com', '0124444444', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(18, 'staff', 0, NULL, NULL, 'Daniel Ong', 'STU005', 'danielong@gmail.com', '0125555555', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(19, 'staff', 0, NULL, NULL, 'Michael Tan', 'STF001', 'michaeltan@gmail.com', '0131111111', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(20, 'staff', 0, NULL, NULL, 'Sarah Lim', 'STF002', 'sarahlim@gmail.com', '0132222222', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(21, 'staff', 0, NULL, NULL, 'Kevin Lee', 'STF003', 'kevinlee@gmail.com', '0133333333', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35');

-- --------------------------------------------------------

--
-- Table structure for table `volleyball_court`
--

CREATE TABLE `volleyball_court` (
  `court_id` int(11) NOT NULL,
  `court_name` varchar(50) NOT NULL,
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volleyball_court`
--

INSERT INTO `volleyball_court` (`court_id`, `court_name`, `status`, `created_at`) VALUES
(1, 'Court 1', 'available', '2026-05-22 03:43:41'),
(2, 'Court 2', 'available', '2026-05-22 03:43:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badminton_court`
--
ALTER TABLE `badminton_court`
  ADD PRIMARY KEY (`court_id`);

--
-- Indexes for table `basketball_court`
--
ALTER TABLE `basketball_court`
  ADD PRIMARY KEY (`court_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_bookings_payment_status` (`payment_status`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `futsal_court`
--
ALTER TABLE `futsal_court`
  ADD PRIMARY KEY (`court_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `snooker_room`
--
ALTER TABLE `snooker_room`
  ADD PRIMARY KEY (`table_id`);

--
-- Indexes for table `tennis_court`
--
ALTER TABLE `tennis_court`
  ADD PRIMARY KEY (`court_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `volleyball_court`
--
ALTER TABLE `volleyball_court`
  ADD PRIMARY KEY (`court_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badminton_court`
--
ALTER TABLE `badminton_court`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `basketball_court`
--
ALTER TABLE `basketball_court`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `futsal_court`
--
ALTER TABLE `futsal_court`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `snooker_room`
--
ALTER TABLE `snooker_room`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tennis_court`
--
ALTER TABLE `tennis_court`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `volleyball_court`
--
ALTER TABLE `volleyball_court`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
