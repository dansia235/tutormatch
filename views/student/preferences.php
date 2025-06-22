<?php
/**
 * Vue pour la gestion des préférences de stage par l'étudiant
 */

// Initialiser les variables
$pageTitle = 'Mes préférences de stage';
$currentPage = 'preferences';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// CSS personnalisé pour le drag and drop
$customCSS = "
<style>
.preference-item {
    transition: all 0.2s ease;
    cursor: grab;
}

.preference-item:active {
    cursor: grabbing;
}

.preference-item.dragging {
    opacity: 0.5;
    transform: scale(0.98);
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
}

.preference-item.drop-above {
    border-top: 2px dashed var(--bs-primary) !important;
    padding-top: calc(1rem - 2px);
}

.preference-item.drop-below {
    border-bottom: 2px dashed var(--bs-primary) !important;
    padding-bottom: calc(1rem - 2px);
}

.drag-handle {
    font-size: 1.2rem;
    cursor: grab;
}

.drag-handle:hover {
    color: var(--bs-primary);
}

.highlight-drop {
    animation: pulse-highlight 1s;
}

@keyframes pulse-highlight {
    0% { background-color: rgba(var(--bs-primary-rgb), 0.1); }
    50% { background-color: rgba(var(--bs-primary-rgb), 0.2); }
    100% { background-color: transparent; }
}
</style>
";

// Récupérer l'ID de l'étudiant - vérifier les deux formats possibles
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
error_log("User ID (from session): " . ($user_id ?: 'null'));

// Charger les préférences directement depuis la base de données
$currentStudentPreferences = [];
$student_id = null;

try {
    // S'assurer que l'identifiant de l'utilisateur est disponible
    error_log("Loading preferences with user_id: " . ($user_id ?: 'null'));
    
    if ($user_id) {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($user_id);
        
        if ($student) {
            error_log("Found student with ID: " . $student['id']);
            $preferences = $studentModel->getPreferences($student['id']);
            error_log("Found " . count($preferences) . " preferences for student ID " . $student['id']);
            
            // Sauvegarder l'ID de l'étudiant pour l'utiliser dans le HTML
            $student_id = $student['id'];
            
            // Formatter les préférences pour le JavaScript
            foreach ($preferences as $pref) {
                $currentStudentPreferences[] = [
                    'internship_id' => $pref['internship_id'],
                    'title' => $pref['title'] ?? 'Stage sans titre',
                    'company_name' => $pref['company_name'] ?? 'Entreprise non spécifiée',
                    'preference_order' => $pref['preference_order'] ?? 1,
                    'rank' => $pref['preference_order'] ?? 1,
                    'reason' => $pref['reason'] ?? null
                ];
            }
            
            error_log("Formatted " . count($currentStudentPreferences) . " preferences for JavaScript. Student ID: " . $student_id);
        } else {
            error_log("Student not found for user_id: " . $user_id);
        }
    } else {
        error_log("No user_id available in session");
    }
} catch (Exception $e) {
    // Log l'erreur pour le débogage
    error_log("Error loading preferences in page: " . $e->getMessage());
}

// Statistiques pour initialiser l'interface
$internshipModel = new Internship($db);
$availableInternships = count($internshipModel->getAvailable());
$preferencesCount = count($currentStudentPreferences);

