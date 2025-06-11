<?php
/**
 * Vue pour la messagerie des administrateurs et coordinateurs
 * Permet d'afficher et d'envoyer des messages
 */

// Initialiser les variables
$pageTitle = 'Messagerie';
$currentPage = 'messages';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est admin ou coordinateur
if (!in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    setFlashMessage('error', 'Accès non autorisé.');
    redirect('/tutoring/index.php');
}

// Récupérer l'utilisateur
$userModel = new User($db);
$user = $userModel->getById($_SESSION['user_id']);

if (!$user) {
    setFlashMessage('error', 'Profil utilisateur non trouvé');
    redirect('/tutoring/views/admin/dashboard.php');
}

// Récupérer les messages de l'utilisateur
$messageModel = new Message($db);
$allMessages = $messageModel->getConversationsByUserId($_SESSION['user_id']);

// Organiser les messages par conversation
$conversations = [];
foreach ($allMessages as $message) {
    $conversationId = $message['conversation_id'];
    if (!isset($conversations[$conversationId])) {
        $conversations[$conversationId] = [
            'id' => $conversationId,
            'title' => $message['conversation_title'],
            'participants' => [],
            'last_message' => null,
            'unread_count' => 0,
            'messages' => []
        ];
    }
    
    // Ajouter le participant s'il n'existe pas déjà
    $participantId = $message['sender_id'] == $_SESSION['user_id'] ? $message['receiver_id'] : $message['sender_id'];
    if (!in_array($participantId, array_column($conversations[$conversationId]['participants'], 'id'))) {
        $conversations[$conversationId]['participants'][] = [
            'id' => $participantId,
            'name' => $message['sender_id'] == $_SESSION['user_id'] ? 
                ($message['receiver_first_name'] . ' ' . $message['receiver_last_name']) : 
                ($message['sender_first_name'] . ' ' . $message['sender_last_name']),
            'role' => $message['sender_id'] == $_SESSION['user_id'] ? $message['receiver_role'] : $message['sender_role'],
            'avatar' => ''
        ];
    }
    
    // Ajouter le message à la conversation
    $conversations[$conversationId]['messages'][] = $message;
    
    // Mettre à jour le dernier message si nécessaire
    if (!$conversations[$conversationId]['last_message'] || strtotime($message['sent_at']) > strtotime($conversations[$conversationId]['last_message']['sent_at'])) {
        $conversations[$conversationId]['last_message'] = $message;
    }
    
    // Compter les messages non lus
    if ($message['receiver_id'] == $_SESSION['user_id'] && !$message['is_read']) {
        $conversations[$conversationId]['unread_count']++;
    }
}

// Trier les conversations par date du dernier message (plus récent en premier)
usort($conversations, function($a, $b) {
    $dateA = isset($a['last_message']['sent_at']) ? strtotime($a['last_message']['sent_at']) : 0;
    $dateB = isset($b['last_message']['sent_at']) ? strtotime($b['last_message']['sent_at']) : 0;
    return $dateB - $dateA;
});

// Traitement de l'envoi de message si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        // Envoi d'un nouveau message
        $receiverId = $_POST['receiver_id'];
        $subject = $_POST['subject'] ?? 'Nouveau message';
        $content = $_POST['message_content'];
        $conversationId = $_POST['conversation_id'] ?? null;
        
        // Trouver l'ID utilisateur du destinataire si nécessaire
        $receiverUserId = $receiverId;
        
        if (isset($_POST['receiver_type'])) {
            if ($_POST['receiver_type'] === 'student') {
                $studentModel = new Student($db);
                $student = $studentModel->getById($receiverId);
                if ($student && isset($student['user_id'])) {
                    $receiverUserId = $student['user_id'];
                }
            } elseif ($_POST['receiver_type'] === 'teacher') {
                $teacherModel = new Teacher($db);
                $teacher = $teacherModel->getById($receiverId);
                if ($teacher && isset($teacher['user_id'])) {
                    $receiverUserId = $teacher['user_id'];
                }
            }
        }
        
        // Création du message
        $messageData = [
            'sender_id' => $_SESSION['user_id'],
            'receiver_id' => $receiverUserId,
            'subject' => $subject,
            'content' => $content,
            'sent_at' => date('Y-m-d H:i:s'),
            'status' => 'sent'
        ];
        
        error_log("Sending message data: " . json_encode($messageData));
        
        try {
            $messageId = $messageModel->send($messageData);
            error_log("Message creation result: " . ($messageId ? "Success (ID: $messageId)" : "Failed"));
            
            if ($messageId) {
                setFlashMessage('success', 'Message envoyé avec succès');
                redirect('/tutoring/views/admin/messages-new.php');
            } else {
                setFlashMessage('error', 'Erreur lors de l\'envoi du message');
            }
        } catch (PDOException $e) {
            error_log("PDO Error in message creation: " . $e->getMessage());
            setFlashMessage('error', 'Erreur lors de l\'envoi du message: ' . $e->getMessage());
        }
    } elseif (isset($_POST['mark_as_read'])) {
        // Marquer un message comme lu
        $messageId = $_POST['message_id'];
        if ($messageModel->markAsRead($messageId, $_SESSION['user_id'])) {
            // Réponse pour AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            setFlashMessage('success', 'Message marqué comme lu');
            redirect('/tutoring/views/admin/messages-new.php');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour du message');
        }
    }
}

