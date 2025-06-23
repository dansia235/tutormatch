-- Script pour mettre à jour la structure des tables d'évaluation
-- Objectif : Supprimer les tables redondantes et conserver uniquement le modèle JSON

-- 1. Vérifier si les colonnes nécessaires existent dans la table evaluations
-- Si elles n'existent pas, les ajouter

-- Ajouter colonne pour la moyenne technique si elle n'existe pas
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `technical_avg` DECIMAL(3,1) DEFAULT 0.0 AFTER `criteria_scores`;

-- Ajouter colonne pour la moyenne professionnelle si elle n'existe pas
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `professional_avg` DECIMAL(3,1) DEFAULT 0.0 AFTER `technical_avg`;

-- Ajouter colonne score si elle n'existe pas
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `score` DECIMAL(3,1) DEFAULT 0.0 AFTER `type`;

-- Ajouter colonne pour les commentaires généraux (renommée de feedback)
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `comments` TEXT DEFAULT NULL AFTER `professional_avg`;

-- Ajouter colonne pour les zones d'amélioration (renommée de areas_to_improve)
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `areas_for_improvement` TEXT DEFAULT NULL AFTER `strengths`;

-- Ajouter colonne pour les prochaines étapes
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `next_steps` TEXT DEFAULT NULL AFTER `areas_for_improvement`;

-- Ajouter colonne pour le statut
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `status` ENUM('draft', 'submitted', 'approved') NOT NULL DEFAULT 'submitted' AFTER `next_steps`;

-- Ajouter colonne pour la date de mise à jour
ALTER TABLE `evaluations` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `submission_date`;

-- 2. Supprimer les tables redondantes
-- Les tables evaluation_criteria et evaluation_scores sont maintenant redondantes
-- puisque toutes les données sont stockées dans criteria_scores (JSON)

-- Désactiver temporairement les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer la table evaluation_criteria si elle existe
DROP TABLE IF EXISTS `evaluation_criteria`;

-- Supprimer la table evaluation_scores si elle existe
DROP TABLE IF EXISTS `evaluation_scores`;

-- Supprimer la table predefined_criteria si elle existe (n'est plus nécessaire)
DROP TABLE IF EXISTS `predefined_criteria`;

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- 3. Mettre à jour les données existantes
-- Si les colonnes feedback et areas_to_improve existent encore et contiennent des données,
-- les copier vers comments et areas_for_improvement

-- Copier feedback vers comments
UPDATE `evaluations` 
SET `comments` = `feedback` 
WHERE `comments` IS NULL AND `feedback` IS NOT NULL;

-- Copier areas_to_improve vers areas_for_improvement
UPDATE `evaluations` 
SET `areas_for_improvement` = `areas_to_improve` 
WHERE `areas_for_improvement` IS NULL AND `areas_to_improve` IS NOT NULL;

-- 4. Supprimer les anciennes colonnes redondantes (optionnel)
-- Ces colonnes peuvent être supprimées si vous êtes certain que toutes les données ont été migrées

-- Supprimer la colonne feedback si elle existe
ALTER TABLE `evaluations` DROP COLUMN IF EXISTS `feedback`;

-- Supprimer la colonne areas_to_improve si elle existe
ALTER TABLE `evaluations` DROP COLUMN IF EXISTS `areas_to_improve`;

-- 5. Mettre à jour les scripts existants
-- Assurer que vos scripts utilisent les nouveaux noms de colonnes
-- - comments au lieu de feedback
-- - areas_for_improvement au lieu de areas_to_improve