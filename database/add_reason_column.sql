-- Ajout de la colonne "reason" à la table student_preferences
ALTER TABLE `student_preferences` 
ADD COLUMN `reason` TEXT NULL AFTER `preference_order`;

-- Commentaire sur la modification
-- Cette colonne permet à l'étudiant de stocker la raison pour laquelle il a choisi ce stage comme préférence