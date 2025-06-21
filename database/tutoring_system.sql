-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2025 at 11:04 PM
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
-- Table structure for table `algorithm_executions`
--

CREATE TABLE `algorithm_executions` (
  `id` int(11) NOT NULL,
  `parameters_id` int(11) NOT NULL,
  `executed_by` int(11) NOT NULL,
  `execution_time` float NOT NULL,
  `students_count` int(11) NOT NULL,
  `teachers_count` int(11) NOT NULL,
  `assignments_count` int(11) NOT NULL,
  `unassigned_count` int(11) NOT NULL,
  `average_satisfaction` float NOT NULL,
  `notes` text DEFAULT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `algorithm_executions`
--

INSERT INTO `algorithm_executions` (`id`, `parameters_id`, `executed_by`, `execution_time`, `students_count`, `teachers_count`, `assignments_count`, `unassigned_count`, `average_satisfaction`, `notes`, `executed_at`) VALUES
(1, 6, 1, 33.3397, 81, 38, 14, 67, 23.0952, 'Test affectation 1', '2025-06-11 19:13:20'),
(2, 7, 1, 0.568872, 67, 37, 0, 67, 0, 'Test Affectation 2', '2025-06-11 19:14:44'),
(3, 8, 1, 0.279732, 67, 37, 0, 67, 0, 'Test Affectation 3', '2025-06-11 19:32:43'),
(4, 9, 1, 0.265224, 67, 37, 0, 67, 0, 'Test Affectation 4', '2025-06-11 19:33:32'),
(5, 10, 1, 0.43345, 67, 37, 0, 67, 0, 'Afectaion 10', '2025-06-11 20:56:15'),
(6, 11, 1, 0.169823, 67, 37, 0, 67, 0, 'Affectation 11', '2025-06-11 22:12:13'),
(7, 12, 1, 0.158788, 67, 37, 0, 67, 0, 'Affectation 4', '2025-06-11 22:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `algorithm_parameters`
--

CREATE TABLE `algorithm_parameters` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `algorithm_parameters`
--

INSERT INTO `algorithm_parameters` (`id`, `name`, `description`, `algorithm_type`, `department_weight`, `preference_weight`, `capacity_weight`, `allow_cross_department`, `prioritize_preferences`, `balance_workload`, `is_default`, `created_at`) VALUES
(1, 'Paramètres par défaut', 'Paramètres générés automatiquement lors de l\'installation', 'greedy', 50, 30, 20, 0, 1, 1, 1, '2025-06-11 16:59:46'),
(6, 'Exécution du 2025-06-11 21:12', 'Test affectation 1', 'hybrid', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 19:12:46'),
(7, 'Exécution du 2025-06-11 21:14', 'Test Affectation 2', 'greedy', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 19:14:44'),
(8, 'Exécution du 2025-06-11 21:32', 'Test Affectation 3', 'genetic', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 19:32:43'),
(9, 'Exécution du 2025-06-11 21:33', 'Test Affectation 4', 'genetic', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 19:33:32'),
(10, 'Exécution du 2025-06-11 22:55', 'Afectaion 10', 'genetic', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 20:56:14'),
(11, 'Exécution du 2025-06-12 00:11', 'Affectation 11', 'hungarian', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 22:12:13'),
(12, 'Exécution du 2025-06-11 00:13', 'Affectation 4', 'genetic', 50, 30, 20, 0, 1, 1, 0, '2025-06-11 22:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','rejected','completed') NOT NULL DEFAULT 'pending',
  `satisfaction_score` decimal(5,2) DEFAULT NULL,
  `compatibility_score` decimal(5,2) DEFAULT NULL,
  `assignment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmation_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `student_id`, `teacher_id`, `internship_id`, `status`, `satisfaction_score`, `compatibility_score`, `assignment_date`, `confirmation_date`, `notes`) VALUES
(1, 3, 2, 1, 'confirmed', NULL, 4.10, '2025-06-07 09:42:54', '2025-06-21 16:07:32', ''),
(2, 5, 3, 2, 'confirmed', NULL, 4.30, '2025-06-07 09:42:54', '2025-06-11 22:47:44', ''),
(3, 7, 6, 3, 'confirmed', NULL, NULL, '2025-06-07 09:42:54', NULL, NULL),
(4, 6, 2, 4, 'confirmed', NULL, NULL, '2025-06-07 09:42:54', NULL, NULL),
(5, 97, 4, 1, 'confirmed', 0.00, 0.00, '2025-06-11 19:13:20', '2025-06-11 20:32:02', 'Affectation générée automatiquement par l\'algorithme hybrid'),
(6, 99, 4, 38, 'confirmed', 0.00, 0.50, '2025-06-11 19:13:20', '2025-06-11 21:00:04', 'Affectation générée automatiquement par l\'algorithme hybrid'),
(7, 84, 2, 45, 'confirmed', 0.00, 1.40, '2025-06-11 19:13:20', '2025-06-11 20:59:45', 'Affectation générée automatiquement par l\'algorithme hybrid'),
(8, 78, 4, 84, 'confirmed', 0.00, 0.50, '2025-06-11 19:13:20', '2025-06-11 21:00:34', 'Affectation générée automatiquement par l\'algorithme hybrid'),
(9, 4, 3, 2, 'completed', 0.00, 3.90, '2025-06-11 19:13:20', '2025-06-11 22:00:56', 'Affectation générée automatiquement par l\'algorithme hybrid'),
(10, 42, 3, 11, 'pending', 0.00, 24.00, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(11, 51, 2, 91, 'pending', 0.00, 23.33, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(12, 91, 13, 135, 'pending', 0.00, 20.00, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(13, 36, 14, 174, 'pending', 0.00, 20.00, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(14, 43, 15, 181, 'rejected', 0.00, 1.50, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(15, 71, 16, 225, 'pending', 0.00, 20.00, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(16, 94, 17, 10, 'pending', 0.00, 20.00, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(17, 79, 18, 51, 'rejected', 0.00, 1.50, '2025-06-11 19:13:20', NULL, 'Affectation générée automatiquement par l\'algorithme hybrid'),
(18, 29, 32, 56, 'completed', 0.00, 1.33, '2025-06-11 19:13:20', '2025-06-11 22:01:15', 'Affectation générée automatiquement par l\'algorithme hybrid');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `address`, `city`, `country`, `website`, `description`, `logo_path`, `contact_name`, `contact_email`, `contact_phone`, `active`, `created_at`) VALUES
(1, 'TechSolutions', '123 Rue de l\'Innovation', 'Paris', 'France', 'www.techsolutions.fr', 'Entreprise spécialisée dans le développement de solutions logicielles innovantes.', NULL, 'Jean Dupont', 'contact@techsolutions.fr', '+33123456789', 1, '2025-06-07 09:42:54'),
(2, 'DataInsight', '45 Avenue des Données', 'Lyon', 'France', 'www.datainsight.fr', 'Leader dans l\'analyse de données et l\'intelligence d\'affaires.', NULL, 'Marie Laurent', 'info@datainsight.fr', '+33987654321', 1, '2025-06-07 09:42:54'),
(3, 'CyberGuard', '78 Boulevard de la Sécurité', 'Lille', 'France', 'www.cyberguard.fr', 'Entreprise spécialisée dans les solutions de cybersécurité pour les PME et grands groupes.', NULL, 'Thomas Martin', 'contact@cyberguard.fr', '+33678912345', 1, '2025-06-07 09:42:54'),
(4, 'MobileTech', '15 Rue des Applications', 'Bordeaux', 'France', 'www.mobiletech.fr', 'Agence de développement d\'applications mobiles innovantes.', NULL, 'Sophie Dubois', 'info@mobiletech.fr', '+33234567890', 1, '2025-06-07 09:42:54'),
(5, 'SmartSystems', '56 Avenue de l\'Électronique', 'Toulouse', 'France', 'www.smartsystems.fr', 'Conception et développement de systèmes embarqués intelligents.', NULL, 'Pierre Rousseau', 'contact@smartsystems.fr', '+33345678901', 1, '2025-06-07 09:42:54'),
(6, 'TechInnovate', '15 rue de l\'Innovation', 'Paris', 'France', 'www.techinnovate.fr', 'Entreprise spécialisée dans le développement de solutions innovantes pour l\'industrie 4.0', '/uploads/logos/techinnovate.png', 'Sophie Martin', 'sophie.martin@techinnovate.fr', '01 23 45 67 89', 1, '2025-06-10 09:23:15'),
(7, 'DataVision', '45 avenue des Données', 'Lyon', 'France', 'www.datavision.fr', 'Experts en analyse de données et intelligence artificielle', '/uploads/logos/datavision.png', 'Thomas Dubois', 'thomas.dubois@datavision.fr', '04 56 78 90 12', 1, '2025-06-10 09:23:15'),
(8, 'CyberGuard', '8 boulevard de la Sécurité', 'Lille', 'France', 'www.cyberguard.fr', 'Solutions de cybersécurité pour entreprises', '/uploads/logos/cyberguard.png', 'Camille Leroy', 'camille.leroy@cyberguard.fr', '03 21 43 65 87', 1, '2025-06-10 09:23:15'),
(9, 'MobileTech', '27 rue des Applications', 'Bordeaux', 'France', 'www.mobiletech.fr', 'Développement d\'applications mobiles sur mesure', '/uploads/logos/mobiletech.png', 'Lucas Bernard', 'lucas.bernard@mobiletech.fr', '05 56 78 90 12', 1, '2025-06-10 09:23:15'),
(10, 'EmbedSys', '12 rue des Capteurs', 'Toulouse', 'France', 'www.embedsys.fr', 'Systèmes embarqués pour l\'industrie et l\'IoT', '/uploads/logos/embedsys.png', 'Emma Petit', 'emma.petit@embedsys.fr', '05 61 23 45 67', 1, '2025-06-10 09:23:15'),
(11, 'CloudFlow', '36 avenue du Cloud', 'Nantes', 'France', 'www.cloudflow.fr', 'Services cloud et infrastructure as code', '/uploads/logos/cloudflow.png', 'Alexandre Moreau', 'alexandre.moreau@cloudflow.fr', '02 40 12 34 56', 1, '2025-06-10 09:23:15'),
(12, 'AILabs', '5 place de l\'Intelligence', 'Grenoble', 'France', 'www.ailabs.fr', 'Recherche et développement en intelligence artificielle', '/uploads/logos/ailabs.png', 'Julie Roux', 'julie.roux@ailabs.fr', '04 76 23 45 67', 1, '2025-06-10 09:23:15'),
(13, 'WebSphere', '19 rue du Web', 'Strasbourg', 'France', 'www.websphere.fr', 'Agence de développement web full-stack', '/uploads/logos/websphere.png', 'Nicolas Blanc', 'nicolas.blanc@websphere.fr', '03 88 45 67 89', 1, '2025-06-10 09:23:15'),
(14, 'BlockChainSolutions', '7 rue de la Blockchain', 'Montpellier', 'France', 'www.blockchainsolutions.fr', 'Solutions basées sur la blockchain pour la finance et la logistique', '/uploads/logos/blockchainsolutions.png', 'Marie Dupont', 'marie.dupont@blockchainsolutions.fr', '04 67 89 01 23', 1, '2025-06-10 09:23:15'),
(15, 'IoTConnect', '25 boulevard des Objets', 'Nice', 'France', 'www.iotconnect.fr', 'Plateforme IoT et solutions connectées', '/uploads/logos/iotconnect.png', 'Antoine Garcia', 'antoine.garcia@iotconnect.fr', '04 93 12 34 56', 1, '2025-06-10 09:23:15'),
(16, 'QuantumBits', '3 allée Quantique', 'Rennes', 'France', 'www.quantumbits.fr', 'Recherche en informatique quantique et applications', '/uploads/logos/quantumbits.png', 'Léa Marchand', 'lea.marchand@quantumbits.fr', '02 99 78 90 12', 1, '2025-06-10 09:23:15'),
(17, 'VRTech', '14 rue de la Réalité Virtuelle', 'Marseille', 'France', 'www.vrtech.fr', 'Développement d\'expériences en réalité virtuelle et augmentée', '/uploads/logos/vrtech.png', 'Julien Robert', 'julien.robert@vrtech.fr', '04 91 23 45 67', 1, '2025-06-10 09:23:15'),
(18, 'EcoTech', '48 rue Verte', 'Angers', 'France', 'www.ecotech.fr', 'Solutions technologiques pour la transition écologique', '/uploads/logos/ecotech.png', 'Clara Simon', 'clara.simon@ecotech.fr', '02 41 56 78 90', 1, '2025-06-10 09:23:15'),
(19, 'BioInformatique', '22 avenue de la Génomique', 'Tours', 'France', 'www.bioinformatique.fr', 'Analyse de données biologiques et génomiques', '/uploads/logos/bioinformatique.png', 'Pierre Lambert', 'pierre.lambert@bioinformatique.fr', '02 47 12 34 56', 1, '2025-06-10 09:23:15'),
(20, 'SmartCity', '9 place de la Ville Intelligente', 'Dijon', 'France', 'www.smartcity.fr', 'Solutions numériques pour les villes intelligentes', '/uploads/logos/smartcity.png', 'Elise Fournier', 'elise.fournier@smartcity.fr', '03 80 45 67 89', 1, '2025-06-10 09:23:15'),
(21, 'FinTech', '31 rue de la Finance', 'Bordeaux', 'France', 'www.fintech.fr', 'Technologies innovantes pour le secteur bancaire et financier', '/uploads/logos/fintech.png', 'Maxime Durand', 'maxime.durand@fintech.fr', '05 57 89 01 23', 1, '2025-06-10 09:23:15'),
(22, 'RoboTech', '17 avenue de la Robotique', 'Toulouse', 'France', 'www.robotech.fr', 'Conception et programmation de robots industriels et de service', '/uploads/logos/robotech.png', 'Sarah Bonnet', 'sarah.bonnet@robotech.fr', '05 62 34 56 78', 1, '2025-06-10 09:23:15'),
(23, 'EdTech', '42 rue de l\'Éducation', 'Lyon', 'France', 'www.edtech.fr', 'Solutions numériques pour l\'éducation et la formation', '/uploads/logos/edtech.png', 'Hugo Martin', 'hugo.martin@edtech.fr', '04 72 90 12 34', 1, '2025-06-10 09:23:15'),
(24, 'MedTech', '6 boulevard de la Santé', 'Lille', 'France', 'www.medtech.fr', 'Technologies médicales et solutions e-santé', '/uploads/logos/medtech.png', 'Chloé Leroux', 'chloe.leroux@medtech.fr', '03 20 56 78 90', 1, '2025-06-10 09:23:15'),
(25, 'GreenEnergy', '11 rue des Énergies Renouvelables', 'Nantes', 'France', 'www.greenenergy.fr', 'Solutions informatiques pour la gestion de l\'énergie renouvelable', '/uploads/logos/greenenergy.png', 'Victor Moreau', 'victor.moreau@greenenergy.fr', '02 40 23 45 67', 1, '2025-06-10 09:23:15'),
(26, 'Agritech', '24 chemin des Cultures', 'Montpellier', 'France', 'www.agritech.fr', 'Technologies pour l\'agriculture moderne et durable', '/uploads/logos/agritech.png', 'Aurélie Rousseau', 'aurelie.rousseau@agritech.fr', '04 67 12 34 56', 1, '2025-06-10 09:23:15'),
(27, 'SecurNet', '38 rue du Firewall', 'Paris', 'France', 'www.securnet.fr', 'Sécurité réseau et protection des infrastructures critiques', '/uploads/logos/securnet.png', 'David Mercier', 'david.mercier@securnet.fr', '01 45 67 89 01', 1, '2025-06-10 09:23:15'),
(28, '3DPrint', '16 avenue de l\'Impression 3D', 'Grenoble', 'France', 'www.3dprint.fr', 'Solutions logicielles pour l\'impression 3D industrielle', '/uploads/logos/3dprint.png', 'Mathilde Girard', 'mathilde.girard@3dprint.fr', '04 76 89 01 23', 1, '2025-06-10 09:23:15'),
(29, 'DevOpsTools', '29 rue de l\'Intégration', 'Strasbourg', 'France', 'www.devopstools.fr', 'Outils et plateformes pour l\'intégration continue et le déploiement', '/uploads/logos/devopstools.png', 'Romain Faure', 'romain.faure@devopstools.fr', '03 88 12 34 56', 1, '2025-06-10 09:23:15'),
(30, 'NeuroTech', '13 impasse des Neurones', 'Marseille', 'France', 'www.neurotech.fr', 'Interfaces cerveau-machine et algorithmes d\'apprentissage profond', '/uploads/logos/neurotech.png', 'Inès Blanc', 'ines.blanc@neurotech.fr', '04 91 45 67 89', 1, '2025-06-10 09:23:15'),
(31, 'AudioTech', '52 boulevard du Son', 'Nice', 'France', 'www.audiotech.fr', 'Traitement du signal audio et reconnaissance vocale', '/uploads/logos/audiotech.png', 'Théo Gaillard', 'theo.gaillard@audiotech.fr', '04 93 90 12 34', 1, '2025-06-10 09:23:15'),
(32, 'LogisticTech', '18 rue de la Logistique', 'Le Havre', 'France', 'www.logistictech.fr', 'Solutions numériques pour la chaîne d\'approvisionnement', '/uploads/logos/logistictech.png', 'Laura Bertrand', 'laura.bertrand@logistictech.fr', '02 35 56 78 90', 1, '2025-06-10 09:23:15'),
(33, 'HRTech', '33 avenue des Ressources Humaines', 'Bordeaux', 'France', 'www.hrtech.fr', 'Plateformes RH et gestion des talents', '/uploads/logos/hrtech.png', 'Benoît Lefebvre', 'benoit.lefebvre@hrtech.fr', '05 56 23 45 67', 1, '2025-06-10 09:23:15'),
(34, 'GameDev', '21 rue du Jeu Vidéo', 'Lille', 'France', 'www.gamedev.fr', 'Studio de développement de jeux vidéo et expériences interactives', '/uploads/logos/gamedev.png', 'Zoé Legrand', 'zoe.legrand@gamedev.fr', '03 20 78 90 12', 1, '2025-06-10 09:23:15'),
(35, 'LegalTech', '44 rue du Droit', 'Lyon', 'France', 'www.legaltech.fr', 'Solutions technologiques pour le secteur juridique', '/uploads/logos/legaltech.png', 'Guillaume Morel', 'guillaume.morel@legaltech.fr', '04 72 34 56 78', 1, '2025-06-10 09:23:15'),
(36, 'TechInnovate', '15 rue de l\'Innovation', 'Paris', 'France', 'www.techinnovate.fr', 'Entreprise spécialisée dans le développement de solutions innovantes pour l\'industrie 4.0', '/uploads/logos/techinnovate.png', 'Sophie Martin', 'sophie.martin@techinnovate.fr', '01 23 45 67 89', 1, '2025-06-10 09:50:27'),
(37, 'DataVision', '45 avenue des Données', 'Lyon', 'France', 'www.datavision.fr', 'Experts en analyse de données et intelligence artificielle', '/uploads/logos/datavision.png', 'Thomas Dubois', 'thomas.dubois@datavision.fr', '04 56 78 90 12', 1, '2025-06-10 09:50:27'),
(38, 'CyberGuard', '8 boulevard de la Sécurité', 'Lille', 'France', 'www.cyberguard.fr', 'Solutions de cybersécurité pour entreprises', '/uploads/logos/cyberguard.png', 'Camille Leroy', 'camille.leroy@cyberguard.fr', '03 21 43 65 87', 1, '2025-06-10 09:50:27'),
(39, 'MobileTech', '27 rue des Applications', 'Bordeaux', 'France', 'www.mobiletech.fr', 'Développement d\'applications mobiles sur mesure', '/uploads/logos/mobiletech.png', 'Lucas Bernard', 'lucas.bernard@mobiletech.fr', '05 56 78 90 12', 1, '2025-06-10 09:50:27'),
(40, 'EmbedSys', '12 rue des Capteurs', 'Toulouse', 'France', 'www.embedsys.fr', 'Systèmes embarqués pour l\'industrie et l\'IoT', '/uploads/logos/embedsys.png', 'Emma Petit', 'emma.petit@embedsys.fr', '05 61 23 45 67', 1, '2025-06-10 09:50:27'),
(41, 'CloudFlow', '36 avenue du Cloud', 'Nantes', 'France', 'www.cloudflow.fr', 'Services cloud et infrastructure as code', '/uploads/logos/cloudflow.png', 'Alexandre Moreau', 'alexandre.moreau@cloudflow.fr', '02 40 12 34 56', 1, '2025-06-10 09:50:27'),
(42, 'AILabs', '5 place de l\'Intelligence', 'Grenoble', 'France', 'www.ailabs.fr', 'Recherche et développement en intelligence artificielle', '/uploads/logos/ailabs.png', 'Julie Roux', 'julie.roux@ailabs.fr', '04 76 23 45 67', 1, '2025-06-10 09:50:27'),
(43, 'WebSphere', '19 rue du Web', 'Strasbourg', 'France', 'www.websphere.fr', 'Agence de développement web full-stack', '/uploads/logos/websphere.png', 'Nicolas Blanc', 'nicolas.blanc@websphere.fr', '03 88 45 67 89', 1, '2025-06-10 09:50:27'),
(44, 'BlockChainSolutions', '7 rue de la Blockchain', 'Montpellier', 'France', 'www.blockchainsolutions.fr', 'Solutions basées sur la blockchain pour la finance et la logistique', '/uploads/logos/blockchainsolutions.png', 'Marie Dupont', 'marie.dupont@blockchainsolutions.fr', '04 67 89 01 23', 1, '2025-06-10 09:50:27'),
(45, 'IoTConnect', '25 boulevard des Objets', 'Nice', 'France', 'www.iotconnect.fr', 'Plateforme IoT et solutions connectées', '/uploads/logos/iotconnect.png', 'Antoine Garcia', 'antoine.garcia@iotconnect.fr', '04 93 12 34 56', 1, '2025-06-10 09:50:27'),
(46, 'QuantumBits', '3 allée Quantique', 'Rennes', 'France', 'www.quantumbits.fr', 'Recherche en informatique quantique et applications', '/uploads/logos/quantumbits.png', 'Léa Marchand', 'lea.marchand@quantumbits.fr', '02 99 78 90 12', 1, '2025-06-10 09:50:27'),
(47, 'VRTech', '14 rue de la Réalité Virtuelle', 'Marseille', 'France', 'www.vrtech.fr', 'Développement d\'expériences en réalité virtuelle et augmentée', '/uploads/logos/vrtech.png', 'Julien Robert', 'julien.robert@vrtech.fr', '04 91 23 45 67', 1, '2025-06-10 09:50:27'),
(48, 'EcoTech', '48 rue Verte', 'Angers', 'France', 'www.ecotech.fr', 'Solutions technologiques pour la transition écologique', '/uploads/logos/ecotech.png', 'Clara Simon', 'clara.simon@ecotech.fr', '02 41 56 78 90', 1, '2025-06-10 09:50:27'),
(49, 'BioInformatique', '22 avenue de la Génomique', 'Tours', 'France', 'www.bioinformatique.fr', 'Analyse de données biologiques et génomiques', '/uploads/logos/bioinformatique.png', 'Pierre Lambert', 'pierre.lambert@bioinformatique.fr', '02 47 12 34 56', 1, '2025-06-10 09:50:27'),
(50, 'SmartCity', '9 place de la Ville Intelligente', 'Dijon', 'France', 'www.smartcity.fr', 'Solutions numériques pour les villes intelligentes', '/uploads/logos/smartcity.png', 'Elise Fournier', 'elise.fournier@smartcity.fr', '03 80 45 67 89', 1, '2025-06-10 09:50:27'),
(51, 'FinTech', '31 rue de la Finance', 'Bordeaux', 'France', 'www.fintech.fr', 'Technologies innovantes pour le secteur bancaire et financier', '/uploads/logos/fintech.png', 'Maxime Durand', 'maxime.durand@fintech.fr', '05 57 89 01 23', 1, '2025-06-10 09:50:27'),
(52, 'RoboTech', '17 avenue de la Robotique', 'Toulouse', 'France', 'www.robotech.fr', 'Conception et programmation de robots industriels et de service', '/uploads/logos/robotech.png', 'Sarah Bonnet', 'sarah.bonnet@robotech.fr', '05 62 34 56 78', 1, '2025-06-10 09:50:27'),
(53, 'EdTech', '42 rue de l\'Éducation', 'Lyon', 'France', 'www.edtech.fr', 'Solutions numériques pour l\'éducation et la formation', '/uploads/logos/edtech.png', 'Hugo Martin', 'hugo.martin@edtech.fr', '04 72 90 12 34', 1, '2025-06-10 09:50:27'),
(54, 'MedTech', '6 boulevard de la Santé', 'Lille', 'France', 'www.medtech.fr', 'Technologies médicales et solutions e-santé', '/uploads/logos/medtech.png', 'Chloé Leroux', 'chloe.leroux@medtech.fr', '03 20 56 78 90', 1, '2025-06-10 09:50:27'),
(55, 'GreenEnergy', '11 rue des Énergies Renouvelables', 'Nantes', 'France', 'www.greenenergy.fr', 'Solutions informatiques pour la gestion de l\'énergie renouvelable', '/uploads/logos/greenenergy.png', 'Victor Moreau', 'victor.moreau@greenenergy.fr', '02 40 23 45 67', 1, '2025-06-10 09:50:27'),
(56, 'Agritech', '24 chemin des Cultures', 'Montpellier', 'France', 'www.agritech.fr', 'Technologies pour l\'agriculture moderne et durable', '/uploads/logos/agritech.png', 'Aurélie Rousseau', 'aurelie.rousseau@agritech.fr', '04 67 12 34 56', 1, '2025-06-10 09:50:27'),
(57, 'SecurNet', '38 rue du Firewall', 'Paris', 'France', 'www.securnet.fr', 'Sécurité réseau et protection des infrastructures critiques', '/uploads/logos/securnet.png', 'David Mercier', 'david.mercier@securnet.fr', '01 45 67 89 01', 1, '2025-06-10 09:50:27'),
(58, '3DPrint', '16 avenue de l\'Impression 3D', 'Grenoble', 'France', 'www.3dprint.fr', 'Solutions logicielles pour l\'impression 3D industrielle', '/uploads/logos/3dprint.png', 'Mathilde Girard', 'mathilde.girard@3dprint.fr', '04 76 89 01 23', 1, '2025-06-10 09:50:27'),
(59, 'DevOpsTools', '29 rue de l\'Intégration', 'Strasbourg', 'France', 'www.devopstools.fr', 'Outils et plateformes pour l\'intégration continue et le déploiement', '/uploads/logos/devopstools.png', 'Romain Faure', 'romain.faure@devopstools.fr', '03 88 12 34 56', 1, '2025-06-10 09:50:27'),
(60, 'NeuroTech', '13 impasse des Neurones', 'Marseille', 'France', 'www.neurotech.fr', 'Interfaces cerveau-machine et algorithmes d\'apprentissage profond', '/uploads/logos/neurotech.png', 'Inès Blanc', 'ines.blanc@neurotech.fr', '04 91 45 67 89', 1, '2025-06-10 09:50:27'),
(61, 'AudioTech', '52 boulevard du Son', 'Nice', 'France', 'www.audiotech.fr', 'Traitement du signal audio et reconnaissance vocale', '/uploads/logos/audiotech.png', 'Théo Gaillard', 'theo.gaillard@audiotech.fr', '04 93 90 12 34', 1, '2025-06-10 09:50:27'),
(62, 'LogisticTech', '18 rue de la Logistique', 'Le Havre', 'France', 'www.logistictech.fr', 'Solutions numériques pour la chaîne d\'approvisionnement', '/uploads/logos/logistictech.png', 'Laura Bertrand', 'laura.bertrand@logistictech.fr', '02 35 56 78 90', 1, '2025-06-10 09:50:27'),
(63, 'HRTech', '33 avenue des Ressources Humaines', 'Bordeaux', 'France', 'www.hrtech.fr', 'Plateformes RH et gestion des talents', '/uploads/logos/hrtech.png', 'Benoît Lefebvre', 'benoit.lefebvre@hrtech.fr', '05 56 23 45 67', 1, '2025-06-10 09:50:27'),
(64, 'GameDev', '21 rue du Jeu Vidéo', 'Lille', 'France', 'www.gamedev.fr', 'Studio de développement de jeux vidéo et expériences interactives', '/uploads/logos/gamedev.png', 'Zoé Legrand', 'zoe.legrand@gamedev.fr', '03 20 78 90 12', 1, '2025-06-10 09:50:27'),
(65, 'LegalTech', '44 rue du Droit', 'Lyon', 'France', 'www.legaltech.fr', 'Solutions technologiques pour le secteur juridique', '/uploads/logos/legaltech.png', 'Guillaume Morel', 'guillaume.morel@legaltech.fr', '04 72 34 56 78', 1, '2025-06-10 09:50:27'),
(66, 'CryptoVault', '8 place de la Monnaie Numérique', 'Paris', 'France', 'www.cryptovault.fr', 'Sécurisation et gestion d\'actifs numériques et cryptomonnaies', '/uploads/logos/cryptovault.png', 'Léo Dumas', 'leo.dumas@cryptovault.fr', '01 57 89 02 34', 1, '2025-06-10 09:50:27'),
(67, 'RetailTech', '27 rue du Commerce', 'Nantes', 'France', 'www.retailtech.fr', 'Solutions numériques pour la transformation du commerce de détail', '/uploads/logos/retailtech.png', 'Alice Renard', 'alice.renard@retailtech.fr', '02 40 67 89 12', 1, '2025-06-10 09:50:27'),
(68, 'SportTech', '15 boulevard des Sports', 'Montpellier', 'France', 'www.sporttech.fr', 'Technologies connectées pour le sport et le bien-être', '/uploads/logos/sporttech.png', 'Karim Belhaj', 'karim.belhaj@sporttech.fr', '04 67 34 56 78', 1, '2025-06-10 09:50:27'),
(69, 'DeepLearn', '42 avenue de l\'Intelligence', 'Paris', 'France', 'www.deeplearn.fr', 'Développement d\'algorithmes de deep learning pour la vision par ordinateur', '/uploads/logos/deeplearn.png', 'Sophia Chen', 'sophia.chen@deeplearn.fr', '01 89 01 23 45', 1, '2025-06-10 09:50:27'),
(70, 'SpaceTech', '7 allée des Étoiles', 'Toulouse', 'France', 'www.spacetech.fr', 'Technologies spatiales et traitement des données satellitaires', '/uploads/logos/spacetech.png', 'Adrien Costa', 'adrien.costa@spacetech.fr', '05 61 90 12 34', 1, '2025-06-10 09:50:27'),
(71, 'DataGovernance', '19 rue de la Conformité', 'Lyon', 'France', 'www.datagovernance.fr', 'Solutions de gouvernance des données et conformité RGPD', '/uploads/logos/datagovernance.png', 'Émilie Perrin', 'emilie.perrin@datagovernance.fr', '04 72 12 34 56', 1, '2025-06-10 09:50:27'),
(72, 'InsurTech', '33 boulevard de l\'Assurance', 'Bordeaux', 'France', 'www.insurtech.fr', 'Transformation numérique du secteur des assurances', '/uploads/logos/insurtech.png', 'Thomas Nguyen', 'thomas.nguyen@insurtech.fr', '05 56 12 34 56', 1, '2025-06-10 09:50:27'),
(73, 'TransportTech', '55 avenue de la Mobilité', 'Strasbourg', 'France', 'www.transporttech.fr', 'Solutions pour l\'optimisation des transports et la mobilité durable', '/uploads/logos/transporttech.png', 'Nadia Klein', 'nadia.klein@transporttech.fr', '03 88 67 89 01', 1, '2025-06-10 09:50:27'),
(74, 'AutomationSystems', '11 rue des Robots', 'Grenoble', 'France', 'www.automationsystems.fr', 'Automatisation industrielle et systèmes de contrôle', '/uploads/logos/automationsystems.png', 'Sébastien Martin', 'sebastien.martin@automationsystems.fr', '04 76 12 34 56', 1, '2025-06-10 09:50:27'),
(75, 'ConstructTech', '30 rue du Bâtiment', 'Lille', 'France', 'www.constructtech.fr', 'Solutions numériques pour l\'industrie de la construction', '/uploads/logos/constructtech.png', 'Mélanie Dubois', 'melanie.dubois@constructtech.fr', '03 20 90 12 34', 1, '2025-06-10 09:50:27'),
(76, 'AeroTech', '14 boulevard de l\'Aviation', 'Toulouse', 'France', 'www.aerotech.fr', 'Systèmes embarqués et logiciels pour l\'aéronautique', '/uploads/logos/aerotech.png', 'Paul Rivière', 'paul.riviere@aerotech.fr', '05 61 45 67 89', 1, '2025-06-10 09:50:27'),
(77, 'WaterTech', '18 quai des Océans', 'Marseille', 'France', 'www.watertech.fr', 'Technologies pour la gestion et la préservation des ressources en eau', '/uploads/logos/watertech.png', 'Marine Costa', 'marine.costa@watertech.fr', '04 91 67 89 01', 1, '2025-06-10 09:50:27'),
(78, 'CyberDefense', '10 place de la Sécurité', 'Rennes', 'France', 'www.cyberdefense.fr', 'Systèmes avancés de défense contre les cyberattaques', '/uploads/logos/cyberdefense.png', 'Julien Mercier', 'julien.mercier@cyberdefense.fr', '02 99 23 45 67', 1, '2025-06-10 09:50:27'),
(79, 'FoodTech', '23 rue de la Gastronomie', 'Lyon', 'France', 'www.foodtech.fr', 'Innovations technologiques pour l\'industrie alimentaire', '/uploads/logos/foodtech.png', 'Audrey Boucher', 'audrey.boucher@foodtech.fr', '04 72 78 90 12', 1, '2025-06-10 09:50:27'),
(80, 'OptimizeIT', '36 rue de la Performance', 'Paris', 'France', 'www.optimizeit.fr', 'Solutions d\'optimisation des performances et de l\'efficacité des systèmes IT', '/uploads/logos/optimizeit.png', 'Marc Leblanc', 'marc.leblanc@optimizeit.fr', '01 34 56 78 90', 1, '2025-06-10 09:50:27'),
(81, 'TourismTech', '27 avenue des Voyages', 'Nice', 'France', 'www.tourismtech.fr', 'Plateformes et solutions pour l\'industrie du tourisme et des voyages', '/uploads/logos/tourismtech.png', 'Isabelle Moreau', 'isabelle.moreau@tourismtech.fr', '04 93 45 67 89', 1, '2025-06-10 09:50:27'),
(82, 'EventTech', '15 rue des Congrès', 'Cannes', 'France', 'www.eventtech.fr', 'Technologies pour l\'organisation et la gestion d\'événements', '/uploads/logos/eventtech.png', 'Laurent Dupuy', 'laurent.dupuy@eventtech.fr', '04 93 01 23 45', 1, '2025-06-10 09:50:27'),
(83, 'IndustrialAI', '9 boulevard de l\'Usine', 'Grenoble', 'France', 'www.industrialai.fr', 'Intelligence artificielle appliquée aux processus industriels', '/uploads/logos/industrialai.png', 'Caroline Lefèvre', 'caroline.lefevre@industrialai.fr', '04 76 56 78 90', 1, '2025-06-10 09:50:27'),
(84, 'SmartRetail', '41 rue du Shopping', 'Lille', 'France', 'www.smartretail.fr', 'Solutions digitales pour le commerce de détail connecté', '/uploads/logos/smartretail.png', 'Antoine Morel', 'antoine.morel@smartretail.fr', '03 20 34 56 78', 1, '2025-06-10 09:50:27'),
(85, 'SecurePayment', '22 allée de la Transaction', 'Paris', 'France', 'www.securepayment.fr', 'Solutions de paiement sécurisées et technologies financières', '/uploads/logos/securepayment.png', 'Sarah Leroux', 'sarah.leroux@securepayment.fr', '01 67 89 01 23', 1, '2025-06-10 09:50:27'),
(86, 'TechInnovate', '15 rue de l\'Innovation', 'Paris', 'France', 'www.techinnovate.fr', 'Entreprise spécialisée dans le développement de solutions innovantes pour l\'industrie 4.0', '/uploads/logos/techinnovate.png', 'Sophie Martin', 'sophie.martin@techinnovate.fr', '01 23 45 67 89', 1, '2025-06-10 10:01:42'),
(87, 'DataVision', '45 avenue des Données', 'Lyon', 'France', 'www.datavision.fr', 'Experts en analyse de données et intelligence artificielle', '/uploads/logos/datavision.png', 'Thomas Dubois', 'thomas.dubois@datavision.fr', '04 56 78 90 12', 1, '2025-06-10 10:01:42'),
(88, 'CyberGuard', '8 boulevard de la Sécurité', 'Lille', 'France', 'www.cyberguard.fr', 'Solutions de cybersécurité pour entreprises', '/uploads/logos/cyberguard.png', 'Camille Leroy', 'camille.leroy@cyberguard.fr', '03 21 43 65 87', 1, '2025-06-10 10:01:42'),
(89, 'MobileTech', '27 rue des Applications', 'Bordeaux', 'France', 'www.mobiletech.fr', 'Développement d\'applications mobiles sur mesure', '/uploads/logos/mobiletech.png', 'Lucas Bernard', 'lucas.bernard@mobiletech.fr', '05 56 78 90 12', 1, '2025-06-10 10:01:42'),
(90, 'EmbedSys', '12 rue des Capteurs', 'Toulouse', 'France', 'www.embedsys.fr', 'Systèmes embarqués pour l\'industrie et l\'IoT', '/uploads/logos/embedsys.png', 'Emma Petit', 'emma.petit@embedsys.fr', '05 61 23 45 67', 1, '2025-06-10 10:01:42'),
(91, 'CloudFlow', '36 avenue du Cloud', 'Nantes', 'France', 'www.cloudflow.fr', 'Services cloud et infrastructure as code', '/uploads/logos/cloudflow.png', 'Alexandre Moreau', 'alexandre.moreau@cloudflow.fr', '02 40 12 34 56', 1, '2025-06-10 10:01:42'),
(92, 'AILabs', '5 place de l\'Intelligence', 'Grenoble', 'France', 'www.ailabs.fr', 'Recherche et développement en intelligence artificielle', '/uploads/logos/ailabs.png', 'Julie Roux', 'julie.roux@ailabs.fr', '04 76 23 45 67', 1, '2025-06-10 10:01:42'),
(93, 'WebSphere', '19 rue du Web', 'Strasbourg', 'France', 'www.websphere.fr', 'Agence de développement web full-stack', '/uploads/logos/websphere.png', 'Nicolas Blanc', 'nicolas.blanc@websphere.fr', '03 88 45 67 89', 1, '2025-06-10 10:01:42'),
(94, 'BlockChainSolutions', '7 rue de la Blockchain', 'Montpellier', 'France', 'www.blockchainsolutions.fr', 'Solutions basées sur la blockchain pour la finance et la logistique', '/uploads/logos/blockchainsolutions.png', 'Marie Dupont', 'marie.dupont@blockchainsolutions.fr', '04 67 89 01 23', 1, '2025-06-10 10:01:42'),
(95, 'IoTConnect', '25 boulevard des Objets', 'Nice', 'France', 'www.iotconnect.fr', 'Plateforme IoT et solutions connectées', '/uploads/logos/iotconnect.png', 'Antoine Garcia', 'antoine.garcia@iotconnect.fr', '04 93 12 34 56', 1, '2025-06-10 10:01:42'),
(96, 'QuantumBits', '3 allée Quantique', 'Rennes', 'France', 'www.quantumbits.fr', 'Recherche en informatique quantique et applications', '/uploads/logos/quantumbits.png', 'Léa Marchand', 'lea.marchand@quantumbits.fr', '02 99 78 90 12', 1, '2025-06-10 10:01:42'),
(97, 'VRTech', '14 rue de la Réalité Virtuelle', 'Marseille', 'France', 'www.vrtech.fr', 'Développement d\'expériences en réalité virtuelle et augmentée', '/uploads/logos/vrtech.png', 'Julien Robert', 'julien.robert@vrtech.fr', '04 91 23 45 67', 1, '2025-06-10 10:01:42'),
(98, 'EcoTech', '48 rue Verte', 'Angers', 'France', 'www.ecotech.fr', 'Solutions technologiques pour la transition écologique', '/uploads/logos/ecotech.png', 'Clara Simon', 'clara.simon@ecotech.fr', '02 41 56 78 90', 1, '2025-06-10 10:01:42'),
(99, 'BioInformatique', '22 avenue de la Génomique', 'Tours', 'France', 'www.bioinformatique.fr', 'Analyse de données biologiques et génomiques', '/uploads/logos/bioinformatique.png', 'Pierre Lambert', 'pierre.lambert@bioinformatique.fr', '02 47 12 34 56', 1, '2025-06-10 10:01:42'),
(100, 'SmartCity', '9 place de la Ville Intelligente', 'Dijon', 'France', 'www.smartcity.fr', 'Solutions numériques pour les villes intelligentes', '/uploads/logos/smartcity.png', 'Elise Fournier', 'elise.fournier@smartcity.fr', '03 80 45 67 89', 1, '2025-06-10 10:01:42'),
(101, 'FinTech', '31 rue de la Finance', 'Bordeaux', 'France', 'www.fintech.fr', 'Technologies innovantes pour le secteur bancaire et financier', '/uploads/logos/fintech.png', 'Maxime Durand', 'maxime.durand@fintech.fr', '05 57 89 01 23', 1, '2025-06-10 10:01:42'),
(102, 'RoboTech', '17 avenue de la Robotique', 'Toulouse', 'France', 'www.robotech.fr', 'Conception et programmation de robots industriels et de service', '/uploads/logos/robotech.png', 'Sarah Bonnet', 'sarah.bonnet@robotech.fr', '05 62 34 56 78', 1, '2025-06-10 10:01:42'),
(103, 'EdTech', '42 rue de l\'Éducation', 'Lyon', 'France', 'www.edtech.fr', 'Solutions numériques pour l\'éducation et la formation', '/uploads/logos/edtech.png', 'Hugo Martin', 'hugo.martin@edtech.fr', '04 72 90 12 34', 1, '2025-06-10 10:01:42'),
(104, 'MedTech', '6 boulevard de la Santé', 'Lille', 'France', 'www.medtech.fr', 'Technologies médicales et solutions e-santé', '/uploads/logos/medtech.png', 'Chloé Leroux', 'chloe.leroux@medtech.fr', '03 20 56 78 90', 1, '2025-06-10 10:01:42'),
(105, 'GreenEnergy', '11 rue des Énergies Renouvelables', 'Nantes', 'France', 'www.greenenergy.fr', 'Solutions informatiques pour la gestion de l\'énergie renouvelable', '/uploads/logos/greenenergy.png', 'Victor Moreau', 'victor.moreau@greenenergy.fr', '02 40 23 45 67', 1, '2025-06-10 10:01:42'),
(106, 'Agritech', '24 chemin des Cultures', 'Montpellier', 'France', 'www.agritech.fr', 'Technologies pour l\'agriculture moderne et durable', '/uploads/logos/agritech.png', 'Aurélie Rousseau', 'aurelie.rousseau@agritech.fr', '04 67 12 34 56', 1, '2025-06-10 10:01:42'),
(107, 'SecurNet', '38 rue du Firewall', 'Paris', 'France', 'www.securnet.fr', 'Sécurité réseau et protection des infrastructures critiques', '/uploads/logos/securnet.png', 'David Mercier', 'david.mercier@securnet.fr', '01 45 67 89 01', 1, '2025-06-10 10:01:42'),
(108, '3DPrint', '16 avenue de l\'Impression 3D', 'Grenoble', 'France', 'www.3dprint.fr', 'Solutions logicielles pour l\'impression 3D industrielle', '/uploads/logos/3dprint.png', 'Mathilde Girard', 'mathilde.girard@3dprint.fr', '04 76 89 01 23', 1, '2025-06-10 10:01:42'),
(109, 'DevOpsTools', '29 rue de l\'Intégration', 'Strasbourg', 'France', 'www.devopstools.fr', 'Outils et plateformes pour l\'intégration continue et le déploiement', '/uploads/logos/devopstools.png', 'Romain Faure', 'romain.faure@devopstools.fr', '03 88 12 34 56', 1, '2025-06-10 10:01:42'),
(110, 'NeuroTech', '13 impasse des Neurones', 'Marseille', 'France', 'www.neurotech.fr', 'Interfaces cerveau-machine et algorithmes d\'apprentissage profond', '/uploads/logos/neurotech.png', 'Inès Blanc', 'ines.blanc@neurotech.fr', '04 91 45 67 89', 1, '2025-06-10 10:01:42'),
(111, 'AudioTech', '52 boulevard du Son', 'Nice', 'France', 'www.audiotech.fr', 'Traitement du signal audio et reconnaissance vocale', '/uploads/logos/audiotech.png', 'Théo Gaillard', 'theo.gaillard@audiotech.fr', '04 93 90 12 34', 1, '2025-06-10 10:01:42'),
(112, 'LogisticTech', '18 rue de la Logistique', 'Le Havre', 'France', 'www.logistictech.fr', 'Solutions numériques pour la chaîne d\'approvisionnement', '/uploads/logos/logistictech.png', 'Laura Bertrand', 'laura.bertrand@logistictech.fr', '02 35 56 78 90', 1, '2025-06-10 10:01:42'),
(113, 'HRTech', '33 avenue des Ressources Humaines', 'Bordeaux', 'France', 'www.hrtech.fr', 'Plateformes RH et gestion des talents', '/uploads/logos/hrtech.png', 'Benoît Lefebvre', 'benoit.lefebvre@hrtech.fr', '05 56 23 45 67', 1, '2025-06-10 10:01:42'),
(114, 'GameDev', '21 rue du Jeu Vidéo', 'Lille', 'France', 'www.gamedev.fr', 'Studio de développement de jeux vidéo et expériences interactives', '/uploads/logos/gamedev.png', 'Zoé Legrand', 'zoe.legrand@gamedev.fr', '03 20 78 90 12', 1, '2025-06-10 10:01:42'),
(115, 'LegalTech', '44 rue du Droit', 'Lyon', 'France', 'www.legaltech.fr', 'Solutions technologiques pour le secteur juridique', '/uploads/logos/legaltech.png', 'Guillaume Morel', 'guillaume.morel@legaltech.fr', '04 72 34 56 78', 1, '2025-06-10 10:01:42'),
(116, 'CryptoVault', '8 place de la Monnaie Numérique', 'Paris', 'France', 'www.cryptovault.fr', 'Sécurisation et gestion d\'actifs numériques et cryptomonnaies', '/uploads/logos/cryptovault.png', 'Léo Dumas', 'leo.dumas@cryptovault.fr', '01 57 89 02 34', 1, '2025-06-10 10:01:42'),
(117, 'RetailTech', '27 rue du Commerce', 'Nantes', 'France', 'www.retailtech.fr', 'Solutions numériques pour la transformation du commerce de détail', '/uploads/logos/retailtech.png', 'Alice Renard', 'alice.renard@retailtech.fr', '02 40 67 89 12', 1, '2025-06-10 10:01:42'),
(118, 'SportTech', '15 boulevard des Sports', 'Montpellier', 'France', 'www.sporttech.fr', 'Technologies connectées pour le sport et le bien-être', '/uploads/logos/sporttech.png', 'Karim Belhaj', 'karim.belhaj@sporttech.fr', '04 67 34 56 78', 1, '2025-06-10 10:01:42'),
(119, 'DeepLearn', '42 avenue de l\'Intelligence', 'Paris', 'France', 'www.deeplearn.fr', 'Développement d\'algorithmes de deep learning pour la vision par ordinateur', '/uploads/logos/deeplearn.png', 'Sophia Chen', 'sophia.chen@deeplearn.fr', '01 89 01 23 45', 1, '2025-06-10 10:01:42'),
(120, 'SpaceTech', '7 allée des Étoiles', 'Toulouse', 'France', 'www.spacetech.fr', 'Technologies spatiales et traitement des données satellitaires', '/uploads/logos/spacetech.png', 'Adrien Costa', 'adrien.costa@spacetech.fr', '05 61 90 12 34', 1, '2025-06-10 10:01:42'),
(121, 'DataGovernance', '19 rue de la Conformité', 'Lyon', 'France', 'www.datagovernance.fr', 'Solutions de gouvernance des données et conformité RGPD', '/uploads/logos/datagovernance.png', 'Émilie Perrin', 'emilie.perrin@datagovernance.fr', '04 72 12 34 56', 1, '2025-06-10 10:01:42'),
(122, 'InsurTech', '33 boulevard de l\'Assurance', 'Bordeaux', 'France', 'www.insurtech.fr', 'Transformation numérique du secteur des assurances', '/uploads/logos/insurtech.png', 'Thomas Nguyen', 'thomas.nguyen@insurtech.fr', '05 56 12 34 56', 1, '2025-06-10 10:01:42'),
(123, 'TransportTech', '55 avenue de la Mobilité', 'Strasbourg', 'France', 'www.transporttech.fr', 'Solutions pour l\'optimisation des transports et la mobilité durable', '/uploads/logos/transporttech.png', 'Nadia Klein', 'nadia.klein@transporttech.fr', '03 88 67 89 01', 1, '2025-06-10 10:01:42'),
(124, 'AutomationSystems', '11 rue des Robots', 'Grenoble', 'France', 'www.automationsystems.fr', 'Automatisation industrielle et systèmes de contrôle', '/uploads/logos/automationsystems.png', 'Sébastien Martin', 'sebastien.martin@automationsystems.fr', '04 76 12 34 56', 1, '2025-06-10 10:01:42'),
(125, 'ConstructTech', '30 rue du Bâtiment', 'Lille', 'France', 'www.constructtech.fr', 'Solutions numériques pour l\'industrie de la construction', '/uploads/logos/constructtech.png', 'Mélanie Dubois', 'melanie.dubois@constructtech.fr', '03 20 90 12 34', 1, '2025-06-10 10:01:42'),
(126, 'AeroTech', '14 boulevard de l\'Aviation', 'Toulouse', 'France', 'www.aerotech.fr', 'Systèmes embarqués et logiciels pour l\'aéronautique', '/uploads/logos/aerotech.png', 'Paul Rivière', 'paul.riviere@aerotech.fr', '05 61 45 67 89', 1, '2025-06-10 10:01:42'),
(127, 'WaterTech', '18 quai des Océans', 'Marseille', 'France', 'www.watertech.fr', 'Technologies pour la gestion et la préservation des ressources en eau', '/uploads/logos/watertech.png', 'Marine Costa', 'marine.costa@watertech.fr', '04 91 67 89 01', 1, '2025-06-10 10:01:42'),
(128, 'CyberDefense', '10 place de la Sécurité', 'Rennes', 'France', 'www.cyberdefense.fr', 'Systèmes avancés de défense contre les cyberattaques', '/uploads/logos/cyberdefense.png', 'Julien Mercier', 'julien.mercier@cyberdefense.fr', '02 99 23 45 67', 1, '2025-06-10 10:01:42'),
(129, 'FoodTech', '23 rue de la Gastronomie', 'Lyon', 'France', 'www.foodtech.fr', 'Innovations technologiques pour l\'industrie alimentaire', '/uploads/logos/foodtech.png', 'Audrey Boucher', 'audrey.boucher@foodtech.fr', '04 72 78 90 12', 1, '2025-06-10 10:01:42'),
(130, 'OptimizeIT', '36 rue de la Performance', 'Paris', 'France', 'www.optimizeit.fr', 'Solutions d\'optimisation des performances et de l\'efficacité des systèmes IT', '/uploads/logos/optimizeit.png', 'Marc Leblanc', 'marc.leblanc@optimizeit.fr', '01 34 56 78 90', 1, '2025-06-10 10:01:42'),
(131, 'TourismTech', '27 avenue des Voyages', 'Nice', 'France', 'www.tourismtech.fr', 'Plateformes et solutions pour l\'industrie du tourisme et des voyages', '/uploads/logos/tourismtech.png', 'Isabelle Moreau', 'isabelle.moreau@tourismtech.fr', '04 93 45 67 89', 1, '2025-06-10 10:01:42'),
(132, 'EventTech', '15 rue des Congrès', 'Cannes', 'France', 'www.eventtech.fr', 'Technologies pour l\'organisation et la gestion d\'événements', '/uploads/logos/eventtech.png', 'Laurent Dupuy', 'laurent.dupuy@eventtech.fr', '04 93 01 23 45', 1, '2025-06-10 10:01:42'),
(133, 'IndustrialAI', '9 boulevard de l\'Usine', 'Grenoble', 'France', 'www.industrialai.fr', 'Intelligence artificielle appliquée aux processus industriels', '/uploads/logos/industrialai.png', 'Caroline Lefèvre', 'caroline.lefevre@industrialai.fr', '04 76 56 78 90', 1, '2025-06-10 10:01:42'),
(134, 'SmartRetail', '41 rue du Shopping', 'Lille', 'France', 'www.smartretail.fr', 'Solutions digitales pour le commerce de détail connecté', '/uploads/logos/smartretail.png', 'Antoine Morel', 'antoine.morel@smartretail.fr', '03 20 34 56 78', 1, '2025-06-10 10:01:42'),
(135, 'SecurePayment', '22 allée de la Transaction', 'Paris', 'France', 'www.securepayment.fr', 'Solutions de paiement sécurisées et technologies financières', '/uploads/logos/securepayment.png', 'Sarah Leroux', 'sarah.leroux@securepayment.fr', '01 67 89 01 23', 1, '2025-06-10 10:01:42');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('contract','report','evaluation','certificate','other') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `visibility` enum('private','restricted','public') NOT NULL DEFAULT 'private',
  `feedback` text DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `assignment_id`, `title`, `description`, `type`, `file_path`, `file_type`, `file_size`, `upload_date`, `status`, `visibility`, `feedback`, `version`) VALUES
(2, 14, 1, 'CV - Lucas Dupont', NULL, 'other', 'uploads/documents/1_cv.pdf', NULL, NULL, '2025-06-07 09:42:54', 'approved', 'private', NULL, NULL),
(5, 16, 2, 'CV - Louis Moreau', NULL, 'other', 'uploads/documents/3_cv.pdf', NULL, NULL, '2025-06-07 09:42:54', 'approved', 'private', NULL, NULL),
(6, 18, 3, 'Convention de stage - Hugo Simon', NULL, 'contract', 'uploads/documents/5_convention_stage.pdf', NULL, NULL, '2025-06-07 09:42:54', 'approved', 'private', NULL, NULL),
(8, 9, NULL, 'Guide du rapport final', NULL, 'other', 'uploads/documents/guide_rapport_final.pdf', NULL, NULL, '2025-06-07 09:42:54', 'approved', 'private', NULL, NULL),
(1003, 1, NULL, 'Test 1', '', 'report', 'uploads/documents/1749736178_Hugo Simon CV.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 44917, '2025-06-12 13:49:38', '', 'private', NULL, '1.0'),
(1004, 1, NULL, 'Document de test SQL', '', 'report', 'uploads/documents/test_sql.pdf', 'application/pdf', 12345, '2025-06-12 13:51:42', '', 'private', NULL, NULL),
(1005, 1, NULL, 'Document de test via modèle', NULL, 'report', 'uploads/documents/test_model.pdf', 'application/pdf', 12345, '2025-06-12 13:51:42', 'submitted', 'private', NULL, NULL),
(1006, 18, 3, 'CV', '', 'other', 'uploads/documents/1749736339_Hugo Simon CV.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 44917, '2025-06-12 13:52:19', 'submitted', 'private', NULL, '1.0'),
(1008, 18, NULL, 'Auto-évaluation', 'Auto-évaluation pour la période mi-parcours du stage', 'other', 'evaluations/self_684f15d690689.json', 'application/json', 1024, '2025-06-15 18:49:58', 'submitted', 'private', NULL, NULL),
(1010, 18, NULL, 'Auto-évaluation', 'Auto-évaluation pour la période mi-parcours du stage', 'other', 'evaluations/self_684f15efa5fc9.json', 'application/json', 1024, '2025-06-15 18:50:23', 'submitted', 'private', NULL, NULL),
(1011, 18, NULL, 'Évaluation mi-parcours', 'Évaluation du tuteur pour la période mi-parcours du stage', 'evaluation', 'evaluations/test_684f15f1f2f0d.json', 'application/json', 1024, '2025-06-15 18:50:26', 'submitted', 'private', NULL, NULL),
(1012, 18, NULL, 'Auto-évaluation', 'Auto-évaluation pour la période mi-parcours du stage', 'other', 'evaluations/self_684f15f20c5e0.json', 'application/json', 1024, '2025-06-15 18:50:26', 'submitted', 'private', NULL, NULL),
(1013, 18, NULL, 'Évaluation mi-parcours', 'Évaluation du tuteur pour la période mi-parcours du stage', 'other', 'evaluations/test_684f15f3152fe.json', 'application/json', 1024, '2025-06-15 18:50:27', '', 'private', NULL, NULL),
(1014, 18, NULL, 'Auto-évaluation', 'Auto-évaluation pour la période mi-parcours du stage', 'other', 'evaluations/self_684f15f31d02e.json', 'application/json', 1024, '2025-06-15 18:50:27', 'submitted', 'private', NULL, NULL),
(1015, 18, NULL, 'Évaluation mi-parcours', 'Évaluation du tuteur pour la période mi-parcours du stage', 'evaluation', 'evaluations/test_684f15f3c98cb.json', 'application/json', 1024, '2025-06-15 18:50:27', 'submitted', 'private', NULL, NULL),
(1016, 18, NULL, 'Auto-évaluation', 'Auto-évaluation pour la période mi-parcours du stage', 'other', 'evaluations/self_684f15f3cd2d4.json', 'application/json', 1024, '2025-06-15 18:50:27', 'submitted', 'private', NULL, NULL);

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
(1, 1, 9, 14, 'teacher', 4.5, 'Excellent travail et bonne intégration dans l\'équipe. Continue ainsi.', NULL, NULL, '2025-06-07 09:42:54'),
(2, 2, 10, 16, 'teacher', 4.0, 'Bon travail, participation active aux projets de l\'équipe.', NULL, NULL, '2025-06-07 09:42:54'),
(3, 3, 13, 18, 'teacher', 4.2, 'Très bonnes compétences techniques et bonne autonomie.', NULL, NULL, '2025-06-07 09:42:54'),
(4, 4, 9, 17, 'teacher', 3.8, 'Bon travail, mais besoin d\'améliorer la communication.', NULL, NULL, '2025-06-07 09:42:54'),
(5, 3, 6, 7, 'final', 14.0, 'Etudiant travailleur.', '', '', '2025-06-12 14:46:52'),
(6, 3, 6, 7, 'mid_term', 18.0, 'Prends en compte les suggestions et remarque. Tres attentionné.', '', '', '2025-06-12 22:24:06');

-- --------------------------------------------------------

--
-- Table structure for table `internships`
--

CREATE TABLE `internships` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `work_mode` enum('on_site','remote','hybrid') NOT NULL DEFAULT 'on_site',
  `compensation` decimal(10,2) DEFAULT NULL,
  `domain` varchar(100) NOT NULL,
  `status` enum('available','assigned','completed','cancelled') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internships`
--

INSERT INTO `internships` (`id`, `company_id`, `title`, `description`, `requirements`, `start_date`, `end_date`, `location`, `work_mode`, `compensation`, `domain`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Développeur Web Full-Stack', 'Stage de développement web sur un projet e-commerce. Vous participerez à la conception et au développement de nouvelles fonctionnalités.', '', '2023-09-01', '2024-02-28', '', 'on_site', 800.00, 'Développement Web', 'assigned', '2025-06-07 09:42:54', '2025-06-21 15:07:32'),
(2, 2, 'Data Scientist Junior', 'Analyse de données clients et développement de modèles prédictifs pour optimiser les campagnes marketing.', 'Python, R, SQL, Machine Learning', '2023-09-01', '2024-02-28', 'Lyon', 'hybrid', 850.00, 'Data Science', 'assigned', '2025-06-07 09:42:54', '2025-06-11 19:13:20'),
(3, 3, 'Analyste en Cybersécurité', 'Participation aux audits de sécurité et implémentation de solutions de protection pour nos clients.', 'Connaissances en cybersécurité, Linux, réseaux', '2023-09-01', '2024-02-28', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-07 09:42:54', '2025-06-07 09:42:54'),
(4, 4, 'Développeur Mobile', 'Développement d\'applications mobiles Android et iOS pour divers clients.', 'Java, Kotlin, Swift ou React Native', '2023-09-01', '2024-02-28', 'Bordeaux', 'hybrid', 800.00, 'Développement Mobile', 'available', '2025-06-07 09:42:54', '2025-06-07 09:42:54'),
(5, 5, 'Ingénieur en Systèmes Embarqués', 'Conception et programmation de systèmes embarqués pour l\'industrie automobile.', 'C/C++, microcontrôleurs, électronique de base', '2023-09-01', '2024-02-28', 'Toulouse', 'on_site', 900.00, 'Systèmes Embarqués', 'available', '2025-06-07 09:42:54', '2025-06-07 09:42:54'),
(6, 6, 'Développeur Front-End React', 'Développement d\'interfaces utilisateurs modernes avec React et intégration avec des APIs REST.', 'React, JavaScript, HTML5, CSS3, Git', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 800.00, 'Développement Web', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(7, 8, 'Développeur Back-End PHP', 'Développement et maintenance d\'APIs et services backend pour une plateforme e-commerce.', 'PHP, MySQL, RESTful APIs, Laravel ou Symfony', '2025-09-01', '2026-02-28', 'Strasbourg', 'on_site', 850.00, 'Développement Web', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(8, 12, 'Développeur Full-Stack JavaScript', 'Conception et implémentation de fonctionnalités full-stack pour une application SaaS en croissance.', 'JavaScript, Node.js, React, MongoDB, Express', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Développement Web', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(9, 15, 'Intégrateur Web / UX', 'Intégration de maquettes et développement d\'interfaces utilisateur pour des applications de Smart City.', 'HTML5, CSS3, SASS, JavaScript, expérience en design', '2025-09-01', '2026-02-28', 'Dijon', 'remote', 750.00, 'Développement Web', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(10, 2, 'Analyste de Données Marketing', 'Analyse des données client et des campagnes marketing pour optimiser les conversions et le ROI.', 'Python, Pandas, SQL, visualisation de données (Tableau ou PowerBI)', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 850.00, 'Data Science', 'assigned', '2025-06-10 09:23:15', '2025-06-11 19:13:20'),
(11, 7, 'Data Scientist en NLP', 'Développement de modèles de traitement du langage naturel pour l\'analyse de sentiments et la classification de textes.', 'Python, scikit-learn, NLTK ou spaCy, PyTorch ou TensorFlow', '2025-09-01', '2026-02-28', 'Grenoble', 'hybrid', 950.00, 'Intelligence Artificielle', 'assigned', '2025-06-10 09:23:15', '2025-06-11 19:13:20'),
(12, 11, 'Chercheur en Informatique Quantique', 'Participation à des projets de recherche sur les algorithmes quantiques et leurs applications.', 'Python, algèbre linéaire, notions d\'informatique quantique', '2025-09-01', '2026-02-28', 'Rennes', 'on_site', 1000.00, 'Informatique Quantique', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(13, 14, 'Bio-informaticien', 'Analyse de données génomiques et développement d\'algorithmes pour la recherche médicale.', 'Python ou R, statistiques, connaissances en biologie', '2025-09-01', '2026-02-28', 'Tours', 'hybrid', 900.00, 'Bio-informatique', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(14, 25, 'Ingénieur IA pour interfaces neuronales', 'Développement d\'algorithmes d\'apprentissage pour interpréter les signaux cérébraux.', 'Python, TensorFlow ou PyTorch, traitement du signal', '2025-09-01', '2026-02-28', 'Marseille', 'on_site', 1000.00, 'NeuroTechnologies', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(15, 3, 'Analyste SOC Junior', 'Surveillance des alertes de sécurité et analyse des incidents dans un centre opérationnel de sécurité.', 'Connaissances en cybersécurité, réseaux, Linux', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(16, 22, 'Pentesteur Junior', 'Participation aux tests d\'intrusion et à l\'évaluation de la sécurité des systèmes d\'information.', 'Connaissances en sécurité informatique, réseaux, programmation', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Cybersécurité', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(17, 22, 'Analyste en Sécurité des Applications', 'Audit de sécurité d\'applications web et mobiles, détection de vulnérabilités et recommandations.', 'OWASP Top 10, techniques de pentest, développement web', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 900.00, 'Cybersécurité', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(18, 4, 'Développeur iOS', 'Développement et maintenance d\'applications iOS pour le secteur du retail.', 'Swift, UIKit, CoreData, Git', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'Développement Mobile', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(19, 4, 'Développeur Android', 'Conception et développement de nouvelles fonctionnalités pour une application Android à forte audience.', 'Java ou Kotlin, Android SDK, Architecture Components', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'Développement Mobile', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(20, 12, 'Développeur React Native', 'Développement d\'applications mobiles cross-platform pour la réalité augmentée.', 'React Native, JavaScript, expérience en AR', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Développement Mobile', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(21, 5, 'Ingénieur Logiciel Embarqué', 'Développement de firmware pour systèmes embarqués dans le secteur automobile.', 'C/C++, microcontrôleurs, protocoles de communication (CAN, LIN)', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Systèmes Embarqués', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(22, 10, 'Développeur IoT', 'Conception et développement de solutions IoT pour la maison connectée.', 'C/C++, protocoles IoT (MQTT, CoAP), Linux embarqué', '2025-09-01', '2026-02-28', 'Nice', 'hybrid', 900.00, 'Internet des Objets', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(23, 17, 'Ingénieur en Robotique', 'Développement de logiciels pour robots industriels et participation à leur intégration.', 'C++, ROS, notions de mécatronique', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Robotique', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(24, 6, 'Ingénieur DevOps Junior', 'Mise en place et amélioration des pipelines CI/CD et de l\'infrastructure cloud.', 'Linux, Docker, Kubernetes, Git, AWS ou Azure', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 900.00, 'DevOps', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(25, 24, 'Ingénieur Cloud', 'Développement d\'outils d\'automatisation et de déploiement sur des infrastructures cloud.', 'AWS ou Azure, Terraform, Python ou Go, CI/CD', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 950.00, 'Cloud Computing', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(26, 6, 'SRE (Site Reliability Engineer)', 'Garantir la fiabilité et les performances des applications en production.', 'Linux, monitoring (Prometheus, Grafana), scripting', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 950.00, 'DevOps', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(27, 9, 'Développeur Blockchain', 'Développement de smart contracts et d\'applications décentralisées pour le secteur financier.', 'Solidity, Ethereum, Web3.js, JavaScript', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(28, 12, 'Développeur AR/VR', 'Création d\'expériences immersives en réalité virtuelle et augmentée.', 'Unity ou Unreal Engine, C#, 3D, design UX', '2025-09-01', '2026-02-28', 'Marseille', 'on_site', 900.00, 'Réalité Virtuelle', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(29, 23, 'Ingénieur en Fabrication Additive', 'Développement de logiciels pour optimiser les processus d\'impression 3D industrielle.', 'C++, Python, CAO, connaissances en fabrication additive', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 900.00, 'Impression 3D', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(30, 13, 'Développeur pour Solutions Écologiques', 'Création d\'applications de suivi et d\'optimisation de l\'empreinte carbone des entreprises.', 'Java ou Python, bases de données, APIs', '2025-09-01', '2026-02-28', 'Angers', 'hybrid', 850.00, 'Green Tech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(31, 19, 'Ingénieur Logiciel pour la Santé', 'Développement d\'applications médicales conformes aux normes du secteur de la santé.', 'Java ou C#, connaissance des normes (HIPAA, RGPD médical)', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 900.00, 'Santé Numérique', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(32, 18, 'Développeur de Plateformes E-learning', 'Conception et développement de fonctionnalités pour une plateforme éducative en ligne.', 'PHP ou Python, frameworks web, UX/UI pour l\'éducation', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 850.00, 'Ed Tech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(33, 21, 'Data Engineer pour l\'Agriculture', 'Mise en place de pipelines de données pour l\'analyse des cultures et l\'agriculture de précision.', 'Python, ETL, bases de données, IoT', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 880.00, 'Agri Tech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(34, 16, 'Analyste Fintech', 'Développement et analyse d\'algorithmes pour la détection de fraudes dans les transactions financières.', 'Python, SQL, machine learning, connaissances en finance', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 900.00, 'Fintech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(35, 20, 'Ingénieur en Efficacité Énergétique', 'Développement de solutions logicielles pour optimiser la consommation énergétique des bâtiments.', 'Python, IoT, algorithmes d\'optimisation', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 880.00, 'Green Tech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(36, 29, 'Développeur pour Solutions RH', 'Développement de modules pour une plateforme SaaS de gestion des talents et du recrutement.', 'JavaScript, React, Node.js, bases de données', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'HR Tech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(37, 30, 'Développeur Legal Tech', 'Conception et développement d\'outils d\'automatisation pour le secteur juridique.', 'Python ou Java, NLP, bases de données', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 900.00, 'Legal Tech', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(38, 1, 'Architecte Logiciel Junior', 'Participation à la conception et à l\'évolution de l\'architecture de nos solutions cloud.', 'Java, Spring, microservices, patterns de conception', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 950.00, 'Architecture Logicielle', 'assigned', '2025-06-10 09:23:15', '2025-06-11 19:13:20'),
(39, 8, 'UX/UI Designer', 'Conception d\'interfaces utilisateur et amélioration de l\'expérience utilisateur sur nos applications web.', 'Figma ou Adobe XD, HTML/CSS, notions de JavaScript', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 800.00, 'Design Numérique', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(40, 25, 'Ingénieur Test et Qualité', 'Mise en place de stratégies de test et automatisation pour garantir la qualité des applications.', 'Selenium, Cypress ou JUnit, CI/CD, méthodologies de test', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 850.00, 'Qualité Logicielle', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(41, 27, 'Ingénieur en Traitement du Signal Audio', 'Développement d\'algorithmes de traitement du signal pour des applications de reconnaissance vocale.', 'Python, traitement du signal, machine learning', '2025-09-01', '2026-02-28', 'Nice', 'on_site', 900.00, 'Traitement Audio', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(42, 7, 'Data Engineer', 'Conception et développement de pipelines de données pour l\'alimentation de modèles d\'IA.', 'Python, Spark, SQL, cloud (AWS/Azure/GCP)', '2025-09-01', '2026-02-28', 'Grenoble', 'hybrid', 920.00, 'Ingénierie des Données', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(43, 28, 'Développeur de Jeux Vidéo', 'Développement de mécaniques de jeu et intégration de contenus pour un jeu mobile.', 'Unity, C#, game design, 2D/3D', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 850.00, 'Jeux Vidéo', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(44, 26, 'Ingénieur Logistique et Supply Chain', 'Développement d\'outils d\'optimisation pour la chaîne d\'approvisionnement et la logistique.', 'Python, algorithmes d\'optimisation, bases de données', '2025-09-01', '2026-02-28', 'Le Havre', 'hybrid', 880.00, 'Logistique', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(45, 1, 'Développeur Web React Junior', 'Développement de composants React et intégration avec des API REST.', 'React, JavaScript, HTML/CSS, Git', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 800.00, 'Développement Web', 'assigned', '2025-06-10 09:23:15', '2025-06-11 19:13:20'),
(46, 7, 'Machine Learning Engineer', 'Implémentation et optimisation de modèles de machine learning pour des applications industrielles.', 'Python, scikit-learn, TensorFlow ou PyTorch', '2026-01-01', '2026-06-30', 'Grenoble', 'on_site', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(47, 3, 'Analyste en Sécurité des Réseaux', 'Surveillance et analyse de la sécurité des infrastructures réseau.', 'Réseaux, sécurité, Linux, outils de monitoring', '2026-01-01', '2026-06-30', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(48, 10, 'Développeur IoT Firmware', 'Développement de firmware pour objets connectés dans le domaine de la domotique.', 'C/C++, microcontrôleurs, protocoles IoT', '2026-01-01', '2026-06-30', 'Nice', 'on_site', 880.00, 'Internet des Objets', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(49, 24, 'Ingénieur CI/CD', 'Amélioration des pipelines de déploiement continu et automatisation des tests.', 'GitLab CI ou Jenkins, Docker, Kubernetes, scripting', '2026-01-01', '2026-06-30', 'Strasbourg', 'hybrid', 900.00, 'DevOps', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(50, 15, 'Développeur Python pour Smart City', 'Développement de services backend pour applications de ville intelligente.', 'Python, Django ou Flask, APIs, bases de données', '2026-01-01', '2026-06-30', 'Dijon', 'hybrid', 850.00, 'Smart City', 'available', '2025-06-10 09:23:15', '2025-06-10 09:23:15'),
(51, 2, 'Data Scientist Marketing', 'Analyse de données clients et modélisation prédictive pour optimiser les campagnes marketing.', 'Python, R, SQL, statistiques, machine learning', '2026-01-01', '2026-06-30', 'Lyon', 'hybrid', 900.00, 'Data Science', 'assigned', '2025-06-10 09:23:15', '2025-06-11 19:13:20'),
(52, 6, 'Développeur Front-End React', 'Développement d\'interfaces utilisateurs modernes avec React et intégration avec des APIs REST.', 'React, JavaScript, HTML5, CSS3, Git', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 800.00, 'Développement Web', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(53, 8, 'Développeur Back-End PHP', 'Développement et maintenance d\'APIs et services backend pour une plateforme e-commerce.', 'PHP, MySQL, RESTful APIs, Laravel ou Symfony', '2025-09-01', '2026-02-28', 'Strasbourg', 'on_site', 850.00, 'Développement Web', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(54, 12, 'Développeur Full-Stack JavaScript', 'Conception et implémentation de fonctionnalités full-stack pour une application SaaS en croissance.', 'JavaScript, Node.js, React, MongoDB, Express', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Développement Web', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(55, 15, 'Intégrateur Web / UX', 'Intégration de maquettes et développement d\'interfaces utilisateur pour des applications de Smart City.', 'HTML5, CSS3, SASS, JavaScript, expérience en design', '2025-09-01', '2026-02-28', 'Dijon', 'remote', 750.00, 'Développement Web', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(56, 2, 'Analyste de Données Marketing', 'Analyse des données client et des campagnes marketing pour optimiser les conversions et le ROI.', 'Python, Pandas, SQL, visualisation de données (Tableau ou PowerBI)', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 850.00, 'Data Science', 'assigned', '2025-06-10 09:50:27', '2025-06-11 19:13:20'),
(57, 7, 'Data Scientist en NLP', 'Développement de modèles de traitement du langage naturel pour l\'analyse de sentiments et la classification de textes.', 'Python, scikit-learn, NLTK ou spaCy, PyTorch ou TensorFlow', '2025-09-01', '2026-02-28', 'Grenoble', 'hybrid', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(58, 11, 'Chercheur en Informatique Quantique', 'Participation à des projets de recherche sur les algorithmes quantiques et leurs applications.', 'Python, algèbre linéaire, notions d\'informatique quantique', '2025-09-01', '2026-02-28', 'Rennes', 'on_site', 1000.00, 'Informatique Quantique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(59, 14, 'Bio-informaticien', 'Analyse de données génomiques et développement d\'algorithmes pour la recherche médicale.', 'Python ou R, statistiques, connaissances en biologie', '2025-09-01', '2026-02-28', 'Tours', 'hybrid', 900.00, 'Bio-informatique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(60, 25, 'Ingénieur IA pour interfaces neuronales', 'Développement d\'algorithmes d\'apprentissage pour interpréter les signaux cérébraux.', 'Python, TensorFlow ou PyTorch, traitement du signal', '2025-09-01', '2026-02-28', 'Marseille', 'on_site', 1000.00, 'NeuroTechnologies', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(61, 3, 'Analyste SOC Junior', 'Surveillance des alertes de sécurité et analyse des incidents dans un centre opérationnel de sécurité.', 'Connaissances en cybersécurité, réseaux, Linux', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(62, 22, 'Pentesteur Junior', 'Participation aux tests d\'intrusion et à l\'évaluation de la sécurité des systèmes d\'information.', 'Connaissances en sécurité informatique, réseaux, programmation', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Cybersécurité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(63, 22, 'Analyste en Sécurité des Applications', 'Audit de sécurité d\'applications web et mobiles, détection de vulnérabilités et recommandations.', 'OWASP Top 10, techniques de pentest, développement web', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 900.00, 'Cybersécurité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(64, 4, 'Développeur iOS', 'Développement et maintenance d\'applications iOS pour le secteur du retail.', 'Swift, UIKit, CoreData, Git', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'Développement Mobile', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(65, 4, 'Développeur Android', 'Conception et développement de nouvelles fonctionnalités pour une application Android à forte audience.', 'Java ou Kotlin, Android SDK, Architecture Components', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'Développement Mobile', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(66, 12, 'Développeur React Native', 'Développement d\'applications mobiles cross-platform pour la réalité augmentée.', 'React Native, JavaScript, expérience en AR', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Développement Mobile', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(67, 5, 'Ingénieur Logiciel Embarqué', 'Développement de firmware pour systèmes embarqués dans le secteur automobile.', 'C/C++, microcontrôleurs, protocoles de communication (CAN, LIN)', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Systèmes Embarqués', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(68, 10, 'Développeur IoT', 'Conception et développement de solutions IoT pour la maison connectée.', 'C/C++, protocoles IoT (MQTT, CoAP), Linux embarqué', '2025-09-01', '2026-02-28', 'Nice', 'hybrid', 900.00, 'Internet des Objets', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(69, 17, 'Ingénieur en Robotique', 'Développement de logiciels pour robots industriels et participation à leur intégration.', 'C++, ROS, notions de mécatronique', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Robotique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(70, 6, 'Ingénieur DevOps Junior', 'Mise en place et amélioration des pipelines CI/CD et de l\'infrastructure cloud.', 'Linux, Docker, Kubernetes, Git, AWS ou Azure', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 900.00, 'DevOps', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(71, 24, 'Ingénieur Cloud', 'Développement d\'outils d\'automatisation et de déploiement sur des infrastructures cloud.', 'AWS ou Azure, Terraform, Python ou Go, CI/CD', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 950.00, 'Cloud Computing', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(72, 6, 'SRE (Site Reliability Engineer)', 'Garantir la fiabilité et les performances des applications en production.', 'Linux, monitoring (Prometheus, Grafana), scripting', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 950.00, 'DevOps', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(73, 9, 'Développeur Blockchain', 'Développement de smart contracts et d\'applications décentralisées pour le secteur financier.', 'Solidity, Ethereum, Web3.js, JavaScript', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(74, 12, 'Développeur AR/VR', 'Création d\'expériences immersives en réalité virtuelle et augmentée.', 'Unity ou Unreal Engine, C#, 3D, design UX', '2025-09-01', '2026-02-28', 'Marseille', 'on_site', 900.00, 'Réalité Virtuelle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(75, 23, 'Ingénieur en Fabrication Additive', 'Développement de logiciels pour optimiser les processus d\'impression 3D industrielle.', 'C++, Python, CAO, connaissances en fabrication additive', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 900.00, 'Impression 3D', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(76, 13, 'Développeur pour Solutions Écologiques', 'Création d\'applications de suivi et d\'optimisation de l\'empreinte carbone des entreprises.', 'Java ou Python, bases de données, APIs', '2025-09-01', '2026-02-28', 'Angers', 'hybrid', 850.00, 'Green Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(77, 19, 'Ingénieur Logiciel pour la Santé', 'Développement d\'applications médicales conformes aux normes du secteur de la santé.', 'Java ou C#, connaissance des normes (HIPAA, RGPD médical)', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 900.00, 'Santé Numérique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(78, 18, 'Développeur de Plateformes E-learning', 'Conception et développement de fonctionnalités pour une plateforme éducative en ligne.', 'PHP ou Python, frameworks web, UX/UI pour l\'éducation', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 850.00, 'Ed Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(79, 21, 'Data Engineer pour l\'Agriculture', 'Mise en place de pipelines de données pour l\'analyse des cultures et l\'agriculture de précision.', 'Python, ETL, bases de données, IoT', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 880.00, 'Agri Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(80, 16, 'Analyste Fintech', 'Développement et analyse d\'algorithmes pour la détection de fraudes dans les transactions financières.', 'Python, SQL, machine learning, connaissances en finance', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 900.00, 'Fintech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(81, 20, 'Ingénieur en Efficacité Énergétique', 'Développement de solutions logicielles pour optimiser la consommation énergétique des bâtiments.', 'Python, IoT, algorithmes d\'optimisation', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 880.00, 'Green Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(82, 29, 'Développeur pour Solutions RH', 'Développement de modules pour une plateforme SaaS de gestion des talents et du recrutement.', 'JavaScript, React, Node.js, bases de données', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'HR Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(83, 30, 'Développeur Legal Tech', 'Conception et développement d\'outils d\'automatisation pour le secteur juridique.', 'Python ou Java, NLP, bases de données', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 900.00, 'Legal Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(84, 1, 'Architecte Logiciel Junior', 'Participation à la conception et à l\'évolution de l\'architecture de nos solutions cloud.', 'Java, Spring, microservices, patterns de conception', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 950.00, 'Architecture Logicielle', 'assigned', '2025-06-10 09:50:27', '2025-06-11 19:13:20'),
(85, 8, 'UX/UI Designer', 'Conception d\'interfaces utilisateur et amélioration de l\'expérience utilisateur sur nos applications web.', 'Figma ou Adobe XD, HTML/CSS, notions de JavaScript', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 800.00, 'Design Numérique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(86, 25, 'Ingénieur Test et Qualité', 'Mise en place de stratégies de test et automatisation pour garantir la qualité des applications.', 'Selenium, Cypress ou JUnit, CI/CD, méthodologies de test', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 850.00, 'Qualité Logicielle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(87, 27, 'Ingénieur en Traitement du Signal Audio', 'Développement d\'algorithmes de traitement du signal pour des applications de reconnaissance vocale.', 'Python, traitement du signal, machine learning', '2025-09-01', '2026-02-28', 'Nice', 'on_site', 900.00, 'Traitement Audio', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(88, 7, 'Data Engineer', 'Conception et développement de pipelines de données pour l\'alimentation de modèles d\'IA.', 'Python, Spark, SQL, cloud (AWS/Azure/GCP)', '2025-09-01', '2026-02-28', 'Grenoble', 'hybrid', 920.00, 'Ingénierie des Données', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(89, 28, 'Développeur de Jeux Vidéo', 'Développement de mécaniques de jeu et intégration de contenus pour un jeu mobile.', 'Unity, C#, game design, 2D/3D', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 850.00, 'Jeux Vidéo', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(90, 26, 'Ingénieur Logistique et Supply Chain', 'Développement d\'outils d\'optimisation pour la chaîne d\'approvisionnement et la logistique.', 'Python, algorithmes d\'optimisation, bases de données', '2025-09-01', '2026-02-28', 'Le Havre', 'hybrid', 880.00, 'Logistique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(91, 1, 'Développeur Web React Junior', 'Développement de composants React et intégration avec des API REST.', 'React, JavaScript, HTML/CSS, Git', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 800.00, 'Développement Web', 'assigned', '2025-06-10 09:50:27', '2025-06-11 19:13:20'),
(92, 7, 'Machine Learning Engineer', 'Implémentation et optimisation de modèles de machine learning pour des applications industrielles.', 'Python, scikit-learn, TensorFlow ou PyTorch', '2026-01-01', '2026-06-30', 'Grenoble', 'on_site', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(93, 3, 'Analyste en Sécurité des Réseaux', 'Surveillance et analyse de la sécurité des infrastructures réseau.', 'Réseaux, sécurité, Linux, outils de monitoring', '2026-01-01', '2026-06-30', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(94, 10, 'Développeur IoT Firmware', 'Développement de firmware pour objets connectés dans le domaine de la domotique.', 'C/C++, microcontrôleurs, protocoles IoT', '2026-01-01', '2026-06-30', 'Nice', 'on_site', 880.00, 'Internet des Objets', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(95, 24, 'Ingénieur CI/CD', 'Amélioration des pipelines de déploiement continu et automatisation des tests.', 'GitLab CI ou Jenkins, Docker, Kubernetes, scripting', '2026-01-01', '2026-06-30', 'Strasbourg', 'hybrid', 900.00, 'DevOps', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(96, 15, 'Développeur Python pour Smart City', 'Développement de services backend pour applications de ville intelligente.', 'Python, Django ou Flask, APIs, bases de données', '2026-01-01', '2026-06-30', 'Dijon', 'hybrid', 850.00, 'Smart City', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(97, 2, 'Data Scientist Marketing', 'Analyse de données clients et modélisation prédictive pour optimiser les campagnes marketing.', 'Python, R, SQL, statistiques, machine learning', '2026-01-01', '2026-06-30', 'Lyon', 'hybrid', 900.00, 'Data Science', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(98, 31, 'Développeur DeFi', 'Développement de protocoles financiers décentralisés et intégration avec des plateformes existantes.', 'Solidity, Ethereum, DeFi, Web3.js, React, tests smart contracts (Truffle/Hardhat), sécurité blockchain, cryptographie', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(99, 31, 'Analyste Sécurité Blockchain', 'Audit de sécurité des smart contracts et analyse des vulnérabilités dans les applications décentralisées.', 'Ethereum, Solidity, techniques d\'audit, MythX, Slither, patterns de sécurité smart contracts, cryptographie, SAST/DAST', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 950.00, 'Blockchain', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(100, 9, 'Ingénieur Blockchain Supply Chain', 'Développement d\'une solution de traçabilité basée sur la blockchain pour la chaîne d\'approvisionnement.', 'Hyperledger Fabric, Go ou JavaScript, APIs REST, bases de données distribuées, Docker, réseau p2p, systèmes de consensus', '2026-01-01', '2026-06-30', 'Montpellier', 'hybrid', 900.00, 'Blockchain', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(101, 32, 'Développeur E-commerce', 'Développement de fonctionnalités pour une plateforme e-commerce omnicanal en forte croissance.', 'PHP, Symfony ou Laravel, JavaScript, React, APIs REST, SQL, RabbitMQ, expérience e-commerce, Elasticsearch, Redis', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 850.00, 'E-commerce', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(102, 32, 'Data Analyst Retail', 'Analyse des données de vente et comportementales pour optimiser les performances commerciales.', 'Python, R, SQL, Pandas, Tableau ou PowerBI, statistiques, A/B testing, segmentation client, machine learning, ETL', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 880.00, 'Data Science', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(103, 49, 'Développeur Solutions Retail Connecté', 'Création d\'applications pour connecter l\'expérience en magasin et en ligne.', 'JavaScript, React Native, Node.js, APIs, IoT, beacons, RFID, NFC, systèmes de paiement, architecture microservices', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 850.00, 'Retail Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(104, 33, 'Développeur Applications Sportives', 'Développement d\'applications mobiles pour le suivi des performances sportives et la santé.', 'Swift ou Kotlin, Firebase, APIs REST, Bluetooth LE, HealthKit/Google Fit, algorithmes d\'analyse de performance, UX/UI design sportif', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 850.00, 'Sport Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(105, 19, 'Ingénieur Biomécanique', 'Développement d\'algorithmes pour l\'analyse de mouvements à partir de capteurs inertiels.', 'Python, traitement du signal, machine learning, mécanique, capteurs, IMU, analyse biomécanique, modélisation 3D, mathématiques', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 900.00, 'Santé Numérique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(106, 33, 'Data Scientist Performance Sportive', 'Analyse des données de performance sportive et développement d\'algorithmes prédictifs.', 'Python, R, statistiques avancées, machine learning, traitement du signal, visualisation de données, time series, clustering', '2026-01-01', '2026-06-30', 'Montpellier', 'hybrid', 900.00, 'Sport Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(107, 34, 'Ingénieur Computer Vision', 'Développement d\'algorithmes de vision par ordinateur pour la détection et reconnaissance d\'objets.', 'Python, TensorFlow ou PyTorch, OpenCV, CNN, YOLO, transformers, traitement d\'images, OCR, optimisation d\'inférence', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(108, 34, 'Chercheur en IA Générative', 'Recherche et implémentation de modèles génératifs pour la création de contenu multimédia.', 'Python, PyTorch, GANs, Diffusion models, transformers, VAEs, traitement d\'images, deep learning, mathématiques, statistiques', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 1000.00, 'Intelligence Artificielle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(109, 48, 'Ingénieur IA Industrielle', 'Application de l\'IA pour l\'optimisation des processus industriels et la maintenance prédictive.', 'Python, scikit-learn, PyTorch, séries temporelles, anomaly detection, MLOps, systèmes industriels, IoT, Edge computing', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(110, 35, 'Analyste Données Satellitaires', 'Traitement et analyse d\'images satellitaires pour des applications environnementales.', 'Python, GDAL, Rasterio, Earth Engine, machine learning, traitement d\'images, SIG, télédétection, photogrammétrie', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 920.00, 'Spatial', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(111, 41, 'Ingénieur Logiciel Embarqué Aéronautique', 'Développement de logiciels embarqués critiques pour systèmes avioniques.', 'C/C++, RTOS, ARINC 653, DO-178C, systèmes temps réel, tests unitaires, MISRA C, modélisation UML, méthodes formelles', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Aéronautique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(112, 35, 'Développeur Logiciel Spatial', 'Développement d\'applications pour le traitement et la visualisation de données spatiales.', 'Python, JavaScript, WebGL, Cesium.js, visualisation 3D, format GIS, SQL spatial, REST APIs, cloud computing', '2026-01-01', '2026-06-30', 'Toulouse', 'hybrid', 900.00, 'Spatial', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(113, 36, 'Ingénieur en Conformité RGPD', 'Développement d\'outils pour faciliter la conformité RGPD et la protection des données personnelles.', 'Java ou Python, SQL, GDPR/RGPD, audits, anonymisation, chiffrement, data lineage, data mapping, tests de pénétration', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 900.00, 'RGPD', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(114, 36, 'Développeur Sécurité des Données', 'Implémentation de mécanismes de sécurisation des données et de gestion des consentements.', 'Java, Spring Security, JWT, OAuth2, chiffrement, PostgreSQL, Redis, microservices, DevSecOps, CI/CD', '2025-09-01', '2026-02-28', 'Lyon', 'on_site', 900.00, 'Sécurité des Données', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(115, 22, 'Analyste Risques Cyber', 'Évaluation des risques cyber et développement de tableaux de bord de suivi de la sécurité.', 'Python, SQL, SIEM, logs analysis, framework ISO 27001, NIST, visualisation de données, forensics, threat intelligence', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 920.00, 'Cybersécurité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(116, 37, 'Développeur FinTech RegTech', 'Développement de solutions pour automatiser la conformité réglementaire dans le secteur financier.', 'Java ou Python, Spring Boot, microservices, SQL, NoSQL, machine learning, APIs financières, connaissance MiFID II/GDPR', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 900.00, 'Fintech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(117, 37, 'Data Scientist Actuariat', 'Développement de modèles prédictifs pour l\'évaluation des risques en assurance.', 'R ou Python, statistiques, machine learning, GLM, GAM, séries temporelles, SQL, SAS, connaissance actuariat', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 950.00, 'Insurtech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(118, 50, 'Ingénieur Blockchain Paiements', 'Développement de solutions de paiement sécurisées basées sur la blockchain.', 'JavaScript, Node.js, Solidity, Web3.js, bases de données, sécurité, cryptographie, API REST, systèmes de paiement', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Fintech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(119, 38, 'Développeur Backend Mobilité', 'Développement d\'APIs et services backend pour une plateforme de mobilité multimodale.', 'Java ou Python, Spring Boot, Django, REST APIs, SQL, NoSQL, message brokers, tests unitaires, intégration, CI/CD', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 880.00, 'Mobilité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(120, 38, 'Data Scientist Optimisation Transport', 'Développement d\'algorithmes d\'optimisation pour la planification des trajets et la réduction de l\'empreinte carbone.', 'Python, algorithmes d\'optimisation, graphes, recherche opérationnelle, OR-Tools, machine learning, GIS, SQL', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 900.00, 'Mobilité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(121, 38, 'Développeur Mobile MaaS', 'Développement d\'une application Mobility-as-a-Service intégrant différents modes de transport.', 'Flutter ou React Native, JavaScript, APIs REST, paiement mobile, géolocalisation, WebSockets, OAuth2, UX/UI', '2026-01-01', '2026-06-30', 'Strasbourg', 'hybrid', 880.00, 'Mobilité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(122, 39, 'Ingénieur en Automatisation', 'Développement de systèmes de contrôle et d\'automatisation pour l\'industrie 4.0.', 'Python, C/C++, OPC UA, MQTT, Modbus, automates, SCADA, IEC 61131-3, Ethernet industriel, machine learning', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 950.00, 'Automatisation', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(123, 39, 'Développeur HMI/SCADA', 'Conception et développement d\'interfaces homme-machine pour systèmes industriels.', 'C#, WPF, HTML5/JavaScript, SVG, REST APIs, SCADA, bases de données temps réel, UX industriel, ergonomie', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 900.00, 'Automatisation', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(124, 39, 'Data Engineer Industrie 4.0', 'Mise en place de systèmes de collecte et d\'analyse de données pour l\'industrie connectée.', 'Python, ETL, SQL, NoSQL, Kafka, Spark, Azure IoT/AWS IoT, Docker, Kubernetes, edge computing', '2026-01-01', '2026-06-30', 'Grenoble', 'hybrid', 920.00, 'Industrie 4.0', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(125, 40, 'Développeur BIM', 'Développement d\'outils pour la modélisation des informations du bâtiment et la visualisation 3D.', 'C# ou Python, Revit API, IFC, Unity3D, WebGL, bases de données spatiales, géométrie 3D, BIM, AutoCAD', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 880.00, 'Construction', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(126, 40, 'Data Scientist Bâtiment Intelligent', 'Analyse des données de capteurs pour optimiser la performance énergétique des bâtiments.', 'Python, IoT, machine learning, séries temporelles, statistiques, HVAC, efficacité énergétique, modélisation thermique', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 900.00, 'Smart Building', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(127, 13, 'Développeur Smart Building', 'Développement d\'applications pour la gestion intelligente des bâtiments et l\'efficacité énergétique.', 'JavaScript, Node.js, React, IoT, MQTT, bases de données temporelles, WebSockets, APIs REST, UI/UX', '2026-01-01', '2026-06-30', 'Angers', 'hybrid', 880.00, 'Smart Building', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(128, 42, 'Ingénieur Traitement des Eaux', 'Développement de solutions numériques pour la surveillance et l\'optimisation des systèmes de traitement d\'eau.', 'Python, IoT, traitement du signal, machine learning, modélisation hydraulique, chimie de l\'eau, capteurs, SCADA', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Water Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(129, 43, 'Analyste Cyber Threat Intelligence', 'Collecte et analyse d\'informations sur les menaces cybernétiques pour améliorer la sécurité.', 'Python, OSINT, MISP, STIX/TAXII, Jupyter, forensics, malware analysis, threat hunting, CTI frameworks', '2025-09-01', '2026-02-28', 'Rennes', 'hybrid', 920.00, 'Cybersécurité', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(130, 44, 'Développeur Solutions Alimentaires', 'Développement d\'applications pour la traçabilité et la sécurité alimentaire.', 'Java ou Python, Spring Boot, SQL, NoSQL, blockchain, APIs REST, microservices, règles HACCP, certifications', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 880.00, 'Food Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(131, 45, 'DevOps Performance Engineer', 'Optimisation des performances d\'infrastructures cloud et d\'applications distribuées.', 'Linux, Kubernetes, AWS/Azure/GCP, Terraform, Ansible, monitoring, profiling, load testing, APM, Go ou Python', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'DevOps', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(132, 46, 'Développeur Expérience Touristique', 'Création d\'applications mobiles pour enrichir l\'expérience touristique avec la réalité augmentée.', 'Swift ou Kotlin, ARKit/ARCore, Unity, géolocalisation, POI, backend REST, contenu multimédia, UX/UI, design', '2025-09-01', '2026-02-28', 'Nice', 'hybrid', 850.00, 'Tourisme', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(133, 47, 'Développeur Gestion Événementielle', 'Conception et développement de solutions pour la gestion et la billetterie d\'événements.', 'PHP ou Node.js, React, SQL, RabbitMQ, APIs REST, paiement en ligne, QR codes, RFID, cartographie, CRM', '2025-09-01', '2026-02-28', 'Cannes', 'hybrid', 850.00, 'Événementiel', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(134, 46, 'Développeur Réalité Mixte Tourisme', 'Développement d\'applications de réalité mixte pour la mise en valeur du patrimoine culturel.', 'Unity, C#, AR/VR, modélisation 3D, WebGL, WebXR, contenu historique, design d\'expérience, storytelling', '2026-01-01', '2026-06-30', 'Nice', 'hybrid', 880.00, 'Tourisme', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(135, 1, 'Développeur Full-Stack Web3', 'Développement d\'une plateforme décentralisée avec composants frontaux et intégration blockchain.', 'React, Node.js, Ethereum, Web3.js, Solidity, GraphQL, IPFS, testing DApps, UX/UI pour crypto', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 950.00, 'Blockchain', 'assigned', '2025-06-10 09:50:27', '2025-06-11 19:13:20'),
(136, 25, 'Bioinformaticien Génomique', 'Analyse de données de séquençage et développement d\'algorithmes pour la recherche génomique.', 'Python ou R, NGS, pipelines bioinformatiques, statistiques, Bioconductor, génomique, data visualization', '2026-01-01', '2026-06-30', 'Marseille', 'hybrid', 920.00, 'Bio-informatique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(137, 28, 'Développeur Unity 3D', 'Création d\'un jeu vidéo mobile avec intégration de fonctionnalités multijoueurs et monétisation.', 'Unity, C#, design patterns, networking, UI/UX, animations, optimisation mobile, shaders, analytics', '2026-01-01', '2026-06-30', 'Lille', 'hybrid', 900.00, 'Jeux Vidéo', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(138, 13, 'Ingénieur Énergie Renouvelable', 'Développement de solutions pour le monitoring et l\'optimisation de systèmes d\'énergie renouvelable.', 'Python, IoT, algorithmes d\'optimisation, modélisation énergétique, SCADA, séries temporelles, prévision', '2026-01-01', '2026-06-30', 'Angers', 'hybrid', 900.00, 'Green Tech', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(139, 9, 'Développeur Smart Contracts NFT', 'Création d\'une plateforme NFT avec smart contracts et interfaces utilisateur pour les créateurs.', 'Solidity, JavaScript, React, IPFS, ERC-721/ERC-1155, ethers.js, tests Hardhat, metadata standards', '2026-01-01', '2026-06-30', 'Montpellier', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(140, 12, 'Développeur 3D WebXR', 'Création d\'expériences 3D interactives pour le web avec technologies de réalité virtuelle et augmentée.', 'JavaScript, Three.js, WebXR, WebGL, 3D modeling, animation, optimisation, UX immersive, PWA', '2026-01-01', '2026-06-30', 'Marseille', 'hybrid', 900.00, 'Réalité Virtuelle', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(141, 17, 'Ingénieur Vision Robotique', 'Développement d\'algorithmes de vision par ordinateur pour robots industriels et mobiles.', 'Python, C++, OpenCV, ROS, détection d\'objets, SLAM, calibration, deep learning, traitement d\'images temps réel', '2026-01-01', '2026-06-30', 'Toulouse', 'on_site', 950.00, 'Robotique', 'available', '2025-06-10 09:50:27', '2025-06-10 09:50:27'),
(142, 6, 'Développeur Front-End React', 'Développement d\'interfaces utilisateurs modernes avec React et intégration avec des APIs REST.', 'React, JavaScript, HTML5, CSS3, Git', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 800.00, 'Développement Web', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(143, 8, 'Développeur Back-End PHP', 'Développement et maintenance d\'APIs et services backend pour une plateforme e-commerce.', 'PHP, MySQL, RESTful APIs, Laravel ou Symfony', '2025-09-01', '2026-02-28', 'Strasbourg', 'on_site', 850.00, 'Développement Web', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(144, 12, 'Développeur Full-Stack JavaScript', 'Conception et implémentation de fonctionnalités full-stack pour une application SaaS en croissance.', 'JavaScript, Node.js, React, MongoDB, Express', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Développement Web', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(145, 15, 'Intégrateur Web / UX', 'Intégration de maquettes et développement d\'interfaces utilisateur pour des applications de Smart City.', 'HTML5, CSS3, SASS, JavaScript, expérience en design', '2025-09-01', '2026-02-28', 'Dijon', 'remote', 750.00, 'Développement Web', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(146, 2, 'Analyste de Données Marketing', 'Analyse des données client et des campagnes marketing pour optimiser les conversions et le ROI.', 'Python, Pandas, SQL, visualisation de données (Tableau ou PowerBI)', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 850.00, 'Data Science', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(147, 7, 'Data Scientist en NLP', 'Développement de modèles de traitement du langage naturel pour l\'analyse de sentiments et la classification de textes.', 'Python, scikit-learn, NLTK ou spaCy, PyTorch ou TensorFlow', '2025-09-01', '2026-02-28', 'Grenoble', 'hybrid', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(148, 11, 'Chercheur en Informatique Quantique', 'Participation à des projets de recherche sur les algorithmes quantiques et leurs applications.', 'Python, algèbre linéaire, notions d\'informatique quantique', '2025-09-01', '2026-02-28', 'Rennes', 'on_site', 1000.00, 'Informatique Quantique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42');
INSERT INTO `internships` (`id`, `company_id`, `title`, `description`, `requirements`, `start_date`, `end_date`, `location`, `work_mode`, `compensation`, `domain`, `status`, `created_at`, `updated_at`) VALUES
(149, 14, 'Bio-informaticien', 'Analyse de données génomiques et développement d\'algorithmes pour la recherche médicale.', 'Python ou R, statistiques, connaissances en biologie', '2025-09-01', '2026-02-28', 'Tours', 'hybrid', 900.00, 'Bio-informatique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(150, 25, 'Ingénieur IA pour interfaces neuronales', 'Développement d\'algorithmes d\'apprentissage pour interpréter les signaux cérébraux.', 'Python, TensorFlow ou PyTorch, traitement du signal', '2025-09-01', '2026-02-28', 'Marseille', 'on_site', 1000.00, 'NeuroTechnologies', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(151, 3, 'Analyste SOC Junior', 'Surveillance des alertes de sécurité et analyse des incidents dans un centre opérationnel de sécurité.', 'Connaissances en cybersécurité, réseaux, Linux', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(152, 22, 'Pentesteur Junior', 'Participation aux tests d\'intrusion et à l\'évaluation de la sécurité des systèmes d\'information.', 'Connaissances en sécurité informatique, réseaux, programmation', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Cybersécurité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(153, 22, 'Analyste en Sécurité des Applications', 'Audit de sécurité d\'applications web et mobiles, détection de vulnérabilités et recommandations.', 'OWASP Top 10, techniques de pentest, développement web', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 900.00, 'Cybersécurité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(154, 4, 'Développeur iOS', 'Développement et maintenance d\'applications iOS pour le secteur du retail.', 'Swift, UIKit, CoreData, Git', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'Développement Mobile', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(155, 4, 'Développeur Android', 'Conception et développement de nouvelles fonctionnalités pour une application Android à forte audience.', 'Java ou Kotlin, Android SDK, Architecture Components', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'Développement Mobile', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(156, 12, 'Développeur React Native', 'Développement d\'applications mobiles cross-platform pour la réalité augmentée.', 'React Native, JavaScript, expérience en AR', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Développement Mobile', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(157, 5, 'Ingénieur Logiciel Embarqué', 'Développement de firmware pour systèmes embarqués dans le secteur automobile.', 'C/C++, microcontrôleurs, protocoles de communication (CAN, LIN)', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Systèmes Embarqués', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(158, 10, 'Développeur IoT', 'Conception et développement de solutions IoT pour la maison connectée.', 'C/C++, protocoles IoT (MQTT, CoAP), Linux embarqué', '2025-09-01', '2026-02-28', 'Nice', 'hybrid', 900.00, 'Internet des Objets', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(159, 17, 'Ingénieur en Robotique', 'Développement de logiciels pour robots industriels et participation à leur intégration.', 'C++, ROS, notions de mécatronique', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Robotique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(160, 6, 'Ingénieur DevOps Junior', 'Mise en place et amélioration des pipelines CI/CD et de l\'infrastructure cloud.', 'Linux, Docker, Kubernetes, Git, AWS ou Azure', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 900.00, 'DevOps', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(161, 24, 'Ingénieur Cloud', 'Développement d\'outils d\'automatisation et de déploiement sur des infrastructures cloud.', 'AWS ou Azure, Terraform, Python ou Go, CI/CD', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 950.00, 'Cloud Computing', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(162, 6, 'SRE (Site Reliability Engineer)', 'Garantir la fiabilité et les performances des applications en production.', 'Linux, monitoring (Prometheus, Grafana), scripting', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 950.00, 'DevOps', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(163, 9, 'Développeur Blockchain', 'Développement de smart contracts et d\'applications décentralisées pour le secteur financier.', 'Solidity, Ethereum, Web3.js, JavaScript', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(164, 12, 'Développeur AR/VR', 'Création d\'expériences immersives en réalité virtuelle et augmentée.', 'Unity ou Unreal Engine, C#, 3D, design UX', '2025-09-01', '2026-02-28', 'Marseille', 'on_site', 900.00, 'Réalité Virtuelle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(165, 23, 'Ingénieur en Fabrication Additive', 'Développement de logiciels pour optimiser les processus d\'impression 3D industrielle.', 'C++, Python, CAO, connaissances en fabrication additive', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 900.00, 'Impression 3D', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(166, 13, 'Développeur pour Solutions Écologiques', 'Création d\'applications de suivi et d\'optimisation de l\'empreinte carbone des entreprises.', 'Java ou Python, bases de données, APIs', '2025-09-01', '2026-02-28', 'Angers', 'hybrid', 850.00, 'Green Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(167, 19, 'Ingénieur Logiciel pour la Santé', 'Développement d\'applications médicales conformes aux normes du secteur de la santé.', 'Java ou C#, connaissance des normes (HIPAA, RGPD médical)', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 900.00, 'Santé Numérique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(168, 18, 'Développeur de Plateformes E-learning', 'Conception et développement de fonctionnalités pour une plateforme éducative en ligne.', 'PHP ou Python, frameworks web, UX/UI pour l\'éducation', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 850.00, 'Ed Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(169, 21, 'Data Engineer pour l\'Agriculture', 'Mise en place de pipelines de données pour l\'analyse des cultures et l\'agriculture de précision.', 'Python, ETL, bases de données, IoT', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 880.00, 'Agri Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(170, 16, 'Analyste Fintech', 'Développement et analyse d\'algorithmes pour la détection de fraudes dans les transactions financières.', 'Python, SQL, machine learning, connaissances en finance', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 900.00, 'Fintech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(171, 20, 'Ingénieur en Efficacité Énergétique', 'Développement de solutions logicielles pour optimiser la consommation énergétique des bâtiments.', 'Python, IoT, algorithmes d\'optimisation', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 880.00, 'Green Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(172, 29, 'Développeur pour Solutions RH', 'Développement de modules pour une plateforme SaaS de gestion des talents et du recrutement.', 'JavaScript, React, Node.js, bases de données', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 850.00, 'HR Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(173, 30, 'Développeur Legal Tech', 'Conception et développement d\'outils d\'automatisation pour le secteur juridique.', 'Python ou Java, NLP, bases de données', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 900.00, 'Legal Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(174, 1, 'Architecte Logiciel Junior', 'Participation à la conception et à l\'évolution de l\'architecture de nos solutions cloud.', 'Java, Spring, microservices, patterns de conception', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 950.00, 'Architecture Logicielle', 'assigned', '2025-06-10 10:01:42', '2025-06-11 19:13:20'),
(175, 8, 'UX/UI Designer', 'Conception d\'interfaces utilisateur et amélioration de l\'expérience utilisateur sur nos applications web.', 'Figma ou Adobe XD, HTML/CSS, notions de JavaScript', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 800.00, 'Design Numérique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(176, 25, 'Ingénieur Test et Qualité', 'Mise en place de stratégies de test et automatisation pour garantir la qualité des applications.', 'Selenium, Cypress ou JUnit, CI/CD, méthodologies de test', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 850.00, 'Qualité Logicielle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(177, 27, 'Ingénieur en Traitement du Signal Audio', 'Développement d\'algorithmes de traitement du signal pour des applications de reconnaissance vocale.', 'Python, traitement du signal, machine learning', '2025-09-01', '2026-02-28', 'Nice', 'on_site', 900.00, 'Traitement Audio', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(178, 7, 'Data Engineer', 'Conception et développement de pipelines de données pour l\'alimentation de modèles d\'IA.', 'Python, Spark, SQL, cloud (AWS/Azure/GCP)', '2025-09-01', '2026-02-28', 'Grenoble', 'hybrid', 920.00, 'Ingénierie des Données', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(179, 28, 'Développeur de Jeux Vidéo', 'Développement de mécaniques de jeu et intégration de contenus pour un jeu mobile.', 'Unity, C#, game design, 2D/3D', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 850.00, 'Jeux Vidéo', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(180, 26, 'Ingénieur Logistique et Supply Chain', 'Développement d\'outils d\'optimisation pour la chaîne d\'approvisionnement et la logistique.', 'Python, algorithmes d\'optimisation, bases de données', '2025-09-01', '2026-02-28', 'Le Havre', 'hybrid', 880.00, 'Logistique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(181, 1, 'Développeur Web React Junior', 'Développement de composants React et intégration avec des API REST.', 'React, JavaScript, HTML/CSS, Git', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 800.00, 'Développement Web', 'assigned', '2025-06-10 10:01:42', '2025-06-11 19:13:20'),
(182, 7, 'Machine Learning Engineer', 'Implémentation et optimisation de modèles de machine learning pour des applications industrielles.', 'Python, scikit-learn, TensorFlow ou PyTorch', '2026-01-01', '2026-06-30', 'Grenoble', 'on_site', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(183, 3, 'Analyste en Sécurité des Réseaux', 'Surveillance et analyse de la sécurité des infrastructures réseau.', 'Réseaux, sécurité, Linux, outils de monitoring', '2026-01-01', '2026-06-30', 'Lille', 'on_site', 900.00, 'Cybersécurité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(184, 10, 'Développeur IoT Firmware', 'Développement de firmware pour objets connectés dans le domaine de la domotique.', 'C/C++, microcontrôleurs, protocoles IoT', '2026-01-01', '2026-06-30', 'Nice', 'on_site', 880.00, 'Internet des Objets', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(185, 24, 'Ingénieur CI/CD', 'Amélioration des pipelines de déploiement continu et automatisation des tests.', 'GitLab CI ou Jenkins, Docker, Kubernetes, scripting', '2026-01-01', '2026-06-30', 'Strasbourg', 'hybrid', 900.00, 'DevOps', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(186, 15, 'Développeur Python pour Smart City', 'Développement de services backend pour applications de ville intelligente.', 'Python, Django ou Flask, APIs, bases de données', '2026-01-01', '2026-06-30', 'Dijon', 'hybrid', 850.00, 'Smart City', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(187, 2, 'Data Scientist Marketing', 'Analyse de données clients et modélisation prédictive pour optimiser les campagnes marketing.', 'Python, R, SQL, statistiques, machine learning', '2026-01-01', '2026-06-30', 'Lyon', 'hybrid', 900.00, 'Data Science', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(188, 31, 'Développeur DeFi', 'Développement de protocoles financiers décentralisés et intégration avec des plateformes existantes.', 'Solidity, Ethereum, DeFi, Web3.js, React, tests smart contracts (Truffle/Hardhat), sécurité blockchain, cryptographie', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(189, 31, 'Analyste Sécurité Blockchain', 'Audit de sécurité des smart contracts et analyse des vulnérabilités dans les applications décentralisées.', 'Ethereum, Solidity, techniques d\'audit, MythX, Slither, patterns de sécurité smart contracts, cryptographie, SAST/DAST', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 950.00, 'Blockchain', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(190, 9, 'Ingénieur Blockchain Supply Chain', 'Développement d\'une solution de traçabilité basée sur la blockchain pour la chaîne d\'approvisionnement.', 'Hyperledger Fabric, Go ou JavaScript, APIs REST, bases de données distribuées, Docker, réseau p2p, systèmes de consensus', '2026-01-01', '2026-06-30', 'Montpellier', 'hybrid', 900.00, 'Blockchain', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(191, 32, 'Développeur E-commerce', 'Développement de fonctionnalités pour une plateforme e-commerce omnicanal en forte croissance.', 'PHP, Symfony ou Laravel, JavaScript, React, APIs REST, SQL, RabbitMQ, expérience e-commerce, Elasticsearch, Redis', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 850.00, 'E-commerce', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(192, 32, 'Data Analyst Retail', 'Analyse des données de vente et comportementales pour optimiser les performances commerciales.', 'Python, R, SQL, Pandas, Tableau ou PowerBI, statistiques, A/B testing, segmentation client, machine learning, ETL', '2025-09-01', '2026-02-28', 'Nantes', 'hybrid', 880.00, 'Data Science', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(193, 49, 'Développeur Solutions Retail Connecté', 'Création d\'applications pour connecter l\'expérience en magasin et en ligne.', 'JavaScript, React Native, Node.js, APIs, IoT, beacons, RFID, NFC, systèmes de paiement, architecture microservices', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 850.00, 'Retail Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(194, 33, 'Développeur Applications Sportives', 'Développement d\'applications mobiles pour le suivi des performances sportives et la santé.', 'Swift ou Kotlin, Firebase, APIs REST, Bluetooth LE, HealthKit/Google Fit, algorithmes d\'analyse de performance, UX/UI design sportif', '2025-09-01', '2026-02-28', 'Montpellier', 'hybrid', 850.00, 'Sport Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(195, 19, 'Ingénieur Biomécanique', 'Développement d\'algorithmes pour l\'analyse de mouvements à partir de capteurs inertiels.', 'Python, traitement du signal, machine learning, mécanique, capteurs, IMU, analyse biomécanique, modélisation 3D, mathématiques', '2025-09-01', '2026-02-28', 'Lille', 'on_site', 900.00, 'Santé Numérique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(196, 33, 'Data Scientist Performance Sportive', 'Analyse des données de performance sportive et développement d\'algorithmes prédictifs.', 'Python, R, statistiques avancées, machine learning, traitement du signal, visualisation de données, time series, clustering', '2026-01-01', '2026-06-30', 'Montpellier', 'hybrid', 900.00, 'Sport Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(197, 34, 'Ingénieur Computer Vision', 'Développement d\'algorithmes de vision par ordinateur pour la détection et reconnaissance d\'objets.', 'Python, TensorFlow ou PyTorch, OpenCV, CNN, YOLO, transformers, traitement d\'images, OCR, optimisation d\'inférence', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(198, 34, 'Chercheur en IA Générative', 'Recherche et implémentation de modèles génératifs pour la création de contenu multimédia.', 'Python, PyTorch, GANs, Diffusion models, transformers, VAEs, traitement d\'images, deep learning, mathématiques, statistiques', '2025-09-01', '2026-02-28', 'Paris', 'on_site', 1000.00, 'Intelligence Artificielle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(199, 48, 'Ingénieur IA Industrielle', 'Application de l\'IA pour l\'optimisation des processus industriels et la maintenance prédictive.', 'Python, scikit-learn, PyTorch, séries temporelles, anomaly detection, MLOps, systèmes industriels, IoT, Edge computing', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 950.00, 'Intelligence Artificielle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(200, 35, 'Analyste Données Satellitaires', 'Traitement et analyse d\'images satellitaires pour des applications environnementales.', 'Python, GDAL, Rasterio, Earth Engine, machine learning, traitement d\'images, SIG, télédétection, photogrammétrie', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 920.00, 'Spatial', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(201, 41, 'Ingénieur Logiciel Embarqué Aéronautique', 'Développement de logiciels embarqués critiques pour systèmes avioniques.', 'C/C++, RTOS, ARINC 653, DO-178C, systèmes temps réel, tests unitaires, MISRA C, modélisation UML, méthodes formelles', '2025-09-01', '2026-02-28', 'Toulouse', 'on_site', 950.00, 'Aéronautique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(202, 35, 'Développeur Logiciel Spatial', 'Développement d\'applications pour le traitement et la visualisation de données spatiales.', 'Python, JavaScript, WebGL, Cesium.js, visualisation 3D, format GIS, SQL spatial, REST APIs, cloud computing', '2026-01-01', '2026-06-30', 'Toulouse', 'hybrid', 900.00, 'Spatial', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(203, 36, 'Ingénieur en Conformité RGPD', 'Développement d\'outils pour faciliter la conformité RGPD et la protection des données personnelles.', 'Java ou Python, SQL, GDPR/RGPD, audits, anonymisation, chiffrement, data lineage, data mapping, tests de pénétration', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 900.00, 'RGPD', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(204, 36, 'Développeur Sécurité des Données', 'Implémentation de mécanismes de sécurisation des données et de gestion des consentements.', 'Java, Spring Security, JWT, OAuth2, chiffrement, PostgreSQL, Redis, microservices, DevSecOps, CI/CD', '2025-09-01', '2026-02-28', 'Lyon', 'on_site', 900.00, 'Sécurité des Données', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(205, 22, 'Analyste Risques Cyber', 'Évaluation des risques cyber et développement de tableaux de bord de suivi de la sécurité.', 'Python, SQL, SIEM, logs analysis, framework ISO 27001, NIST, visualisation de données, forensics, threat intelligence', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 920.00, 'Cybersécurité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(206, 37, 'Développeur FinTech RegTech', 'Développement de solutions pour automatiser la conformité réglementaire dans le secteur financier.', 'Java ou Python, Spring Boot, microservices, SQL, NoSQL, machine learning, APIs financières, connaissance MiFID II/GDPR', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 900.00, 'Fintech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(207, 37, 'Data Scientist Actuariat', 'Développement de modèles prédictifs pour l\'évaluation des risques en assurance.', 'R ou Python, statistiques, machine learning, GLM, GAM, séries temporelles, SQL, SAS, connaissance actuariat', '2025-09-01', '2026-02-28', 'Bordeaux', 'hybrid', 950.00, 'Insurtech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(208, 50, 'Ingénieur Blockchain Paiements', 'Développement de solutions de paiement sécurisées basées sur la blockchain.', 'JavaScript, Node.js, Solidity, Web3.js, bases de données, sécurité, cryptographie, API REST, systèmes de paiement', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'Fintech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(209, 38, 'Développeur Backend Mobilité', 'Développement d\'APIs et services backend pour une plateforme de mobilité multimodale.', 'Java ou Python, Spring Boot, Django, REST APIs, SQL, NoSQL, message brokers, tests unitaires, intégration, CI/CD', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 880.00, 'Mobilité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(210, 38, 'Data Scientist Optimisation Transport', 'Développement d\'algorithmes d\'optimisation pour la planification des trajets et la réduction de l\'empreinte carbone.', 'Python, algorithmes d\'optimisation, graphes, recherche opérationnelle, OR-Tools, machine learning, GIS, SQL', '2025-09-01', '2026-02-28', 'Strasbourg', 'hybrid', 900.00, 'Mobilité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(211, 38, 'Développeur Mobile MaaS', 'Développement d\'une application Mobility-as-a-Service intégrant différents modes de transport.', 'Flutter ou React Native, JavaScript, APIs REST, paiement mobile, géolocalisation, WebSockets, OAuth2, UX/UI', '2026-01-01', '2026-06-30', 'Strasbourg', 'hybrid', 880.00, 'Mobilité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(212, 39, 'Ingénieur en Automatisation', 'Développement de systèmes de contrôle et d\'automatisation pour l\'industrie 4.0.', 'Python, C/C++, OPC UA, MQTT, Modbus, automates, SCADA, IEC 61131-3, Ethernet industriel, machine learning', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 950.00, 'Automatisation', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(213, 39, 'Développeur HMI/SCADA', 'Conception et développement d\'interfaces homme-machine pour systèmes industriels.', 'C#, WPF, HTML5/JavaScript, SVG, REST APIs, SCADA, bases de données temps réel, UX industriel, ergonomie', '2025-09-01', '2026-02-28', 'Grenoble', 'on_site', 900.00, 'Automatisation', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(214, 39, 'Data Engineer Industrie 4.0', 'Mise en place de systèmes de collecte et d\'analyse de données pour l\'industrie connectée.', 'Python, ETL, SQL, NoSQL, Kafka, Spark, Azure IoT/AWS IoT, Docker, Kubernetes, edge computing', '2026-01-01', '2026-06-30', 'Grenoble', 'hybrid', 920.00, 'Industrie 4.0', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(215, 40, 'Développeur BIM', 'Développement d\'outils pour la modélisation des informations du bâtiment et la visualisation 3D.', 'C# ou Python, Revit API, IFC, Unity3D, WebGL, bases de données spatiales, géométrie 3D, BIM, AutoCAD', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 880.00, 'Construction', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(216, 40, 'Data Scientist Bâtiment Intelligent', 'Analyse des données de capteurs pour optimiser la performance énergétique des bâtiments.', 'Python, IoT, machine learning, séries temporelles, statistiques, HVAC, efficacité énergétique, modélisation thermique', '2025-09-01', '2026-02-28', 'Lille', 'hybrid', 900.00, 'Smart Building', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(217, 13, 'Développeur Smart Building', 'Développement d\'applications pour la gestion intelligente des bâtiments et l\'efficacité énergétique.', 'JavaScript, Node.js, React, IoT, MQTT, bases de données temporelles, WebSockets, APIs REST, UI/UX', '2026-01-01', '2026-06-30', 'Angers', 'hybrid', 880.00, 'Smart Building', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(218, 42, 'Ingénieur Traitement des Eaux', 'Développement de solutions numériques pour la surveillance et l\'optimisation des systèmes de traitement d\'eau.', 'Python, IoT, traitement du signal, machine learning, modélisation hydraulique, chimie de l\'eau, capteurs, SCADA', '2025-09-01', '2026-02-28', 'Marseille', 'hybrid', 900.00, 'Water Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(219, 43, 'Analyste Cyber Threat Intelligence', 'Collecte et analyse d\'informations sur les menaces cybernétiques pour améliorer la sécurité.', 'Python, OSINT, MISP, STIX/TAXII, Jupyter, forensics, malware analysis, threat hunting, CTI frameworks', '2025-09-01', '2026-02-28', 'Rennes', 'hybrid', 920.00, 'Cybersécurité', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(220, 44, 'Développeur Solutions Alimentaires', 'Développement d\'applications pour la traçabilité et la sécurité alimentaire.', 'Java ou Python, Spring Boot, SQL, NoSQL, blockchain, APIs REST, microservices, règles HACCP, certifications', '2025-09-01', '2026-02-28', 'Lyon', 'hybrid', 880.00, 'Food Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(221, 45, 'DevOps Performance Engineer', 'Optimisation des performances d\'infrastructures cloud et d\'applications distribuées.', 'Linux, Kubernetes, AWS/Azure/GCP, Terraform, Ansible, monitoring, profiling, load testing, APM, Go ou Python', '2025-09-01', '2026-02-28', 'Paris', 'hybrid', 950.00, 'DevOps', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(222, 46, 'Développeur Expérience Touristique', 'Création d\'applications mobiles pour enrichir l\'expérience touristique avec la réalité augmentée.', 'Swift ou Kotlin, ARKit/ARCore, Unity, géolocalisation, POI, backend REST, contenu multimédia, UX/UI, design', '2025-09-01', '2026-02-28', 'Nice', 'hybrid', 850.00, 'Tourisme', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(223, 47, 'Développeur Gestion Événementielle', 'Conception et développement de solutions pour la gestion et la billetterie d\'événements.', 'PHP ou Node.js, React, SQL, RabbitMQ, APIs REST, paiement en ligne, QR codes, RFID, cartographie, CRM', '2025-09-01', '2026-02-28', 'Cannes', 'hybrid', 850.00, 'Événementiel', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(224, 46, 'Développeur Réalité Mixte Tourisme', 'Développement d\'applications de réalité mixte pour la mise en valeur du patrimoine culturel.', 'Unity, C#, AR/VR, modélisation 3D, WebGL, WebXR, contenu historique, design d\'expérience, storytelling', '2026-01-01', '2026-06-30', 'Nice', 'hybrid', 880.00, 'Tourisme', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(225, 1, 'Développeur Full-Stack Web3', 'Développement d\'une plateforme décentralisée avec composants frontaux et intégration blockchain.', 'React, Node.js, Ethereum, Web3.js, Solidity, GraphQL, IPFS, testing DApps, UX/UI pour crypto', '2026-01-01', '2026-06-30', 'Paris', 'hybrid', 950.00, 'Blockchain', 'assigned', '2025-06-10 10:01:42', '2025-06-11 19:13:20'),
(226, 25, 'Bioinformaticien Génomique', 'Analyse de données de séquençage et développement d\'algorithmes pour la recherche génomique.', 'Python ou R, NGS, pipelines bioinformatiques, statistiques, Bioconductor, génomique, data visualization', '2026-01-01', '2026-06-30', 'Marseille', 'hybrid', 920.00, 'Bio-informatique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(227, 28, 'Développeur Unity 3D', 'Création d\'un jeu vidéo mobile avec intégration de fonctionnalités multijoueurs et monétisation.', 'Unity, C#, design patterns, networking, UI/UX, animations, optimisation mobile, shaders, analytics', '2026-01-01', '2026-06-30', 'Lille', 'hybrid', 900.00, 'Jeux Vidéo', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(228, 13, 'Ingénieur Énergie Renouvelable', 'Développement de solutions pour le monitoring et l\'optimisation de systèmes d\'énergie renouvelable.', 'Python, IoT, algorithmes d\'optimisation, modélisation énergétique, SCADA, séries temporelles, prévision', '2026-01-01', '2026-06-30', 'Angers', 'hybrid', 900.00, 'Green Tech', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(229, 9, 'Développeur Smart Contracts NFT', 'Création d\'une plateforme NFT avec smart contracts et interfaces utilisateur pour les créateurs.', 'Solidity, JavaScript, React, IPFS, ERC-721/ERC-1155, ethers.js, tests Hardhat, metadata standards', '2026-01-01', '2026-06-30', 'Montpellier', 'hybrid', 950.00, 'Blockchain', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(230, 12, 'Développeur 3D WebXR', 'Création d\'expériences 3D interactives pour le web avec technologies de réalité virtuelle et augmentée.', 'JavaScript, Three.js, WebXR, WebGL, 3D modeling, animation, optimisation, UX immersive, PWA', '2026-01-01', '2026-06-30', 'Marseille', 'hybrid', 900.00, 'Réalité Virtuelle', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42'),
(231, 17, 'Ingénieur Vision Robotique', 'Développement d\'algorithmes de vision par ordinateur pour robots industriels et mobiles.', 'Python, C++, OpenCV, ROS, détection d\'objets, SLAM, calibration, deep learning, traitement d\'images temps réel', '2026-01-01', '2026-06-30', 'Toulouse', 'on_site', 950.00, 'Robotique', 'available', '2025-06-10 10:01:42', '2025-06-10 10:01:42');

-- --------------------------------------------------------

--
-- Table structure for table `internship_skills`
--

CREATE TABLE `internship_skills` (
  `id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internship_skills`
--

INSERT INTO `internship_skills` (`id`, `internship_id`, `skill_name`) VALUES
(5, 2, 'Python'),
(6, 2, 'R'),
(7, 2, 'SQL'),
(8, 2, 'Machine Learning'),
(9, 3, 'Linux'),
(10, 3, 'Réseaux'),
(11, 3, 'Sécurité informatique'),
(12, 3, 'Pentest'),
(13, 4, 'Java'),
(14, 4, 'Kotlin'),
(15, 4, 'Swift'),
(16, 4, 'React Native'),
(17, 5, 'C'),
(18, 5, 'C++'),
(19, 5, 'Microcontrôleurs'),
(20, 5, 'Électronique'),
(21, 1, 'JavaScript, React, Node.js, SQL');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_time` datetime NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `location` varchar(255) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `organizer_id` int(11) NOT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assignment_id` int(11) DEFAULT NULL,
  `date` date GENERATED ALWAYS AS (cast(`date_time` as date)) VIRTUAL,
  `start_time` time GENERATED ALWAYS AS (cast(`date_time` as time)) VIRTUAL,
  `end_time` time GENERATED ALWAYS AS (cast(`date_time` + interval `duration` minute as time)) VIRTUAL,
  `student_attended` tinyint(1) DEFAULT 0 COMMENT 'Indique si l''étudiant était présent',
  `notes` text DEFAULT NULL COMMENT 'Notes de la réunion',
  `completed_at` datetime DEFAULT NULL COMMENT 'Date et heure de complétion',
  `updated_at` datetime DEFAULT NULL COMMENT 'Date et heure de dernière mise à jour'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `title`, `description`, `date_time`, `duration`, `location`, `meeting_link`, `organizer_id`, `status`, `created_at`, `assignment_id`, `student_attended`, `notes`, `completed_at`, `updated_at`) VALUES
(1, 'Réunion de lancement - Stage TechSolutions', 'Première réunion pour discuter des objectifs et du déroulement du stage', '2023-09-05 14:00:00', 60, 'Bureau 203 - Bâtiment A', NULL, 9, 'scheduled', '2025-06-07 09:42:54', 1, 0, NULL, NULL, NULL),
(2, 'Point d\'avancement - Lucas Dupont', 'Réunion mensuelle de suivi de stage', '2023-10-03 14:00:00', 45, 'Teams', 'https://teams.microsoft.com/l/meeting/...', 9, 'scheduled', '2025-06-07 09:42:54', 1, 0, NULL, NULL, NULL),
(3, 'Réunion de lancement - Stage DataInsight', 'Première réunion pour discuter des objectifs et du déroulement du stage', '2023-09-06 10:00:00', 60, 'Bureau 105 - Bâtiment B', NULL, 10, 'scheduled', '2025-06-07 09:42:54', 2, 0, NULL, NULL, NULL),
(4, 'Point d\'avancement - Louis Moreau', 'Réunion mensuelle de suivi de stage', '2023-10-04 10:00:00', 45, 'Zoom', 'https://zoom.us/j/...', 10, 'scheduled', '2025-06-07 09:42:54', 2, 0, NULL, NULL, NULL),
(5, 'Réunion de lancement - Stage CyberGuard', 'Première réunion pour discuter des objectifs et du déroulement du stage', '2023-09-07 15:00:00', 60, 'Bureau 208 - Bâtiment A', NULL, 13, 'completed', '2025-06-07 09:42:54', 3, 1, '', '2025-06-21 21:00:09', '2025-06-21 21:00:09'),
(8, 'desd', '', '2025-06-10 18:24:00', 60, 'Zoom', '', 18, 'completed', '2025-06-09 15:31:48', 3, 1, '', '2025-06-21 20:59:54', '2025-06-21 20:59:54'),
(9, 'Besoin d\'eclaircissement', '', '2025-06-10 15:40:00', 60, 'Zoom', '', 18, 'completed', '2025-06-09 15:32:33', 3, 1, '', '2025-06-21 21:00:00', '2025-06-21 21:00:00'),
(10, 'Approbarion du Tuteur', '', '2025-06-11 17:30:00', 60, 'Bureau du tuteur', '', 18, 'completed', '2025-06-09 15:33:44', 3, 1, '', '2025-06-21 20:59:49', '2025-06-21 20:59:49'),
(11, 'Suivi', 'Ordre du jour : seance tenante.', '2025-06-14 10:30:00', 60, 'Bureau du tuteur', '', 18, 'completed', '2025-06-12 10:14:12', 3, 1, '', '2025-06-21 20:59:43', '2025-06-21 20:59:43'),
(12, 'Suivi du déroulement du stage', 'Type: Suivi régulier\n\n', '2025-06-17 09:45:00', 60, 'Bureau du tuteur', NULL, 13, 'completed', '2025-06-13 09:10:07', 3, 1, '', '2025-06-21 20:59:27', '2025-06-21 20:59:27'),
(13, 'Evaluation de Hugo Simon', 'Type: Évaluation\n\n', '2025-06-24 10:00:00', 60, 'Bureau du tuteur', NULL, 13, 'scheduled', '2025-06-21 18:54:50', 3, 0, NULL, NULL, NULL),
(14, 'Reunion de prise de contact.', 'Type: Présentation\n\nPrensentation et echange sur le déroulement du stage. ', '2025-06-22 11:00:00', 60, 'Bureau du tuteur', NULL, 13, 'scheduled', '2025-06-21 18:56:20', 3, 0, NULL, NULL, NULL),
(15, 'Reunion clarification', '', '2025-07-01 09:30:00', 60, 'Microsoft Teams', '', 18, 'scheduled', '2025-06-21 20:28:43', 3, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `meeting_participants`
--

CREATE TABLE `meeting_participants` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('invited','confirmed','declined','attended') NOT NULL DEFAULT 'invited'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meeting_participants`
--

INSERT INTO `meeting_participants` (`id`, `meeting_id`, `user_id`, `status`) VALUES
(1, 1, 9, 'confirmed'),
(2, 1, 14, 'confirmed'),
(3, 2, 9, 'confirmed'),
(4, 2, 14, 'confirmed'),
(5, 3, 10, 'confirmed'),
(6, 3, 16, 'confirmed'),
(7, 4, 10, 'confirmed'),
(8, 4, 16, 'confirmed'),
(9, 5, 13, 'confirmed'),
(10, 5, 18, 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('admin','coordinator','teacher','student') DEFAULT NULL,
  `receiver_id` int(11) NOT NULL,
  `recipient_type` enum('admin','coordinator','teacher','student') DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `status` enum('sent','read','archived','deleted') NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `conversation_id` int(11) DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `sender_type`, `receiver_id`, `recipient_type`, `subject`, `content`, `sent_at`, `read_at`, `status`, `created_at`, `conversation_id`, `is_group`) VALUES
(1, 9, 'teacher', 14, 'student', 'Bienvenue', 'Bonjour Lucas, j\'espère que ton stage se passe bien. N\'hésite pas à me contacter si tu as des questions.', '2025-06-07 09:42:54', NULL, 'sent', '2025-06-07 09:42:54', NULL, 0),
(2, 14, 'student', 9, 'teacher', 'Re: Bienvenue', 'Bonjour Professeur, merci de votre message. Tout se passe bien pour le moment. Je vous tiendrai informé de l\'avancement.', '2025-06-07 09:42:54', NULL, 'read', '2025-06-07 09:42:54', NULL, 0),
(3, 10, 'teacher', 16, 'student', 'Bienvenue', 'Bonjour Louis, comment se déroule ton intégration chez DataInsight ?', '2025-06-07 09:42:54', NULL, 'sent', '2025-06-07 09:42:54', NULL, 0),
(4, 16, 'student', 10, 'teacher', 'Re: Bienvenue', 'Bonjour Professeur, l\'intégration se passe très bien. L\'équipe est accueillante et les projets intéressants.', '2025-06-07 09:42:54', NULL, 'read', '2025-06-07 09:42:54', NULL, 0),
(5, 13, 'teacher', 18, 'student', 'Bienvenue', 'Bonjour Hugo, j\'espère que ton stage en cybersécurité répond à tes attentes.', '2025-06-07 09:42:54', '2025-06-09 11:18:11', 'read', '2025-06-07 09:42:54', NULL, 0),
(6, 18, 'student', 13, 'teacher', 'Re: Bienvenue', 'Bonjour Professeur, c\'est passionnant ! Je travaille actuellement sur un audit de sécurité très instructif.', '2025-06-07 09:42:54', NULL, 'read', '2025-06-07 09:42:54', NULL, 0),
(11, 18, 'student', 1, 'admin', 'Nouveau message', 'Bonjour !', '2025-06-07 14:20:24', '2025-06-10 13:37:06', 'read', '2025-06-07 13:20:24', NULL, 0),
(12, 18, 'student', 1, 'admin', 'Nouveau message', 'Bonjour !', '2025-06-07 14:44:03', '2025-06-10 13:37:06', 'read', '2025-06-07 13:44:03', NULL, 0),
(13, 18, 'student', 1, 'admin', 'Nouveau message', 'Bonjour !', '2025-06-07 14:44:25', '2025-06-10 13:37:06', 'read', '2025-06-07 13:44:25', NULL, 0),
(14, 18, 'student', 13, 'teacher', 'Nouveau message', 'Bonjou', '2025-06-07 14:52:28', '2025-06-09 09:52:57', 'read', '2025-06-07 13:52:28', NULL, 0),
(15, 18, 'student', 1, 'admin', 'Nouveau message', 'Hello', '2025-06-07 14:52:53', '2025-06-10 13:37:06', 'read', '2025-06-07 13:52:53', NULL, 0),
(16, 18, 'student', 13, 'teacher', 'Nouveau message', 'Bonjour Thomas', '2025-06-08 16:50:30', '2025-06-09 09:52:57', 'read', '2025-06-08 15:50:30', NULL, 0),
(17, 18, 'student', 1, 'admin', 'Nouveau message', 'Hi', '2025-06-08 18:18:31', '2025-06-10 13:37:06', 'read', '2025-06-08 17:18:31', NULL, 0),
(18, 18, 'student', 13, 'teacher', 'Nouveau message', 'Bonjour M. Robert', '2025-06-08 18:18:53', '2025-06-09 09:52:57', 'read', '2025-06-08 17:18:53', NULL, 0),
(19, 18, 'student', 1, 'admin', 'Nouveau message', 'Bonjour', '2025-06-08 19:14:41', '2025-06-10 13:37:06', 'read', '2025-06-08 18:14:41', NULL, 0),
(20, 18, 'student', 13, 'teacher', 'Nouveau message', 'Hello', '2025-06-08 20:00:54', '2025-06-09 09:52:57', 'read', '2025-06-08 19:00:54', NULL, 0),
(21, 18, 'student', 13, 'teacher', 'Nouveau message', 'Bonjour ', '2025-06-08 20:01:04', '2025-06-09 09:52:57', 'read', '2025-06-08 19:01:04', NULL, 0),
(22, 18, 'student', 13, 'teacher', 'Nouveau message', 'Comment allez-vous ?', '2025-06-08 20:01:16', '2025-06-09 09:52:57', 'read', '2025-06-08 19:01:16', NULL, 0),
(23, 13, 'teacher', 18, 'student', 'Nouveau message', 'Bonjour', '2025-06-08 20:08:42', '2025-06-09 11:18:11', 'read', '2025-06-08 19:08:42', NULL, 0),
(24, 13, 'teacher', 18, 'student', 'Nouveau message', 'Bonjour Hugo', '2025-06-08 20:57:56', '2025-06-09 11:18:11', 'read', '2025-06-08 19:57:56', NULL, 0),
(25, 13, 'teacher', 18, 'student', 'Prise de contact', 'Hello !', '2025-06-08 21:16:10', '2025-06-09 11:18:11', 'read', '2025-06-08 20:16:10', NULL, 0),
(26, 13, 'teacher', 50, 'coordinator', 'Prise de contact', 'Bonjou !', '2025-06-08 21:17:40', NULL, 'sent', '2025-06-08 20:17:40', NULL, 0),
(27, 13, 'teacher', 50, 'coordinator', 'Nouveau message', 'Bonjour !', '2025-06-08 21:17:51', NULL, 'sent', '2025-06-08 20:17:52', NULL, 0),
(28, 13, 'teacher', 50, 'coordinator', 'Nouveau message', 'Hello\r\n', '2025-06-08 22:02:05', NULL, 'sent', '2025-06-08 21:02:05', NULL, 0),
(29, 13, NULL, 18, NULL, 'Nouveau message', 'Je vais bien et vous ?', '2025-06-09 10:53:13', '2025-06-09 11:18:11', 'read', '2025-06-09 09:53:13', NULL, 0),
(30, 18, NULL, 13, NULL, 'Nouveau message', 'Cava, Merci.', '2025-06-09 10:53:55', '2025-06-09 09:54:18', 'read', '2025-06-09 09:53:55', NULL, 0),
(31, 18, NULL, 13, NULL, 'Message de l\'étudiant', 'Bonjour', '2025-06-09 13:00:21', '2025-06-12 09:31:16', 'read', '2025-06-09 12:00:21', NULL, 0),
(32, 1, NULL, 18, NULL, 'Nouveau message', 'Bonjour Hugo', '2025-06-10 14:37:18', '2025-06-12 08:34:59', 'read', '2025-06-10 13:37:18', NULL, 0),
(33, 18, NULL, 1, NULL, 'Nouveau message', 'Bonjour Monsieur', '2025-06-12 09:35:20', '2025-06-12 08:44:17', 'read', '2025-06-12 08:35:20', NULL, 0),
(34, 18, NULL, 13, NULL, 'Nouveau message', 'Bonjour Tuteur', '2025-06-12 09:35:35', '2025-06-12 09:31:16', 'read', '2025-06-12 08:35:35', NULL, 0),
(35, 18, NULL, 1, NULL, 'Nouveau message', 'Bonjour Admin', '2025-06-12 10:12:09', '2025-06-12 09:12:53', 'read', '2025-06-12 09:12:09', NULL, 0),
(36, 1, NULL, 18, NULL, 'Nouveau message', 'Oui Simon ! Bonjour !', '2025-06-12 10:13:07', '2025-06-15 18:19:49', 'read', '2025-06-12 09:13:07', NULL, 0),
(37, 13, NULL, 18, NULL, 'Nouveau message', 'Bonjour Hugo', '2025-06-12 10:31:25', '2025-06-15 18:19:47', 'read', '2025-06-12 09:31:25', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `message_recipients`
--

CREATE TABLE `message_recipients` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `related_type`, `related_id`, `created_at`, `read_at`, `link`) VALUES
(1, 14, 'Nouveau document partagé', 'Un nouveau document a été partagé avec vous: Guide du rapport final', 'document', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/student/documents.php'),
(2, 14, 'Réunion planifiée', 'Réunion de lancement - Stage TechSolutions le 05/09/2023 à 14h00', 'meeting', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/student/meetings.php'),
(3, 14, 'Nouvelle évaluation', 'Vous avez reçu une nouvelle évaluation de votre tuteur', 'evaluation', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/student/evaluations.php'),
(4, 16, 'Nouveau document partagé', 'Un nouveau document a été partagé avec vous: Guide du rapport final', 'document', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/student/documents.php'),
(5, 16, 'Réunion planifiée', 'Réunion de lancement - Stage DataInsight le 06/09/2023 à 10h00', 'meeting', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/student/meetings.php'),
(6, 18, 'Réunion planifiée', 'Réunion de lancement - Stage CyberGuard le 07/09/2023 à 15h00', 'meeting', NULL, NULL, '2025-06-07 09:42:54', '2025-06-12 10:11:36', '/tutoring/views/student/meetings.php'),
(7, 9, 'Document en attente', 'Un nouveau document a été soumis et est en attente de validation: Rapport intermédiaire - Lucas Dupont', 'document', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/tutor/documents.php'),
(8, 9, 'Réunion planifiée', 'Rappel: Point d\'avancement - Lucas Dupont le 03/10/2023 à 14h00', 'meeting', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/tutor/meetings.php'),
(9, 10, 'Réunion planifiée', 'Rappel: Point d\'avancement - Louis Moreau le 04/10/2023 à 10h00', 'meeting', NULL, NULL, '2025-06-07 09:42:54', NULL, '/tutoring/views/tutor/meetings.php'),
(10, 13, 'Nouveau document', 'L\'étudiant Hugo Simon a téléversé un nouveau document: CV', 'info', 'document', 1006, '2025-06-12 14:52:19', '2025-06-15 18:10:35', '/tutoring/views/tutor/documents.php');

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
(3, 14, 'INF2023001', 'Ingénierie Informatique', 'Master 2', 3.80, 2025, 'JavaScript, React, Node.js, Python', NULL, 'active'),
(4, 15, 'INF2023002', 'Ingénierie Informatique', 'Master 2', 3.50, 2025, 'SQL, Docker, AWS, Linux', NULL, 'active'),
(5, 16, 'INF2023003', 'Ingénierie Informatique', 'Master 2', 3.60, 2025, 'Python, TensorFlow, R, SQL', NULL, 'active'),
(6, 17, 'INF2023004', 'Master Informatique', 'M1', 3.40, 2025, 'HTML, CSS, JavaScript, Figma', NULL, 'active'),
(7, 18, 'INF2023005', 'Ingénierie Informatique', 'Master 2', 3.70, 2025, 'Linux, Nmap, Wireshark, Kali', NULL, 'active'),
(24, 51, 'STU2025001', 'Biochimie', 'Doctorat', 3.60, 2030, 'Python, Django, Flask, PostgreSQL', NULL, 'active'),
(25, 52, 'STU2025002', 'Ingénierie Informatique', 'Master 2', 2.60, 2029, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(26, 53, 'STU2025003', 'Génie Civil', 'Licence 2', 3.10, 2027, 'Game Development, Unity, C++', NULL, 'active'),
(27, 54, 'STU2025004', 'Science des Données', 'Doctorat', 2.60, 2029, 'Game Development, Unity, C++', NULL, 'active'),
(28, 55, 'STU2025005', 'Ingénierie Informatique', 'Licence 2', 3.00, 2028, 'Game Development, Unity, C++', NULL, 'active'),
(29, 56, 'STU2025006', 'Intelligence Artificielle', 'Licence 3', 2.60, 2027, 'React, Angular, Node.js, MongoDB', NULL, 'active'),
(30, 57, 'STU2025007', 'Science des Données', 'Licence 2', 2.50, 2028, 'C#, .NET Framework, SQL Server', NULL, 'active'),
(31, 58, 'STU2025008', 'Physique Théorique', 'Master 1', 3.30, 2027, 'Cloud Computing, Azure, Google Cloud', NULL, 'active'),
(32, 59, 'STU2025009', 'Intelligence Artificielle', 'Master 2', 3.30, 2030, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active'),
(33, 60, 'STU2025010', 'Réseaux et Sécurité', 'Licence 3', 3.50, 2028, 'Network Security, Penetration Testing', NULL, 'active'),
(34, 61, 'STU2025011', 'Génie Civil', 'Licence 1', 2.60, 2029, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(35, 62, 'STU2025012', 'Physique Théorique', 'Licence 2', 3.80, 2026, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(36, 63, 'STU2025013', 'Mathématiques Appliquées', 'Licence 3', 3.30, 2028, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(37, 64, 'STU2025014', 'Science des Données', 'Doctorat', 3.90, 2026, 'Network Security, Penetration Testing', NULL, 'active'),
(38, 65, 'STU2025015', 'Intelligence Artificielle', 'Licence 3', 2.50, 2028, 'C#, .NET Framework, SQL Server', NULL, 'active'),
(39, 66, 'STU2025016', 'Biochimie', 'Licence 3', 3.30, 2027, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(40, 67, 'STU2025017', 'Génie Civil', 'Doctorat', 3.30, 2027, 'Network Security, Penetration Testing', NULL, 'active'),
(41, 68, 'STU2025018', 'Science des Données', 'Licence 2', 3.90, 2028, 'Network Security, Penetration Testing', NULL, 'active'),
(42, 69, 'STU2025019', 'Développement Web', 'Doctorat', 3.50, 2028, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(43, 70, 'STU2025020', 'Réseaux et Sécurité', 'Licence 1', 3.30, 2027, 'MATLAB, Statistical Analysis, Modeling', NULL, 'active'),
(46, 73, 'QT2025001', 'Intelligence Artificielle', 'Master 1', 3.20, 2027, 'Java, Spring, Hibernate, RESTful API', NULL, 'active'),
(47, 74, 'CV2025002', 'Génie Civil', 'Licence 1', 3.60, 2030, 'Java, Spring, Hibernate, RESTful API', NULL, 'active'),
(48, 75, 'NE2025003', 'Mathématiques Appliquées', 'Licence 1', 2.60, 2028, 'Cloud Computing, Azure, Google Cloud', NULL, 'active'),
(49, 76, 'ZY2025004', 'Physique Théorique', 'Licence 3', 4.00, 2027, 'Python, Django, Flask, PostgreSQL', NULL, 'active'),
(50, 77, 'IN2025005', 'Science des Données', 'Licence 1', 2.50, 2029, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(51, 78, 'VM2025006', 'Chimie Organique', 'Licence 2', 3.40, 2026, 'Python, Django, Flask, PostgreSQL', NULL, 'active'),
(52, 79, 'AK2025007', 'Intelligence Artificielle', 'Master 1', 3.40, 2029, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active'),
(53, 80, 'CY2025008', 'Science des Données', 'Licence 3', 3.20, 2028, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(54, 81, 'WB2025009', 'Réseaux et Sécurité', 'Licence 1', 3.40, 2028, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(55, 82, 'TP2025010', 'Ingénierie Informatique', 'Doctorat', 3.00, 2026, 'React, Angular, Node.js, MongoDB', NULL, 'active'),
(56, 83, 'FR2025011', 'Ingénierie Informatique', 'Licence 1', 3.50, 2028, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(57, 84, 'DH2025012', 'Réseaux et Sécurité', 'Licence 3', 2.90, 2026, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(58, 85, 'SS2025013', 'Physique Théorique', 'Licence 2', 2.80, 2030, 'MATLAB, Statistical Analysis, Modeling', NULL, 'active'),
(59, 86, 'IP2025014', 'Science des Données', 'Doctorat', 2.90, 2029, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(60, 87, 'FW2025015', 'Réseaux et Sécurité', 'Licence 1', 2.60, 2030, 'Game Development, Unity, C++', NULL, 'active'),
(61, 88, 'KC2025016', 'Physique Théorique', 'Master 1', 3.50, 2027, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active'),
(62, 89, 'AU2025017', 'Physique Théorique', 'Licence 1', 2.80, 2028, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(63, 90, 'UP2025018', 'Chimie Organique', 'Doctorat', 4.00, 2030, 'React, Angular, Node.js, MongoDB', NULL, 'active'),
(64, 91, 'HR2025019', 'Ingénierie Informatique', 'Licence 2', 4.00, 2028, 'Mobile Development, Android, iOS, Flutter', NULL, 'active'),
(65, 92, 'JR2025020', 'Mathématiques Appliquées', 'Licence 1', 2.80, 2028, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(66, 93, 'AQ2025001', 'Intelligence Artificielle', 'Licence 3', 3.30, 2027, 'Game Development, Unity, C++', NULL, 'active'),
(67, 94, 'MN2025002', 'Biochimie', 'Master 1', 3.70, 2028, 'Cloud Computing, Azure, Google Cloud', NULL, 'active'),
(68, 95, 'UE2025003', 'Génie Civil', 'Licence 1', 2.50, 2026, 'React, Angular, Node.js, MongoDB', NULL, 'active'),
(69, 96, 'GY2025004', 'Chimie Organique', 'Master 1', 3.70, 2028, 'Mobile Development, Android, iOS, Flutter', NULL, 'active'),
(70, 97, 'MX2025005', 'Intelligence Artificielle', 'Master 2', 3.00, 2027, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(71, 98, 'DN2025006', 'Réseaux et Sécurité', 'Licence 2', 3.30, 2028, 'Mobile Development, Android, iOS, Flutter', NULL, 'active'),
(72, 99, 'MF2025007', 'Physique Théorique', 'Master 2', 3.30, 2028, 'React, Angular, Node.js, MongoDB', NULL, 'active'),
(73, 100, 'UM2025008', 'Génie Civil', 'Master 2', 3.10, 2026, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active'),
(74, 101, 'DK2025009', 'Chimie Organique', 'Master 2', 3.80, 2026, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(75, 102, 'LL2025010', 'Ingénierie Informatique', 'Doctorat', 3.50, 2029, 'React, Angular, Node.js, MongoDB', NULL, 'active'),
(76, 103, 'UL2025011', 'Mathématiques Appliquées', 'Licence 2', 2.90, 2030, 'MATLAB, Statistical Analysis, Modeling', NULL, 'active'),
(77, 104, 'HB2025012', 'Ingénierie Informatique', 'Doctorat', 3.10, 2027, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(78, 105, 'JH2025013', 'Biochimie', 'Licence 2', 3.70, 2028, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active'),
(79, 106, 'CW2025014', 'Développement Web', 'Master 2', 2.70, 2030, 'Python, Django, Flask, PostgreSQL', NULL, 'active'),
(80, 107, 'WR2025015', 'Biochimie', 'Master 2', 3.50, 2028, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(81, 108, 'PC2025016', 'Chimie Organique', 'Doctorat', 2.90, 2029, 'Mobile Development, Android, iOS, Flutter', NULL, 'active'),
(82, 109, 'XB2025017', 'Ingénierie Informatique', 'Licence 3', 4.00, 2026, 'MATLAB, Statistical Analysis, Modeling', NULL, 'active'),
(83, 110, 'TH2025018', 'Science des Données', 'Master 1', 4.00, 2028, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(84, 111, 'CO2025019', 'Physique Théorique', 'Licence 1', 3.90, 2029, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(85, 112, 'NK2025020', 'Génie Civil', 'Master 1', 3.00, 2030, 'Mobile Development, Android, iOS, Flutter', NULL, 'active'),
(86, 113, 'JJ2025001', 'Biochimie', 'Doctorat', 3.70, 2026, 'C#, .NET Framework, SQL Server', NULL, 'active'),
(87, 114, 'KI2025002', 'Biochimie', 'Licence 1', 3.50, 2026, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(88, 115, 'LF2025003', 'Ingénierie Informatique', 'Licence 1', 3.10, 2026, 'Mobile Development, Android, iOS, Flutter', NULL, 'active'),
(89, 116, 'YM2025004', 'Physique Théorique', 'Licence 2', 3.80, 2028, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(90, 117, 'KV2025005', 'Physique Théorique', 'Doctorat', 3.80, 2030, 'MATLAB, Statistical Analysis, Modeling', NULL, 'active'),
(91, 118, 'AC2025006', 'Intelligence Artificielle', 'Licence 2', 3.40, 2027, 'Java, Spring, Hibernate, RESTful API', NULL, 'active'),
(92, 119, 'RH2025007', 'Physique Théorique', 'Licence 1', 2.50, 2029, 'C#, .NET Framework, SQL Server', NULL, 'active'),
(93, 120, 'EU2025008', 'Développement Web', 'Licence 1', 3.20, 2029, 'Network Security, Penetration Testing', NULL, 'active'),
(94, 121, 'PH2025009', 'Chimie Organique', 'Licence 1', 3.30, 2027, 'PHP, MySQL, JavaScript, HTML, CSS', NULL, 'active'),
(95, 122, 'BL2025010', 'Intelligence Artificielle', 'Licence 2', 3.20, 2026, 'Cloud Computing, Azure, Google Cloud', NULL, 'active'),
(96, 123, 'KM2025011', 'Science des Données', 'Master 1', 3.30, 2026, 'Game Development, Unity, C++', NULL, 'active'),
(97, 124, 'WR2025012', 'Chimie Organique', 'Licence 1', 4.00, 2027, 'DevOps, Docker, Kubernetes, AWS', NULL, 'active'),
(98, 125, 'CR2025013', 'Ingénierie Informatique', 'Doctorat', 3.40, 2026, 'Game Development, Unity, C++', NULL, 'active'),
(99, 126, 'WD2025014', 'Intelligence Artificielle', 'Licence 2', 4.00, 2027, 'Java, Spring, Hibernate, RESTful API', NULL, 'active'),
(100, 127, 'YQ2025015', 'Science des Données', 'Master 2', 2.80, 2026, 'Python, Data Analysis, Machine Learning', NULL, 'active'),
(101, 128, 'ED2025016', 'Chimie Organique', 'Doctorat', 3.20, 2029, 'Java, Spring, Hibernate, RESTful API', NULL, 'active'),
(102, 129, 'UH2025017', 'Génie Civil', 'Doctorat', 2.60, 2026, 'UX/UI Design, Adobe XD, Figma', NULL, 'active'),
(103, 130, 'HD2025018', 'Réseaux et Sécurité', 'Licence 1', 2.50, 2030, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active'),
(104, 131, 'GP2025019', 'Réseaux et Sécurité', 'Licence 1', 3.40, 2029, 'DevOps, Docker, Kubernetes, AWS', NULL, 'active'),
(105, 132, 'BT2025020', 'Physique Théorique', 'Master 2', 3.90, 2029, 'Data Science, R, TensorFlow, PyTorch', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `student_preferences`
--

CREATE TABLE `student_preferences` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `preference_order` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_preferences`
--

INSERT INTO `student_preferences` (`id`, `student_id`, `internship_id`, `preference_order`, `reason`, `created_at`) VALUES
(1, 3, 1, 1, NULL, '2025-06-07 09:42:54'),
(2, 3, 4, 2, NULL, '2025-06-07 09:42:54'),
(3, 4, 2, 1, NULL, '2025-06-07 09:42:54'),
(4, 4, 1, 2, NULL, '2025-06-07 09:42:54'),
(5, 5, 2, 1, NULL, '2025-06-07 09:42:54'),
(6, 6, 4, 1, NULL, '2025-06-07 09:42:54'),
(7, 6, 1, 2, NULL, '2025-06-07 09:42:54'),
(8, 7, 3, 1, NULL, '2025-06-07 09:42:54'),
(9, 7, 21, 2, NULL, '2025-06-21 20:54:55');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) NOT NULL DEFAULT 'string',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'TutorMatch - Système de Gestion des Stages', 'string', 'general', 'Nom du site', '2025-06-06 18:42:43', '2025-06-06 22:31:37'),
(2, 'contact_email', 'support@tutormatch.example.com', 'string', 'general', 'Email de contact', '2025-06-06 18:42:43', '2025-06-06 22:31:37'),
(3, 'items_per_page', '20', 'integer', 'general', 'Éléments par page', '2025-06-06 18:42:43', '2025-06-06 22:31:37'),
(4, 'max_internships_per_student', '5', 'integer', 'internships', 'Nombre maximum de préférences par étudiant', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(5, 'max_students_per_teacher', '8', 'integer', 'internships', 'Nombre maximum d\'étudiants par tuteur', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(6, 'allow_cross_department', '1', 'boolean', 'internships', 'Autoriser les affectations inter-départements', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(7, 'algorithm_type', 'greedy', 'string', 'algorithm', 'Type d\'algorithme d\'affectation', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(8, 'preference_weight', '40', 'integer', 'algorithm', 'Poids des préférences', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(9, 'department_weight', '30', 'integer', 'algorithm', 'Poids du département', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(10, 'workload_weight', '30', 'integer', 'algorithm', 'Poids d\'équilibrage de charge', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(11, 'maintenance_mode', '0', 'boolean', 'maintenance', 'Activer le mode maintenance', '2025-06-06 18:42:43', '2025-06-06 18:42:43'),
(12, 'maintenance_message', 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.', 'text', 'maintenance', 'Message de maintenance', '2025-06-06 18:42:43', '2025-06-06 18:42:43');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `assignee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(2, 9, 'Professeur', 'Développement Web', 'Bâtiment A - Bureau 203', 5, 1, 'Spécialiste en développement web et architectures logicielles'),
(3, 10, 'Docteur', 'Intelligence Artificielle', 'Bâtiment B - Bureau 105', 5, 1, 'Docteur en IA, spécialiste en apprentissage automatique'),
(4, 11, 'Maître de conférences', 'Mathématiques appliquées', 'Bâtiment C - Bureau 310', 3, 1, 'Spécialiste en mathématiques appliquées'),
(5, 12, 'Ingénieur', 'Systèmes embarqués', 'Bâtiment D - Bureau 112', 4, 1, 'Expert en systèmes embarqués et IoT'),
(6, 13, 'Consultant', 'Cybersécurité', 'Bâtiment A - Bureau 208', 3, 1, 'Consultant en cybersécurité et infrastructures'),
(13, 133, 'Professeur', 'Développement Web', 'Bâtiment A - Bureau 204', 5, 1, 'Expert en développement front-end (React, Vue, Angular) et architecture moderne'),
(14, 134, 'Maître de conférences', 'Développement Web', 'Bâtiment A - Bureau 206', 4, 1, 'Spécialiste back-end PHP, Node.js et bases de données NoSQL'),
(15, 135, 'Ingénieur', 'Développement Web', 'Bâtiment A - Bureau 208', 4, 1, 'Architecte logiciel, microservices et performance web'),
(16, 136, 'Docteur', 'Développement Mobile', 'Bâtiment A - Bureau 210', 3, 1, 'Experte en développement iOS (Swift) et applications cross-platform'),
(17, 137, 'Ingénieur', 'Développement Mobile', 'Bâtiment A - Bureau 212', 5, 1, 'Spécialiste Android (Kotlin) et frameworks hybrides (React Native, Flutter)'),
(18, 138, 'Professeur', 'UX/UI Design', 'Bâtiment A - Bureau 214', 4, 1, 'Experte en design d\'interface, accessibilité et recherche utilisateur'),
(19, 139, 'Docteur', 'Machine Learning', 'Bâtiment B - Bureau 102', 4, 1, 'Expert en apprentissage profond, vision par ordinateur et PyTorch'),
(20, 140, 'Professeur', 'Data Science', 'Bâtiment B - Bureau 104', 3, 1, 'Spécialiste en analyse de données, statistiques et visualisation de données'),
(21, 141, 'Docteur', 'NLP', 'Bâtiment B - Bureau 106', 3, 1, 'Expert en traitement du langage naturel et modèles de langage'),
(22, 142, 'Maître de conférences', 'Mathématiques pour l\'IA', 'Bâtiment B - Bureau 108', 5, 1, 'Experte en mathématiques appliquées à l\'IA et optimisation'),
(23, 143, 'Ingénieur', 'MLOps', 'Bâtiment B - Bureau 110', 4, 1, 'Spécialiste en déploiement de modèles et infrastructures ML à grande échelle'),
(24, 144, 'Ingénieur', 'Sécurité offensive', 'Bâtiment A - Bureau 218', 3, 1, 'Experte en tests d\'intrusion, audit de sécurité et sécurité web'),
(25, 145, 'Professeur', 'Cryptographie', 'Bâtiment A - Bureau 220', 4, 1, 'Spécialiste en cryptographie appliquée, PKI et sécurité des protocoles'),
(26, 146, 'Docteur', 'Sécurité défensive', 'Bâtiment A - Bureau 222', 3, 1, 'Experte en détection d\'intrusion, analyse de malware et forensics'),
(27, 147, 'Ingénieur', 'Systèmes embarqués', 'Bâtiment D - Bureau 110', 4, 1, 'Expert en systèmes temps réel, microcontrôleurs et FPGA'),
(28, 148, 'Docteur', 'IoT', 'Bâtiment D - Bureau 114', 3, 1, 'Spécialiste en réseaux de capteurs, protocoles IoT et systèmes basse consommation'),
(29, 149, 'Professeur', 'Systèmes électroniques', 'Bâtiment D - Bureau 116', 4, 1, 'Expert en conception électronique, PCB et systèmes de contrôle'),
(30, 150, 'Ingénieur', 'DevOps', 'Bâtiment C - Bureau 304', 5, 1, 'Experte en CI/CD, containers (Docker, Kubernetes) et automatisation'),
(31, 151, 'Ingénieur', 'Cloud Architecture', 'Bâtiment C - Bureau 306', 4, 1, 'Spécialiste en architectures cloud, serverless et infrastructure as code'),
(32, 152, 'Docteur', 'Blockchain', 'Bâtiment C - Bureau 308', 3, 1, 'Experte en smart contracts, DApps et protocoles blockchain'),
(33, 153, 'Ingénieur', 'Finance décentralisée', 'Bâtiment C - Bureau 312', 3, 1, 'Spécialiste en DeFi, tokenomics et cryptomonnaies'),
(34, 154, 'Ingénieur', 'Réalité virtuelle', 'Bâtiment E - Bureau 202', 3, 1, 'Experte en développement VR, moteurs 3D et interfaces immersives'),
(35, 155, 'Professeur', 'Réalité augmentée', 'Bâtiment E - Bureau 204', 4, 1, 'Spécialiste en AR, vision par ordinateur et expériences interactives'),
(36, 156, 'Docteur', 'Robotique autonome', 'Bâtiment D - Bureau 120', 3, 1, 'Experte en robotique mobile, SLAM et navigation autonome'),
(37, 157, 'Ingénieur', 'Robotique industrielle', 'Bâtiment D - Bureau 122', 4, 1, 'Spécialiste en robotique collaborative, programmation robot et intégration'),
(38, 158, 'Professeur', 'Efficacité énergétique', 'Bâtiment F - Bureau 102', 3, 1, 'Experte en solutions numériques pour l\'optimisation énergétique'),
(39, 159, 'Ingénieur', 'Green IT', 'Bâtiment F - Bureau 104', 4, 1, 'Spécialiste en développement durable, éco-conception logicielle et IT responsable'),
(40, 160, 'Docteur', 'Génomique', 'Bâtiment G - Bureau 102', 3, 1, 'Experte en analyse de données génomiques et algorithmique biologique'),
(41, 161, 'Maître de conférences', 'Bio-informatique structurale', 'Bâtiment G - Bureau 104', 3, 1, 'Spécialiste en modélisation moléculaire et analyse structurale'),
(42, 162, 'Professeur', 'Technologies éducatives', 'Bâtiment H - Bureau 102', 5, 1, 'Experte en plateformes e-learning et pédagogie numérique'),
(43, 163, 'Docteur', 'Learning analytics', 'Bâtiment H - Bureau 104', 4, 1, 'Spécialiste en analytique d\'apprentissage et personnalisation éducative'),
(44, 164, 'Professeur', 'Finance digitale', 'Bâtiment I - Bureau 102', 3, 1, 'Experte en systèmes de paiement, RegTech et conformité financière'),
(45, 165, 'Ingénieur', 'Technologies financières', 'Bâtiment I - Bureau 104', 4, 1, 'Spécialiste en trading algorithmique, InsurTech et modélisation financière');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_preferences`
--

CREATE TABLE `teacher_preferences` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `preference_type` enum('DEPARTMENT','LEVEL','PROGRAM','DOMAIN','COMPANY') NOT NULL,
  `preference_value` varchar(255) NOT NULL,
  `priority_value` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_preferences`
--

INSERT INTO `teacher_preferences` (`id`, `teacher_id`, `preference_type`, `preference_value`, `priority_value`, `created_at`) VALUES
(1, 2, 'DEPARTMENT', 'Informatique', 5, '2025-06-07 09:42:54'),
(2, 2, 'DOMAIN', 'Développement Web', 5, '2025-06-07 09:42:54'),
(3, 3, 'DEPARTMENT', 'Informatique', 4, '2025-06-07 09:42:54'),
(4, 3, 'DOMAIN', 'Intelligence Artificielle', 5, '2025-06-07 09:42:54'),
(5, 4, 'DEPARTMENT', 'Mathématiques', 5, '2025-06-07 09:42:54'),
(6, 5, 'DOMAIN', 'Systèmes Embarqués', 5, '2025-06-07 09:42:54'),
(7, 6, 'DOMAIN', 'Cybersécurité', 5, '2025-06-07 09:42:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','coordinator','teacher','student') NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `department`, `profile_image`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$iQ7C/0pvxFYDHQvZh59P8uvtcSslbiSJyF4frwYSiN6CxzK/nTU1.', 'admin@example.com', 'Admin', 'System', 'admin', NULL, NULL, '2025-06-03 10:43:29', '2025-06-21 18:58:58', '2025-06-21 18:58:58'),
(3, 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin2@example.com', 'Admin', 'Secondaire', 'admin', 'Administration', NULL, '2025-06-07 08:42:49', '2025-06-07 08:42:49', NULL),
(4, 'teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher1@example.com', 'Paul', 'Martin', 'teacher', 'Informatique', NULL, '2025-06-07 08:42:49', '2025-06-07 08:42:49', NULL),
(5, 'teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher2@example.com', 'Marie', 'Dubois', 'teacher', 'Informatique', NULL, '2025-06-07 08:42:49', '2025-06-07 08:42:49', NULL),
(6, 'teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher3@example.com', 'Jean', 'Petit', 'teacher', 'Mathématiques', NULL, '2025-06-07 08:42:49', '2025-06-07 08:42:49', NULL),
(7, 'teacher4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher4@example.com', 'Sophie', 'Bernard', 'teacher', 'Électronique', NULL, '2025-06-07 08:42:49', '2025-06-07 08:42:49', NULL),
(8, 'admin_sample', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_sample@example.com', 'Admin', 'Secondaire', 'admin', 'Administration', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(9, 'prof_martin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof_martin@example.com', 'Paul', 'Martin', 'teacher', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(10, 'prof_dubois', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof_dubois@example.com', 'Marie', 'Dubois', 'teacher', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(11, 'prof_petit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof_petit@example.com', 'Jean', 'Petit', 'teacher', 'Mathématiques', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(12, 'prof_bernard', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof_bernard@example.com', 'Sophie', 'Bernard', 'teacher', 'Électronique', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(13, 'prof_robert', '$2y$10$ZRLuMdm3wselkH92Sv6x4.Rw9ubtgOTPKal2sOY8FT5SnQ4Kgdrp.', 'prof_robert@example.com', 'Thomas', 'Robert', 'teacher', 'Réseaux', NULL, '2025-06-07 09:42:54', '2025-06-21 20:49:21', '2025-06-21 20:49:21'),
(14, 'etud_dupont', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etud_dupont@example.com', 'Lucas', 'Dupont', 'student', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(15, 'etud_leroy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etud_leroy@example.com', 'Emma', 'Leroy', 'student', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(16, 'etud_moreau', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etud_moreau@example.com', 'Louis', 'Moreau', 'student', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-07 09:42:54', NULL),
(17, 'etud_fournier', '$2y$10$PDqgdfSLyx9eDLFbi/lbR.2XOrNQBmvQ5oQv5P1OaBCPX9l3FRxty', 'etud_fournier@example.com', 'Chloé', 'Fournier', 'student', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-10 10:04:00', NULL),
(18, 'etud_simon', '$2y$10$uPZ35WZl2io5x6eon6JN6OSKM.GZNg3XMzc4mDIImn3TY9vcOg7K6', 'etud_simon@example.com', 'Hugo', 'Simon', 'student', 'Informatique', NULL, '2025-06-07 09:42:54', '2025-06-21 20:54:13', '2025-06-21 20:54:13'),
(50, 'coordo', '$2y$10$hD8jR6DTr7T8y1K3F7Ojs.WWQH6MTmXdmvEYrN/ma2crI9p/NpHJe', 'coordo@gmail.com', 'Coordo', 'Dansia', 'coordinator', '', NULL, '2025-06-08 19:11:39', '2025-06-12 08:54:16', '2025-06-12 08:54:16'),
(51, 'hbonnet17', '$2y$10$Tpbea7gOI3fpNJkZCoPHfOSn8Kil//DT.pvcg0.FdF6MFXQP6bz6q', 'hbonnet17@example.com', 'Hugo', 'Bonnet', 'student', 'Économie', NULL, '2025-06-10 06:44:57', '2025-06-10 06:44:57', NULL),
(52, 'gfournier15', '$2y$10$BUBawhQhtIbTS9kARJi.AeBBiAr7VkN/msHAONtxFvIXquPCqHX1G', 'gfournier15@example.com', 'Gabriel', 'Fournier', 'student', 'Médecine', NULL, '2025-06-10 06:44:57', '2025-06-10 06:44:57', NULL),
(53, 'jgirard60', '$2y$10$ZOXMWgrQnvWa0BE2HmPyXefLZV/saAEzMk0sGK974NHvrnocilbuy', 'jgirard60@example.com', 'Juliette', 'Girard', 'student', 'Chimie', NULL, '2025-06-10 06:44:57', '2025-06-10 06:44:57', NULL),
(54, 'jbernard43', '$2y$10$4SBctLpBapgE5wptrkFq9.8Qq6z5/Ifyl./FXefvZzvjJ6qGL8OXi', 'jbernard43@example.com', 'Juliette', 'Bernard', 'student', 'Langues', NULL, '2025-06-10 06:44:57', '2025-06-10 06:44:57', NULL),
(55, 'jdavid23', '$2y$10$IT6D/MG4DQMdBUaE5D6K7es/gegOv8rMfbYqG8GDJjMhBniemToay', 'jdavid23@example.com', 'Jade', 'David', 'student', 'Médecine', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(56, 'efournier1', '$2y$10$s81k2UbIQepl6mULKtzTM.LJKEP8sJPqyGLFhYFNO6thuS4VYwSM.', 'efournier1@example.com', 'Emma', 'Fournier', 'student', 'Informatique', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(57, 'tandré28', '$2y$10$IIjYAqVpMum4J43LnA384.YA29HcDEzheNrxuUCnUfem7DLNKkS66', 'tandré28@example.com', 'Thomas', 'André', 'student', 'Physique', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(58, 'elefebvre61', '$2y$10$NAGDnClRKIHA6JsPNC02i.Cy4G2FHRvLsdVHsFCeNXLac7cUIouEm', 'elefebvre61@example.com', 'Emma', 'Lefebvre', 'student', 'Droit', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(59, 'tgarcia10', '$2y$10$mjte6ZS11Ws2.7mjG43GuehvbkgZDmK.RKLG8ei2Y5k7XAGaV2PD2', 'tgarcia10@example.com', 'Théo', 'Garcia', 'student', 'Droit', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(60, 'smorel64', '$2y$10$6I24Al/.EH9UM9d0WVOlj.APOj4Xm4dvqlTFcINZx6.TcF/icwZdK', 'smorel64@example.com', 'Sophie', 'Morel', 'student', 'Gestion', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(61, 'jbernard42', '$2y$10$gMk8xMQv9ZZnM0SGzEdnuezqVWHR6VeD2GknLRrCOfTbucudyfkHi', 'jbernard42@example.com', 'Juliette', 'Bernard', 'student', 'Droit', NULL, '2025-06-10 06:44:58', '2025-06-10 06:44:58', NULL),
(62, 'edurand60', '$2y$10$m0D81tj5yYW5R4TvzYtgC.7ti4OyeGgei2buGrY3A3dJ.velaLQnS', 'edurand60@example.com', 'Emma', 'Durand', 'student', 'Langues', NULL, '2025-06-10 06:44:59', '2025-06-10 06:44:59', NULL),
(63, 'efrançois88', '$2y$10$N/Ec9EHEqWaZB7hvasG3.u5llTUM3XwdDZIRlZAyVqFQNV54Y6wZu', 'efrançois88@example.com', 'Emma', 'François', 'student', 'Informatique', NULL, '2025-06-10 06:44:59', '2025-06-10 06:44:59', NULL),
(64, 'lrobert71', '$2y$10$avkRMuYDpKorqN6vw6g7Ke6PsDE4ovev1LOHzLVZcHT3Zs9JWnKDO', 'lrobert71@example.com', 'Léa', 'Robert', 'student', 'Économie', NULL, '2025-06-10 06:44:59', '2025-06-10 06:44:59', NULL),
(65, 'jmercier26', '$2y$10$aElyTrDzAyk9e6CBZGd3uuz9/ezCxHyMmpAwJ0tZ3R04pRFnVk0TC', 'jmercier26@example.com', 'Jade', 'Mercier', 'student', 'Mathématiques', NULL, '2025-06-10 06:44:59', '2025-06-10 06:44:59', NULL),
(66, 'lmoreau90', '$2y$10$H3EdDVH5AEaMmisFshZ8GOB7tMEX6sANqEhnrPfwfOJ.jY4Z950DO', 'lmoreau90@example.com', 'Lina', 'Moreau', 'student', 'Économie', NULL, '2025-06-10 06:44:59', '2025-06-10 06:44:59', NULL),
(67, 'ebonnet42', '$2y$10$Lim3g3RulMnRtxhHaZQ2j.R.Smb.dsAyp3G6gXPyIB5PVIp2CoZDG', 'ebonnet42@example.com', 'Emma', 'Bonnet', 'student', 'Droit', NULL, '2025-06-10 06:44:59', '2025-06-10 06:44:59', NULL),
(68, 'llefevre51', '$2y$10$Jte6mE1XhCVZtUQijRVwd.nkLjgdJpgivPfyV0Bs5eUcEpULKCrne', 'llefevre51@example.com', 'Léa', 'Lefevre', 'student', 'Chimie', NULL, '2025-06-10 06:45:00', '2025-06-10 06:45:00', NULL),
(69, 'rrichard89', '$2y$10$IB6WDrLhZewIMG2lRvjNtujJ5SvbnnYbz6uGDbKvMiFe1a/5HNL4C', 'rrichard89@example.com', 'Raphaël', 'Richard', 'student', 'Informatique', NULL, '2025-06-10 06:45:00', '2025-06-10 06:45:00', NULL),
(70, 'zbonnet73', '$2y$10$GarMvQwjBOOyipm.JQELkOquqg3ydCJn3uOij.smkSTFUNTPjmoQy', 'zbonnet73@example.com', 'Zoé', 'Bonnet', 'student', 'Informatique', NULL, '2025-06-10 06:45:00', '2025-06-10 06:45:00', NULL),
(73, 'jmercier62', '$2y$10$w3aMNtoRLocnNp3QBkuT4.qwCf.oFFmWY2aBoHy8szCZfYJilZfke', 'jmercier62@example.com', 'Jade', 'Mercier', 'student', 'Chimie', NULL, '2025-06-10 06:53:01', '2025-06-10 06:53:01', NULL),
(74, 'vrobert72', '$2y$10$5OOupPq4/DmyWgLZK0Vc9eaQq5RtySNVYePiC7XbwOwoD6akASiy.', 'vrobert72@example.com', 'Victor', 'Robert', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:01', '2025-06-10 06:53:01', NULL),
(75, 'jmartinez94', '$2y$10$WAo94K4aiYzuZMdpRalM6usSVKSR3MIpB45ADaXGKv4wZz88b6PvK', 'jmartinez94@example.com', 'Jade', 'Martinez', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:02', '2025-06-10 06:53:02', NULL),
(76, 'llaurent37', '$2y$10$P4WUc0Wk/xSuf0zD9w8zmuQkk1KJt.NuSqLgDFy8308cjgYT3Xj7y', 'llaurent37@example.com', 'Lucie', 'Laurent', 'student', 'Gestion', NULL, '2025-06-10 06:53:02', '2025-06-10 06:53:02', NULL),
(77, 'arobert51', '$2y$10$zMxHlQNBWPAB6tBlEpfAXelzce0DisMuxG3uIQez/LAHn0Lz5SZT.', 'arobert51@example.com', 'Arthur', 'Robert', 'student', 'Médecine', NULL, '2025-06-10 06:53:02', '2025-06-10 06:53:02', NULL),
(78, 'vfrançois76', '$2y$10$rh6qeuzfCQ6Xbl6ZB4FvneYnnzB/zFmPEFTqNOUmjJMWluyUxfw1O', 'vfrançois76@example.com', 'Victor', 'François', 'student', 'Informatique', NULL, '2025-06-10 06:53:02', '2025-06-10 06:53:02', NULL),
(79, 'mandré91', '$2y$10$JVU.WeeZXfzaI8QbtjPZiOOyShG3ZIDSjxA3ZVyKj596UfB98dha6', 'mandré91@example.com', 'Manon', 'André', 'student', 'Économie', NULL, '2025-06-10 06:53:03', '2025-06-10 06:53:03', NULL),
(80, 'cmartinez65', '$2y$10$apR2PVcnpxiM.k8XhLVJBOU7YF80CUctxdt6.VuMYM25z6jLaZXna', 'cmartinez65@example.com', 'Camille', 'Martinez', 'student', 'Médecine', NULL, '2025-06-10 06:53:03', '2025-06-10 06:53:03', NULL),
(81, 'cpetit85', '$2y$10$TuOPsu1jdXmGHOcNT6VOXejcjz/UVFV2bLrjO82C6JxWplTe.oY2a', 'cpetit85@example.com', 'Chloé', 'Petit', 'student', 'Biologie', NULL, '2025-06-10 06:53:03', '2025-06-10 06:53:03', NULL),
(82, 'ileroy18', '$2y$10$mB8vQhdnKGRW1dSEulQUpuxA13Qtvh5kLQhEnq7tN34pF6iySyVOW', 'ileroy18@example.com', 'Inès', 'Leroy', 'student', 'Langues', NULL, '2025-06-10 06:53:03', '2025-06-10 06:53:03', NULL),
(83, 'nmartin74', '$2y$10$fULp9BT19ueXPD0Y3MTL9OXrfAPoE7wLoZ1eV7WoBU00ntcEL6iaa', 'nmartin74@example.com', 'Noah', 'Martin', 'student', 'Gestion', NULL, '2025-06-10 06:53:03', '2025-06-10 06:53:03', NULL),
(84, 'tbonnet30', '$2y$10$n8TvdSCK8vPYWGrGx1zJnOtbz1FqJSfQnb4pRRen07QysyTzgcLci', 'tbonnet30@example.com', 'Théo', 'Bonnet', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:03', '2025-06-10 06:53:03', NULL),
(85, 'nlaurent3', '$2y$10$M9dYs65CjVHtZq6O4BpK4eLwx.5epf4dP5FpeJ31OLg9w0MHwhSkK', 'nlaurent3@example.com', 'Nathan', 'Laurent', 'student', 'Gestion', NULL, '2025-06-10 06:53:04', '2025-06-10 06:53:04', NULL),
(86, 'hlefebvre76', '$2y$10$LgOHFi3Slgyk1QAfqkL5SesaGYMcyl6hy7fdg6ayCOaLjKN2r5R5u', 'hlefebvre76@example.com', 'Hugo', 'Lefebvre', 'student', 'Médecine', NULL, '2025-06-10 06:53:04', '2025-06-10 06:53:04', NULL),
(87, 'esimon94', '$2y$10$hJPK97T9Dc.3rPoAhLBp6uJ9xymp6S6l7xUEN5u6dbhfnoEXhzro.', 'esimon94@example.com', 'Ethan', 'Simon', 'student', 'Médecine', NULL, '2025-06-10 06:53:04', '2025-06-10 06:53:04', NULL),
(88, 'jbertrand40', '$2y$10$HnlN4xC2Zm9jxhDoDU8S1.g7MKFtNoVU/PgJkhr9mx0MT/NBsUwpy', 'jbertrand40@example.com', 'Juliette', 'Bertrand', 'student', 'Médecine', NULL, '2025-06-10 06:53:04', '2025-06-10 06:53:04', NULL),
(89, 'efournier6', '$2y$10$wASmp3wXN20J7aZ.Uey9OeHo4IYYlVvZ6/deDisb1DPBYHk/lcXDK', 'efournier6@example.com', 'Eva', 'Fournier', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:05', '2025-06-10 06:53:05', NULL),
(90, 'imichel99', '$2y$10$Czl2RatWYDBqKuPskF2AuenlNTtrajQhNQRdes4kS1VUIwo/kxOo.', 'imichel99@example.com', 'Inès', 'Michel', 'student', 'Gestion', NULL, '2025-06-10 06:53:05', '2025-06-10 06:53:05', NULL),
(91, 'cdurand22', '$2y$10$5gqnjntBD6CengZ2q8V9MOMG.8uuieQ7ZD49q47gdL4gfXYhVUZdS', 'cdurand22@example.com', 'Camille', 'Durand', 'student', 'Gestion', NULL, '2025-06-10 06:53:05', '2025-06-10 06:53:05', NULL),
(92, 'lrichard58', '$2y$10$dBU4/m9BMoSAx4oo3k0nwOhzR7oT.oOYrfIOLqGKJb/vQV3Ayo242', 'lrichard58@example.com', 'Léa', 'Richard', 'student', 'Gestion', NULL, '2025-06-10 06:53:05', '2025-06-10 06:53:05', NULL),
(93, 'gmartinez81', '$2y$10$tDzGORKqsRVMnA1uwGb3F.WPh8U92C7/PwrhtuIMLTqj0DRRy2EP2', 'gmartinez81@example.com', 'Gabriel', 'Martinez', 'student', 'Langues', NULL, '2025-06-10 06:53:21', '2025-06-10 06:53:21', NULL),
(94, 'lbernard26', '$2y$10$TqkKtrAqPGCKfWonxywO6.DNnrF4FGutu1aUy96vTH58gC0OOcoAK', 'lbernard26@example.com', 'Léa', 'Bernard', 'student', 'Droit', NULL, '2025-06-10 06:53:21', '2025-06-10 06:53:21', NULL),
(95, 'ebertrand49', '$2y$10$4OvgjTDVfcB0RICyyqk2GO5/KE.9HFrd3mFmI8gpf3ra7pIphrAm.', 'ebertrand49@example.com', 'Eva', 'Bertrand', 'student', 'Langues', NULL, '2025-06-10 06:53:21', '2025-06-10 06:53:21', NULL),
(96, 'mrobert81', '$2y$10$lRWnVOJ44A9poI5xM8CN2uLCqf.SuqYgUG0niNJbd28ysB4FRdf9q', 'mrobert81@example.com', 'Manon', 'Robert', 'student', 'Langues', NULL, '2025-06-10 06:53:22', '2025-06-10 06:53:22', NULL),
(97, 'edurand80', '$2y$10$64p8KJhUX8gw3THW21M6HOqqT8BQUWFx2itqdiQYXpiJkDSuzDcGe', 'edurand80@example.com', 'Ethan', 'Durand', 'student', 'Chimie', NULL, '2025-06-10 06:53:22', '2025-06-10 06:53:22', NULL),
(98, 'lbertrand17', '$2y$10$7Mq.HflFKyxtjNzRC53Iae2gEMFhDAT0XjI41MGSjYtXfZuUZir3q', 'lbertrand17@example.com', 'Lina', 'Bertrand', 'student', 'Informatique', NULL, '2025-06-10 06:53:22', '2025-06-10 06:53:22', NULL),
(99, 'aleroy66', '$2y$10$l36xBHR7AzHQUEv5OoNksunBQVX1ISTAYtuU1fRN9FyGLD5k4f5G6', 'aleroy66@example.com', 'Adam', 'Leroy', 'student', 'Chimie', NULL, '2025-06-10 06:53:22', '2025-06-10 06:53:22', NULL),
(100, 'lleroy97', '$2y$10$dLT25e/hEQoaxuO8z751CuebTqEyjACZRqaH.230wn6JA8iWtLuK.', 'lleroy97@example.com', 'Léa', 'Leroy', 'student', 'Économie', NULL, '2025-06-10 06:53:22', '2025-06-10 06:53:22', NULL),
(101, 'erobert68', '$2y$10$Rb/kdFnXU415mTTpaTHQ1.YWhsA9AMOQEzfW8dYldZm5RoitN2Avm', 'erobert68@example.com', 'Ethan', 'Robert', 'student', 'Physique', NULL, '2025-06-10 06:53:22', '2025-06-10 06:53:22', NULL),
(102, 'llaurent42', '$2y$10$4JackRw2vCbSZsZgN.G6MOKlaBGn/M7gBc2MGhyY/rW5eVQJ5hDJu', 'llaurent42@example.com', 'Lucie', 'Laurent', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(103, 'elefebvre91', '$2y$10$RS69DvLLfRujbStfCIbwBe5NvgfYqHMyeZUojEFKTI95tLyo3F44G', 'elefebvre91@example.com', 'Ethan', 'Lefebvre', 'student', 'Droit', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(104, 'nbertrand77', '$2y$10$KlMBGndzGvWZxiGSB5mN5OefEslj62ALHgijGAs5Vz.SC52JxARDK', 'nbertrand77@example.com', 'Noah', 'Bertrand', 'student', 'Chimie', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(105, 'gfournier62', '$2y$10$8M/yztyp.mtWY1D3w8iCaedRZvQe4dEJMlXaiPmhHo3LjTOAkTDpa', 'gfournier62@example.com', 'Gabriel', 'Fournier', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(106, 'jmartinez5', '$2y$10$pn4OG4OEmJYSGSZR5iYq7eSjEnkkT1thb3mVj.ZUYCcp/RWBEjbLm', 'jmartinez5@example.com', 'Juliette', 'Martinez', 'student', 'Informatique', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(107, 'jmorel40', '$2y$10$gU/0hPt5mpE6dLeUWboAweRbBhh21EwythKnzh3q4mdUo5jGvpmly', 'jmorel40@example.com', 'Juliette', 'Morel', 'student', 'Chimie', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(108, 'mdupont84', '$2y$10$f9cKUXzZOXAPeHMXL1bDQeCtzxPM9xFbhYVwE0Vto2ymiqdPvoiBK', 'mdupont84@example.com', 'Manon', 'Dupont', 'student', 'Droit', NULL, '2025-06-10 06:53:23', '2025-06-10 06:53:23', NULL),
(109, 'zmoreau50', '$2y$10$GKRagZJVWG66GScMFwtaKub9nap5Qvw4G4y08yGKnEvJp46LPDCzi', 'zmoreau50@example.com', 'Zoé', 'Moreau', 'student', 'Droit', NULL, '2025-06-10 06:53:24', '2025-06-10 06:53:24', NULL),
(110, 'ethomas24', '$2y$10$Oj/my8mkDf6ljJVZX0jys.YSlOFbDqTENKY/W4Zt7THNlNPE9aVLW', 'ethomas24@example.com', 'Eva', 'Thomas', 'student', 'Gestion', NULL, '2025-06-10 06:53:24', '2025-06-10 06:53:24', NULL),
(111, 'cmartinez2', '$2y$10$cITN44RprzagQc7cjop.xe0PPcY53HERK9cHswcSp8XY3rOC.xf4m', 'cmartinez2@example.com', 'Charlotte', 'Martinez', 'student', 'Informatique', NULL, '2025-06-10 06:53:24', '2025-06-10 06:53:24', NULL),
(112, 'cpetit73', '$2y$10$fpjZhxadakRaYaXMbjLg1u5h1jZxvrMOs9wkBAfl.mvDjsxwnA23u', 'cpetit73@example.com', 'Camille', 'Petit', 'student', 'Gestion', NULL, '2025-06-10 06:53:24', '2025-06-10 06:53:24', NULL),
(113, 'cmercier22', '$2y$10$Iyy9nk1luGbC0wp6WFj2e.5flRpv53rIXpNceb9PfSUV62uLZJ1U.', 'cmercier22@example.com', 'Camille', 'Mercier', 'student', 'Droit', NULL, '2025-06-10 06:53:25', '2025-06-10 06:53:25', NULL),
(114, 'lmorel67', '$2y$10$cFKhbkblpAOZoToFL1Xo1.05zxzYOF76FnPXqNRk6JHOe5aAHm0Cu', 'lmorel67@example.com', 'Lucas', 'Morel', 'student', 'Chimie', NULL, '2025-06-10 06:53:26', '2025-06-10 06:53:26', NULL),
(115, 'ebernard94', '$2y$10$WJhUncM3ECy6L57OMBTJbOyEqWyBYw.HHOAMVN6tn8WVOsR9LjV1S', 'ebernard94@example.com', 'Ethan', 'Bernard', 'student', 'Chimie', NULL, '2025-06-10 06:53:26', '2025-06-10 06:53:26', NULL),
(116, 'emartinez58', '$2y$10$lKnZ5YvqNLsPIZ6Yda3BleDCPQkXGNUQyj7LeMbV68qwOMM3o9Xje', 'emartinez58@example.com', 'Emma', 'Martinez', 'student', 'Biologie', NULL, '2025-06-10 06:53:26', '2025-06-10 06:53:26', NULL),
(117, 'arichard89', '$2y$10$0ry69v1jV8ao3xyr4gAYEehL/MNMs9DBZZtu.ivzg2S0zJ4F.oQxW', 'arichard89@example.com', 'Alexandre', 'Richard', 'student', 'Droit', NULL, '2025-06-10 06:53:26', '2025-06-10 06:53:26', NULL),
(118, 'hlefevre47', '$2y$10$WVYN9GshqinVKffz26VWpuxPsfCFrbddQKdMBanuc4dki7i6gs5Pe', 'hlefevre47@example.com', 'Hugo', 'Lefevre', 'student', 'Informatique', NULL, '2025-06-10 06:53:26', '2025-06-10 06:53:26', NULL),
(119, 'lsimon67', '$2y$10$x0Rdrww1VLW2EDkwPKYcVO.OQMTt2kOfkGNESBGH7Zn3QAQOMeI2G', 'lsimon67@example.com', 'Léa', 'Simon', 'student', 'Économie', NULL, '2025-06-10 06:53:26', '2025-06-10 06:53:26', NULL),
(120, 'lbertrand40', '$2y$10$q/iWxGpf7N63qJfwmnsLZeI7vdubHLhzOka7HXFyNeAefsiA2CDda', 'lbertrand40@example.com', 'Lucas', 'Bertrand', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:27', '2025-06-10 06:53:27', NULL),
(121, 'jdavid81', '$2y$10$tpmLSHfyXvfzWkPiBq8BX.rKDqT4C0NxTQzoxT9aHlVhEpxE1Dlqu', 'jdavid81@example.com', 'Juliette', 'David', 'student', 'Informatique', NULL, '2025-06-10 06:53:27', '2025-06-10 06:53:27', NULL),
(122, 'amorel10', '$2y$10$hmfNDLlN3iNHoKFbthuxQe1i9EPfuMorfjzN8kxmNHATx4JZBapTS', 'amorel10@example.com', 'Adam', 'Morel', 'student', 'Droit', NULL, '2025-06-10 06:53:27', '2025-06-10 06:53:27', NULL),
(123, 'jpetit36', '$2y$10$/MMkwYl0uZsQVzgcJXYxmu4MVies5awA6.s4aATUda5jMGczd20O2', 'jpetit36@example.com', 'Jules', 'Petit', 'student', 'Médecine', NULL, '2025-06-10 06:53:27', '2025-06-10 06:53:27', NULL),
(124, 'emercier80', '$2y$10$71seB2maEDtpT2g0T4UxPuGoz3TR0gbo.0Fw2pQMw7E81PhzJ62Pm', 'emercier80@example.com', 'Eva', 'Mercier', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:27', '2025-06-10 06:53:27', NULL),
(125, 'nfrançois26', '$2y$10$PS4iQAFpOzGuJwCPTFKYUeYKx0IKz.8hGgGhQY40EPI6qBtIzgWAi', 'nfrançois26@example.com', 'Noah', 'François', 'student', 'Physique', NULL, '2025-06-10 06:53:27', '2025-06-10 06:53:27', NULL),
(126, 'tpetit40', '$2y$10$FerYHKhwpQ3jCCCohoaI1.t0zvkvFM5fXjzVa.Ohvlg4MCLYufKBW', 'tpetit40@example.com', 'Théo', 'Petit', 'student', 'Mathématiques', NULL, '2025-06-10 06:53:28', '2025-06-10 06:53:28', NULL),
(127, 'lfrançois41', '$2y$10$GZZ96OCN23asKxeFsWjq.OVGVh/y8R9bfQYBpRBw8iHhgFGS/2XxW', 'lfrançois41@example.com', 'Louis', 'François', 'student', 'Langues', NULL, '2025-06-10 06:53:28', '2025-06-10 06:53:28', NULL),
(128, 'ebertrand62', '$2y$10$YSXSCWVlyflODL/oON8nkO9pU10snEWDnQV6ocIZMhkL80BZZrSQq', 'ebertrand62@example.com', 'Eva', 'Bertrand', 'student', 'Médecine', NULL, '2025-06-10 06:53:28', '2025-06-10 06:53:28', NULL),
(129, 'nsimon71', '$2y$10$9vGrsUaGcnArwoeO80Zt9uSd0giBAV3oDvdxIjqEzG864ZCa0q5Kq', 'nsimon71@example.com', 'Nathan', 'Simon', 'student', 'Langues', NULL, '2025-06-10 06:53:28', '2025-06-10 06:53:28', NULL),
(130, 'rthomas55', '$2y$10$BcoDOb5XNk.E41YlmH5C5OzCsq9fMnMoCHeYmbNV8u76LmDyd1P8e', 'rthomas55@example.com', 'Raphaël', 'Thomas', 'student', 'Médecine', NULL, '2025-06-10 06:53:28', '2025-06-10 06:53:28', NULL),
(131, 'mdupont90', '$2y$10$Fp9nP3MN2JwFEZxXW0WIsuIO5ahCpnYLqYFVCOsJG0XJ9sO4R9vJe', 'mdupont90@example.com', 'Manon', 'Dupont', 'student', 'Droit', NULL, '2025-06-10 06:53:28', '2025-06-10 06:53:28', NULL),
(132, 'nlambert94', '$2y$10$Sp13l4YjFKzcgBNz5QD5wukJleFIbjT4wLS97v1Le8nan3PAUmTYS', 'nlambert94@example.com', 'Nathan', 'Lambert', 'student', 'Physique', NULL, '2025-06-10 06:53:29', '2025-06-10 06:53:29', NULL),
(133, 'francois.durand', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'francois.durand@example.com', 'François', 'Durand', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(134, 'nathalie.rousseau', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nathalie.rousseau@example.com', 'Nathalie', 'Rousseau', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(135, 'jacques.moreau', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jacques.moreau@example.com', 'Jacques', 'Moreau', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(136, 'stephanie.lambert', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'stephanie.lambert@example.com', 'Stéphanie', 'Lambert', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(137, 'marc.dupont', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'marc.dupont@example.com', 'Marc', 'Dupont', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(138, 'laurence.girard', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'laurence.girard@example.com', 'Laurence', 'Girard', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(139, 'thomas.robert', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'thomas.robert@example.com', 'Thomas', 'Robert', 'teacher', 'Intelligence Artificielle', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(140, 'emilie.simon', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'emilie.simon@example.com', 'Émilie', 'Simon', 'teacher', 'Intelligence Artificielle', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(141, 'nicolas.leroy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nicolas.leroy@example.com', 'Nicolas', 'Leroy', 'teacher', 'Intelligence Artificielle', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(142, 'claire.michel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'claire.michel@example.com', 'Claire', 'Michel', 'teacher', 'Mathématiques Appliquées', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(143, 'pierre.martinez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pierre.martinez@example.com', 'Pierre', 'Martinez', 'teacher', 'Intelligence Artificielle', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(144, 'sophie.nguyen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sophie.nguyen@example.com', 'Sophie', 'Nguyen', 'teacher', 'Cybersécurité', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(145, 'antoine.dubois', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'antoine.dubois@example.com', 'Antoine', 'Dubois', 'teacher', 'Cybersécurité', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(146, 'celine.lefevre', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'celine.lefevre@example.com', 'Céline', 'Lefèvre', 'teacher', 'Cybersécurité', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(147, 'julien.richard', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'julien.richard@example.com', 'Julien', 'Richard', 'teacher', 'Électronique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(148, 'audrey.thomas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'audrey.thomas@example.com', 'Audrey', 'Thomas', 'teacher', 'Électronique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(149, 'bruno.fournier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bruno.fournier@example.com', 'Bruno', 'Fournier', 'teacher', 'Électronique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(150, 'helene.petit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'helene.petit@example.com', 'Hélène', 'Petit', 'teacher', 'Infrastructure Cloud', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(151, 'vincent.morel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vincent.morel@example.com', 'Vincent', 'Morel', 'teacher', 'Infrastructure Cloud', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(152, 'laure.faure', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'laure.faure@example.com', 'Laure', 'Faure', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(153, 'eric.blanc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eric.blanc@example.com', 'Éric', 'Blanc', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(154, 'caroline.gautier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caroline.gautier@example.com', 'Caroline', 'Gautier', 'teacher', 'Audiovisuel', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(155, 'mathieu.bonnet', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mathieu.bonnet@example.com', 'Mathieu', 'Bonnet', 'teacher', 'Informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(156, 'nadia.robin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nadia.robin@example.com', 'Nadia', 'Robin', 'teacher', 'Robotique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(157, 'lucas.chevalier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lucas.chevalier@example.com', 'Lucas', 'Chevalier', 'teacher', 'Robotique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(158, 'sabine.laurent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sabine.laurent@example.com', 'Sabine', 'Laurent', 'teacher', 'Développement Durable', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(159, 'remi.mercier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'remi.mercier@example.com', 'Rémi', 'Mercier', 'teacher', 'Développement Durable', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(160, 'valerie.roux', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'valerie.roux@example.com', 'Valérie', 'Roux', 'teacher', 'Bio-informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(161, 'hugo.garnier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hugo.garnier@example.com', 'Hugo', 'Garnier', 'teacher', 'Bio-informatique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(162, 'marie.rey', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'marie.rey@example.com', 'Marie', 'Rey', 'teacher', 'Pédagogie Numérique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(163, 'olivier.colin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'olivier.colin@example.com', 'Olivier', 'Colin', 'teacher', 'Pédagogie Numérique', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(164, 'amandine.guerin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'amandine.guerin@example.com', 'Amandine', 'Guérin', 'teacher', 'Finance', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL),
(165, 'adrien.masson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'adrien.masson@example.com', 'Adrien', 'Masson', 'teacher', 'Finance', NULL, '2025-06-10 10:01:43', '2025-06-10 10:01:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_login_history`
--

CREATE TABLE `user_login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL DEFAULT 'success',
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_login_history`
--

INSERT INTO `user_login_history` (`id`, `user_id`, `login_date`, `ip_address`, `user_agent`, `device`, `status`, `details`) VALUES
(27, 1, '2025-05-22 21:26:05', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(28, 1, '2025-05-27 21:26:05', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(29, 1, '2025-06-01 21:26:05', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(30, 1, '2025-06-05 21:26:05', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(31, 8, '2025-05-25 21:26:05', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(32, 8, '2025-05-29 21:26:05', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(33, 8, '2025-06-03 21:26:05', '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', 'Safari sur iPhone', 'success', NULL),
(34, 13, '2025-05-23 21:26:05', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(35, 13, '2025-05-30 21:26:05', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(36, 13, '2025-06-04 21:26:05', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(37, 14, '2025-05-24 21:26:05', '192.168.1.104', 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 Mobile Safari/537.36', 'Chrome sur Android', 'success', NULL),
(38, 14, '2025-05-31 21:26:05', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'failed', 'Mot de passe incorrect'),
(39, 14, '2025-05-31 21:26:05', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'success', NULL),
(40, 1, '2025-05-22 21:45:16', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(41, 1, '2025-05-27 21:45:16', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(42, 1, '2025-06-01 21:45:16', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(43, 1, '2025-06-05 21:45:16', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(44, 8, '2025-05-25 21:45:16', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(45, 8, '2025-05-29 21:45:16', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(46, 8, '2025-06-03 21:45:16', '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', 'Safari sur iPhone', 'success', NULL),
(47, 13, '2025-05-23 21:45:16', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(48, 13, '2025-05-30 21:45:16', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(49, 13, '2025-06-04 21:45:16', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(50, 14, '2025-05-24 21:45:16', '192.168.1.104', 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 Mobile Safari/537.36', 'Chrome sur Android', 'success', NULL),
(51, 14, '2025-05-31 21:45:16', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'failed', 'Mot de passe incorrect'),
(52, 14, '2025-05-31 21:45:16', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'success', NULL),
(53, 1, '2025-05-22 22:08:43', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(54, 1, '2025-05-27 22:08:43', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(55, 1, '2025-06-01 22:08:43', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(56, 1, '2025-06-05 22:08:43', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(57, 8, '2025-05-25 22:08:43', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(58, 8, '2025-05-29 22:08:43', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(59, 8, '2025-06-03 22:08:43', '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', 'Safari sur iPhone', 'success', NULL),
(60, 13, '2025-05-23 22:08:43', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(61, 13, '2025-05-30 22:08:43', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(62, 13, '2025-06-04 22:08:43', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(63, 14, '2025-05-24 22:08:43', '192.168.1.104', 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 Mobile Safari/537.36', 'Chrome sur Android', 'success', NULL),
(64, 14, '2025-05-31 22:08:43', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'failed', 'Mot de passe incorrect'),
(65, 14, '2025-05-31 22:08:43', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'success', NULL),
(66, 1, '2025-05-22 22:08:50', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(67, 1, '2025-05-27 22:08:50', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(68, 1, '2025-06-01 22:08:50', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(69, 1, '2025-06-05 22:08:50', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'Chrome sur Windows', 'success', NULL),
(70, 8, '2025-05-25 22:08:50', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(71, 8, '2025-05-29 22:08:50', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15', 'Safari sur macOS', 'success', NULL),
(72, 8, '2025-06-03 22:08:50', '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', 'Safari sur iPhone', 'success', NULL),
(73, 13, '2025-05-23 22:08:50', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(74, 13, '2025-05-30 22:08:50', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(75, 13, '2025-06-04 22:08:50', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0', 'Firefox sur Windows', 'success', NULL),
(76, 14, '2025-05-24 22:08:50', '192.168.1.104', 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 Mobile Safari/537.36', 'Chrome sur Android', 'success', NULL),
(77, 14, '2025-05-31 22:08:50', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'failed', 'Mot de passe incorrect'),
(78, 14, '2025-05-31 22:08:50', '192.168.1.105', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', 'Chrome sur macOS', 'success', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`, `created_at`, `updated_at`) VALUES
(111, 1, 'theme', 'light', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(112, 1, 'primary_color', 'blue', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(113, 1, 'font_size', '100', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(114, 1, 'notification_frequency', 'realtime', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(115, 8, 'theme', 'dark', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(116, 8, 'primary_color', 'green', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(117, 8, 'animations_enabled', 'true', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(118, 13, 'theme', 'system', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(119, 13, 'primary_color', 'purple', '2025-06-06 22:31:37', '2025-06-06 22:31:37'),
(120, 13, 'font_size', '110', '2025-06-06 22:31:37', '2025-06-06 22:31:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `algorithm_executions`
--
ALTER TABLE `algorithm_executions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parameters_id` (`parameters_id`),
  ADD KEY `executed_by` (`executed_by`);

--
-- Indexes for table `algorithm_parameters`
--
ALTER TABLE `algorithm_parameters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_assignment` (`student_id`,`internship_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conversation_user` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_assignment_id` (`assignment_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `evaluator_id` (`evaluator_id`),
  ADD KEY `evaluatee_id` (`evaluatee_id`);

--
-- Indexes for table `internships`
--
ALTER TABLE `internships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `internship_skills`
--
ALTER TABLE `internship_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `meeting_participants`
--
ALTER TABLE `meeting_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meeting_user` (`meeting_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_preferences`
--
ALTER TABLE `student_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_internship_preference` (`student_id`,`internship_id`),
  ADD KEY `internship_id` (`internship_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `creator_id` (`creator_id`),
  ADD KEY `assignee_id` (`assignee_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_preferences`
--
ALTER TABLE `teacher_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_login_history`
--
ALTER TABLE `user_login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_preference` (`user_id`,`preference_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `algorithm_executions`
--
ALTER TABLE `algorithm_executions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `algorithm_parameters`
--
ALTER TABLE `algorithm_parameters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1018;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `internships`
--
ALTER TABLE `internships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=232;

--
-- AUTO_INCREMENT for table `internship_skills`
--
ALTER TABLE `internship_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `meeting_participants`
--
ALTER TABLE `meeting_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `message_recipients`
--
ALTER TABLE `message_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `student_preferences`
--
ALTER TABLE `student_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `teacher_preferences`
--
ALTER TABLE `teacher_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `user_login_history`
--
ALTER TABLE `user_login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `algorithm_executions`
--
ALTER TABLE `algorithm_executions`
  ADD CONSTRAINT `algorithm_executions_ibfk_1` FOREIGN KEY (`parameters_id`) REFERENCES `algorithm_parameters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `algorithm_executions_ibfk_2` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_3` FOREIGN KEY (`evaluatee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internships`
--
ALTER TABLE `internships`
  ADD CONSTRAINT `internships_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internship_skills`
--
ALTER TABLE `internship_skills`
  ADD CONSTRAINT `internship_skills_ibfk_1` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meeting_participants`
--
ALTER TABLE `meeting_participants`
  ADD CONSTRAINT `meeting_participants_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meeting_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD CONSTRAINT `message_recipients_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_preferences`
--
ALTER TABLE `student_preferences`
  ADD CONSTRAINT `student_preferences_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_preferences_ibfk_2` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_preferences`
--
ALTER TABLE `teacher_preferences`
  ADD CONSTRAINT `teacher_preferences_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_login_history`
--
ALTER TABLE `user_login_history`
  ADD CONSTRAINT `user_login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
