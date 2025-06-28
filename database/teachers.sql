-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2025 at 02:03 PM
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
-- Database: `tutoring_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `office_location` varchar(100) DEFAULT NULL,
  `max_students` int(11) NOT NULL DEFAULT 5,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `expertise` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `title`, `specialty`, `office_location`, `max_students`, `available`, `expertise`) VALUES
(1, 2, NULL, NULL, NULL, 5, 1, NULL),
(2, 3, NULL, NULL, NULL, 5, 1, NULL),
(3, 4, NULL, NULL, NULL, 5, 1, NULL),
(4, 5, NULL, NULL, NULL, 5, 1, NULL),
(5, 6, NULL, NULL, NULL, 5, 1, NULL),
(6, 7, NULL, NULL, NULL, 5, 1, NULL),
(7, 8, NULL, NULL, NULL, 5, 1, NULL),
(8, 9, NULL, NULL, NULL, 5, 1, NULL),
(9, 10, NULL, NULL, NULL, 5, 1, NULL),
(10, 11, NULL, NULL, NULL, 5, 1, NULL),
(11, 12, NULL, NULL, NULL, 5, 1, NULL),
(12, 13, NULL, NULL, NULL, 5, 1, NULL),
(13, 14, NULL, NULL, NULL, 5, 1, NULL),
(14, 15, NULL, NULL, NULL, 5, 1, NULL),
(15, 16, NULL, NULL, NULL, 5, 1, NULL),
(16, 17, NULL, NULL, NULL, 5, 1, NULL),
(17, 18, NULL, NULL, NULL, 5, 1, NULL),
(18, 19, NULL, NULL, NULL, 5, 1, NULL),
(19, 20, NULL, NULL, NULL, 5, 1, NULL),
(20, 21, NULL, NULL, NULL, 5, 1, NULL),
(21, 22, NULL, NULL, NULL, 5, 1, NULL),
(22, 23, NULL, NULL, NULL, 5, 1, NULL),
(23, 24, NULL, NULL, NULL, 5, 1, NULL),
(24, 25, NULL, NULL, NULL, 5, 1, NULL),
(25, 26, NULL, NULL, NULL, 5, 1, NULL),
(26, 27, NULL, NULL, NULL, 5, 1, NULL),
(27, 28, NULL, NULL, NULL, 5, 1, NULL),
(28, 29, NULL, NULL, NULL, 5, 1, NULL),
(29, 30, NULL, NULL, NULL, 5, 1, NULL),
(30, 31, NULL, NULL, NULL, 5, 1, NULL),
(31, 32, NULL, NULL, NULL, 5, 1, NULL),
(32, 33, NULL, NULL, NULL, 5, 1, NULL),
(33, 34, NULL, NULL, NULL, 5, 1, NULL),
(34, 35, NULL, NULL, NULL, 5, 1, NULL),
(35, 36, NULL, NULL, NULL, 5, 1, NULL),
(36, 37, NULL, NULL, NULL, 5, 1, NULL),
(37, 38, NULL, NULL, NULL, 5, 1, NULL),
(38, 39, NULL, NULL, NULL, 5, 1, NULL),
(39, 40, NULL, NULL, NULL, 5, 1, NULL),
(40, 41, NULL, NULL, NULL, 5, 1, NULL),
(41, 42, NULL, NULL, NULL, 5, 1, NULL),
(42, 43, NULL, NULL, NULL, 5, 1, NULL),
(43, 44, NULL, NULL, NULL, 5, 1, NULL),
(44, 45, NULL, NULL, NULL, 5, 1, NULL),
(45, 46, NULL, NULL, NULL, 5, 1, NULL),
(46, 47, NULL, NULL, NULL, 5, 1, NULL),
(47, 48, NULL, NULL, NULL, 5, 1, NULL),
(48, 49, NULL, NULL, NULL, 5, 1, NULL),
(49, 50, NULL, NULL, NULL, 5, 1, NULL),
(50, 51, NULL, NULL, NULL, 5, 1, NULL),
(51, 52, NULL, NULL, NULL, 5, 1, NULL),
(52, 53, NULL, NULL, NULL, 5, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
