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

// Récupérer l'ID de l'étudiant
$student_id = $_SESSION['user']['id'] ?? null;

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
        <!-- Left Column with Preferences Interface -->
        <div class="col-lg-8">
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
                    <!-- Loading Indicator - Masqué car nous affichons directement les préférences -->
                    <div data-student-preferences-target="loadingIndicator" class="text-center py-3 hidden">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement de vos préférences...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <?php if (empty($currentStudentPreferences)): ?>
                    <div class="py-3 text-center">
                        <div class="p-4 bg-light rounded">
                            <i class="bi bi-list-stars fs-1 text-muted"></i>
                            <h5 class="mt-3">Aucune préférence de stage</h5>
                            <p class="text-muted">Vous n'avez pas encore sélectionné de stages préférés. Utilisez la recherche ci-dessous pour ajouter des stages à vos préférences.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Préférences affichées directement par PHP, inspiré de internship.php -->
                    <div class="mb-4">
                        <?php foreach ($currentStudentPreferences as $index => $preference): ?>
                        <div class="d-flex align-items-center p-3 border rounded mb-2 bg-white position-relative preference-item" data-preference-id="<?= $preference['internship_id'] ?>">
                            <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle me-3" style="width: 32px; height: 32px;">
                                <?= $preference['preference_order'] ?>
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
                    <div data-student-preferences-target="maxPreferencesAlert" class="alert alert-warning hidden mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Vous avez atteint le nombre maximum de préférences (5). Veuillez supprimer une préférence avant d'en ajouter une nouvelle.
                    </div>
                    
                    <!-- Internship Search -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Rechercher un stage</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Commencez à taper pour voir les stages correspondants..." 
                                           data-student-preferences-target="internshipSearch" 
                                           data-action="input->student-preferences#search"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <div class="form-text small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Exemple: tapez "p" pour voir les stages commençant par "p", puis "pa" pour affiner la recherche.
                                </div>
                            </div>
                            
                            <!-- Search Results -->
                            <div data-student-preferences-target="searchResults" class="border rounded overflow-hidden"></div>
                        </div>
                    </div>
                    
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
// Charger les préférences directement depuis la base de données comme secours
$currentStudentPreferences = [];

try {
    // S'assurer que l'identifiant de l'utilisateur est disponible
    $user_id = $_SESSION['user_id'] ?? null;
    error_log("Loading preferences with user_id: " . ($user_id ?: 'null'));
    
    if ($user_id) {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($user_id);
        
        if ($student) {
            error_log("Found student with ID: " . $student['id']);
            $preferences = $studentModel->getPreferences($student['id']);
            error_log("Found " . count($preferences) . " preferences for student ID " . $student['id']);
            
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
            
            error_log("Formatted " . count($currentStudentPreferences) . " preferences for JavaScript");
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

// Statistiques de secours pour initialiser l'interface
$internshipModel = new Internship($db);
$availableInternships = count($internshipModel->getAvailable());
$preferencesCount = count($currentStudentPreferences);
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
    
    // Ajouter les paramètres au formulaire
    const formData = new FormData();
    formData.append('internship_id', internshipId);
    
    // Envoyer la requête
    fetch('/tutoring/api/students/remove-preference.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour refléter la suppression
            window.location.reload();
        } else {
            showNotification(data.message || "Erreur lors de la suppression de la préférence", "error");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
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

// Initialiser la recherche automatiquement au chargement
setTimeout(() => {
    // Déclencher une recherche vide pour afficher des stages récents
    const searchInput = document.querySelector('[data-student-preferences-target="internshipSearch"]');
    if (searchInput) {
        searchInput.focus();
        
        // Ajouter un log pour suivre l'événement
        console.log("Triggering initial search event");
        
        // Simuler un événement de recherche pour charger les stages disponibles
        const event = new Event('input', { bubbles: true });
        searchInput.dispatchEvent(event);
        
        // Forcer une recherche vide après un court délai si les résultats n'apparaissent pas
        setTimeout(() => {
            console.log("Forcing direct controller search call");
            const preferencesElement = document.querySelector('[data-controller="student-preferences"]');
            if (preferencesElement) {
                const controller = window.Stimulus.getControllerForElementAndIdentifier(preferencesElement, 'student-preferences');
                if (controller && typeof controller.performSearch === 'function') {
                    console.log("Calling controller.performSearch() directly");
                    controller.performSearch('');
                }
            }
        }, 1000);
    }
}, 500);
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>