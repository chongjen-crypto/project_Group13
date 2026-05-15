-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2026 at 04:47 AM
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
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `basketball_court`
--

CREATE TABLE `basketball_court` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `facility_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `facility_name`) VALUES
(1, 'Badminton Court'),
(2, 'Tennis Court'),
(3, 'Swimming Pool'),
(4, 'Gym Room'),
(5, 'Track Field'),
(6, 'Volleyball Court'),
(7, 'Basketball Court'),
(8, 'Snooker Room'),
(9, 'Futsal Court');

-- --------------------------------------------------------

--
-- Table structure for table `futsal_court`
--

CREATE TABLE `futsal_court` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_room`
--

CREATE TABLE `gym_room` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `snooker_room`
--

CREATE TABLE `snooker_room` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `swimming_pool`
--

CREATE TABLE `swimming_pool` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tennis_court`
--

CREATE TABLE `tennis_court` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `track_field`
--

CREATE TABLE `track_field` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(5, 'student', 1, NULL, NULL, 'zzz', 'zzz', 'zzz@gmail.com', '', '$2y$10$kzHjVKrZnE5eHD3MDUI8/uaO.nLxvo2.LjO40VEUoIWIhXGyuO3yy', '2026-05-11 06:53:28'),
(6, 'student', 1, NULL, NULL, 'zz', 'zz', 'kuangkaize@gmail.com', '', '$2y$10$0PqvWQcV9wardic5alhvYOoEgm2/YCj4ktW8IgVFNh.amDO3r5QpW', '2026-05-11 07:47:15'),
(7, 'student', 1, NULL, NULL, 'zzzz', 'zzzz', 'kaizekuang@gmail.com', '', '$2y$10$m7WXnZVpTz/5zQKQCua04O6tHUZMjYBkdWk.0NBiy8Dd05eTn2lt6', '2026-05-11 08:15:00'),
(11, 'student', 1, NULL, NULL, 'student', 'default', 'student@gmail.com', '', '$2y$10$XKiBe7M39LWJyxCc/cVDJeySAdpMmz00JuepE9CauDHQK.Xds22Oq', '2026-05-14 04:07:52'),
(12, 'staff', 0, NULL, NULL, 'default', 'staff', 'staff@gmail.com', '', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 08:48:05'),
(13, 'admin', 0, NULL, NULL, 'System Administrator', 'admin', 'admin@gmail.com', '0123456789', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 13:43:17'),
(14, 'student', 0, NULL, NULL, 'Jason Lim', 'STU001', 'jasonlim@gmail.com', '0121111111', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(15, 'student', 0, NULL, NULL, 'Emily Tan', 'STU002', 'emilytan@gmail.com', '0122222222', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(16, 'student', 0, NULL, NULL, 'Ryan Wong', 'STU003', 'ryanwong@gmail.com', '0123333333', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(17, 'student', 0, NULL, NULL, 'Sophia Lee', 'STU004', 'sophialee@gmail.com', '0124444444', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(18, 'student', 0, NULL, NULL, 'Daniel Ong', 'STU005', 'danielong@gmail.com', '0125555555', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(19, 'staff', 0, NULL, NULL, 'Michael Tan', 'STF001', 'michaeltan@gmail.com', '0131111111', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(20, 'staff', 0, NULL, NULL, 'Sarah Lim', 'STF002', 'sarahlim@gmail.com', '0132222222', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35'),
(21, 'staff', 0, NULL, NULL, 'Kevin Lee', 'STF003', 'kevinlee@gmail.com', '0133333333', '$2y$10$yjM3CdG99TkYBNaI8YpS.ejOBseYEoMFijRrEOtsnuUe6tJQzAwMe', '2026-05-14 14:44:35');

-- --------------------------------------------------------

--
-- Table structure for table `volleyball_court`
--

CREATE TABLE `volleyball_court` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badminton_court`
--
ALTER TABLE `badminton_court`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `basketball_court`
--
ALTER TABLE `basketball_court`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `futsal_court`
--
ALTER TABLE `futsal_court`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_room`
--
ALTER TABLE `gym_room`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `snooker_room`
--
ALTER TABLE `snooker_room`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `swimming_pool`
--
ALTER TABLE `swimming_pool`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tennis_court`
--
ALTER TABLE `tennis_court`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `track_field`
--
ALTER TABLE `track_field`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badminton_court`
--
ALTER TABLE `badminton_court`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `basketball_court`
--
ALTER TABLE `basketball_court`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `futsal_court`
--
ALTER TABLE `futsal_court`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_room`
--
ALTER TABLE `gym_room`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `snooker_room`
--
ALTER TABLE `snooker_room`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `swimming_pool`
--
ALTER TABLE `swimming_pool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tennis_court`
--
ALTER TABLE `tennis_court`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `track_field`
--
ALTER TABLE `track_field`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `volleyball_court`
--
ALTER TABLE `volleyball_court`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
