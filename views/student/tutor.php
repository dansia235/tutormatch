<?php
/**
 * Vue pour afficher les informations du tuteur d'un étudiant
 * Permet de voir les détails du tuteur et de le contacter
 */

// Initialiser les variables
$pageTitle = 'Mon Tuteur';
$currentPage = 'tutor';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID utilisateur de l'étudiant
$currentUserId = $_SESSION['user_id'];

// Récupérer les informations de l'étudiant
$studentModel = new Student($db);
$student = $studentModel->getByUserId($currentUserId);

if (!$student) {
    setFlashMessage('error', 'Profil étudiant non trouvé');
    redirect('/tutoring/views/student/dashboard.php');
}

// Récupérer l'affectation active de l'étudiant pour trouver son tuteur
$assignmentModel = new Assignment($db);
$assignment = $assignmentModel->getActiveByStudentId($student['id']);

// Initialiser les modèles
$teacherModel = new Teacher($db);
$userModel = new User($db);
$messageModel = new Message($db);

// Variables pour stocker les informations du tuteur
$teacher = null;
$teacherUser = null;

// Si l'étudiant a une affectation, récupérer les informations du tuteur
if ($assignment) {
    $teacher = $teacherModel->getById($assignment['teacher_id']);
    if ($teacher) {
        $teacherUser = $userModel->getById($teacher['user_id']);
    }
}

// Initialiser la variable pour la conversation avec le tuteur
$conversation = null;
$messages = [];

