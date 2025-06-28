-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 02:07 PM
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
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `program` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `average_grade` decimal(4,2) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `status` enum('active','graduated','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_number`, `program`, `level`, `average_grade`, `graduation_year`, `skills`, `cv_path`, `status`) VALUES
(1, 54, 'ET0001', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(2, 55, 'ET0002', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(3, 56, 'ET0003', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(4, 57, 'ET0004', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(5, 58, 'ET0005', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(6, 59, 'ET0006', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(7, 60, 'ET0007', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(8, 61, 'ET0008', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(9, 62, 'ET0009', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(10, 63, 'ET0010', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(11, 64, 'ET0011', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(12, 65, 'ET0012', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(13, 66, 'ET0013', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(14, 67, 'ET0014', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(15, 68, 'ET0015', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(16, 69, 'ET0016', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(17, 70, 'ET0017', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(18, 71, 'ET0018', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(19, 72, 'ET0019', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(20, 73, 'ET0020', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(21, 74, 'ET0021', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(22, 75, 'ET0022', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(23, 76, 'ET0023', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(24, 77, 'ET0024', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(25, 78, 'ET0025', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(26, 79, 'ET0026', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(27, 80, 'ET0027', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(28, 81, 'ET0028', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(29, 82, 'ET0029', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(30, 83, 'ET0030', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(31, 84, 'ET0031', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(32, 85, 'ET0032', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(33, 86, 'ET0033', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(34, 87, 'ET0034', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(35, 88, 'ET0035', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(36, 89, 'ET0036', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(37, 90, 'ET0037', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(38, 91, 'ET0038', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(39, 92, 'ET0039', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(40, 93, 'ET0040', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(41, 94, 'ET0041', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(42, 95, 'ET0042', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(43, 96, 'ET0043', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(44, 97, 'ET0044', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(45, 98, 'ET0045', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(46, 99, 'ET0046', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(47, 100, 'ET0047', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(48, 101, 'ET0048', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(49, 102, 'ET0049', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(50, 103, 'ET0050', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(51, 104, 'ET0051', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(52, 105, 'ET0052', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(53, 106, 'ET0053', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(54, 107, 'ET0054', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(55, 108, 'ET0055', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(56, 109, 'ET0056', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(57, 110, 'ET0057', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(58, 111, 'ET0058', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(59, 112, 'ET0059', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(60, 113, 'ET0060', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(61, 114, 'ET0061', 'Informatique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(62, 115, 'ET0062', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(63, 116, 'ET0063', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(64, 117, 'ET0064', 'Informatique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(65, 118, 'ET0065', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(66, 119, 'ET0066', 'Informatique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(67, 120, 'ET0067', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(68, 121, 'ET0068', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(69, 122, 'ET0069', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(70, 123, 'ET0070', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(71, 124, 'ET0071', 'Informatique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(72, 125, 'ET0072', 'Informatique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(73, 126, 'ET0073', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(74, 127, 'ET0074', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(75, 128, 'ET0075', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(76, 129, 'ET0076', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(77, 130, 'ET0077', 'Génie Civil', 'M1', NULL, NULL, NULL, NULL, 'active'),
(78, 131, 'ET0078', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(79, 132, 'ET0079', 'Génie Civil', 'M1', NULL, NULL, NULL, NULL, 'active'),
(80, 133, 'ET0080', 'Génie Civil', 'M1', NULL, NULL, NULL, NULL, 'active'),
(81, 134, 'ET0081', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(82, 135, 'ET0082', 'Génie Civil', 'M1', NULL, NULL, NULL, NULL, 'active'),
(83, 136, 'ET0083', 'Génie Civil', 'M1', NULL, NULL, NULL, NULL, 'active'),
(84, 137, 'ET0084', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(85, 138, 'ET0085', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(86, 139, 'ET0086', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(87, 140, 'ET0087', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(88, 141, 'ET0088', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(89, 142, 'ET0089', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(90, 143, 'ET0090', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(91, 144, 'ET0091', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(92, 145, 'ET0092', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(93, 146, 'ET0093', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(94, 147, 'ET0094', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(95, 148, 'ET0095', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(96, 149, 'ET0096', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(97, 150, 'ET0097', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(98, 151, 'ET0098', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(99, 152, 'ET0099', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(100, 153, 'ET0100', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(101, 154, 'ET0101', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(102, 155, 'ET0102', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(103, 156, 'ET0103', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(104, 157, 'ET0104', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(105, 158, 'ET0105', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(106, 159, 'ET0106', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(107, 160, 'ET0107', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(108, 161, 'ET0108', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(109, 162, 'ET0109', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(110, 163, 'ET0110', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(111, 164, 'ET0111', 'Génie Civil', 'L2', NULL, NULL, NULL, NULL, 'active'),
(112, 165, 'ET0112', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(113, 166, 'ET0113', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(114, 167, 'ET0114', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(115, 168, 'ET0115', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(116, 169, 'ET0116', 'Génie Civil', 'L1', NULL, NULL, NULL, NULL, 'active'),
(117, 170, 'ET0117', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(118, 171, 'ET0118', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(119, 172, 'ET0119', 'Génie Civil', 'M2', NULL, NULL, NULL, NULL, 'active'),
(120, 173, 'ET0120', 'Génie Civil', 'L3', NULL, NULL, NULL, NULL, 'active'),
(121, 174, 'ET0121', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(122, 175, 'ET0122', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(123, 176, 'ET0123', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(124, 177, 'ET0124', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(125, 178, 'ET0125', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(126, 179, 'ET0126', 'Électronique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(127, 180, 'ET0127', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(128, 181, 'ET0128', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(129, 182, 'ET0129', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(130, 183, 'ET0130', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(131, 184, 'ET0131', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(132, 185, 'ET0132', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(133, 186, 'ET0133', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(134, 187, 'ET0134', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(135, 188, 'ET0135', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(136, 189, 'ET0136', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(137, 190, 'ET0137', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(138, 191, 'ET0138', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(139, 192, 'ET0139', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(140, 193, 'ET0140', 'Électronique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(141, 194, 'ET0141', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(142, 195, 'ET0142', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(143, 196, 'ET0143', 'Électronique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(144, 197, 'ET0144', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(145, 198, 'ET0145', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(146, 199, 'ET0146', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(147, 200, 'ET0147', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(148, 201, 'ET0148', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(149, 202, 'ET0149', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(150, 203, 'ET0150', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(151, 204, 'ET0151', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(152, 205, 'ET0152', 'Électronique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(153, 206, 'ET0153', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(154, 207, 'ET0154', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(155, 208, 'ET0155', 'Électronique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(156, 209, 'ET0156', 'Électronique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(157, 210, 'ET0157', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(158, 211, 'ET0158', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(159, 212, 'ET0159', 'Électronique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(160, 213, 'ET0160', 'Électronique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(161, 214, 'ET0161', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(162, 215, 'ET0162', 'Mécanique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(163, 216, 'ET0163', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(164, 217, 'ET0164', 'Mécanique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(165, 218, 'ET0165', 'Mécanique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(166, 219, 'ET0166', 'Mécanique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(167, 220, 'ET0167', 'Mécanique', 'L2', NULL, NULL, NULL, NULL, 'active'),
(168, 221, 'ET0168', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(169, 222, 'ET0169', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(170, 223, 'ET0170', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(171, 224, 'ET0171', 'Mécanique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(172, 225, 'ET0172', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(173, 226, 'ET0173', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(174, 227, 'ET0174', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(175, 228, 'ET0175', 'Mécanique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(176, 229, 'ET0176', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(177, 230, 'ET0177', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(178, 231, 'ET0178', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(179, 232, 'ET0179', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(180, 233, 'ET0180', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(181, 234, 'ET0181', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(182, 235, 'ET0182', 'Mécanique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(183, 236, 'ET0183', 'Mécanique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(184, 237, 'ET0184', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(185, 238, 'ET0185', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(186, 239, 'ET0186', 'Mécanique', 'M2', NULL, NULL, NULL, NULL, 'active'),
(187, 240, 'ET0187', 'Mécanique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(188, 241, 'ET0188', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(189, 242, 'ET0189', 'Mécanique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(190, 243, 'ET0190', 'Mécanique', 'M1', NULL, NULL, NULL, NULL, 'active'),
(191, 244, 'ET0191', 'Mécanique', 'L1', NULL, NULL, NULL, NULL, 'active'),
(192, 245, 'ET0192', 'Mécanique', 'L3', NULL, NULL, NULL, NULL, 'active'),
(193, 246, 'ET0193', 'Mathématiques', 'M2', NULL, NULL, NULL, NULL, 'active'),
(194, 247, 'ET0194', 'Mathématiques', 'M1', NULL, NULL, NULL, NULL, 'active'),
(195, 248, 'ET0195', 'Mathématiques', 'M2', NULL, NULL, NULL, NULL, 'active'),
(196, 249, 'ET0196', 'Mathématiques', 'L2', NULL, NULL, NULL, NULL, 'active'),
(197, 250, 'ET0197', 'Mathématiques', 'L1', NULL, NULL, NULL, NULL, 'active'),
(198, 251, 'ET0198', 'Mathématiques', 'M2', NULL, NULL, NULL, NULL, 'active'),
(199, 252, 'ET0199', 'Mathématiques', 'L2', NULL, NULL, NULL, NULL, 'active'),
(200, 253, 'ET0200', 'Mathématiques', 'M1', NULL, NULL, NULL, NULL, 'active'),
(201, 254, 'ET0201', 'Mathématiques', 'M2', NULL, NULL, NULL, NULL, 'active'),
(202, 255, 'ET0202', 'Mathématiques', 'L1', NULL, NULL, NULL, NULL, 'active'),
(203, 256, 'ET0203', 'Mathématiques', 'M1', NULL, NULL, NULL, NULL, 'active'),
(204, 257, 'ET0204', 'Mathématiques', 'M2', NULL, NULL, NULL, NULL, 'active'),
(205, 258, 'ET0205', 'Mathématiques', 'M2', NULL, NULL, NULL, NULL, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=206;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
