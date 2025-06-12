-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2025 at 12:36 PM
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
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('contract','report','evaluation','certificate','other') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `feedback` text DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `assignment_id`, `title`, `type`, `file_path`, `upload_date`, `status`, `feedback`, `version`) VALUES
(1, 14, 1, 'Convention de stage - Lucas Dupont', 'contract', 'uploads/documents/1_convention_stage.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL),
(2, 14, 1, 'CV - Lucas Dupont', 'other', 'uploads/documents/1_cv.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL),
(3, 14, 1, 'Rapport intermédiaire - Lucas Dupont', 'report', 'uploads/documents/1_rapport_intermediaire.pdf', '2025-06-07 09:42:54', 'draft', NULL, NULL),
(4, 16, 2, 'Convention de stage - Louis Moreau', 'contract', 'uploads/documents/3_convention_stage.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL),
(5, 16, 2, 'CV - Louis Moreau', 'other', 'uploads/documents/3_cv.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL),
(6, 18, 3, 'Convention de stage - Hugo Simon', 'contract', 'uploads/documents/5_convention_stage.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL),
(7, 17, 4, 'Convention de stage - Chloé Fournier', 'contract', 'uploads/documents/4_convention_stage.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL),
(8, 9, NULL, 'Guide du rapport final', 'other', 'uploads/documents/guide_rapport_final.pdf', '2025-06-07 09:42:54', 'approved', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
