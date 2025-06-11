-- Table pour stocker les paramètres des algorithmes d'affectation
CREATE TABLE IF NOT EXISTS `algorithm_parameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `algorithm_type` varchar(50) NOT NULL DEFAULT 'greedy',
  `department_weight` int(11) NOT NULL DEFAULT 50,
  `preference_weight` int(11) NOT NULL DEFAULT 30,
  `capacity_weight` int(11) NOT NULL DEFAULT 20,
  `allow_cross_department` tinyint(1) NOT NULL DEFAULT 0,
  `prioritize_preferences` tinyint(1) NOT NULL DEFAULT 1,
  `balance_workload` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer un jeu de paramètres par défaut
INSERT INTO `algorithm_parameters` (
  `name`, 
  `description`, 
  `algorithm_type`, 
  `department_weight`, 
  `preference_weight`, 
  `capacity_weight`, 
  `allow_cross_department`, 
  `prioritize_preferences`, 
  `balance_workload`, 
  `is_default`
) VALUES (
  'Paramètres par défaut',
  'Paramètres générés automatiquement lors de l\'installation',
  'greedy',
  50,
  30,
  20,
  0,
  1,
  1,
  1
);