// Récupérer la liste des contacts disponibles pour l'envoi de messages
$contacts = [];

// Récupérer les étudiants
$studentModel = new Student($db);
$students = $studentModel->getAll();

foreach ($students as $student) {
    $contacts[] = [
        'id' => $student['id'],
        'type' => 'student',
        'name' => $student['first_name'] . ' ' . $student['last_name'],
        'role' => 'Étudiant',
        'email' => $student['email'] ?? ''
    ];
}

// Récupérer les tuteurs
$teacherModel = new Teacher($db);
$teachers = $teacherModel->getAll();

foreach ($teachers as $teacher) {
    $contacts[] = [
        'id' => $teacher['id'],
        'type' => 'teacher',
        'name' => $teacher['first_name'] . ' ' . $teacher['last_name'],
        'role' => 'Tuteur',
        'email' => $teacher['email'] ?? ''
    ];
}

// Ajouter d'autres coordinateurs et admins (si l'utilisateur actuel est admin)
if ($_SESSION['user_role'] === 'admin') {
    $otherAdmins = $userModel->getUsersByRole(['admin', 'coordinator']);
    
    foreach ($otherAdmins as $admin) {
        // Ne pas ajouter l'utilisateur actuel
        if ($admin['id'] == $_SESSION['user_id']) {
            continue;
        }
        
        $contacts[] = [
            'id' => $admin['id'],
            'type' => $admin['role'],
            'name' => $admin['first_name'] . ' ' . $admin['last_name'],
            'role' => ($admin['role'] === 'admin') ? 'Administrateur' : 'Coordinateur',
            'email' => $admin['email'] ?? ''
        ];
    }
}

