-- Table pour stocker les exécutions des algorithmes d'affectation
CREATE TABLE IF NOT EXISTS `algorithm_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parameters_id` int(11) NOT NULL,
  `executed_by` int(11) NOT NULL,
  `execution_time` float NOT NULL,
  `students_count` int(11) NOT NULL,
  `teachers_count` int(11) NOT NULL,
  `assignments_count` int(11) NOT NULL,
  `unassigned_count` int(11) NOT NULL,
  `average_satisfaction` float NOT NULL,
  `notes` text DEFAULT NULL,
  `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parameters_id` (`parameters_id`),
  KEY `executed_by` (`executed_by`),
  CONSTRAINT `algorithm_executions_ibfk_1` FOREIGN KEY (`parameters_id`) REFERENCES `algorithm_parameters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `algorithm_executions_ibfk_2` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;