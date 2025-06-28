<?php
/**
 * Page web pour exécuter la migration enrollment_year
 */

require_once __DIR__ . '/includes/init.php';

// Vérifier les permissions admin
requireRole(['admin']);

$messages = [];
$errors = [];
$currentYear = 2025; // Année courante

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute'])) {
    try {
        // Vérifier si la colonne enrollment_year existe déjà
        $checkQuery = "SHOW COLUMNS FROM students LIKE 'enrollment_year'";
        $stmt = $db->query($checkQuery);
        
        if ($stmt->rowCount() > 0) {
            $messages[] = "La colonne enrollment_year existe déjà.";
        } else {
            // Ajouter la colonne enrollment_year
            $alterQuery = "ALTER TABLE students ADD COLUMN enrollment_year INT(4) AFTER level";
            $db->exec($alterQuery);
            $messages[] = "Colonne enrollment_year ajoutée avec succès.";
            
            // Mettre à jour les années d'inscription basées sur le niveau
            $updateQueries = [
                "UPDATE students SET enrollment_year = " . ($currentYear - 0) . " WHERE level = 'L1'",
                "UPDATE students SET enrollment_year = " . ($currentYear - 1) . " WHERE level = 'L2'",
                "UPDATE students SET enrollment_year = " . ($currentYear - 2) . " WHERE level = 'L3'",
                "UPDATE students SET enrollment_year = " . ($currentYear - 3) . " WHERE level = 'M1'",
                "UPDATE students SET enrollment_year = " . ($currentYear - 4) . " WHERE level = 'M2'"
            ];
            
            foreach ($updateQueries as $query) {
                $count = $db->exec($query);
                $messages[] = "Mise à jour effectuée: $count étudiants (" . substr($query, strpos($query, 'WHERE')) . ")";
            }
        }
        
        // Mettre à jour average_grade, graduation_year et skills
        $messages[] = "<strong>Mise à jour des données académiques...</strong>";
        
        // Skills par programme
        $skillsByProgram = [
            'Informatique' => [
                'Programmation (Java, Python, C++)',
                'Développement Web (HTML, CSS, JavaScript, PHP)',
                'Bases de données (MySQL, PostgreSQL)',
                'Algorithmique et structures de données',
                'Réseaux et systèmes',
                'Intelligence Artificielle',
                'DevOps et Cloud Computing',
                'Méthodologies Agile'
            ],
            'Génie Civil' => [
                'AutoCAD et modélisation 3D',
                'Calcul de structures',
                'Gestion de projets BTP',
                'Résistance des matériaux',
                'Hydraulique et mécanique des sols',
                'Topographie',
                'Normes de construction',
                'Développement durable'
            ],
            'Électronique' => [
                'Circuits analogiques et numériques',
                'Microcontrôleurs (Arduino, PIC)',
                'Traitement du signal',
                'Électronique de puissance',
                'Systèmes embarqués',
                'CAO électronique (Proteus, Eagle)',
                'Télécommunications',
                'Automatisme industriel'
            ],
            'Mécanique' => [
                'CAO/DAO (SolidWorks, CATIA)',
                'Mécanique des fluides',
                'Thermodynamique',
                'Résistance des matériaux',
                'Fabrication mécanique',
                'Maintenance industrielle',
                'Robotique',
                'Gestion de production'
            ],
            'Mathématiques' => [
                'Analyse mathématique',
                'Algèbre linéaire',
                'Probabilités et statistiques',
                'Mathématiques appliquées',
                'Modélisation mathématique',
                'Recherche opérationnelle',
                'Logiciels mathématiques (Matlab, R)',
                'Enseignement et pédagogie'
            ]
        ];
        
        // Récupérer tous les étudiants
        $studentsQuery = "SELECT id, program, level FROM students";
        $students = $db->query($studentsQuery)->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($students as $student) {
            // Calculer la moyenne en fonction du niveau (plus élevée pour les niveaux avancés)
            $baseGrade = match($student['level']) {
                'L1' => rand(10, 14),
                'L2' => rand(11, 15),
                'L3' => rand(12, 16),
                'M1' => rand(13, 17),
                'M2' => rand(14, 18),
                default => rand(10, 15)
            };
            $average_grade = $baseGrade + (rand(0, 20) / 10); // Ajouter des décimales
            $average_grade = min(20, $average_grade); // Plafonner à 20
            
            // Calculer l'année de graduation
            $graduation_year = match($student['level']) {
                'L1' => $currentYear + 3,
                'L2' => $currentYear + 2,
                'L3' => $currentYear + 1,
                'M1' => $currentYear + 1,
                'M2' => $currentYear,
                default => $currentYear + 2
            };
            
            // Sélectionner des compétences aléatoires
            $programSkills = $skillsByProgram[$student['program']] ?? $skillsByProgram['Informatique'];
            $numSkills = rand(3, 6);
            $selectedSkills = array_rand(array_flip($programSkills), $numSkills);
            if (!is_array($selectedSkills)) {
                $selectedSkills = [$selectedSkills];
            }
            $skills = implode(', ', $selectedSkills);
            
            // Mettre à jour l'étudiant
            $updateQuery = "UPDATE students SET 
                            average_grade = :grade,
                            graduation_year = :grad_year,
                            skills = :skills
                            WHERE id = :id";
            
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([
                ':grade' => $average_grade,
                ':grad_year' => $graduation_year,
                ':skills' => $skills,
                ':id' => $student['id']
            ]);
        }
        
        $messages[] = "Données académiques mises à jour pour " . count($students) . " étudiants.";
        
        // Vérifier si la colonne created_at existe
        $checkCreatedAt = "SHOW COLUMNS FROM students LIKE 'created_at'";
        $stmt = $db->query($checkCreatedAt);
        
        if ($stmt->rowCount() > 0) {
            $messages[] = "La colonne created_at existe déjà.";
        } else {
            // Ajouter la colonne created_at
            $alterQuery = "ALTER TABLE students ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status";
            $db->exec($alterQuery);
            $messages[] = "Colonne created_at ajoutée avec succès.";
        }
        
    } catch (Exception $e) {
        $errors[] = "Erreur: " . $e->getMessage();
    }
}

