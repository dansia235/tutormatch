<?php
// Script de réparation pour l'interface de messagerie
// Cette version minimale fonctionne sans les fonctionnalités qui causent des problèmes

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Désactiver le buffering de sortie pour afficher immédiatement le contenu
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

// Initialiser les variables
$pageTitle = 'Messagerie - Réparation';
$currentPage = 'messages';

// Inclure le fichier d'initialisation avec précaution
try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDBConnection();
    echo "<!--Connexion à la base de données réussie-->\n";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données: " . $e->getMessage() . "</div>";
    exit;
}

// Vérifier la session
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>Vous n'êtes pas connecté. <a href='/tutoring/login.php'>Se connecter</a></div>";
    exit;
}

// Vérifier le rôle sans utiliser requireRole
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    echo "<div class='alert alert-danger'>Accès non autorisé. Vous devez être un tuteur pour accéder à cette page.</div>";
    exit;
}

// Fonctions utilitaires
function h($str) {
    if ($str === null) return '';
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Charger les modèles nécessaires
try {
    require_once __DIR__ . '/../../models/Teacher.php';
    require_once __DIR__ . '/../../models/Student.php';
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../models/Message.php';
    echo "<!--Modèles chargés avec succès-->\n";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur lors du chargement des modèles: " . $e->getMessage() . "</div>";
    exit;
}

// Récupérer le tuteur et les affectations
try {
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
    
    if (!$teacher) {
        echo "<div class='alert alert-danger'>Profil tuteur non trouvé</div>";
        exit;
    }
    
    $assignments = $teacherModel->getAssignments($teacher['id']);
    echo "<!--Tuteur et affectations récupérés-->\n";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération du profil tuteur: " . $e->getMessage() . "</div>";
    exit;
}

// Récupérer les messages du tuteur
try {
    $messageModel = new Message($db);
    $allMessages = $messageModel->getConversationsByUserId($_SESSION['user_id']);
    echo "<!--Messages récupérés: " . count($allMessages) . "-->\n";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des messages: " . $e->getMessage() . "</div>";
    $allMessages = [];
}

// Organiser les messages par conversation
$conversations = [];
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
    
    // Ajouter le participant s'il n'existe pas déjà
    $participantId = $message['sender_id'] == $_SESSION['user_id'] ? $message['receiver_id'] : $message['sender_id'];
    if (!in_array($participantId, array_column($conversations[$conversationId]['participants'] ?? [], 'id'))) {
        $conversations[$conversationId]['participants'][] = [
            'id' => $participantId,
            'name' => $message['sender_id'] == $_SESSION['user_id'] ? 
                (($message['receiver_first_name'] ?? '') . ' ' . ($message['receiver_last_name'] ?? '')) : 
                (($message['sender_first_name'] ?? '') . ' ' . ($message['sender_last_name'] ?? '')),
            'role' => $message['sender_id'] == $_SESSION['user_id'] ? ($message['receiver_role'] ?? 'Utilisateur') : ($message['sender_role'] ?? 'Utilisateur'),
            'avatar' => ''
        ];
    }
    
    // Ajouter le message à la conversation
    $conversations[$conversationId]['messages'][] = $message;
    
    // Mettre à jour le dernier message si nécessaire
    if (!$conversations[$conversationId]['last_message'] || 
        (isset($message['sent_at']) && isset($conversations[$conversationId]['last_message']['sent_at']) && 
         strtotime($message['sent_at']) > strtotime($conversations[$conversationId]['last_message']['sent_at']))) {
        $conversations[$conversationId]['last_message'] = $message;
    }
    
    // Compter les messages non lus
    if (isset($message['receiver_id']) && isset($message['is_read']) && 
        $message['receiver_id'] == $_SESSION['user_id'] && !$message['is_read']) {
        $conversations[$conversationId]['unread_count']++;
    }
}

// Trier les conversations par date du dernier message (plus récent en premier)
usort($conversations, function($a, $b) {
    $dateA = isset($a['last_message']['sent_at']) ? strtotime($a['last_message']['sent_at']) : 0;
    $dateB = isset($b['last_message']['sent_at']) ? strtotime($b['last_message']['sent_at']) : 0;
    return $dateB - $dateA;
});

// Récupérer la liste des contacts disponibles
$contacts = [];

// Ajouter les étudiants assignés à la liste des contacts
foreach ($assignments as $assignment) {
    $contacts[] = [
        'id' => $assignment['student_id'],
        'type' => 'student',
        'name' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name'],
        'role' => 'Étudiant',
        'email' => $assignment['student_email'] ?? ''
    ];
}