// Traiter le paramètre 'add' pour ajouter un stage aux préférences
if (isset($_GET['add']) && !empty($_GET['add'])) {
    $internshipId = (int)$_GET['add'];
    
    try {
        // Vérifier que l'étudiant existe
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if ($student) {
            // Vérifier que le stage existe et est disponible
            $internshipModel = new Internship($db);
            $internship = $internshipModel->getById($internshipId);
            
            if ($internship && ($internship['status'] == 'active' || $internship['status'] == 'available')) {
                // Récupérer les préférences actuelles pour déterminer l'ordre
                $currentPreferences = $studentModel->getPreferences($student['id']) ?? [];
                
                // Vérifier si l'étudiant a déjà atteint le maximum de 5 préférences
                if (count($currentPreferences) >= 5) {
                    setFlashMessage('warning', 'Vous avez atteint le nombre maximum de préférences (5). Veuillez supprimer une préférence avant d\'en ajouter une nouvelle.');
                    redirect('/tutoring/views/student/preferences.php');
                    exit;
                }
                
                // Déterminer l'ordre de préférence (dernier + 1)
                $preferenceOrder = 1; // Par défaut
                
                if (!empty($currentPreferences)) {
                    $maxOrder = 0;
                    foreach ($currentPreferences as $pref) {
                        if (isset($pref['preference_order']) && $pref['preference_order'] > $maxOrder) {
                            $maxOrder = $pref['preference_order'];
                        }
                    }
                    $preferenceOrder = $maxOrder + 1;
                }
                
                // Vérifier si le stage est déjà dans les préférences
                $alreadyPreferred = false;
                foreach ($currentPreferences as $pref) {
                    if ($pref['internship_id'] == $internshipId) {
                        $alreadyPreferred = true;
                        break;
                    }
                }
                
                // Ajouter la préférence si pas déjà présente
                if (!$alreadyPreferred) {
                    $success = $studentModel->addPreference($student['id'], $internshipId, $preferenceOrder);
                    
                    if ($success) {
                        setFlashMessage('success', 'Stage ajouté à vos préférences avec succès.');
                    } else {
                        setFlashMessage('error', 'Erreur lors de l\'ajout du stage à vos préférences.');
                    }
                } else {
                    setFlashMessage('info', 'Ce stage est déjà dans vos préférences.');
                }
            } else {
                setFlashMessage('error', 'Le stage sélectionné n\'existe pas ou n\'est pas disponible.');
            }
        } else {
            setFlashMessage('error', 'Profil étudiant non trouvé.');
        }
    } catch (Exception $e) {
        error_log("Error adding preference: " . $e->getMessage());
        setFlashMessage('error', 'Une erreur est survenue lors de l\'ajout de la préférence.');
    }
    
    // Rediriger pour éviter le rechargement avec le même paramètre
    redirect('/tutoring/views/student/preferences.php');
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';

// Ajouter le CSS personnalisé pour le drag and drop
echo $customCSS;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-sliders me-2"></i>Mes préférences de stage</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Préférences</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="row">
        <!-- Alert for new search functionality -->
        <div class="col-12 mb-3">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                Utilisez notre <a href="/tutoring/views/student/search-internships.php" class="alert-link">interface de recherche améliorée</a> pour trouver et ajouter des stages à vos préférences.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        
        <!-- Left Column with Preferences Interface -->
        <div class="col-lg-8">
            <!-- Rechercher un stage -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Rechercher un stage</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="bi bi-search fs-3 text-muted mb-3"></i>
                        <h6>Utilisez notre interface de recherche améliorée</h6>
                        <p class="text-muted small mb-3">Trouvez facilement des stages correspondant à vos critères et ajoutez-les à vos préférences.</p>
                        <a href="/tutoring/views/student/search-internships.php" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>Rechercher des stages
                        </a>
                    </div>
                    
                    <!-- Hidden element for Stimulus compatibility -->
                    <div data-student-preferences-target="searchResults" class="border rounded overflow-hidden d-none"></div>
                    <input type="hidden" data-student-preferences-target="internshipSearch">
                </div>
            </div>
            
            <!-- Preferences Interface avec Stimulus -->
            <div class="card border-0 shadow-sm mb-4 fade-in" 
                 data-controller="student-preferences" 
                 <?php if ($student_id): ?>data-student-preferences-student-id-value="<?= $student_id ?>"<?php endif; ?>
                 data-student-preferences-max-preferences-value="5"
                 data-student-preferences-api-url-value="/tutoring/api/students">
                
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Stages préférés</h5>
                    <span class="badge bg-light text-primary" data-student-preferences-target="saveButton">
                        <i class="bi bi-info-circle me-1"></i>Faites glisser pour réordonner
                    </span>
                </div>
                
                <div class="card-body">
                    
                    <!-- Loading Indicator - Complètement supprimé -->
                    
                    <!-- Empty State -->
                    <?php if (empty($currentStudentPreferences)): ?>
                    <div class="py-3 text-center">
                        <div class="p-4 bg-light rounded">
                            <i class="bi bi-list-stars fs-1 text-muted"></i>
                            <h5 class="mt-3">Aucune préférence de stage</h5>
                            <p class="text-muted">Vous n'avez pas encore sélectionné de stages préférés. Utilisez la recherche ci-dessus pour ajouter des stages à vos préférences.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Préférences affichées directement par PHP, inspiré de internship.php -->
                    <div class="mb-4" id="preferences-container">
                        <?php foreach ($currentStudentPreferences as $index => $preference): ?>
                        <div class="d-flex align-items-center p-3 border rounded mb-2 bg-white position-relative preference-item" 
                             data-preference-id="<?= $preference['internship_id'] ?>"
                             data-preference-order="<?= $preference['preference_order'] ?>"
                             draggable="true"
                             ondragstart="dragStart(event)"
                             ondragover="dragOver(event)"
                             ondrop="drop(event)"
                             ondragend="dragEnd(event)">
                            <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle me-3" style="width: 32px; height: 32px;">
                                <?= $preference['preference_order'] ?>
                            </div>
                            <div class="d-flex align-items-center justify-content-center me-3 drag-handle" style="cursor: move; color: #aaa;">
                                <i class="bi bi-grip-vertical"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0"><?= htmlspecialchars($preference['title']) ?></h5>
                                <p class="text-muted mb-0"><?= htmlspecialchars($preference['company_name']) ?></p>
                                <?php if (!empty($preference['reason'])): ?>
                                <p class="small text-muted mt-1"><em>Raison: <?= htmlspecialchars($preference['reason']) ?></em></p>
                                <?php endif; ?>
                            </div>
                            <div class="preference-actions">
                                <?php if ($index > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" 
                                        onclick="movePreferenceUp(<?= $preference['internship_id'] ?>, <?= $preference['preference_order'] ?>)">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($index < count($currentStudentPreferences) - 1): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" 
                                        onclick="movePreferenceDown(<?= $preference['internship_id'] ?>, <?= $preference['preference_order'] ?>)">
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" 
                                        onclick="editReason(<?= $preference['internship_id'] ?>, '<?= addslashes($preference['reason'] ?? '') ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="removePreference(<?= $preference['internship_id'] ?>)">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hidden element for Stimulus compatibility -->
                    <div data-student-preferences-target="selectedPreferences" class="hidden"></div>
                    
                    <!-- Maximum Preferences Alert -->
                    <div data-student-preferences-target="maxPreferencesAlert" class="alert alert-warning d-none mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Vous avez atteint le nombre maximum de préférences (5). Veuillez supprimer une préférence avant d'en ajouter une nouvelle.
                    </div>
                    
                    <!-- La section de recherche a été déplacée en haut de la page -->
                    
                    <?php if (!empty($currentStudentPreferences)): ?>
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-info-circle me-2"></i>Vos préférences sont automatiquement enregistrées à chaque modification.
                    </div>
                    <?php else: ?>
                    <div class="d-grid">
                        <button type="button" class="btn btn-primary" 
                                data-student-preferences-target="saveButton" 
                                data-action="student-preferences#savePreferences" 
                                disabled>
                            <i class="bi bi-save me-2"></i>Enregistrer mes préférences
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Statistiques -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Statistiques
                </div>
                <div class="card-body" id="stats-container">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Préférences définies</span>
                            <strong id="preferences-count">0</strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="preferences-progress"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Stages disponibles</span>
                            <strong id="internships-count">0</strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" id="internships-progress"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>Profil complété</span>
                            <strong id="profile-completion">0%</strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" id="profile-progress"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <a href="/tutoring/views/student/search-internships.php" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-search me-2"></i>Rechercher des stages
                    </a>
                    <a href="/tutoring/views/student/internship.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-briefcase me-2"></i>Voir les stages
                    </a>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Mes documents
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/student/evaluations.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-star me-2"></i>Mes évaluations
                    </a>
                </div>
            </div>
            
            <!-- Aide -->
            <div class="card fade-in">
                <div class="card-header">
                    <i class="bi bi-question-circle me-1"></i> Aide
                </div>
                <div class="card-body">
                    <h6>Comment définir mes préférences ?</h6>
                    <ul class="small">
                        <li>Recherchez des stages qui vous intéressent</li>
                        <li>Ajoutez-les à vos préférences</li>
                        <li>Réorganisez-les par ordre de priorité</li>
                        <li>Enregistrez vos préférences</li>
                    </ul>
                    <hr>
                    <h6>Besoin d'aide ?</h6>
                    <p class="small">Contactez votre coordinateur ou votre tuteur pour obtenir de l'aide.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Section déplacée au début du fichier pour assurer que les préférences sont chargées avant l'affichage
?>

<script>
// Méthode de secours pour les préférences
const fallbackPreferences = <?= json_encode($currentStudentPreferences) ?>;
const fallbackStats = {
    preferences_count: <?= $preferencesCount ?>,
    available_internships: <?= $availableInternships ?>,
    profile_completion: <?= $preferencesCount > 0 ? 80 : 60 ?>
};

// Fonctions pour gérer les préférences directement
function movePreferenceUp(internshipId, currentOrder) {
    // Ajouter les paramètres au formulaire
    const formData = new FormData();
    formData.append('action', 'move_up');
    formData.append('internship_id', internshipId);
    formData.append('current_order', currentOrder);
    
    // Envoyer la requête
    fetch('/tutoring/api/students/update-preference-order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour refléter le nouvel ordre
            window.location.reload();
        } else {
            showNotification(data.message || "Erreur lors du déplacement de la préférence", "error");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
        showNotification("Erreur lors du déplacement de la préférence", "error");
    });
}

function movePreferenceDown(internshipId, currentOrder) {
    // Ajouter les paramètres au formulaire
    const formData = new FormData();
    formData.append('action', 'move_down');
    formData.append('internship_id', internshipId);
    formData.append('current_order', currentOrder);
    
    // Envoyer la requête
    fetch('/tutoring/api/students/update-preference-order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour refléter le nouvel ordre
            window.location.reload();
        } else {
            showNotification(data.message || "Erreur lors du déplacement de la préférence", "error");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
        showNotification("Erreur lors du déplacement de la préférence", "error");
    });
}

