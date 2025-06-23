-- Table pour stocker les critères d'évaluation
CREATE TABLE IF NOT EXISTS evaluation_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    category ENUM('technical', 'professional') NOT NULL,
    name VARCHAR(100) NOT NULL,
    score DECIMAL(3,1) DEFAULT 0,
    comments TEXT DEFAULT NULL,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer des index pour optimiser les requêtes
CREATE INDEX idx_evaluation_criteria_evaluation ON evaluation_criteria(evaluation_id);
CREATE INDEX idx_evaluation_criteria_category ON evaluation_criteria(category);

-- Ajouter une colonne dans la table evaluations pour stocker les scores JSON (pour rétrocompatibilité)
ALTER TABLE evaluations ADD COLUMN criteria_scores JSON DEFAULT NULL AFTER score;

-- Mise à jour de la table student_scores pour inclure plus de compétences spécifiques
ALTER TABLE student_scores 
ADD COLUMN technical_mastery DECIMAL(3,1) DEFAULT 0 AFTER technical_score,
ADD COLUMN work_quality DECIMAL(3,1) DEFAULT 0 AFTER technical_mastery,
ADD COLUMN problem_solving DECIMAL(3,1) DEFAULT 0 AFTER work_quality,
ADD COLUMN documentation DECIMAL(3,1) DEFAULT 0 AFTER problem_solving,
ADD COLUMN autonomy DECIMAL(3,1) DEFAULT 0 AFTER teamwork_score,
ADD COLUMN deadline_respect DECIMAL(3,1) DEFAULT 0 AFTER autonomy;

-- Exemples de compétences prédéfinies (si nécessaire)
CREATE TABLE IF NOT EXISTS predefined_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('technical', 'professional') NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    UNIQUE KEY (category, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les compétences prédéfinies
INSERT INTO predefined_criteria (category, name, description) VALUES
('technical', 'Maîtrise des technologies', 'Capacité à utiliser efficacement les technologies et outils requis'),
('technical', 'Qualité du travail', 'Précision, rigueur et fiabilité des livrables'),
('technical', 'Résolution de problèmes', 'Capacité à identifier et résoudre les problèmes techniques'),
('technical', 'Documentation', 'Qualité et clarté de la documentation produite'),
('professional', 'Autonomie', 'Capacité à travailler de manière indépendante'),
('professional', 'Communication', 'Clarté et efficacité dans la communication orale et écrite'),
('professional', 'Intégration dans l\'équipe', 'Capacité à travailler en équipe et collaborer'),
('professional', 'Respect des délais', 'Ponctualité et respect des échéances fixées');