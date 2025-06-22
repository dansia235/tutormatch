-- Script SQL pour nettoyer et régénérer les évaluations
-- Ce script doit être exécuté dans phpMyAdmin ou via un autre client SQL
-- Il supprime toutes les évaluations et documents d'évaluation existants
-- puis génère de nouvelles évaluations cohérentes pour chaque affectation

-- Utiliser la base de données du système de tutorat
USE tutoring_system;

-- Début de transaction
START TRANSACTION;

-- 1. Nettoyer les données existantes
-- Supprimer toutes les évaluations existantes
DELETE FROM evaluations;

-- Supprimer tous les documents d'évaluation qui pourraient créer des conflits
DELETE FROM documents WHERE type IN ('evaluation', 'self_evaluation');

-- 2. Sélectionner toutes les affectations actives
-- Cette requête aide à visualiser les affectations disponibles pour générer des évaluations
SELECT 
    a.id AS assignment_id,
    a.student_id,
    a.teacher_id,
    a.internship_id,
    s.user_id AS student_user_id,
    t.user_id AS teacher_user_id,
    u_s.first_name AS student_first_name,
    u_s.last_name AS student_last_name,
    u_t.first_name AS teacher_first_name,
    u_t.last_name AS teacher_last_name,
    i.title AS internship_title,
    i.start_date,
    i.end_date
FROM 
    assignments a
JOIN 
    students s ON a.student_id = s.id
JOIN 
    users u_s ON s.user_id = u_s.id
JOIN 
    teachers t ON a.teacher_id = t.id
JOIN 
    users u_t ON t.user_id = u_t.id
JOIN 
    internships i ON a.internship_id = i.id
WHERE 
    a.status IN ('active', 'confirmed');

-- 3. Insérer des évaluations mi-parcours par les tuteurs
-- Nous utilisons les données des affectations actives
INSERT INTO evaluations (
    assignment_id, 
    evaluator_id, 
    evaluatee_id, 
    type, 
    score,
    feedback, 
    strengths, 
    areas_to_improve,
    submission_date
)
SELECT 
    a.id AS assignment_id,
    t.user_id AS evaluator_id,
    s.user_id AS evaluatee_id,
    'mid_term' AS type,
    ROUND(RAND() * 5, 1) AS score, -- Score entre 0 et 5 (directement dans l'échelle finale)
    CONCAT('L\'étudiant ', u_s.first_name, ' montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l\'équipe est bonne. Il doit améliorer sa communication et sa documentation.') AS feedback,
    'Bonne maîtrise technique, autonomie dans les tâches assignées' AS strengths,
    'Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions' AS areas_to_improve,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 30) DAY) AS submission_date
FROM 
    assignments a
JOIN 
    students s ON a.student_id = s.id
JOIN 
    users u_s ON s.user_id = u_s.id
JOIN 
    teachers t ON a.teacher_id = t.id
JOIN 
    users u_t ON t.user_id = u_t.id
JOIN 
    internships i ON a.internship_id = i.id
WHERE 
    a.status IN ('active', 'confirmed');

-- 4. Insérer des auto-évaluations par les étudiants
INSERT INTO evaluations (
    assignment_id, 
    evaluator_id, 
    evaluatee_id, 
    type, 
    score,
    feedback, 
    strengths, 
    areas_to_improve,
    submission_date
)
SELECT 
    a.id AS assignment_id,
    s.user_id AS evaluator_id,
    s.user_id AS evaluatee_id,
    'student' AS type,
    ROUND(RAND() * 4 + 1, 1) AS score, -- Score entre 1 et 5 (légèrement plus positif)
    CONCAT('Je pense avoir bien progressé dans mon stage. J\'ai acquis de nouvelles compétences techniques et j\'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l\'équipe.') AS feedback,
    'Apprentissage rapide des technologies, implication dans les projets' AS strengths,
    'Communication plus régulière\nMeilleure organisation du temps' AS areas_to_improve,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 20) DAY) AS submission_date
FROM 
    assignments a
JOIN 
    students s ON a.student_id = s.id
JOIN 
    users u_s ON s.user_id = u_s.id
JOIN 
    internships i ON a.internship_id = i.id
WHERE 
    a.status IN ('active', 'confirmed');

-- 5. Insérer des évaluations finales par les tuteurs (pour certains stages seulement)
INSERT INTO evaluations (
    assignment_id, 
    evaluator_id, 
    evaluatee_id, 
    type, 
    score,
    feedback, 
    strengths, 
    areas_to_improve,
    submission_date
)
SELECT 
    a.id AS assignment_id,
    t.user_id AS evaluator_id,
    s.user_id AS evaluatee_id,
    'final' AS type,
    ROUND(RAND() * 2 + 3, 1) AS score, -- Score entre 3 et 5 (meilleur que mi-parcours)
    CONCAT('L\'étudiant ', u_s.first_name, ' a réalisé d\'excellents progrès tout au long de son stage. Il a su s\'adapter aux défis techniques et a démontré une bonne capacité d\'intégration dans l\'équipe. Ses compétences techniques se sont nettement améliorées.') AS feedback,
    'Maîtrise technique approfondie, autonomie, capacité d\'analyse et résolution de problèmes' AS strengths,
    'Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées' AS areas_to_improve,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 10) DAY) AS submission_date
FROM 
    assignments a
JOIN 
    students s ON a.student_id = s.id
JOIN 
    users u_s ON s.user_id = u_s.id
JOIN 
    teachers t ON a.teacher_id = t.id
JOIN 
    users u_t ON t.user_id = u_t.id
JOIN 
    internships i ON a.internship_id = i.id
WHERE 
    a.status IN ('active', 'confirmed')
AND 
    RAND() < 0.7; -- Seulement pour 70% des stages (pour simuler que tous ne sont pas terminés)

-- Valider la transaction
COMMIT;

-- Vérifier que les évaluations ont été créées correctement
SELECT * FROM evaluations ORDER BY assignment_id, type;