function editReason(internshipId, currentReason) {
    // Demander la nouvelle raison
    const reason = prompt("Pourquoi avez-vous choisi ce stage? (optionnel)", currentReason || "");
    
    // Si l'utilisateur annule, on ne fait rien
    if (reason === null) return;
    
    // Ajouter les paramètres au formulaire
    const formData = new FormData();
    formData.append('action', 'update_reason');
    formData.append('internship_id', internshipId);
    formData.append('reason', reason);
    
    // Envoyer la requête
    fetch('/tutoring/api/students/update-preference-reason.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour refléter la nouvelle raison
            window.location.reload();
        } else {
            showNotification(data.message || "Erreur lors de la mise à jour de la raison", "error");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
        showNotification("Erreur lors de la mise à jour de la raison", "error");
    });
}

function removePreference(internshipId) {
    // Demander confirmation
    if (!confirm("Êtes-vous sûr de vouloir supprimer cette préférence?")) return;
    
    console.log("Removing preference with ID:", internshipId);
    
    // Ajouter les paramètres au formulaire
    const formData = new FormData();
    formData.append('internship_id', internshipId);
    
    // Afficher un indicateur de chargement
    showNotification("Suppression de la préférence...", "info");
    
    // Log formData pour vérifier
    console.log("FormData entries for removal:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Envoyer la requête
    fetch('/tutoring/api/students/remove-preference.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Remove preference response status:", response.status);
        // Si la réponse n'est pas OK, log le texte brut de la réponse
        if (!response.ok) {
            response.text().then(text => {
                console.error("Raw response:", text);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log("Remove preference response data:", data);
        if (data.success) {
            showNotification("Préférence supprimée avec succès", "success");
            // Recharger la page après un court délai
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || "Erreur lors de la suppression de la préférence", "error");
        }
    })
    .catch(error => {
        console.error("Erreur lors de la suppression:", error);
        showNotification("Erreur lors de la suppression de la préférence", "error");
    });
}

function showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `position-fixed bottom-0 end-0 p-3`;
    notification.style.zIndex = 1050;
    
    // Set the appropriate bootstrap class based on type
    const alertClass = type === 'success' ? 'alert-success' : 
                       type === 'error' ? 'alert-danger' : 
                       'alert-info';
    
    notification.innerHTML = `
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} text-white">
          <strong class="me-auto">${type === 'success' ? 'Succès' : type === 'error' ? 'Erreur' : 'Information'}</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
      notification.remove();
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Enable more detailed console logging
    console.log("Initializing student preferences page");
    
    // Afficher les données de secours pour déboguer
    console.log("Fallback preferences data:", fallbackPreferences);
    console.log("Fallback stats data:", fallbackStats);
    
    // Initialize the controller with fallback data if needed
    window.fallbackPreferencesData = fallbackPreferences;
    window.fallbackStatsData = fallbackStats;
    
    // Vérifier si l'utilisateur a déjà 5 préférences pour afficher l'alerte
    const preferences = <?= json_encode($currentStudentPreferences) ?>;
    if (preferences && preferences.length >= 5) {
        const maxPreferencesAlert = document.querySelector('[data-student-preferences-target="maxPreferencesAlert"]');
        if (maxPreferencesAlert) {
            maxPreferencesAlert.classList.remove('d-none');
        }
    }
    
    // Fetch statistics 
    fetchPreferenceStats();
    
    // Function to fetch preference statistics
    function fetchPreferenceStats() {
        console.log("Fetching preference statistics");
        fetch('/tutoring/api/students/stats.php')
            .then(response => {
                console.log("Stats response status:", response.status);
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des statistiques');
                }
                return response.json();
            })
            .then(data => {
                console.log("Stats data:", data);
                updateStats(data.stats || fallbackStats);
                
                // Afficher l'alerte si l'utilisateur a 5 préférences
                if (data.stats && data.stats.preferences_count >= 5) {
                    const maxPreferencesAlert = document.querySelector('[data-student-preferences-target="maxPreferencesAlert"]');
                    if (maxPreferencesAlert) {
                        maxPreferencesAlert.classList.remove('d-none');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                // Utiliser les statistiques de secours en cas d'erreur
                console.log("Using fallback stats:", fallbackStats);
                updateStats(fallbackStats);
            });
    }
    
    // Update statistics display
    function updateStats(stats) {
        // Update preference count
        const preferencesCount = stats.preferences_count || 0;
        document.getElementById('preferences-count').textContent = preferencesCount + '/5';
        document.getElementById('preferences-progress').style.width = (preferencesCount / 5 * 100) + '%';
        
        // Update internships count
        const internshipsCount = stats.available_internships || 0;
        document.getElementById('internships-count').textContent = internshipsCount;
        document.getElementById('internships-progress').style.width = internshipsCount > 0 ? '100%' : '0%';
        
        // Update profile completion
        const profileCompletion = stats.profile_completion || 0;
        document.getElementById('profile-completion').textContent = profileCompletion + '%';
        document.getElementById('profile-progress').style.width = profileCompletion + '%';
    }
    
    // Amélioration de l'interface de recherche
    const searchInput = document.querySelector('[data-student-preferences-target="internshipSearch"]');
    if (searchInput) {
        console.log("Setting up search input");
        searchInput.setAttribute('placeholder', 'Saisissez une lettre pour rechercher un stage...');
        
        // Log input events to debug search issues
        searchInput.addEventListener('input', function(e) {
            console.log("Search input value:", e.target.value);
        });
    }
    
    // Nous n'avons plus besoin d'appliquer les préférences de secours car nous les affichons directement en PHP
    // Mais nous laissons cette logique au cas où le contrôleur stimulus est toujours utilisé ailleurs
    const applyFallbackPreferences = (forceUpdate = false) => {
        console.log("Checking if fallback preferences need to be applied");
        const preferencesElement = document.querySelector('[data-controller="student-preferences"]');
        if (!preferencesElement) {
            console.log("No preferences element found");
            return;
        }
        
        const controller = window.Stimulus.getControllerForElementAndIdentifier(preferencesElement, 'student-preferences');
        if (!controller) {
            console.log("No controller found for preferences element");
            return;
        }
        
        const shouldApplyFallback = forceUpdate || 
            !controller.preferences || 
            controller.preferences.length === 0;
        
        if (shouldApplyFallback && fallbackPreferences && fallbackPreferences.length > 0) {
            console.log("Applying fallback preferences data");
            controller.preferences = fallbackPreferences.map(pref => ({
                internship_id: pref.internship_id,
                title: pref.title || 'Stage sans titre',
                company: pref.company_name || 'Entreprise non spécifiée',
                rank: pref.rank || pref.preference_order || 1,
                reason: pref.reason || null
            }));
            
            // Masquer l'indicateur de chargement s'il est encore visible
            const loadingIndicator = document.querySelector('[data-student-preferences-target="loadingIndicator"]');
            if (loadingIndicator && !loadingIndicator.classList.contains('hidden')) {
                loadingIndicator.classList.add('hidden');
            }
            
            // Mettre à jour la liste des préférences
            controller.updatePreferencesList();
            console.log("Fallback preferences applied");
        } else if (fallbackPreferences && fallbackPreferences.length > 0) {
            console.log("Controller already has preferences, not applying fallback");
        } else {
            console.log("No fallback preferences data available");
        }
    };
});

// La recherche automatique a été désactivée puisque nous utilisons maintenant
// une page dédiée pour la recherche de stages : search-internships.php
console.log("Search functionality moved to dedicated page: search-internships.php");

// Variables pour le drag and drop
let draggedItem = null;
let originalPosition = null;
let startIndex = null;
let endIndex = null;

// Fonctions pour le drag and drop
function dragStart(event) {
    // Stocker l'élément qu'on est en train de déplacer
    draggedItem = event.target;
    originalPosition = Array.from(draggedItem.parentNode.children).indexOf(draggedItem);
    startIndex = originalPosition;
    
    // Effet visuel pendant le drag
    setTimeout(() => {
        draggedItem.classList.add('dragging');
    }, 0);
    
    // Définir les données transférées
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/html', draggedItem.outerHTML);
}

function dragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    
    const container = document.getElementById('preferences-container');
    const items = Array.from(container.querySelectorAll('.preference-item'));
    
    // Survoler l'élément sur lequel on va déposer
    const overItem = event.target.closest('.preference-item');
    if (!overItem || overItem === draggedItem) return;
    
    // Déterminer la position de l'élément survolé
    const overIndex = items.indexOf(overItem);
    
    // Ajouter une classe pour indiquer où l'élément sera déposé
    items.forEach(item => item.classList.remove('drop-above', 'drop-below'));
    
    if (overIndex > originalPosition) {
        overItem.classList.add('drop-below');
    } else {
        overItem.classList.add('drop-above');
    }
}

function drop(event) {
    event.preventDefault();
    
    const container = document.getElementById('preferences-container');
    const items = Array.from(container.querySelectorAll('.preference-item'));
    
    // Déterminer l'élément sur lequel on dépose
    const overItem = event.target.closest('.preference-item');
    if (!overItem || overItem === draggedItem) return;
    
    console.log("Drop event triggered");
    console.log("Dragged item:", draggedItem);
    console.log("Dropped over:", overItem);
    
    // Trouver la nouvelle position
    endIndex = items.indexOf(overItem);
    
    // Récupérer les données nécessaires pour l'API
    const internshipId = draggedItem.dataset.preferenceId;
    const fromOrder = parseInt(draggedItem.dataset.preferenceOrder);
    const toOrder = parseInt(overItem.dataset.preferenceOrder);
    
    console.log(`Drop: internship=${internshipId}, fromOrder=${fromOrder}, toOrder=${toOrder}`);
    
    // Débogage des attributs data
    console.log("Dragged item attributes:", {
        "data-preference-id": draggedItem.getAttribute("data-preference-id"),
        "data-preference-order": draggedItem.getAttribute("data-preference-order"),
        "dataset.preferenceId": draggedItem.dataset.preferenceId,
        "dataset.preferenceOrder": draggedItem.dataset.preferenceOrder
    });
    console.log("Target item attributes:", {
        "data-preference-id": overItem.getAttribute("data-preference-id"),
        "data-preference-order": overItem.getAttribute("data-preference-order"),
        "dataset.preferenceId": overItem.dataset.preferenceId,
        "dataset.preferenceOrder": overItem.dataset.preferenceOrder
    });
    
    if (isNaN(fromOrder) || isNaN(toOrder) || fromOrder === toOrder) {
        console.error("Invalid order values or same position");
        return;
    }
    
    // Appliquer un effet visuel temporaire
    draggedItem.style.opacity = "0.5";
    overItem.classList.add('highlight-drop');
    
    // Appeler l'API pour mettre à jour l'ordre
    updatePreferenceOrder(internshipId, fromOrder, toOrder);
}

function dragEnd(event) {
    // Réinitialiser le style d'opacité
    if (draggedItem) {
        draggedItem.style.opacity = "";
    }
    
    // Enlever les effets visuels
    event.target.classList.remove('dragging');
    
    // Enlever les indicateurs de drop
    const items = document.querySelectorAll('.preference-item');
    items.forEach(item => {
        item.classList.remove('drop-above', 'drop-below', 'highlight-drop');
    });
}

// Fonction pour mettre à jour l'ordre des préférences via l'API
function updatePreferenceOrder(internshipId, fromOrder, toOrder) {
    console.log(`Updating preference order: internship=${internshipId}, from=${fromOrder}, to=${toOrder}`);
    
    // Validation des données côté client
    if (!internshipId) {
        console.error("Erreur: ID du stage manquant");
        showNotification("Erreur: ID du stage manquant", "error");
        return;
    }
    
    if (isNaN(fromOrder) || fromOrder <= 0) {
        console.error("Erreur: Ordre de départ invalide:", fromOrder);
        showNotification("Erreur: Ordre de départ invalide", "error");
        return;
    }
    
    if (isNaN(toOrder) || toOrder <= 0) {
        console.error("Erreur: Ordre de destination invalide:", toOrder);
        showNotification("Erreur: Ordre de destination invalide", "error");
        return;
    }
    
    const formData = new FormData();
    formData.append('internship_id', internshipId);
    formData.append('from_order', fromOrder);
    formData.append('to_order', toOrder);
    
    // Log formData pour vérifier que les données sont bien présentes
    console.log("FormData entries:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Afficher un indicateur de chargement
    showNotification("Mise à jour de l'ordre des préférences...", "info");
    
    fetch('/tutoring/api/students/update-preference-order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Response status:", response.status);
        // Si la réponse n'est pas OK, log le texte brut de la réponse
        if (!response.ok) {
            response.text().then(text => {
                console.error("Raw response:", text);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log("Response data:", data);
        if (data.success) {
            showNotification("Ordre des préférences mis à jour avec succès", "success");
            // Recharger la page après un court délai
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || "Erreur lors de la mise à jour de l'ordre des préférences", "error");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
        showNotification("Erreur lors de la mise à jour de l'ordre des préférences", "error");
    });
}
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>