<?php
/**
 * Tableau de bord tuteur - Version améliorée et optimisée
 */

// Titre de la page
$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Initialiser les modèles nécessaires
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

// Charger les données directement
$studentModel = new Student($db);
$assignmentModel = new Assignment($db);

// Récupérer les affectations du tuteur (même méthode que students.php)
$assignments = $teacherModel->getAssignments($teacher['id']);

// Récupérer les étudiants
$students = [];
foreach ($assignments as $assignment) {
    // Les données de l'étudiant sont déjà dans l'assignment grâce aux JOINs
    $students[] = [
        'id' => $assignment['student_id'],
        'name' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name'],
        'program' => $assignment['program'] ?? $assignment['level'] ?? 'Non spécifié',
        'assignment_status' => $assignment['status']
    ];
}

// Récupérer les évaluations
$evaluations = [];
if (class_exists('Evaluation')) {
    $evaluationModel = new Evaluation($db);
    $evaluations = $evaluationModel->getByTeacherId($teacher['id']);
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Tableau de bord</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card" id="students-card">
                <div class="card-body">
                    <div class="value"><?php echo count($students); ?></div>
                    <div class="label">Étudiants</div>
                    <div class="progress mt-2">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo count($students) > 0 ? '100' : '0'; ?>%;" 
                             aria-valuenow="<?php echo count($students) > 0 ? '100' : '0'; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Étudiants affectés</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card" id="meetings-card">
                <div class="card-body">
                    <div class="value" id="meetings-count">-</div>
                    <div class="label">Réunions</div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Réunions à venir</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card" id="evaluations-card">
                <div class="card-body">
                    <div class="value"><?php echo count($evaluations); ?></div>
                    <div class="label">Évaluations</div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-info" role="progressbar" 
                             style="width: <?php echo min(100, (count($evaluations) / 10) * 100); ?>%;" 
                             aria-valuenow="<?php echo min(100, (count($evaluations) / 10) * 100); ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Évaluations réalisées</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card" id="messages-card">
                <div class="card-body">
                    <div class="value" id="messages-count">-</div>
                    <div class="label">Messages</div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Messages non lus</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- My Students -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mes étudiants</span>
                    <a href="/tutoring/views/tutor/students.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body" id="students-list">
                    <?php if (empty($students)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucun étudiant affecté pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php 
                            $displayStudents = array_slice($students, 0, 5); 
                            foreach ($displayStudents as $student): 
                                // Déterminer la classe de badge selon le statut
                                $statusClass = 'bg-secondary';
                                $statusText = 'Inconnu';
                                
                                switch ($student['assignment_status']) {
                                    case 'pending':
                                        $statusClass = 'bg-warning text-dark';
                                        $statusText = 'En attente';
                                        break;
                                    case 'confirmed':
                                        $statusClass = 'bg-success';
                                        $statusText = 'Confirmé';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Rejeté';
                                        break;
                                    case 'completed':
                                        $statusClass = 'bg-info';
                                        $statusText = 'Terminé';
                                        break;
                                }
                            ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?php echo h($student['name']); ?></div>
                                        <small><?php echo h($student['program']); ?></small>
                                    </div>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($students) > 5): ?>
                            <div class="text-end mt-3">
                                <a href="/tutoring/views/tutor/students.php" class="text-decoration-none">
                                    Voir tous les <?php echo count($students); ?> étudiants
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upcoming Meetings -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Réunions à venir</span>
                    <a href="/tutoring/views/tutor/meetings.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Planifier
                    </a>
                </div>
                <div class="card-body" id="meetings-list">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Evaluations -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Évaluations récentes</span>
                    <a href="/tutoring/views/tutor/evaluations.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body" id="evaluations-list">
                    <?php if (empty($evaluations)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucune évaluation n'a encore été réalisée.
                            <a href="/tutoring/views/tutor/evaluations.php" class="alert-link">Évaluer un étudiant</a>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Score</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                $displayEvaluations = array_slice($evaluations, 0, 5);
                                $evaluationTypes = [
                                    'mid_term' => 'Mi-parcours',
                                    'final' => 'Finale',
                                    'technical' => 'Technique',
                                    'soft_skills' => 'Compétences',
                                    'student' => 'Auto-évaluation'
                                ];
                                
                                foreach ($displayEvaluations as $evaluation):
                                    // Récupérer l'affectation pour trouver l'étudiant
                                    $assignment = $assignmentModel->getById($evaluation['assignment_id']);
                                    $studentName = "Étudiant non spécifié";
                                    if ($assignment) {
                                        $student = $studentModel->getById($assignment['student_id']);
                                        if ($student) {
                                            $studentName = $student['first_name'] . ' ' . $student['last_name'];
                                        }
                                    }
                                    
                                    // S'assurer que le score est sur une échelle de 5
                                    $scoreOn5 = $evaluation['score'];
                                    if ($scoreOn5 > 5) {
                                        $scoreOn5 = number_format($scoreOn5 / 4, 1);
                                    }
                                    
                                    // Déterminer le type d'évaluation
                                    $evalType = isset($evaluationTypes[$evaluation['type']]) 
                                        ? $evaluationTypes[$evaluation['type']] 
                                        : ucfirst(str_replace('_', ' ', $evaluation['type']));
                                ?>
                                    <tr>
                                        <td><?php echo h($studentName); ?></td>
                                        <td><?php echo h($evalType); ?></td>
                                        <td><?php echo !empty($evaluation['submission_date']) ? date('d/m/Y', strtotime($evaluation['submission_date'])) : (!empty($evaluation['created_at']) ? date('d/m/Y', strtotime($evaluation['created_at'])) : 'Non spécifiée'); ?></td>
                                        <td><?php echo $scoreOn5; ?>/5</td>
                                        <td>
                                            <a href="/tutoring/views/tutor/export_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (count($evaluations) > 5): ?>
                            <div class="text-end mt-3">
                                <a href="/tutoring/views/tutor/evaluations.php" class="text-decoration-none">
                                    Voir toutes les <?php echo count($evaluations); ?> évaluations
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <a href="/tutoring/views/tutor/students.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-mortarboard me-2"></i>Voir mes étudiants
                    </a>
                    <a href="/tutoring/views/tutor/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/tutor/evaluations.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-clipboard-check me-2"></i>Évaluer un étudiant
                    </a>
                    <a href="/tutoring/views/tutor/messages.php" class="btn btn-primary w-100">
                        <i class="bi bi-chat-left-text me-2"></i>Envoyer un message
                    </a>
                </div>
            </div>
            
            <!-- Recent Messages -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Messages récents</span>
                    <a href="/tutoring/views/tutor/messages.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body" id="messages-list">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Notifications -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Notifications récentes
                </div>
                <div class="card-body" id="notifications-list">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ajustement des stat cards */
.stat-card {
    padding: 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color, #3a5fe5);
}

.stat-card .label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Animation pour le fade-in */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Délais pour l'animation */
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Charger les réunions et messages une fois que la page est prête
    loadMeetings();
    loadMessages();
    loadNotifications();
    
    // Rafraîchir les données toutes les 5 minutes
    setInterval(function() {
        loadMeetings();
        loadMessages();
        loadNotifications();
    }, 300000);
});

