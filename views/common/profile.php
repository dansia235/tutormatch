<?php
/**
 * Page de profil utilisateur
 */

// Titre de la page
$pageTitle = 'Mon Profil';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Mon Profil</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/index.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profil</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    Informations personnelles
                </div>
                <div class="card-body text-center">
                    <?php
                    // Récupérer l'avatar de l'utilisateur
                    $initials = '';
                    if (isset($_SESSION['user_name'])) {
                        $nameParts = explode(' ', $_SESSION['user_name']);
                        if (count($nameParts) >= 2) {
                            $initials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1);
                        } else {
                            $initials = mb_substr($_SESSION['user_name'], 0, 2);
                        }
                    }
                    $initials = strtoupper($initials);
                    
                    // Couleur de fond de l'avatar selon le rôle
                    $avatarBg = [
                        'admin' => '3498db',
                        'coordinator' => 'e74c3c',
                        'teacher' => '2ecc71',
                        'student' => 'f39c12'
                    ][$_SESSION['user_role']] ?? '95a5a6';
                    
                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['user_name']) . "&background=" . $avatarBg . "&color=fff&size=200";
                    ?>
                    <img src="<?php echo h($avatarUrl); ?>" alt="User" class="rounded-circle mb-3" width="150" height="150">
                    <h4><?php echo h($_SESSION['user_name']); ?></h4>
                    <p class="text-muted">
                        <?php
                        // Afficher le rôle en français
                        $roles = [
                            'admin' => 'Administrateur',
                            'coordinator' => 'Coordinateur',
                            'teacher' => 'Tuteur',
                            'student' => 'Étudiant'
                        ];
                        echo h($roles[$_SESSION['user_role']] ?? $_SESSION['user_role']);
                        ?>
                    </p>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeAvatarModal">
                        <i class="bi bi-camera me-2"></i>Changer l'avatar
                    </button>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person me-2"></i>Nom d'utilisateur</span>
                        <span class="text-primary"><?php echo h($_SESSION['user_username']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-envelope me-2"></i>Email</span>
                        <span class="text-primary">user@example.com</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-building me-2"></i>Département</span>
                        <span class="text-primary">Non défini</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar me-2"></i>Dernière connexion</span>
                        <span class="text-primary"><?php echo date('d/m/Y H:i'); ?></span>
                    </li>
                </ul>
                <div class="card-body">
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="bi bi-key me-2"></i>Changer le mot de passe
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Modifier le profil</span>
                    <button class="btn btn-sm btn-primary" id="saveProfileBtn">
                        <i class="bi bi-save me-1"></i>Enregistrer
                    </button>
                </div>
                <div class="card-body">
                    <form id="profileForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" value="<?php echo explode(' ', $_SESSION['user_name'])[0] ?? ''; ?>" id="firstName">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" value="<?php echo explode(' ', $_SESSION['user_name'])[1] ?? ''; ?>" id="lastName">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="user@example.com" id="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" placeholder="+33 6 12 34 56 78" id="phone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" placeholder="123 rue de Paris" id="address">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Code postal</label>
                                <input type="text" class="form-control" placeholder="75000" id="postalCode">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" placeholder="Paris" id="city">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pays</label>
                                <input type="text" class="form-control" placeholder="France" id="country">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" rows="4" placeholder="À propos de vous..." id="bio"></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    Notifications
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        <label class="form-check-label" for="emailNotifications">
                            Recevoir les notifications par email
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="newMeetingNotifications" checked>
                        <label class="form-check-label" for="newMeetingNotifications">
                            Notifications pour les nouvelles réunions
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="newMessageNotifications" checked>
                        <label class="form-check-label" for="newMessageNotifications">
                            Notifications pour les nouveaux messages
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="evaluationNotifications" checked>
                        <label class="form-check-label" for="evaluationNotifications">
                            Notifications pour les évaluations
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Changer le mot de passe -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Changer le mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="savePasswordBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Changer l'avatar -->
<div class="modal fade" id="changeAvatarModal" tabindex="-1" aria-labelledby="changeAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeAvatarModalLabel">Changer l'avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changeAvatarForm">
                    <div class="mb-3">
                        <label class="form-label">Télécharger une image</label>
                        <input type="file" class="form-control" id="avatarFile" accept="image/*">
                    </div>
                    <div class="mt-3 text-center">
                        <img src="<?php echo h($avatarUrl); ?>" alt="Preview" id="avatarPreview" class="rounded-circle" width="150" height="150">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveAvatarBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<?php
// Ajouter des scripts supplémentaires pour la page
$extraScripts = <<<HTML
<script>
    // Simuler l'enregistrement du profil
    document.getElementById('saveProfileBtn').addEventListener('click', function() {
        // Simuler une requête AJAX
        setTimeout(() => {
            // Afficher un message de succès
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>Profil mis à jour avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alert, container.firstChild);
            
            // Fermer l'alerte après 5 secondes
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }, 5000);
        }, 1000);
    });
    
    // Prévisualiser l'avatar
    document.getElementById('avatarFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Simuler le changement de mot de passe
    document.getElementById('savePasswordBtn').addEventListener('click', function() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Vérifier que les champs sont remplis
        if (!currentPassword || !newPassword || !confirmPassword) {
            alert('Veuillez remplir tous les champs.');
            return;
        }
        
        // Vérifier que les mots de passe correspondent
        if (newPassword !== confirmPassword) {
            alert('Les mots de passe ne correspondent pas.');
            return;
        }
        
        // Simuler une requête AJAX
        setTimeout(() => {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
            
            // Afficher un message de succès
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>Mot de passe mis à jour avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alert, container.firstChild);
            
            // Fermer l'alerte après 5 secondes
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }, 5000);
            
            // Réinitialiser le formulaire
            document.getElementById('changePasswordForm').reset();
        }, 1000);
    });
</script>
HTML;

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>