<?php
/**
 * Vue pour afficher les stages d'une entreprise
 */

// Initialiser les variables
$pageTitle = 'Stages de l\'entreprise';
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

// Récupérer les stages de l'entreprise
$internshipModel = new Internship($db);
$internships = $internshipModel->getByCompany($companyId);

// Inclure l'en-tête
include_once __DIR__ . '/../../common/header.php';
?>

<style>
    /* Styles pour la page de stages d'une entreprise */
    .company-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .company-logo {
        width: 100px;
        height: 100px;
        object-fit: contain;
        border-radius: 10px;
        background-color: #f8f9fa;
        padding: 10px;
        margin-right: 20px;
    }
    
    .company-avatar {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background-color: #e9ecef;
        color: #495057;
        font-size: 2.5rem;
        margin-right: 20px;
    }
    
    .company-info {
        flex: 1;
    }
    
    .company-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .company-title .status-badge {
        font-size: 0.8rem;
        padding: 3px 10px;
        border-radius: 20px;
    }
    
    .internship-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .internship-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .internship-status {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    
    .internship-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .internship-tag {
        font-size: 0.8rem;
        padding: 5px 10px;
        border-radius: 20px;
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
        margin-right: 5px;
        margin-bottom: 5px;
        display: inline-block;
    }
    
    .internship-detail {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .internship-detail i {
        width: 20px;
        margin-right: 10px;
        text-align: center;
    }
    
    .back-button {
        margin-bottom: 20px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        font-size: 1.1rem;
        color: #6c757d;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid mt-4">
    <!-- Bouton Retour -->
    <div class="back-button">
        <a href="/tutoring/views/admin/companies.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour à la liste des entreprises
        </a>
    </div>
    
    <!-- En-tête de l'entreprise -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="company-header">
                <?php if (!empty($company['logo_path'])): ?>
                <img src="<?php echo h($company['logo_path']); ?>" alt="<?php echo h($company['name']); ?>" class="company-logo">
                <?php else: ?>
                <div class="company-avatar" style="background-color: <?php echo generateAvatarColor($company['name']); ?>; color: white;">
                    <?php echo getInitials($company['name']); ?>
                </div>
                <?php endif; ?>
                
                <div class="company-info">
                    <div class="company-title">
                        <h2 class="mb-0"><?php echo h($company['name']); ?></h2>
                        <span class="badge <?php echo $company['active'] ? 'bg-success' : 'bg-secondary'; ?> status-badge">
                            <?php echo $company['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <p class="text-muted mb-2">
                        <?php 
                        $addressParts = [];
                        if (!empty($company['address'])) $addressParts[] = $company['address'];
                        if (!empty($company['city'])) $addressParts[] = $company['city'];
                        if (!empty($company['country'])) $addressParts[] = $company['country'];
                        echo h(implode(', ', $addressParts));
                        ?>
                    </p>
                    
                    <div class="mt-3">
                        <?php if (!empty($company['website'])): ?>
                        <a href="<?php echo h($company['website']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                            <i class="bi bi-globe me-1"></i>Site web
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['contact_email'])): ?>
                        <a href="mailto:<?php echo h($company['contact_email']); ?>" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-envelope me-1"></i>Contact
                        </a>
                        <?php endif; ?>
                        
                        <a href="#" class="btn btn-sm btn-outline-info" onclick="alert('Fonctionnalité à implémenter');">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($company['description'])): ?>
            <div class="mt-3">
                <h5>À propos de l'entreprise</h5>
                <p><?php echo nl2br(h($company['description'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Liste des stages -->
    <div class="card">
        <div class="card-body">
            <div class="internship-header">
                <h3><i class="bi bi-briefcase me-2"></i>Stages proposés</h3>
                <div>
                    <a href="#" class="btn btn-primary" onclick="alert('Fonctionnalité à implémenter');">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un stage
                    </a>
                </div>
            </div>
            
            <?php if (empty($internships)): ?>
            <div class="empty-state">
                <i class="bi bi-clipboard-x d-block"></i>
                <p>Aucun stage trouvé pour cette entreprise</p>
                <a href="#" class="btn btn-primary" onclick="alert('Fonctionnalité à implémenter');">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter un stage
                </a>
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach ($internships as $internship): ?>
                <div class="col">
                    <div class="card h-100 internship-card">
                        <div class="card-body">
                            <div class="internship-status">
                                <?php
                                $statusLabels = [
                                    'available' => '<span class="badge bg-success">Disponible</span>',
                                    'assigned' => '<span class="badge bg-info">Assigné</span>',
                                    'completed' => '<span class="badge bg-secondary">Terminé</span>',
                                    'cancelled' => '<span class="badge bg-danger">Annulé</span>'
                                ];
                                echo $statusLabels[$internship['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                ?>
                            </div>
                            
                            <h5 class="card-title mb-3"><?php echo h($internship['title']); ?></h5>
                            
                            <div class="internship-detail">
                                <i class="bi bi-buildings-fill text-primary"></i>
                                <span><?php echo h($internship['company_name']); ?></span>
                            </div>
                            
                            <div class="internship-detail">
                                <i class="bi bi-geo-alt text-danger"></i>
                                <span><?php echo h($internship['location']); ?></span>
                            </div>
                            
                            <div class="internship-detail">
                                <i class="bi bi-calendar-date text-success"></i>
                                <span><?php echo formatDate($internship['start_date']) . ' - ' . formatDate($internship['end_date']); ?></span>
                            </div>
                            
                            <div class="internship-detail">
                                <i class="bi bi-laptop text-info"></i>
                                <span><?php echo h($internship['work_mode']); ?></span>
                            </div>
                            
                            <div class="internship-detail">
                                <i class="bi bi-tag text-warning"></i>
                                <span><?php echo h($internship['domain']); ?></span>
                            </div>
                            
                            <?php if (!empty($internship['compensation'])): ?>
                            <div class="internship-detail">
                                <i class="bi bi-currency-euro text-success"></i>
                                <span><?php echo h($internship['compensation']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($internship['skills'])): ?>
                            <div class="mt-3">
                                <p class="text-muted mb-2">Compétences requises:</p>
                                <div>
                                    <?php foreach ($internship['skills'] as $skill): ?>
                                    <span class="internship-tag"><?php echo h($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($internship['description'])): ?>
                            <div class="mt-3">
                                <p class="text-muted small">
                                    <?php echo substr(h($internship['description']), 0, 150) . (strlen($internship['description']) > 150 ? '...' : ''); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-transparent d-flex justify-content-between">
                            <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Détails
                            </a>
                            <div>
                                <a href="/tutoring/views/admin/internships/edit.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil me-1"></i>Modifier
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $internship['id']; ?>)">
                                    <i class="bi bi-trash me-1"></i>Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce stage ?')) {
            window.location.href = '/tutoring/views/admin/internships/delete.php?id=' + id;
        }
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