// Récupérer la structure actuelle
$structure = [];
try {
    $descQuery = "DESCRIBE students";
    $stmt = $db->query($descQuery);
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Impossible de récupérer la structure: " . $e->getMessage();
}

include_once __DIR__ . '/views/common/header.php';
?>

<div class="container mt-4">
    <h2>Migration de la table Students</h2>
    
    <?php if (!empty($messages)): ?>
    <div class="alert alert-success">
        <h5>Opérations effectuées:</h5>
        <ul class="mb-0">
            <?php foreach ($messages as $msg): ?>
            <li><?php echo h($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h5>Erreurs:</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Migration complète de la table Students</h5>
        </div>
        <div class="card-body">
            <p>Cette migration va :</p>
            <ul>
                <li>Ajouter la colonne <code>enrollment_year</code> à la table students</li>
                <li>Calculer automatiquement l'année d'inscription basée sur le niveau actuel</li>
                <li>Ajouter la colonne <code>created_at</code> avec timestamp automatique</li>
                <li><strong>Générer des moyennes académiques</strong> cohérentes selon le niveau (L1: 10-14, L2: 11-15, L3: 12-16, M1: 13-17, M2: 14-18)</li>
                <li><strong>Calculer l'année de graduation</strong> prévue selon le niveau actuel</li>
                <li><strong>Attribuer des compétences</strong> pertinentes selon le programme d'études (3-6 compétences par étudiant)</li>
            </ul>
            
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> Les données générées sont cohérentes avec le parcours académique de chaque étudiant.
            </div>
            
            <form method="POST">
                <button type="submit" name="execute" value="1" class="btn btn-primary" 
                        onclick="return confirm('Êtes-vous sûr de vouloir exécuter cette migration?')">
                    Exécuter la migration
                </button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Structure actuelle de la table students</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Champ</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Clé</th>
                        <th>Défaut</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($structure as $col): ?>
                    <tr>
                        <td><code><?php echo h($col['Field']); ?></code></td>
                        <td><?php echo h($col['Type']); ?></td>
                        <td><?php echo h($col['Null']); ?></td>
                        <td><?php echo h($col['Key']); ?></td>
                        <td><?php echo h($col['Default'] ?? 'NULL'); ?></td>
                        <td><?php echo h($col['Extra']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="/tutoring/views/admin/students.php" class="btn btn-secondary">Retour aux étudiants</a>
    </div>
</div>

<?php include_once __DIR__ . '/views/common/footer.php'; ?>