function loadMeetings() {
    // Utiliser l'endpoint direct pour éviter les problèmes d'API
    fetch('/tutoring/views/tutor/dashboard_data.php?type=meetings')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des réunions');
            }
            return response.json();
        })
        .then(data => {
            updateMeetingsCard(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('meetings-list').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur lors du chargement des réunions.
                </div>
            `;
        });
}

function updateMeetingsCard(data) {
    const meetings = data.data;
    const meetingsCount = data.meta.total_records;
    
    // Mettre à jour le compteur
    document.getElementById('meetings-count').textContent = meetingsCount;
    
    // Mettre à jour la barre de progression (pourcentage basé sur le nombre de réunions, max 5 = 100%)
    const progressBar = document.querySelector('#meetings-card .progress-bar');
    const percentage = Math.min(100, (meetingsCount / 5) * 100);
    progressBar.style.width = `${percentage}%`;
    progressBar.setAttribute('aria-valuenow', percentage);
    
    // Mettre à jour la liste des réunions
    const meetingsList = document.getElementById('meetings-list');
    
    if (meetingsCount === 0) {
        meetingsList.innerHTML = `
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>Aucune réunion planifiée. 
                <a href="/tutoring/views/tutor/meetings.php" class="alert-link">Planifier une réunion</a>.
            </div>
        `;
        return;
    }
    
    let html = '<ul class="list-group list-group-flush">';
    
    for (const meeting of meetings) {
        // Construire la date à partir des champs disponibles
        const meetingDate = meeting.meeting_date || `${meeting.date} ${meeting.start_time}`;
        const dateObj = new Date(meetingDate);
        const formattedDate = dateObj.toLocaleDateString('fr-FR');
        const formattedTime = dateObj.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        
        // Récupérer le nom de l'étudiant si disponible
        let studentName = 'Étudiant non spécifié';
        if (meeting.assignment && meeting.assignment.student) {
            studentName = meeting.assignment.student.name;
        }
        
        html += `
            <li class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${meeting.title}</h5>
                    <small class="text-muted">${formattedDate}</small>
                </div>
                <div class="d-flex w-100 justify-content-between">
                    <p class="mb-1">${studentName}</p>
                    <small class="text-muted">${formattedTime}</small>
                </div>
                <div class="mt-2">
                    <a href="/tutoring/views/tutor/meetings.php?id=${meeting.id}" class="btn btn-sm btn-outline-primary">
                        Détails
                    </a>
                </div>
            </li>
        `;
    }
    
    html += '</ul>';
    
    // Ajouter un lien "Voir tout" si plus de 5 réunions
    if (meetingsCount > 5) {
        html += `
            <div class="text-end mt-3">
                <a href="/tutoring/views/tutor/meetings.php" class="text-decoration-none">
                    Voir toutes les réunions
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        `;
    }
    
    meetingsList.innerHTML = html;
}

function loadMessages() {
    // Utiliser l'endpoint direct pour éviter les problèmes d'API
    fetch('/tutoring/views/tutor/dashboard_data.php?type=messages')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des messages');
            }
            return response.json();
        })
        .then(data => {
            updateMessagesCard(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('messages-list').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur lors du chargement des messages.
                </div>
            `;
        });
}

