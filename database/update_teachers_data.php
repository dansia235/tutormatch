<?php
/**
 * Script de mise à jour des données des tuteurs
 * Ajoute des données cohérentes pour title, specialty, office_location et expertise
 */

// Configuration de la base de données
require_once __DIR__ . '/../config/database.php';

try {
    // Connexion à la base de données en utilisant les constantes définies
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Mise à jour des données des tuteurs</h2>";

    // Définition des données cohérentes par département
    $departmentData = [
        'Informatique' => [
            'titles' => ['Dr.', 'Prof.', 'Ing.', 'M.'],
            'specialties' => [
                'Développement Web,Programmation,Bases de données',
                'Intelligence Artificielle,Machine Learning',
                'Cybersécurité,Réseaux informatiques',
                'Développement Mobile,Interface utilisateur',
                'Systèmes embarqués,IoT',
                'Génie logiciel,Architecture logicielle',
                'Analyse de données,Big Data'
            ],
            'expertises' => [
                'JavaScript,PHP,MySQL,React,Node.js',
                'Python,TensorFlow,Scikit-learn,Deep Learning',
                'Sécurité réseau,Cryptographie,Pentesting',
                'Android,iOS,Flutter,React Native',
                'C/C++,Arduino,Raspberry Pi,Microcontrôleurs',
                'UML,Design Patterns,Agile,DevOps',
                'Python,R,SQL,Tableau,Power BI'
            ],
            'office_prefix' => 'INFO'
        ],
        'Génie Civil' => [
            'titles' => ['Ing.', 'Dr.', 'Prof.', 'M.'],
            'specialties' => [
                'Structures,Béton armé,Calculs de résistance',
                'Géotechnique,Mécanique des sols',
                'Hydraulique,Assainissement',
                'Construction durable,Écoconstruction',
                'Gestion de projet,Planning BTP',
                'Matériaux de construction,Contrôle qualité',
                'Topographie,Aménagement territorial'
            ],
            'expertises' => [
                'AutoCAD,Revit,Robot Structural,Eurocodes',
                'Essais géotechniques,PLAXIS,GeoStudio',
                'HEC-RAS,SWMM,Modélisation hydraulique',
                'Certification BREEAM,HQE,RT2012',
                'MS Project,Primavera,Gestion des coûts',
                'Tests matériaux,Normes NF,Contrôle béton',
                'Géomètre,GPS,SIG,Cartographie'
            ],
            'office_prefix' => 'GC'
        ],
        'Électronique' => [
            'titles' => ['Ing.', 'Dr.', 'M.', 'Prof.'],
            'specialties' => [
                'Électronique analogique,Amplificateurs',
                'Électronique numérique,Microprocesseurs',
                'Systèmes embarqués,FPGA',
                'Télécommunications,Radiofréquences',
                'Automatique,Contrôle commande',
                'Électronique de puissance,Convertisseurs',
                'Instrumentation,Mesures électriques'
            ],
            'expertises' => [
                'SPICE,Proteus,Oscilloscope,Analyseur réseau',
                'VHDL,Verilog,Quartus,Vivado',
                'ARM,PIC,STM32,Embedded C',
                'Antennes,Propagation,Analyseur spectre',
                'MATLAB/Simulink,LabVIEW,Régulation PID',
                'PSIM,PLECS,Onduleurs,Redresseurs',
                'Capteurs,Conditionnement,Acquisition données'
            ],
            'office_prefix' => 'ELEC'
        ],
        'Mécanique' => [
            'titles' => ['Ing.', 'Dr.', 'Prof.', 'M.'],
            'specialties' => [
                'Mécanique des fluides,Thermodynamique',
                'Résistance des matériaux,Calculs mécaniques',
                'Conception mécanique,CAO',
                'Fabrication,Usinage,Procédés industriels',
                'Maintenance industrielle,Fiabilité',
                'Énergétique,Machines thermiques',
                'Mécatronique,Robotique industrielle'
            ],
            'expertises' => [
                'CFD,ANSYS Fluent,Thermique,Transferts',
                'RDM,Contraintes,Fatigue,Éléments finis',
                'SolidWorks,CATIA,Inventor,Simulation',
                'Tournage,Fraisage,Commande numérique',
                'GMAO,TPM,Analyse vibratoire,Diagnostic',
                'Cycles thermodynamiques,Turbomachines',
                'Automates,Actionneurs,Capteurs industriels'
            ],
            'office_prefix' => 'MECA'
        ],
        'Mathématiques' => [
            'titles' => ['Prof.', 'Dr.', 'M.', 'Mme.'],
            'specialties' => [
                'Analyse mathématique,Calcul différentiel',
                'Algèbre linéaire,Géométrie',
                'Statistiques,Probabilités',
                'Mathématiques appliquées,Modélisation',
                'Analyse numérique,Algorithmes'
            ],
            'expertises' => [
                'Fonctions,Intégrales,Séries,Équations diff',
                'Matrices,Espaces vectoriels,Applications',
                'Tests statistiques,Régression,ANOVA',
                'MATLAB,R,Wolfram,Modélisation numérique',
                'Méthodes numériques,Optimisation,Python'
            ],
            'office_prefix' => 'MATH'
        ]
    ];

    // Récupérer tous les tuteurs avec leurs informations utilisateur
    $stmt = $pdo->query("
        SELECT t.id, t.user_id, u.first_name, u.last_name, u.department 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        WHERE u.role = 'teacher'
        ORDER BY t.id
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Trouvé " . count($teachers) . " tuteurs à mettre à jour...</p>";

    $updated = 0;
    
    foreach ($teachers as $teacher) {
        $department = $teacher['department'];
        
        if (!isset($departmentData[$department])) {
            echo "<p style='color: orange;'>⚠️ Département '{$department}' non reconnu pour {$teacher['first_name']} {$teacher['last_name']}</p>";
            continue;
        }
        
        $data = $departmentData[$department];
        
        // Sélectionner des données aléatoirement pour la variété
        $title = $data['titles'][array_rand($data['titles'])];
        $specialty = $data['specialties'][array_rand($data['specialties'])];
        $expertise = $data['expertises'][array_rand($data['expertises'])];
        
        // Générer un bureau cohérent
        $office_number = str_pad($teacher['id'], 3, '0', STR_PAD_LEFT);
        $office_location = $data['office_prefix'] . '-' . $office_number;
        
        // Mise à jour du tuteur
        $updateStmt = $pdo->prepare("
            UPDATE teachers 
            SET title = ?, specialty = ?, office_location = ?, expertise = ?
            WHERE id = ?
        ");
        
        if ($updateStmt->execute([$title, $specialty, $office_location, $expertise, $teacher['id']])) {
            echo "<p style='color: green;'>✅ {$title} {$teacher['first_name']} {$teacher['last_name']} ({$department}) - Bureau: {$office_location}</p>";
            $updated++;
        } else {
            echo "<p style='color: red;'>❌ Erreur pour {$teacher['first_name']} {$teacher['last_name']}</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✅ Mise à jour terminée : {$updated} tuteurs mis à jour avec succès !</h3>";
    
    // Afficher un échantillon des données mises à jour
    echo "<h3>Échantillon des données mises à jour :</h3>";
    $sampleStmt = $pdo->query("
        SELECT t.title, u.first_name, u.last_name, u.department, t.specialty, t.office_location, t.expertise
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        WHERE u.role = 'teacher'
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tuteur</th><th>Département</th><th>Spécialité</th><th>Bureau</th><th>Expertise</th></tr>";
    
    while ($row = $sampleStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['title']} {$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['department']}</td>";
        echo "<td>" . substr($row['specialty'], 0, 30) . "...</td>";
        echo "<td>{$row['office_location']}</td>";
        echo "<td>" . substr($row['expertise'], 0, 40) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de base de données : " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour des données des tuteurs</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h2, h3 { color: #333; }
        table { margin-top: 10px; }
        th { background-color: #f0f0f0; padding: 8px; }
        td { padding: 8px; }
    </style>
</head>
<body>
    <h1>Mise à jour des données des tuteurs</h1>
    <p><strong>Ce script met à jour les champs title, specialty, office_location et expertise des tuteurs avec des données cohérentes basées sur leur département.</strong></p>
    
    <h3>Données par département :</h3>
    <ul>
        <li><strong>Informatique :</strong> Développement, IA, Cybersécurité, Mobile, IoT, etc.</li>
        <li><strong>Génie Civil :</strong> Structures, Géotechnique, Hydraulique, Écoconstruction, etc.</li>
        <li><strong>Électronique :</strong> Analogique, Numérique, Embarqué, Télécoms, etc.</li>
        <li><strong>Mécanique :</strong> Fluides, RDM, CAO, Fabrication, Énergétique, etc.</li>
        <li><strong>Mathématiques :</strong> Analyse, Algèbre, Statistiques, Modélisation, etc.</li>
    </ul>
</body>
</html>