// Si l'étudiant a un tuteur, récupérer les messages entre eux
if ($teacherUser) {
    // Récupérer les messages entre l'étudiant et le tuteur
    $messages = $messageModel->getConversationBetweenUsers($currentUserId, $teacherUser['id']);
    
    // Marquer les messages non lus comme lus
    foreach ($messages as $message) {
        // Vérifier si le message est non lu en utilisant le champ is_read
        if ($message['receiver_id'] == $currentUserId && $message['is_read'] == 0) {
            $messageModel->markAsRead($message['message_id'], $currentUserId);
        }
    }
}

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $teacherUser) {
    $subject = $_POST['subject'] ?? 'Message de l\'étudiant';
    $content = $_POST['message_content'] ?? '';
    
    if (!empty($content)) {
        // Créer le message
        $messageData = [
            'sender_id' => $currentUserId,
            'receiver_id' => $teacherUser['id'],
            'subject' => $subject,
            'content' => $content,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        $messageId = $messageModel->send($messageData);
        
        if ($messageId) {
            setFlashMessage('success', 'Message envoyé avec succès');
            redirect('/tutoring/views/student/tutor.php');
        } else {
            setFlashMessage('error', 'Erreur lors de l\'envoi du message');
        }
    } else {
        setFlashMessage('error', 'Le contenu du message ne peut pas être vide');
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-person-badge me-2"></i>Mon Tuteur</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mon Tuteur</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!$teacher || !$teacherUser): ?>
    <!-- Aucun tuteur assigné -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm fade-in">
                <div class="card-body text-center p-5">
                    <i class="bi bi-person-x-fill display-1 text-muted mb-3"></i>
                    <h3>Aucun tuteur assigné</h3>
                    <p class="text-muted">
                        Vous n'avez pas encore de tuteur assigné. Veuillez patienter jusqu'à ce qu'un tuteur vous soit attribué 
                        ou contactez l'administration si vous pensez qu'il s'agit d'une erreur.
                    </p>
                    <a href="/tutoring/views/student/dashboard.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-left me-1"></i> Retour au tableau de bord
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Informations du tuteur -->
    <div class="row">
        <!-- Profil du tuteur -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 fade-in">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Informations du tuteur</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php 
                        $profileImage = $teacherUser['profile_image'] ?: "https://ui-avatars.com/api/?name=" . 
                            urlencode($teacherUser['first_name'] . ' ' . $teacherUser['last_name']) . 
                            "&background=3498db&color=fff&size=256";
                        ?>
                        <img src="<?php echo h($profileImage); ?>" 
                             alt="Photo de profil" 
                             class="rounded-circle img-thumbnail" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="mt-3"><?php echo h($teacherUser['first_name'] . ' ' . $teacherUser['last_name']); ?></h4>
                        <p class="text-muted"><?php echo h($teacher['title'] ?: 'Tuteur'); ?></p>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Email</h6>
                            </div>
                            <p class="mb-1"><a href="mailto:<?php echo h($teacherUser['email']); ?>"><?php echo h($teacherUser['email']); ?></a></p>
                        </div>
                        
                        <?php if ($teacher['specialty']): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Spécialité</h6>
                            </div>
                            <p class="mb-1"><?php echo h(cleanSpecialty($teacher['specialty'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($teacherUser['department']): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Département</h6>
                            </div>
                            <p class="mb-1"><?php echo h($teacherUser['department']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($teacher['office_location']): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Bureau</h6>
                            </div>
                            <p class="mb-1"><?php echo h($teacher['office_location']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($teacher['expertise']): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Domaines d'expertise</h6>
                            </div>
                            <p class="mb-1"><?php echo h($teacher['expertise']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="bi bi-envelope me-1"></i> Contacter mon tuteur
                    </button>
                </div>
            </div>
            
            <!-- Informations sur le stage -->
            <?php if ($assignment): ?>
            <div class="card shadow-sm mb-4 fade-in">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Mon stage</h5>
                </div>
                <div class="card-body">
                    <h5><?php echo h($assignment['internship_title']); ?></h5>
                    <p class="text-muted"><?php echo h($assignment['company_name']); ?></p>
                    
                    <?php if ($assignment['internship_description']): ?>
                    <p><?php echo nl2br(h($assignment['internship_description'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <small class="text-muted d-block">Date de début</small>
                            <strong><?php echo date('d/m/Y', strtotime($assignment['internship_start_date'])); ?></strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Date de fin</small>
                            <strong><?php echo date('d/m/Y', strtotime($assignment['internship_end_date'])); ?></strong>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top">
                        <span class="badge <?php echo getStatusBadgeClass($assignment['status']); ?>">
                            <?php echo getStatusLabel($assignment['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="/tutoring/views/student/internship.php" class="btn btn-outline-primary">
                        <i class="bi bi-info-circle me-1"></i> Détails du stage
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Zone de messages -->
        <div class="col-lg-8">
            <div class="card shadow-sm fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Messages avec mon tuteur</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                    <div class="text-center p-5">
                        <i class="bi bi-chat-square-text display-1 text-muted mb-3"></i>
                        <h4>Aucun message</h4>
                        <p class="text-muted">
                            Vous n'avez pas encore échangé de messages avec votre tuteur.
                            Utilisez le bouton "Contacter mon tuteur" pour envoyer votre premier message.
                        </p>
                    </div>
                    <?php else: ?>
                    <div id="message-list" class="mb-4">
                        <?php foreach ($messages as $message): ?>
                        <?php 
                        $isOutgoing = $message['sender_id'] == $currentUserId;
                        $senderName = $isOutgoing ? ($student['first_name'] . ' ' . $student['last_name']) : ($teacherUser['first_name'] . ' ' . $teacherUser['last_name']);
                        $senderAvatar = $isOutgoing ? 
                            "https://ui-avatars.com/api/?name=" . urlencode($student['first_name'] . ' ' . $student['last_name']) . "&background=6c757d&color=fff" :
                            "https://ui-avatars.com/api/?name=" . urlencode($teacherUser['first_name'] . ' ' . $teacherUser['last_name']) . "&background=3498db&color=fff";
                        ?>
                        <?php if ($isOutgoing): ?>
                        <!-- Message envoyé (à droite) -->
                        <div class="message-item message-sent mb-3" style="opacity: 0; transform: translateY(20px); transition: all 0.3s ease;">
                            <div class="d-flex justify-content-end">
                                <div class="message-bubble bg-primary text-white">
                                    <div class="message-header">
                                        <strong><?php echo h($senderName); ?></strong>
                                        <span class="message-time">
                                            <?php echo formatMessageDate($message['sent_at']); ?>
                                        </span>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(h($message['content'])); ?>
                                    </div>
                                </div>
                                <img src="<?php echo h($senderAvatar); ?>" alt="Avatar" class="message-avatar-small ms-2">
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Message reçu (à gauche) -->
                        <div class="message-item message-received mb-3" style="opacity: 0; transform: translateY(20px); transition: all 0.3s ease;">
                            <div class="d-flex justify-content-start">
                                <img src="<?php echo h($senderAvatar); ?>" alt="Avatar" class="message-avatar-small me-2">
                                <div class="message-bubble bg-light">
                                    <div class="message-header">
                                        <strong><?php echo h($senderName); ?></strong>
                                        <span class="message-time">
                                            <?php echo formatMessageDate($message['sent_at']); ?>
                                        </span>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(h($message['content'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="reply-section mt-4 pt-3 border-top">
                        <h5 class="mb-3">Répondre</h5>
                        <form method="POST" action="">
                            <textarea class="form-control mb-3" name="message_content" rows="4" placeholder="Écrivez votre message ici..." required></textarea>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Envoyer
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Nouveau Message -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nouveau message à mon tuteur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <?php if ($teacherUser): ?>
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <input type="text" class="form-control" value="<?php echo h($teacherUser['first_name'] . ' ' . $teacherUser['last_name']); ?>" readonly>
                        <input type="hidden" name="receiver_id" value="<?php echo h($teacherUser['id']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" placeholder="Objet du message" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message_content" rows="8" placeholder="Écrivez votre message ici..." required></textarea>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Vous n'avez pas de tuteur assigné. Impossible d'envoyer un message.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <?php if ($teacherUser): ?>
                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer le message</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.currentUserId = <?php echo $currentUserId; ?>;
    
    // Fonctions pour la messagerie
    document.addEventListener('DOMContentLoaded', function() {
        // Faire défiler vers le bas des messages
        const messageList = document.getElementById('message-list');
        if (messageList) {
            messageList.scrollTop = messageList.scrollHeight;
        }
        
        // Animation d'entrée pour les messages
        const messages = document.querySelectorAll('.message-item');
        messages.forEach((msg, index) => {
            setTimeout(() => {
                msg.style.opacity = '1';
                msg.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

<style>
    /* Styles pour la messagerie */
    .message-card {
        border-left: 3px solid #3498db;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .message-card:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .message-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .message-avatar-small {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .message-preview {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
        color: inherit !important; /* Garantir que le texte est toujours visible */
    }
    
    .message-bubble {
        padding: 10px 15px;
        border-radius: 15px;
        max-width: 80%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .message-sent .message-bubble {
        border-bottom-right-radius: 0;
    }
    
    .message-received .message-bubble {
        border-bottom-left-radius: 0;
    }
    
    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-left: 10px;
    }
    
    .message-header {
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    #message-list {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
    }
    
    /* Styles pour le scrollbar */
    #message-list::-webkit-scrollbar {
        width: 6px;
    }
    
    #message-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    #message-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }
    
    #message-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Style pour les messages non lus */
    .message-card.unread {
        background-color: rgba(13, 110, 253, 0.05);
        border-left-color: #dc3545;
    }
    
    .fade-in {
        animation: fadeIn 0.5s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php
/**
 * Fonction pour formater la date des messages
 */
function formatMessageDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'À l\'instant';
    } elseif ($diff < 3600) {
        return 'Il y a ' . floor($diff / 60) . ' min';
    } elseif ($diff < 86400) {
        return 'Il y a ' . floor($diff / 3600) . ' h';
    } elseif (date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('yesterday'))) {
        return 'Hier';
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Fonction pour obtenir la classe CSS du badge selon le statut
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'confirmed':
            return 'bg-success';
        case 'completed':
            return 'bg-info';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

/**
 * Fonction pour obtenir le libellé du statut
 */
function getStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return 'En attente';
        case 'confirmed':
            return 'Confirmé';
        case 'completed':
            return 'Terminé';
        case 'rejected':
            return 'Rejeté';
        default:
            return 'Inconnu';
    }
}

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>