function updateMessagesCard(data) {
    const conversations = data.data;
    
    // Calculer le nombre total de messages non lus
    let unreadCount = 0;
    for (const conversation of conversations) {
        unreadCount += conversation.unread_count;
    }
    
    // Mettre à jour le compteur
    document.getElementById('messages-count').textContent = unreadCount;
    
    // Mettre à jour la barre de progression (pourcentage basé sur le nombre de messages non lus)
    const progressBar = document.querySelector('#messages-card .progress-bar');
    const percentage = Math.min(100, (unreadCount / 10) * 100); // 10 messages non lus = 100%
    progressBar.style.width = `${percentage}%`;
    progressBar.setAttribute('aria-valuenow', percentage);
    
    // Mettre à jour la liste des messages
    const messagesList = document.getElementById('messages-list');
    
    if (conversations.length === 0) {
        messagesList.innerHTML = `
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>Aucun message pour le moment.
                <a href="/tutoring/views/tutor/messages.php" class="alert-link">Envoyer un message</a>.
            </div>
        `;
        return;
    }
    
    let html = '<ul class="list-group list-group-flush">';
    
    for (const conversation of conversations) {
        const participant = conversation.participants[0];
        
        // Déterminer s'il y a des messages non lus
        const hasUnread = conversation.unread_count > 0;
        const unreadBadge = hasUnread ? 
            `<span class="badge bg-danger rounded-pill">${conversation.unread_count}</span>` : '';
        
        // Récupérer le dernier message
        let lastMessageText = 'Pas de messages';
        if (conversation.last_message) {
            // Tronquer le contenu du message s'il est trop long
            const content = conversation.last_message.content;
            lastMessageText = content.length > 50 ? content.substring(0, 47) + '...' : content;
        }
        
        const lastMessageDate = conversation.last_message ? 
            new Date(conversation.last_message.sent_at).toLocaleDateString('fr-FR') : '';
        
        html += `
            <li class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${participant.name}</h5>
                    ${unreadBadge}
                </div>
                <p class="mb-1">${lastMessageText}</p>
                <div class="d-flex w-100 justify-content-between">
                    <small class="text-muted">${lastMessageDate}</small>
                    <a href="/tutoring/views/tutor/messages.php?conversation=${conversation.id}" class="btn btn-sm btn-outline-primary">
                        Voir
                    </a>
                </div>
            </li>
        `;
    }
    
    html += '</ul>';
    
    messagesList.innerHTML = html;
}

