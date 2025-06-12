-- Script pour corriger la structure de la table documents

-- Ajouter AUTO_INCREMENT à la colonne id
ALTER TABLE `documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Ajouter PRIMARY KEY si elle n'existe pas déjà
ALTER TABLE `documents` ADD PRIMARY KEY (`id`);

-- Ajouter des colonnes manquantes (file_type et file_size)
ALTER TABLE `documents` ADD COLUMN `file_type` varchar(100) DEFAULT NULL AFTER `file_path`;
ALTER TABLE `documents` ADD COLUMN `file_size` int(11) DEFAULT NULL AFTER `file_type`;

-- Ajouter des index pour améliorer les performances
ALTER TABLE `documents` ADD INDEX `idx_user_id` (`user_id`);
ALTER TABLE `documents` ADD INDEX `idx_assignment_id` (`assignment_id`);
ALTER TABLE `documents` ADD INDEX `idx_type` (`type`);
ALTER TABLE `documents` ADD INDEX `idx_status` (`status`);

-- Mettre à jour AUTO_INCREMENT à une valeur supérieure aux ID existants
ALTER TABLE `documents` AUTO_INCREMENT = 1000;