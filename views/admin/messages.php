<?php
/**
 * Vue pour la messagerie de l'administrateur
 * Permet d'afficher et d'envoyer des messages aux utilisateurs du système
 */

// Initialiser les variables
$pageTitle = 'Messagerie';
$currentPage = 'messages';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Récupérer l'ID utilisateur courant
$currentUserId = $_SESSION['user_id'];

// Initialiser le modèle de message
$messageModel = new Message($db);
$userModel = new User($db);

// Récupérer les conversations de l'administrateur
$allMessages = $messageModel->getConversationsByUserId($currentUserId);

// Organiser les messages par conversation
$conversations = [];
$conversationIndex = [];

foreach ($allMessages as $message) {
    $conversationId = $message['conversation_id'];
    
    if (!isset($conversations[$conversationId])) {
        $conversations[$conversationId] = [
            'id' => $conversationId,
            'title' => $message['conversation_title'] ?? 'Conversation',
            'participants' => [],
            'last_message' => null,
            'unread_count' => 0,
            'messages' => []
        ];
    }
    
    // Déterminer l'autre participant
    $otherParticipantId = ($message['sender_id'] == $currentUserId) ? 
        $message['receiver_id'] : $message['sender_id'];
    
    // Ajouter le participant s'il n'existe pas déjà
    if (!isset($conversationIndex[$conversationId][$otherParticipantId])) {
        $otherUser = $userModel->getById($otherParticipantId);
        if ($otherUser) {
            // Définir la couleur de l'avatar en fonction du rôle
            $avatarBg = [
                'admin' => '3498db',
                'coordinator' => 'e74c3c',
                'teacher' => '2ecc71',
                'student' => 'f39c12'
            ][$otherUser['role']] ?? '95a5a6';
            
            $conversations[$conversationId]['participants'][] = [
                'id' => $otherUser['id'],
                'name' => $otherUser['first_name'] . ' ' . $otherUser['last_name'],
                'role' => $otherUser['role'],
                'avatar' => "https://ui-avatars.com/api/?name=" . 
                    urlencode($otherUser['first_name'] . ' ' . $otherUser['last_name']) . 
                    "&background=" . $avatarBg . "&color=fff"
            ];
            $conversationIndex[$conversationId][$otherParticipantId] = true;
        }
    }
    
    // Ajouter le message à la conversation
    $conversations[$conversationId]['messages'][] = $message;
    
    // Mettre à jour le dernier message
    if (!$conversations[$conversationId]['last_message'] || 
        strtotime($message['sent_at']) > strtotime($conversations[$conversationId]['last_message']['sent_at'])) {
        $conversations[$conversationId]['last_message'] = $message;
    }
    
    // Compter les messages non lus
    if ($message['receiver_id'] == $currentUserId && $message['is_read'] == 0) {
        $conversations[$conversationId]['unread_count']++;
    }
}

// Trier les conversations par date du dernier message
usort($conversations, function($a, $b) {
    if (!$a['last_message'] || !$b['last_message']) return 0;
    $dateA = strtotime($a['last_message']['sent_at']);
    $dateB = strtotime($b['last_message']['sent_at']);
    return $dateB - $dateA;
});

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'] ?? null;
    $subject = $_POST['subject'] ?? 'Nouveau message';
    $content = $_POST['message_content'] ?? '';
    
    if ($receiverId && !empty($content)) {
        // Créer le message avec l'user_id de la session
        $messageData = [
            'sender_id' => $currentUserId,
            'receiver_id' => $receiverId,
            'subject' => $subject,
            'content' => $content,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        $messageId = $messageModel->send($messageData);
        
        if ($messageId) {
            setFlashMessage('success', 'Message envoyé avec succès');
            redirect('/tutoring/views/admin/messages.php');
        } else {
            setFlashMessage('error', 'Erreur lors de l\'envoi du message');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    // Marquer un message comme lu
    $messageId = $_POST['message_id'];
    if ($messageModel->markAsRead($messageId, $currentUserId)) {
        // Réponse pour AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        setFlashMessage('success', 'Message marqué comme lu');
        redirect('/tutoring/views/admin/messages.php');
    }
}

// Récupérer la liste des contacts disponibles
$contacts = [];

// Récupérer tous les étudiants
$studentModel = new Student($db);
$students = $studentModel->getAll();

foreach ($students as $student) {
    $studentUser = $userModel->getById($student['user_id']);
    if ($studentUser) {
        $contacts[] = [
            'id' => $studentUser['id'],
            'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name'],
            'role' => 'Étudiant',
            'email' => $studentUser['email']
        ];
    }
}

// Récupérer tous les tuteurs
$teacherModel = new Teacher($db);
$teachers = $teacherModel->getAll();

foreach ($teachers as $teacher) {
    $teacherUser = $userModel->getById($teacher['user_id']);
    if ($teacherUser) {
        $contacts[] = [
            'id' => $teacherUser['id'],
            'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name'],
            'role' => 'Tuteur académique',
            'email' => $teacherUser['email']
        ];
    }
}