function loadNotifications() {
    // Utiliser l'endpoint direct pour éviter les problèmes d'API
    fetch('/tutoring/views/tutor/dashboard_data.php?type=notifications')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des notifications');
            }
            return response.json();
        })
        .then(data => {
            updateNotificationsCard(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('notifications-list').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur lors du chargement des notifications.
                </div>
            `;
        });
}

function updateNotificationsCard(data) {
    const notifications = data.data;
    const totalUnread = data.meta.total_unread;
    
    // Mettre à jour la liste des notifications
    const notificationsList = document.getElementById('notifications-list');
    
    if (notifications.length === 0) {
        notificationsList.innerHTML = `
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>Aucune notification non lue.
            </div>
        `;
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    
    for (const notification of notifications) {
        const dateObj = new Date(notification.created_at);
        const formattedDate = dateObj.toLocaleDateString('fr-FR');
        const formattedTime = dateObj.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        
        // Déterminer l'icône à afficher
        let icon = 'bi-bell';
        
        switch (notification.type) {
            case 'assignment':
                icon = 'bi-person-check';
                break;
            case 'meeting':
                icon = 'bi-calendar-event';
                break;
            case 'message':
                icon = 'bi-chat-left-text';
                break;
            case 'document':
                icon = 'bi-file-earmark-text';
                break;
            case 'evaluation':
                icon = 'bi-clipboard-check';
                break;
        }
        
        // Déterminer l'URL de l'action
        let actionUrl = '#';
        if (notification.action_url) {
            actionUrl = notification.action_url;
        }
        
        html += `
            <a href="${actionUrl}" class="list-group-item list-group-item-action" 
               onclick="markNotificationRead(${notification.id}, event)">
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <i class="${icon} me-2"></i>
                        ${notification.message}
                    </div>
                    <small class="text-muted">${formattedDate} ${formattedTime}</small>
                </div>
            </a>
        `;
    }
    
    html += '</div>';
    
    // Ajouter un lien "Voir tout" si plus de 5 notifications
    if (totalUnread > 5) {
        html += `
            <div class="text-end mt-3">
                <a href="/tutoring/views/notifications/index.php" class="text-decoration-none">
                    Voir toutes les ${totalUnread} notifications
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        `;
    }
    
    notificationsList.innerHTML = html;
}

function markNotificationRead(id, event) {
    // Empêcher la navigation par défaut
    event.preventDefault();
    
    // Envoyer une requête pour marquer la notification comme lue
    fetch(`/tutoring/api/notifications/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: id }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur lors du marquage de la notification');
        }
        return response.json();
    })
    .then(data => {
        // Rediriger vers l'URL de la notification
        const linkElement = event.currentTarget;
        window.location.href = linkElement.getAttribute('href');
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Rediriger quand même en cas d'erreur
        const linkElement = event.currentTarget;
        window.location.href = linkElement.getAttribute('href');
    });
}
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>