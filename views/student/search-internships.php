<?php
/**
 * Script amélioré pour la recherche de stages
 * Ce script permet de rechercher des stages et de les ajouter aux préférences
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
$student_id = null;

if ($user_id) {
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($user_id);
    
    if ($student) {
        $student_id = $student['id'];
    }
}

// Traitement de la recherche
$searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
$searchResults = [];

if (!empty($searchTerm) || isset($_GET['show_all'])) {
    $internshipModel = new Internship($db);
    $filters = ['status' => 'available'];
    
    try {
        if (isset($_GET['show_all'])) {
            // Récupérer tous les stages disponibles sans limite
            $searchResults = $internshipModel->getAvailable();
            if (empty($searchResults)) {
                // Si getAvailable() ne fonctionne pas, essayer avec getAvailableForStudent
                if ($student_id) {
                    $searchResults = $internshipModel->getAvailableForStudent($student_id);
                } else {
                    // Dernière tentative avec search mais sans limite
                    $searchResults = $internshipModel->search('', 'available', $filters, 1000, 0);
                }
            }
        } else {
            // Recherche normale avec terme de recherche
            $searchResults = $internshipModel->search($searchTerm, 'available', $filters, 20, 0);
        }
        error_log("Search for '$searchTerm' found " . count($searchResults) . " results");
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        $searchResults = [];
        setFlashMessage('error', 'Une erreur est survenue lors de la recherche de stages.');
    }
}

// Récupérer les préférences actuelles de l'étudiant
$currentPreferences = [];
if ($student_id) {
    $studentModel = new Student($db);
    $currentPreferences = $studentModel->getPreferences($student_id);
}

// Fonction pour vérifier si un stage est déjà dans les préférences
function isInPreferences($internshipId, $preferences) {
    foreach ($preferences as $preference) {
        if ($preference['internship_id'] == $internshipId) {
            return true;
        }
    }
    return false;
}

// Traitement de l'ajout aux préférences
if (isset($_GET['add']) && !empty($_GET['add']) && $student_id) {
    $internshipId = (int)$_GET['add'];
    
    // Vérifier que le stage existe
    $internshipModel = new Internship($db);
    $internship = $internshipModel->getById($internshipId);
    
    // Vérifier si l'étudiant a déjà atteint le maximum de 5 préférences
    if (count($currentPreferences) >= 5) {
        setFlashMessage('warning', 'Vous avez atteint le nombre maximum de préférences (5). Veuillez supprimer une préférence avant d\'en ajouter une nouvelle.');
    }
    else if ($internship && !isInPreferences($internshipId, $currentPreferences)) {
        // Déterminer l'ordre de préférence (dernier + 1)
        $preferenceOrder = 1;
        
        if (!empty($currentPreferences)) {
            $maxOrder = 0;
            foreach ($currentPreferences as $pref) {
                if (isset($pref['preference_order']) && $pref['preference_order'] > $maxOrder) {
                    $maxOrder = $pref['preference_order'];
                }
            }
            $preferenceOrder = $maxOrder + 1;
        }
        
        // Ajouter la préférence
        $success = $studentModel->addPreference($student_id, $internshipId, $preferenceOrder);
        
        if ($success) {
            setFlashMessage('success', 'Stage ajouté à vos préférences avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de l\'ajout du stage à vos préférences.');
        }
    } else if (isInPreferences($internshipId, $currentPreferences)) {
        setFlashMessage('info', 'Ce stage est déjà dans vos préférences.');
    } else {
        setFlashMessage('error', 'Stage non trouvé ou non disponible.');
    }
    
    // Recharger les préférences
    $currentPreferences = $studentModel->getPreferences($student_id);
}

// Titre de la page
$pageTitle = 'Recherche de stages';
$currentPage = 'preferences';

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-search me-2"></i>Recherche de stages</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/preferences.php">Préférences</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Recherche</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="row">
        <!-- Left Column - Search and Results -->
        <div class="col-lg-8">
            <!-- Search Box -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Rechercher un stage</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" name="term" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Titre, domaine, entreprise..." aria-label="Terme de recherche">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search me-1"></i> Rechercher
                            </button>
                        </div>
                        <div class="form-text small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Vous pouvez rechercher par titre de stage, domaine, compétence ou entreprise.
                        </div>
                    </form>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <a href="?show_all=1" class="text-decoration-none">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i>Afficher tous les stages disponibles
                        </a>
                        <a href="/tutoring/views/student/preferences.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Retour aux préférences
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Search Results -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Résultats de recherche</h5>
                    <span class="badge bg-primary"><?= count($searchResults) ?> stages trouvés</span>
                </div>
                <div class="card-body">
                    <?php if (empty($searchResults)): ?>
                        <?php if (!empty($searchTerm) || isset($_GET['show_all'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Aucun stage trouvé pour votre recherche.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Utilisez le champ de recherche ci-dessus pour trouver des stages.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($searchResults as $internship): ?>
                                <?php 
                                    // Vérifier si le stage est déjà dans les préférences
                                    $isPreferred = isInPreferences($internship['id'], $currentPreferences);
                                ?>
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($internship['title']) ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($internship['company_name']) ?></h6>
                                            
                                            <div class="mb-2">
                                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($internship['domain']) ?></span>
                                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($internship['location']) ?></span>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($internship['work_mode']) ?></span>
                                            </div>
                                            
                                            <p class="card-text small">
                                                <?= substr(htmlspecialchars($internship['description']), 0, 100) . (strlen($internship['description']) > 100 ? '...' : '') ?>
                                            </p>
                                            
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-calendar-event me-1 text-muted"></i>
                                                <small class="text-muted">Du <?= date('d/m/Y', strtotime($internship['start_date'])) ?> au <?= date('d/m/Y', strtotime($internship['end_date'])) ?></small>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <?php if ($isPreferred): ?>
                                                    <button class="btn btn-success btn-sm" disabled>
                                                        <i class="bi bi-check-circle me-1"></i>Dans vos préférences
                                                    </button>
                                                <?php elseif (count($currentPreferences) >= 5): ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Maximum atteint (5)
                                                    </button>
                                                <?php else: ?>
                                                    <a href="?term=<?= urlencode($searchTerm) ?>&add=<?= $internship['id'] ?><?= isset($_GET['show_all']) ? '&show_all=1' : '' ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-plus-circle me-1"></i>Ajouter aux préférences
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Current Preferences and Help -->
        <div class="col-lg-4">
            <!-- Current Preferences Summary -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Vos préférences actuelles</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($currentPreferences)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>Vous n'avez pas encore défini de préférences.
                        </div>
                    <?php else: ?>
                        <p class="mb-3">Vous avez défini <strong><?= count($currentPreferences) ?></strong> préférence(s) sur un maximum de 5.</p>
                        <div class="list-group">
                            <?php foreach ($currentPreferences as $index => $preference): ?>
                                <div class="list-group-item list-group-item-action d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3"><?= $preference['preference_order'] ?></span>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($preference['title']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($preference['company_name']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-grid mt-3">
                            <a href="/tutoring/views/student/preferences.php" class="btn btn-primary">
                                <i class="bi bi-gear me-1"></i>Gérer mes préférences
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-question-circle me-1"></i>Aide</h5>
                </div>
                <div class="card-body">
                    <h6>Comment choisir mes stages préférés ?</h6>
                    <ol class="small">
                        <li>Recherchez des stages qui correspondent à vos intérêts</li>
                        <li>Cliquez sur "Ajouter aux préférences" pour les ajouter à votre liste</li>
                        <li>Retournez à la page "Préférences" pour les ordonner selon votre priorité</li>
                        <li>Vous pouvez sélectionner jusqu'à 5 stages préférés</li>
                    </ol>
                    <h6>Conseils</h6>
                    <ul class="small">
                        <li>Essayez différents termes de recherche pour trouver plus de stages</li>
                        <li>Consultez la description complète avant de faire votre choix</li>
                        <li>Vérifiez les dates pour vous assurer de votre disponibilité</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>