// Ajouter d'autres coordinateurs et admins (si l'utilisateur actuel est admin)
if ($_SESSION['user_role'] === 'admin') {
    $coordinators = $userModel->getUsersByRole(['coordinator', 'admin']);
    foreach ($coordinators as $coordinator) {
        // Ne pas ajouter l'utilisateur actuel
        if ($coordinator['id'] == $currentUserId) {
            continue;
        }
        $contacts[] = [
            'id' => $coordinator['id'],
            'name' => $coordinator['first_name'] . ' ' . $coordinator['last_name'],
            'role' => $coordinator['role'] === 'admin' ? 'Administrateur' : 'Coordinateur',
            'email' => $coordinator['email']
        ];
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-envelope me-2"></i>Messagerie</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Messagerie</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Définir l'ID de l'utilisateur pour le JavaScript -->
    <meta name="user-id" content="<?php echo $currentUserId; ?>">
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Liste des conversations -->
        <div class="col-lg-4">
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Boîte de réception</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="bi bi-pencil-square me-1"></i>Nouveau
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($conversations)): ?>
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle me-2"></i> Vous n'avez pas encore de messages. Utilisez le bouton "Nouveau" pour commencer une conversation.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conversation): ?>
                        <?php
                        $otherParticipant = isset($conversation['participants'][0]) ? $conversation['participants'][0] : null;
                        $lastMessage = $conversation['last_message'];
                        $unreadMessageIds = [];
                        
                        // Collecter les IDs des messages non lus
                        foreach ($conversation['messages'] as $msg) {
                            if ($msg['receiver_id'] == $currentUserId && $msg['is_read'] == 0) {
                                $unreadMessageIds[] = $msg['message_id'];
                            }
                        }
                        ?>
                        <a href="#" class="list-group-item list-group-item-action message-card <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>"
                           data-conversation-id="<?php echo h($conversation['id']); ?>"
                           data-message-ids="<?php echo h(implode(',', $unreadMessageIds)); ?>"
                           <?php if ($otherParticipant): ?>
                           data-participant-id="<?php echo h($otherParticipant['id']); ?>"
                           data-participant-role="<?php echo h($otherParticipant['role']); ?>"
                           <?php endif; ?>>
                            <div class="d-flex align-items-center">
                                <?php if ($otherParticipant): ?>
                                <img src="<?php echo h($otherParticipant['avatar']); ?>" 
                                     alt="Avatar" 
                                     class="message-avatar me-3">
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong>
                                            <?php echo h($otherParticipant ? $otherParticipant['name'] : 'Inconnu'); ?>
                                            <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="badge bg-danger ms-1"><?php echo $conversation['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </strong>
                                        <small class="text-muted">
                                            <?php echo $lastMessage ? formatMessageDate($lastMessage['sent_at']) : ''; ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 message-preview">
                                        <?php 
                                        if ($lastMessage) {
                                            $content = $lastMessage['content'];
                                            echo h(substr($content, 0, 50)) . (strlen($content) > 50 ? '...' : '');
                                        } else {
                                            echo 'Aucun message';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Détails de la conversation -->
        <div class="col-lg-8">
            <div class="card mb-4 fade-in" id="conversation-details">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Messages</span>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" id="reply-button" style="display: none;">
                            <i class="bi bi-reply"></i> Répondre
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="delete-button" style="display: none;">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="conversation-placeholder" class="text-center p-5">
                        <i class="bi bi-envelope-open display-1 text-muted"></i>
                        <p class="mt-3">Sélectionnez une conversation pour afficher les messages</p>
                    </div>
                    
                    <div id="conversation-content" style="display: none;">
                        <div class="d-flex align-items-center mb-4" id="conversation-header">
                            <img src="" alt="Avatar" class="message-avatar me-3" id="conversation-avatar">
                            <div>
                                <h5 class="mb-0" id="conversation-participant"></h5>
                                <small class="text-muted" id="conversation-participant-role"></small>
                            </div>
                        </div>
                        
                        <div id="message-list" class="mb-4">
                            <!-- Les messages seront chargés dynamiquement ici -->
                        </div>
                        
                        <div class="reply-section" id="reply-section" style="display: none;">
                            <h6>Répondre</h6>
                            <form id="reply-form" method="POST" action="">
                                <input type="hidden" name="conversation_id" id="reply-conversation-id">
                                <input type="hidden" name="receiver_id" id="reply-receiver-id">
                                <textarea class="form-control mb-2" name="message_content" rows="4" placeholder="Votre réponse..." required></textarea>
                                <button type="submit" name="send_message" class="btn btn-primary">Envoyer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <select class="form-select" name="receiver_id" required>
                            <option value="">Sélectionner un contact...</option>
                            <?php foreach ($contacts as $contact): ?>
                            <option value="<?php echo h($contact['id']); ?>">
                                <?php echo h($contact['name']) . ' (' . h($contact['role']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" placeholder="Objet du message" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message_content" rows="8" placeholder="Écrivez votre message ici..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.currentUserId = <?php echo $currentUserId; ?>;
</script>
<script src="/tutoring/assets/js/messages.js"></script>

<style>
    /* Styles pour la messagerie */
    .message-card {
        border-left: 3px solid #3498db;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .message-card:hover {
        transform: translateX(5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
        $minutes = floor($diff / 60);
        return 'Il y a ' . $minutes . ' min';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Il y a ' . $hours . ' h';
    } elseif (date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('yesterday'))) {
        return 'Hier';
    } else {
        return date('d/m', $timestamp);
    }
}

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>