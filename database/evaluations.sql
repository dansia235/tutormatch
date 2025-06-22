-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 12:38 PM
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
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `evaluatee_id` int(11) NOT NULL,
  `type` enum('mid_term','final','supervisor','teacher','student') NOT NULL,
  `score` decimal(3,1) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_to_improve` text DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `assignment_id`, `evaluator_id`, `evaluatee_id`, `type`, `score`, `feedback`, `strengths`, `areas_to_improve`, `submission_date`) VALUES
(37, 1, 9, 14, 'mid_term', 13.0, 'L\'étudiant Lucas montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-06-10 23:00:00'),
(38, 2, 10, 16, 'mid_term', 12.0, 'L\'étudiant Louis montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-05-24 23:00:00'),
(39, 3, 13, 18, 'mid_term', 15.0, 'L\'étudiant Hugo montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-06-12 23:00:00'),
(40, 4, 9, 17, 'mid_term', 15.0, 'L\'étudiant Chloé montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-05-29 23:00:00'),
(41, 5, 11, 124, 'mid_term', 15.0, 'L\'étudiant Eva montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-06-20 23:00:00'),
(42, 6, 11, 126, 'mid_term', 13.0, 'L\'étudiant Théo montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-06-19 23:00:00'),
(43, 7, 9, 111, 'mid_term', 15.0, 'L\'étudiant Charlotte montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-06-13 23:00:00'),
(44, 8, 11, 105, 'mid_term', 13.0, 'L\'étudiant Gabriel montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.', 'Bonne maîtrise technique, autonomie dans les tâches assignées', 'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions', '2025-06-11 23:00:00'),
(52, 1, 14, 14, 'student', 12.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-19 23:00:00'),
(53, 2, 16, 16, 'student', 14.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-05 23:00:00'),
(54, 3, 18, 18, 'student', 17.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-21 23:00:00'),
(55, 4, 17, 17, 'student', 14.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-15 23:00:00'),
(56, 5, 124, 124, 'student', 13.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-02 23:00:00'),
(57, 6, 126, 126, 'student', 14.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-05 23:00:00'),
(58, 7, 111, 111, 'student', 17.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-15 23:00:00'),
(59, 8, 105, 105, 'student', 16.0, 'Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.', 'Apprentissage rapide des technologies, implication dans les projets', 'Communication plus régulière\nMeilleure organisation du temps', '2025-06-06 23:00:00'),
(67, 1, 9, 14, 'final', 17.0, 'L\'étudiant Lucas a réalisé d\'excellents progrès tout au long de son stage. Il a su s\'adapter aux défis techniques et a démontré une bonne capacité d\'intégration dans l\'équipe. Ses compétences techniques se sont nettement améliorées.', 'Maîtrise technique approfondie, autonomie, capacité d\'analyse et résolution de problèmes', 'Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées', '2025-06-21 23:00:00'),
(68, 2, 10, 16, 'final', 14.0, 'L\'étudiant Louis a réalisé d\'excellents progrès tout au long de son stage. Il a su s\'adapter aux défis techniques et a démontré une bonne capacité d\'intégration dans l\'équipe. Ses compétences techniques se sont nettement améliorées.', 'Maîtrise technique approfondie, autonomie, capacité d\'analyse et résolution de problèmes', 'Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées', '2025-06-20 23:00:00'),
(69, 5, 13, 18, 'final', 19.0, 'L\'étudiant réalisé d\'excellents progrès tout au long de son stage. Il a su s\'adapter aux défis techniques et a démontré une bonne capacité d\'intégration dans l\'équipe. Ses compétences techniques se sont nettement améliorées.', 'Maîtrise technique approfondie, autonomie, capacité d\'analyse et résolution de problèmes', 'Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées', '2025-06-13 23:00:00'),
(70, 6, 11, 126, 'final', 19.0, 'L\'étudiant Théo a réalisé d\'excellents progrès tout au long de son stage. Il a su s\'adapter aux défis techniques et a démontré une bonne capacité d\'intégration dans l\'équipe. Ses compétences techniques se sont nettement améliorées.', 'Maîtrise technique approfondie, autonomie, capacité d\'analyse et résolution de problèmes', 'Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées', '2025-06-13 23:00:00'),
(71, 7, 9, 111, 'final', 17.0, 'L\'étudiant Charlotte a réalisé d\'excellents progrès tout au long de son stage. Il a su s\'adapter aux défis techniques et a démontré une bonne capacité d\'intégration dans l\'équipe. Ses compétences techniques se sont nettement améliorées.', 'Maîtrise technique approfondie, autonomie, capacité d\'analyse et résolution de problèmes', 'Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées', '2025-06-18 23:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `evaluator_id` (`evaluator_id`),
  ADD KEY `evaluatee_id` (`evaluatee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_3` FOREIGN KEY (`evaluatee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
