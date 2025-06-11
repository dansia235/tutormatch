<?php
/**
 * Page de gestion des préférences du tuteur
 */

// Titre de la page
$pageTitle = 'Mes préférences de tutorat';
$currentPage = 'preferences';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Récupérer le tuteur de la session
$userModel = new User($db);
$user = $userModel->getById($_SESSION['user_id']);

// Récupérer le modèle du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    setFlashMessage('error', 'Profil de tuteur non trouvé.');
    redirect('/tutoring/index.php');
}

// Récupérer le modèle des stages
$internshipModel = new Internship($db);
$domains = $internshipModel->getDomains();

// Récupérer les préférences actuelles du tuteur
$preferences = [];
if (!empty($teacher['preferences'])) {
    $preferences = json_decode($teacher['preferences'], true);
    if (!is_array($preferences)) {
        $preferences = [];
    }
}

// Traiter la mise à jour des préférences générales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general_preferences'])) {
    $generalPreferences = [
        'preferred_domains' => isset($_POST['preferred_domains']) ? implode(',', $_POST['preferred_domains']) : '',
        'max_students' => isset($_POST['max_students']) ? intval($_POST['max_students']) : 5,
        'preferred_meeting_days' => isset($_POST['preferred_meeting_days']) ? implode(',', $_POST['preferred_meeting_days']) : '',
        'preferred_meeting_times' => isset($_POST['preferred_meeting_times']) ? $_POST['preferred_meeting_times'] : '',
        'communication_preferences' => isset($_POST['communication_preferences']) ? implode(',', $_POST['communication_preferences']) : '',
        'expertise_areas' => isset($_POST['expertise_areas']) ? $_POST['expertise_areas'] : '',
        'additional_notes' => isset($_POST['additional_notes']) ? $_POST['additional_notes'] : ''
    ];
    
    if ($teacherModel->update($teacher['id'], ['preferences' => json_encode($generalPreferences)])) {
        setFlashMessage('success', 'Préférences mises à jour avec succès');
        
        // Mettre à jour les préférences en mémoire
        $preferences = $generalPreferences;
    } else {
        setFlashMessage('error', 'Erreur lors de la mise à jour des préférences');
    }
    
    // Redirection pour éviter les soumissions multiples par rafraîchissement
    redirect('/tutoring/views/tutor/preferences.php');
}

// Traiter la mise à jour des préférences de disponibilité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    // Récupérer les données de disponibilité
    $availability = [];
    $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $timeSlots = ['morning', 'afternoon', 'evening'];
    
    foreach ($weekdays as $day) {
        $availability[$day] = [];
        foreach ($timeSlots as $slot) {
            $availability[$day][$slot] = isset($_POST['availability'][$day][$slot]) ? true : false;
        }
    }
    
    // Mettre à jour les préférences existantes avec les nouvelles disponibilités
    $updatedPreferences = $preferences;
    $updatedPreferences['availability'] = $availability;
    
    if ($teacherModel->update($teacher['id'], ['preferences' => json_encode($updatedPreferences)])) {
        setFlashMessage('success', 'Disponibilités mises à jour avec succès');
        
        // Mettre à jour les préférences en mémoire
        $preferences = $updatedPreferences;
    } else {
        setFlashMessage('error', 'Erreur lors de la mise à jour des disponibilités');
    }
    
    // Redirection pour éviter les soumissions multiples par rafraîchissement
    redirect('/tutoring/views/tutor/preferences.php');
}

