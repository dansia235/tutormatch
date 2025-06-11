<?php
/**
 * Vue pour modifier une entreprise
 */

// Initialiser les variables
$pageTitle = 'Modifier une entreprise';
$currentPage = 'companies';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID de l'entreprise
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'entreprise invalide');
    redirect('/tutoring/views/admin/companies.php');
}

$companyId = (int)$_GET['id'];

// Récupérer les informations sur l'entreprise
$query = "SELECT * FROM companies WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $companyId);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    setFlashMessage('error', 'Entreprise non trouvée');
    redirect('/tutoring/views/admin/companies.php');
}

// Récupérer les données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? $company;
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Inclure l'en-tête
include_once __DIR__ . '/../../common/header.php';
?>

<style>
    .form-card {
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    
    .form-card .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 15px 20px;
    }
    
    .form-card .card-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 15px 20px;
    }
    
    .required-field::after {
        content: "*";
        color: #dc3545;
        margin-left: 4px;
    }
    
    .preview-avatar {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: white;
        font-size: 2rem;
        margin-bottom: 15px;
    }
    
    .logo-preview {
        max-width: 100px;
        max-height: 100px;
        margin-bottom: 15px;
        border-radius: 10px;
        padding: 5px;
        background-color: #f8f9fa;
    }
    
    .back-button {
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid mt-4">
    <!-- Bouton Retour -->
    <div class="back-button">
        <a href="/tutoring/views/admin/companies/show.php?id=<?php echo $companyId; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour aux détails de l'entreprise
        </a>
    </div>
    
    <!-- Titre de la page -->
    <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Modifier l'entreprise</h2>
    
    <!-- Affichage des erreurs -->
    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Erreur :</strong> Veuillez corriger les erreurs suivantes :
        <ul class="mb-0 mt-2">
            <?php foreach ($formErrors as $error): ?>
            <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Formulaire de modification -->
    <div class="card form-card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations de l'entreprise</h5>
        </div>
        
        <div class="card-body">
            <form action="/tutoring/views/admin/companies/update.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $companyId; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h5>Identité de l'entreprise</h5>
                        <p class="text-muted small">Informations principales pour identifier l'entreprise.</p>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label required-field">Nom de l'entreprise</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo h($formData['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="logo_path" class="form-label">Logo de l'entreprise</label>
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($company['logo_path'])): ?>
                                        <img src="<?php echo h($company['logo_path']); ?>" alt="Logo actuel" class="logo-preview me-3">
                                        <?php else: ?>
                                        <div class="preview-avatar me-3" style="background-color: <?php echo generateAvatarColor($company['name']); ?>;">
                                            <?php echo getInitials($company['name']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <?php if (!empty($company['logo_path'])): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo">
                                                <label class="form-check-label" for="remove_logo">
                                                    Supprimer le logo actuel
                                                </label>
                                            </div>
                                            <?php endif; ?>
                                            <div>Logo actuel <?php echo !empty($company['logo_path']) ? 'ci-dessus' : 'non défini (avatar généré)'; ?></div>
                                        </div>
                                    </div>
                                    <input type="file" class="form-control" id="logo_path" name="logo_path" accept="image/*">
                                    <div class="form-text">Formats acceptés : JPG, PNG, GIF. Taille maximale : 2 Mo.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo h($formData['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="website" class="form-label">Site web</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo h($formData['website'] ?? ''); ?>" placeholder="https://example.com">
                                </div>
                                
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?php echo (isset($formData['active']) && $formData['active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="active">Entreprise active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h5>Adresse</h5>
                        <p class="text-muted small">Informations sur l'emplacement physique de l'entreprise.</p>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo h($formData['address'] ?? ''); ?>">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">Ville</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo h($formData['city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="postal_code" class="form-label">Code postal</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo h($formData['postal_code'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="country" class="form-label">Pays</label>
                                    <input type="text" class="form-control" id="country" name="country" value="<?php echo h($formData['country'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h5>Contact</h5>
                        <p class="text-muted small">Informations sur la personne à contacter dans l'entreprise.</p>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Nom du contact</label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?php echo h($formData['contact_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_title" class="form-label">Titre/Fonction</label>
                                    <input type="text" class="form-control" id="contact_title" name="contact_title" value="<?php echo h($formData['contact_title'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email du contact</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo h($formData['contact_email'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_phone" class="form-label">Téléphone du contact</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo h($formData['contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer text-end">
                    <a href="/tutoring/views/admin/companies/show.php?id=<?php echo $companyId; ?>" class="btn btn-secondary me-2">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Prévisualiser le logo sélectionné
    document.getElementById('logo_path').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.querySelector('.logo-preview');
                if (preview) {
                    preview.src = event.target.result;
                } else {
                    const newPreview = document.createElement('img');
                    newPreview.src = event.target.result;
                    newPreview.classList.add('logo-preview', 'me-3');
                    newPreview.alt = 'Aperçu du logo';
                    
                    const avatar = document.querySelector('.preview-avatar');
                    if (avatar) {
                        avatar.parentNode.replaceChild(newPreview, avatar);
                    }
                }
                
                // Décocher la case "Supprimer le logo" si cochée
                const removeLogoCheckbox = document.getElementById('remove_logo');
                if (removeLogoCheckbox) {
                    removeLogoCheckbox.checked = false;
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Gérer le changement de nom de l'entreprise
    document.getElementById('name').addEventListener('input', function(e) {
        const name = e.target.value;
        const avatar = document.querySelector('.preview-avatar');
        
        if (avatar) {
            // Générer une couleur basée sur le nom
            const hash = md5(name);
            const h = parseInt(hash.substr(0, 2), 16) % 360;
            avatar.style.backgroundColor = `hsl(${h}, 75%, 45%)`;
            
            // Mettre à jour les initiales
            const words = name.trim().split(/\s+/);
            let initials = '';
            
            for (const word of words) {
                if (word) {
                    initials += word.charAt(0);
                    if (initials.length >= 2) break;
                }
            }
            
            avatar.textContent = initials.toUpperCase();
        }
    });
    
    // Fonction MD5 pour générer un hash (simplifié pour l'exemple)
    function md5(string) {
        let hash = 0;
        if (string.length === 0) return hash.toString(16).padStart(32, '0');
        
        for (let i = 0; i < string.length; i++) {
            const char = string.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        
        return Math.abs(hash).toString(16).padStart(32, '0');
    }
</script>

<?php
// Fonction pour générer une couleur d'avatar à partir du nom
function generateAvatarColor($name) {
    $hash = md5($name);
    $h = hexdec(substr($hash, 0, 2)) % 360;
    $s = 75; // Saturation à 75%
    $l = 45; // Luminosité à 45%
    
    return "hsl($h, $s%, $l%)";
}

// Fonction pour obtenir les initiales
function getInitials($name) {
    $words = preg_split('/\s+/', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= mb_substr($word, 0, 1, 'UTF-8');
            if (strlen($initials) >= 2) break;
        }
    }
    
    return strtoupper($initials);
}

// Inclure le pied de page
include_once __DIR__ . '/../../common/footer.php';
?>