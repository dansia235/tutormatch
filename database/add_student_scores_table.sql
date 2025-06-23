-- Table pour stocker les scores des étudiants de manière cohérente
CREATE TABLE IF NOT EXISTS student_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    assignment_id INT NOT NULL,
    technical_score DECIMAL(3,1) DEFAULT 0,
    communication_score DECIMAL(3,1) DEFAULT 0,
    teamwork_score DECIMAL(3,1) DEFAULT 0,
    autonomy_score DECIMAL(3,1) DEFAULT 0,
    average_score DECIMAL(3,1) DEFAULT 0,
    completed_evaluations INT DEFAULT 0,
    total_evaluations INT DEFAULT 5,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_assignment (student_id, assignment_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- Index pour accélérer les recherches
CREATE INDEX idx_student_scores_student ON student_scores(student_id);
CREATE INDEX idx_student_scores_assignment ON student_scores(assignment_id);