// Traiter la mise à jour des préférences de notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $notificationPreferences = [
        'email_notifications' => isset($_POST['email_notifications']) ? $_POST['email_notifications'] : [],
        'sms_notifications' => isset($_POST['sms_notifications']) ? $_POST['sms_notifications'] : [],
        'notification_frequency' => isset($_POST['notification_frequency']) ? $_POST['notification_frequency'] : 'daily'
    ];
    
    // Mettre à jour les préférences existantes avec les nouvelles notifications
    $updatedPreferences = $preferences;
    $updatedPreferences['notifications'] = $notificationPreferences;
    
    if ($teacherModel->update($teacher['id'], ['preferences' => json_encode($updatedPreferences)])) {
        setFlashMessage('success', 'Préférences de notification mises à jour avec succès');
        
        // Mettre à jour les préférences en mémoire
        $preferences = $updatedPreferences;
    } else {
        setFlashMessage('error', 'Erreur lors de la mise à jour des préférences de notification');
    }
    
    // Redirection pour éviter les soumissions multiples par rafraîchissement
    redirect('/tutoring/views/tutor/preferences.php');
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-sliders me-2"></i>Mes préférences de tutorat</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Préférences</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Onglets de navigation -->
    <ul class="nav nav-tabs mb-4" id="preferencesTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-content" 
                    type="button" role="tab" aria-controls="general-content" aria-selected="true">
                <i class="bi bi-gear me-1"></i> Préférences générales
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability-content" 
                    type="button" role="tab" aria-controls="availability-content" aria-selected="false">
                <i class="bi bi-calendar-week me-1"></i> Disponibilité
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications-content" 
                    type="button" role="tab" aria-controls="notifications-content" aria-selected="false">
                <i class="bi bi-bell me-1"></i> Notifications
            </button>
        </li>
    </ul>
    
    <!-- Contenu des onglets -->
    <div class="tab-content" id="preferencesTabContent">
        <!-- Onglet des préférences générales -->
        <div class="tab-pane fade show active" id="general-content" role="tabpanel" aria-labelledby="general-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Préférences générales de tutorat</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Ces préférences seront prises en compte lors de l'affectation des étudiants et la planification des activités de tutorat.</p>
                    
                    <form action="/tutoring/views/tutor/preferences.php" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="preferred_domains" class="form-label fw-bold">Domaines d'expertise préférés</label>
                                    <select id="preferred_domains" name="preferred_domains[]" class="form-select" multiple>
                                        <?php foreach ($domains as $domain): ?>
                                        <option value="<?php echo h($domain); ?>" <?php echo (isset($preferences['preferred_domains']) && in_array($domain, explode(',', $preferences['preferred_domains']))) ? 'selected' : ''; ?>>
                                            <?php echo h($domain); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs domaines</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="max_students" class="form-label fw-bold">Nombre maximum d'étudiants</label>
                                    <input type="number" id="max_students" name="max_students" class="form-control" 
                                           value="<?php echo h($preferences['max_students'] ?? 5); ?>" 
                                           min="1" max="20">
                                    <small class="form-text text-muted">Nombre maximum d'étudiants que vous souhaitez encadrer simultanément</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jours préférés pour les réunions</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="preferred_meeting_days[]" value="monday" id="day_monday"
                                               <?php echo (isset($preferences['preferred_meeting_days']) && strpos($preferences['preferred_meeting_days'], 'monday') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_monday">Lundi</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="preferred_meeting_days[]" value="tuesday" id="day_tuesday"
                                               <?php echo (isset($preferences['preferred_meeting_days']) && strpos($preferences['preferred_meeting_days'], 'tuesday') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_tuesday">Mardi</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="preferred_meeting_days[]" value="wednesday" id="day_wednesday"
                                               <?php echo (isset($preferences['preferred_meeting_days']) && strpos($preferences['preferred_meeting_days'], 'wednesday') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_wednesday">Mercredi</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="preferred_meeting_days[]" value="thursday" id="day_thursday"
                                               <?php echo (isset($preferences['preferred_meeting_days']) && strpos($preferences['preferred_meeting_days'], 'thursday') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_thursday">Jeudi</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="preferred_meeting_days[]" value="friday" id="day_friday"
                                               <?php echo (isset($preferences['preferred_meeting_days']) && strpos($preferences['preferred_meeting_days'], 'friday') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_friday">Vendredi</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="preferred_meeting_times" class="form-label fw-bold">Horaires préférés pour les réunions</label>
                                    <input type="text" id="preferred_meeting_times" name="preferred_meeting_times" class="form-control" 
                                           value="<?php echo h($preferences['preferred_meeting_times'] ?? ''); ?>" 
                                           placeholder="Ex: 10h-12h, 14h-16h">
                                    <small class="form-text text-muted">Précisez vos créneaux horaires préférés</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Méthodes de communication préférées</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="communication_preferences[]" value="email" id="comm_email"
                                               <?php echo (isset($preferences['communication_preferences']) && strpos($preferences['communication_preferences'], 'email') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="comm_email">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="communication_preferences[]" value="phone" id="comm_phone"
                                               <?php echo (isset($preferences['communication_preferences']) && strpos($preferences['communication_preferences'], 'phone') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="comm_phone">Téléphone</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="communication_preferences[]" value="video" id="comm_video"
                                               <?php echo (isset($preferences['communication_preferences']) && strpos($preferences['communication_preferences'], 'video') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="comm_video">Visioconférence</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="communication_preferences[]" value="in_person" id="comm_in_person"
                                               <?php echo (isset($preferences['communication_preferences']) && strpos($preferences['communication_preferences'], 'in_person') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="comm_in_person">En personne</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="expertise_areas" class="form-label fw-bold">Domaines d'expertise spécifiques</label>
                                    <textarea id="expertise_areas" name="expertise_areas" class="form-control" rows="3" 
                                             placeholder="Ex: Développement web, Intelligence artificielle, Gestion de projet..."><?php echo h($preferences['expertise_areas'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Précisez vos domaines d'expertise spécifiques qui pourraient être utiles pour les étudiants</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="additional_notes" class="form-label fw-bold">Notes complémentaires</label>
                            <textarea id="additional_notes" name="additional_notes" class="form-control" rows="4" 
                                     placeholder="Autres informations ou précisions sur vos préférences..."><?php echo h($preferences['additional_notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="update_general_preferences" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Enregistrer les préférences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Onglet de disponibilité -->
        <div class="tab-pane fade" id="availability-content" role="tabpanel" aria-labelledby="availability-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Disponibilité pour le tutorat</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Indiquez vos disponibilités hebdomadaires pour les réunions avec les étudiants et autres activités de tutorat.</p>
                    
                    <form action="/tutoring/views/tutor/preferences.php" method="POST">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jour</th>
                                        <th>Matin (8h-12h)</th>
                                        <th>Après-midi (12h-17h)</th>
                                        <th>Soir (17h-20h)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $days = [
                                        'monday' => 'Lundi',
                                        'tuesday' => 'Mardi',
                                        'wednesday' => 'Mercredi',
                                        'thursday' => 'Jeudi',
                                        'friday' => 'Vendredi'
                                    ];
                                    
                                    $timeSlots = ['morning', 'afternoon', 'evening'];
                                    
                                    foreach ($days as $dayKey => $dayName):
                                    ?>
                                    <tr>
                                        <td><?php echo h($dayName); ?></td>
                                        <?php foreach ($timeSlots as $slot): ?>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="availability[<?php echo $dayKey; ?>][<?php echo $slot; ?>]" 
                                                       id="avail_<?php echo $dayKey; ?>_<?php echo $slot; ?>"
                                                       <?php echo (isset($preferences['availability'][$dayKey][$slot]) && $preferences['availability'][$dayKey][$slot]) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="avail_<?php echo $dayKey; ?>_<?php echo $slot; ?>">
                                                    Disponible
                                                </label>
                                            </div>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <button type="submit" name="update_availability" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Enregistrer les disponibilités
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Onglet des notifications -->
        <div class="tab-pane fade" id="notifications-content" role="tabpanel" aria-labelledby="notifications-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Préférences de notification</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Personnalisez la façon dont vous souhaitez être notifié des événements liés au tutorat.</p>
                    
                    <form action="/tutoring/views/tutor/preferences.php" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Notifications par email</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[]" value="new_document" id="email_new_document"
                                               <?php echo (isset($preferences['notifications']['email_notifications']) && in_array('new_document', $preferences['notifications']['email_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_new_document">Nouveau document soumis par un étudiant</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[]" value="meeting_request" id="email_meeting_request"
                                               <?php echo (isset($preferences['notifications']['email_notifications']) && in_array('meeting_request', $preferences['notifications']['email_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_meeting_request">Demande de réunion</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[]" value="meeting_reminder" id="email_meeting_reminder"
                                               <?php echo (isset($preferences['notifications']['email_notifications']) && in_array('meeting_reminder', $preferences['notifications']['email_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_meeting_reminder">Rappel de réunion</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[]" value="new_message" id="email_new_message"
                                               <?php echo (isset($preferences['notifications']['email_notifications']) && in_array('new_message', $preferences['notifications']['email_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_new_message">Nouveau message</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[]" value="evaluation_reminder" id="email_evaluation_reminder"
                                               <?php echo (isset($preferences['notifications']['email_notifications']) && in_array('evaluation_reminder', $preferences['notifications']['email_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_evaluation_reminder">Rappel d'évaluation à effectuer</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[]" value="new_student" id="email_new_student"
                                               <?php echo (isset($preferences['notifications']['email_notifications']) && in_array('new_student', $preferences['notifications']['email_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_new_student">Nouvel étudiant assigné</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Notifications par SMS</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications[]" value="meeting_reminder" id="sms_meeting_reminder"
                                               <?php echo (isset($preferences['notifications']['sms_notifications']) && in_array('meeting_reminder', $preferences['notifications']['sms_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sms_meeting_reminder">Rappel de réunion</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications[]" value="urgent_message" id="sms_urgent_message"
                                               <?php echo (isset($preferences['notifications']['sms_notifications']) && in_array('urgent_message', $preferences['notifications']['sms_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sms_urgent_message">Message urgent</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications[]" value="meeting_request" id="sms_meeting_request"
                                               <?php echo (isset($preferences['notifications']['sms_notifications']) && in_array('meeting_request', $preferences['notifications']['sms_notifications'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sms_meeting_request">Demande de réunion</label>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notification_frequency" class="form-label fw-bold">Fréquence des résumés</label>
                                    <select id="notification_frequency" name="notification_frequency" class="form-select">
                                        <option value="immediate" <?php echo (isset($preferences['notifications']['notification_frequency']) && $preferences['notifications']['notification_frequency'] === 'immediate') ? 'selected' : ''; ?>>Immédiate (à chaque événement)</option>
                                        <option value="daily" <?php echo (!isset($preferences['notifications']['notification_frequency']) || $preferences['notifications']['notification_frequency'] === 'daily') ? 'selected' : ''; ?>>Quotidienne</option>
                                        <option value="weekly" <?php echo (isset($preferences['notifications']['notification_frequency']) && $preferences['notifications']['notification_frequency'] === 'weekly') ? 'selected' : ''; ?>>Hebdomadaire</option>
                                    </select>
                                    <small class="form-text text-muted">Fréquence à laquelle vous souhaitez recevoir des résumés d'activité</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="update_notifications" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Enregistrer les préférences de notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Activer les onglets Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var triggerTabList = [].slice.call(document.querySelectorAll('#preferencesTab button'))
        triggerTabList.forEach(function(triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })
        
        // Activer l'onglet spécifié dans l'URL si présent
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('tab')) {
            const tab = urlParams.get('tab');
            if (tab === 'availability') {
                const availabilityTab = document.querySelector('#availability-tab');
                if (availabilityTab) {
                    const tabInstance = new bootstrap.Tab(availabilityTab);
                    tabInstance.show();
                }
            } else if (tab === 'notifications') {
                const notificationsTab = document.querySelector('#notifications-tab');
                if (notificationsTab) {
                    const tabInstance = new bootstrap.Tab(notificationsTab);
                    tabInstance.show();
                }
            }
        }
    })
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>