// Ajouter les coordinateurs
try {
    $userModel = new User($db);
    $coordinators = $userModel->getByRole('coordinator');
    foreach ($coordinators as $coordinator) {
        $contacts[] = [
            'id' => $coordinator['id'],
            'type' => 'coordinator',
            'name' => $coordinator['first_name'] . ' ' . $coordinator['last_name'],
            'role' => 'Coordinateur',
            'email' => $coordinator['email']
        ];
    }
    
    // Ajouter les administrateurs
    $admins = $userModel->getByRole('admin');
    foreach ($admins as $admin) {
        $contacts[] = [
            'id' => $admin['id'],
            'type' => 'admin',
            'name' => $admin['first_name'] . ' ' . $admin['last_name'],
            'role' => 'Administrateur',
            'email' => $admin['email']
        ];
    }
} catch (Exception $e) {
    echo "<!--Erreur lors de la récupération des contacts: " . $e->getMessage() . "-->\n";
}

// Fonction pour formater la date des messages
function formatMessageDate($date) {
    if (!$date) return '-';
    
    $timestamp = strtotime($date);
    if (!$timestamp) return '-';
    
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
    } else {
        return date('d/m/Y', $timestamp);
    }
}

// Afficher la page HTML sans inclure header.php/footer.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - Réparation</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css" rel="stylesheet">
    
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
        
        .message-preview {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .message-card.unread {
            background-color: rgba(13, 110, 253, 0.05);
            border-left-color: #dc3545;
        }
        
        .fade-in {
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/tutoring/views/tutor/dashboard.php">Système de Tutorat</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/tutoring/views/tutor/messages-repair.php">Messagerie</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/tutoring/views/tutor/students.php">Étudiants</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/tutoring/logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-envelope me-2"></i>Messagerie</h2>
                    <a href="/tutoring/views/tutor/dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
                    </a>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Messagerie</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Alerte de réparation -->
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Mode de réparation</strong> - Cette page est une version simplifiée de l'interface de messagerie pour résoudre les problèmes de chargement.
        </div>
        
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
                        <div class="list-group list-group-flush">
                            <?php foreach ($conversations as $conversation): ?>
                            <?php
                            // Prepare participant data first
                            $otherParticipant = null;
                            foreach (($conversation['participants'] ?? []) as $participant) {
                                if (isset($participant['id']) && $participant['id'] != $_SESSION['user_id']) {
                                    $otherParticipant = $participant;
                                    break;
                                }
                            }
                            
                            // Build HTML attributes cleanly
                            $cardClass = "list-group-item list-group-item-action message-card";
                            if (isset($conversation['unread_count']) && $conversation['unread_count'] > 0) {
                                $cardClass .= " unread";
                            }
                            ?>
                            <a href="#" class="<?php echo $cardClass; ?>" data-conversation-id="<?php echo h($conversation['id']); ?>">
                                <div class="d-flex align-items-center">
                                    <?php
                                    // Afficher l'avatar du participant
                                    $participantName = 'User';
                                    if ($otherParticipant && isset($otherParticipant['name']) && !empty($otherParticipant['name'])) {
                                        $participantName = $otherParticipant['name'];
                                    }
                                    
                                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($participantName) . "&background=3498db&color=fff";
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

            <!-- Détails de la conversation (version simplifiée sans JavaScript) -->
            <div class="col-lg-8">
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <span>Messages</span>
                    </div>
                    <div class="card-body">
                        <div class="text-center p-5">
                            <i class="bi bi-envelope-open display-1 text-muted"></i>
                            <p class="mt-3">Pour voir les détails d'une conversation, veuillez revenir à l'interface standard une fois les problèmes résolus.</p>
                            <a href="/tutoring/views/tutor/dashboard.php" class="btn btn-primary mt-3">Retour au tableau de bord</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- New Message Form (version simplifiée) -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Nouveau message</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/tutoring/api/messages/send.php">
                            <div class="mb-3">
                                <label class="form-label">Destinataire</label>
                                <select class="form-select" name="receiver_id" required>
                                    <option value="">Sélectionner un contact...</option>
                                    <?php foreach ($contacts as $contact): ?>
                                    <option value="<?php echo h($contact['id']); ?>" data-type="<?php echo h($contact['type']); ?>">
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
                                <textarea class="form-control" name="message_content" rows="5" placeholder="Écrivez votre message ici..." required></textarea>
                            </div>
                            <button type="submit" name="send_message" class="btn btn-primary">Envoyer le message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script minimal pour la recherche de contacts
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Gérer le type de destinataire
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
        });
    </script>
</body>
</html>