// Inclure l'en-tête en utilisant le layout admin
$content = ob_start();
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
    <meta name="user-id" content="<?php echo $_SESSION['user_id']; ?>">
    
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
                    <div class="p-3">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Rechercher..." id="contact-search">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($conversations)): ?>
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle me-2"></i> Vous n'avez pas encore de messages. Utilisez le bouton "Nouveau" pour commencer une conversation.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush contact-list">
                        <?php foreach ($conversations as $conversation): ?>
                        <?php
                        // Prepare participant data first
                        $otherParticipant = null;
                        foreach ($conversation['participants'] as $participant) {
                            if ($participant['id'] != $_SESSION['user_id']) {
                                $otherParticipant = $participant;
                                break;
                            }
                        }
                        
                        // Prepare message IDs
                        $unreadMessageIds = [];
                        if (isset($conversation['last_message']) && 
                            isset($conversation['last_message']['receiver_id']) &&
                            isset($conversation['last_message']['id']) &&
                            $conversation['last_message']['receiver_id'] == $_SESSION['user_id'] && 
                            $conversation['unread_count'] > 0) {
                            $unreadMessageIds[] = $conversation['last_message']['id'];
                        }
                        $messageIdsString = implode(',', $unreadMessageIds);
                        
                        // Build HTML attributes cleanly
                        $cardClass = "list-group-item list-group-item-action message-card";
                        if ($conversation['unread_count'] > 0) {
                            $cardClass .= " unread";
                        }
                        
                        $dataAttributes = 'data-conversation-id="' . $conversation['id'] . '"';
                        $dataAttributes .= ' data-message-ids="' . $messageIdsString . '"';
                        
                        if ($otherParticipant) {
                            $dataAttributes .= ' data-participant-id="' . (int)$otherParticipant['id'] . '"';
                            $dataAttributes .= ' data-participant-role="' . htmlspecialchars($otherParticipant['role'], ENT_QUOTES, 'UTF-8') . '"';
                        }
                        ?>
                        <a href="#" class="<?php echo $cardClass; ?>" <?php echo $dataAttributes; ?>>
                            <div class="d-flex align-items-center">
                                <?php
                                // Afficher l'avatar du dernier participant (autre que l'utilisateur courant)
                                $participantName = 'User';
                                if ($otherParticipant) {
                                    if (isset($otherParticipant['name']) && !empty($otherParticipant['name'])) {
                                        $participantName = $otherParticipant['name'];
                                    } elseif (isset($otherParticipant['role'])) {
                                        $participantName = $otherParticipant['role'];
                                    }
                                }
                                
                                $avatarUrl = "https://ui-avatars.com/api/?name=System&background=2c3e50&color=fff";
                                if ($otherParticipant) {
                                    if (isset($otherParticipant['avatar']) && !empty($otherParticipant['avatar'])) {
                                        $avatarUrl = $otherParticipant['avatar'];
                                    } else {
                                        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($participantName) . "&background=3498db&color=fff";
                                    }
                                }
                                ?>
                                <img src="<?php echo $avatarUrl; ?>" alt="Avatar" class="message-avatar me-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong>
                                            <?php 
                                            if ($otherParticipant && isset($otherParticipant['name']) && !empty($otherParticipant['name'])) {
                                                echo h($otherParticipant['name']);
                                            } else {
                                                echo 'Système';
                                            }
                                            ?>
                                            <?php if (isset($conversation['unread_count']) && $conversation['unread_count'] > 0): ?>
                                            <span class="badge bg-danger ms-1"><?php echo (int)$conversation['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </strong>
                                        <small class="text-muted">
                                            <?php 
                                            if (isset($conversation['last_message']) && isset($conversation['last_message']['sent_at'])) {
                                                echo formatMessageDate($conversation['last_message']['sent_at']);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 message-preview">
                                        <?php 
                                        if (isset($conversation['last_message']) && isset($conversation['last_message']['content'])) {
                                            $content = $conversation['last_message']['content'];
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
                        
                        <div class="mb-4">
                            <h4 id="conversation-title"></h4>
                            <small class="text-muted" id="conversation-date"></small>
                        </div>
                        
                        <div id="message-list" class="mb-4">
                            <!-- Les messages seront chargés dynamiquement ici -->
                        </div>
                        
                        <div class="reply-section" id="reply-section" style="display: none;">
                            <h6>Répondre</h6>
                            <form id="reply-form" method="POST" action="">
                                <input type="hidden" name="conversation_id" id="reply-conversation-id">
                                <input type="hidden" name="receiver_id" id="reply-receiver-id">
                                <input type="hidden" name="receiver_type" id="reply-receiver-type">
                                <textarea class="form-control mb-2" name="message_content" rows="4" placeholder="Votre réponse..." required></textarea>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="attach-file-btn">
                                            <i class="bi bi-paperclip"></i>
                                        </button>
                                    </div>
                                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer</button>
                                </div>
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
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <select class="form-select" name="receiver_id" required>
                            <option value="">Sélectionner un contact...</option>
                            <?php foreach ($contacts as $contact): ?>
                            <option value="<?php echo $contact['id']; ?>" data-type="<?php echo $contact['type']; ?>">
                                <?php echo h($contact['name']) . ' (' . h($contact['role']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="receiver_type" id="receiver_type" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" placeholder="Objet du message" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message_content" rows="8" placeholder="Écrivez votre message ici..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pièces jointes (optionnel)</label>
                        <input type="file" class="form-control" name="attachment" multiple>
                        <div class="form-text">Max. 5 Mo par fichier</div>
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

<!-- Delete Message Confirmation Modal -->
<div class="modal fade" id="deleteMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette conversation ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="" method="POST" id="deleteMessageForm">
                    <input type="hidden" name="conversation_id" id="conversation_id_to_delete">
                    <button type="submit" name="delete_conversation" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Définir l'ID de l'utilisateur pour le script commun
    window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
    
    // Gérer le type de destinataire dans le formulaire de nouveau message
    document.addEventListener('DOMContentLoaded', function() {
        const receiverSelect = document.querySelector('select[name="receiver_id"]');
        const receiverTypeInput = document.getElementById('receiver_type');
        
        if (receiverSelect && receiverTypeInput) {
            receiverSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.dataset.type) {
                    receiverTypeInput.value = selectedOption.dataset.type;
                }
            });
        }
        
        // Recherche de contacts
        const contactSearch = document.getElementById('contact-search');
        if (contactSearch) {
            contactSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const contactItems = document.querySelectorAll('.message-card');
                
                contactItems.forEach(item => {
                    const contactName = item.querySelector('strong').textContent.toLowerCase();
                    if (contactName.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
<script src="/tutoring/assets/js/messages.js"></script>
<script src="/tutoring/views/admin/message-handler.js"></script>

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
    
    /* Styles pour la liste des contacts */
    .contact-list {
        max-height: calc(100vh - 300px);
        overflow-y: auto;
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
 * @param string $date Date au format SQL
 * @return string Date formatée
 */
function formatMessageDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'À l\'instant';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'Il y a ' . $minutes . ' min' . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Il y a ' . $hours . ' heure' . ($hours > 1 ? 's' : '');
    } elseif (date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('yesterday'))) {
        return 'Hier, ' . date('H:i', $timestamp);
    } elseif ($diff < 604800) { // Moins d'une semaine
        return date('l', $timestamp) . ', ' . date('H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}

$content = ob_get_clean();
include_once __DIR__ . '/../../templates/layouts/admin.php';
?>