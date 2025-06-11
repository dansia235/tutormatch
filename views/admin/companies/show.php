<?php
/**
 * Vue pour afficher les détails d'une entreprise
 */

// Initialiser les variables
$pageTitle = 'Détails de l\'entreprise';
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
$queryInternships = "SELECT COUNT(*) as count, 
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM internships 
                    WHERE company_id = :company_id";
$stmtInternships = $db->prepare($queryInternships);
$stmtInternships->bindParam(':company_id', $companyId);
$stmtInternships->execute();
$internshipStats = $stmtInternships->fetch(PDO::FETCH_ASSOC);

// Récupérer les 5 derniers stages de l'entreprise
$queryRecentInternships = "SELECT i.*, 
                          (SELECT COUNT(*) FROM student_preferences WHERE internship_id = i.id) as preference_count
                          FROM internships i 
                          WHERE i.company_id = :company_id 
                          ORDER BY i.created_at DESC 
                          LIMIT 5";
$stmtRecentInternships = $db->prepare($queryRecentInternships);
$stmtRecentInternships->bindParam(':company_id', $companyId);
$stmtRecentInternships->execute();
$recentInternships = $stmtRecentInternships->fetchAll(PDO::FETCH_ASSOC);

// Inclure l'en-tête
include_once __DIR__ . '/../../common/header.php';
?>

<style>
    /* Styles pour la page de détails d'une entreprise */
    .company-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .company-logo {
        width: 120px;
        height: 120px;
        object-fit: contain;
        border-radius: 10px;
        background-color: #f8f9fa;
        padding: 15px;
        margin-right: 25px;
    }
    
    .company-avatar {
        width: 120px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: white;
        font-size: 3rem;
        margin-right: 25px;
    }
    
    .company-info {
        flex: 1;
    }
    
    .company-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .company-title .status-badge {
        font-size: 0.8rem;
        padding: 5px 12px;
        border-radius: 20px;
    }
    
    .company-detail {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }
    
    .company-detail i {
        width: 24px;
        margin-right: 15px;
        text-align: center;
        font-size: 1.1rem;
    }
    
    .stat-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        padding: 20px;
        text-align: center;
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    
    .stat-card .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1;
        margin-bottom: 10px;
    }
    
    .stat-card .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .recent-internship {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 15px;
        border-radius: 8px;
    }
    
    .recent-internship:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .empty-state {
        text-align: center;
        padding: 30px 20px;
    }
    
    .empty-state i {
        font-size: 2.5rem;
        color: #dee2e6;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        font-size: 1rem;
        color: #6c757d;
        margin-bottom: 20px;
    }
    
    .internship-tag {
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 15px;
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
        margin-right: 4px;
        margin-bottom: 4px;
        display: inline-block;
    }
    
    .back-button {
        margin-bottom: 20px;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .contact-info {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
    }
    
    .contact-title {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .contact-title i {
        margin-right: 10px;
        color: #3498db;
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
                <div class="company-avatar" style="background-color: <?php echo generateAvatarColor($company['name']); ?>;">
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
                    
                    <p class="text-muted mb-3">
                        <?php 
                        $addressParts = [];
                        if (!empty($company['address'])) $addressParts[] = $company['address'];
                        if (!empty($company['city'])) $addressParts[] = $company['city'];
                        if (!empty($company['country'])) $addressParts[] = $company['country'];
                        echo h(implode(', ', $addressParts));
                        ?>
                    </p>
                    
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($company['website'])): ?>
                        <a href="<?php echo h($company['website']); ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-globe me-1"></i>Site web
                        </a>
                        <?php endif; ?>
                        
                        <a href="/tutoring/views/admin/companies/company_internships.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-info">
                            <i class="bi bi-briefcase me-1"></i>Voir les stages (<?php echo $internshipStats['count']; ?>)
                        </a>
                        
                        <a href="/tutoring/views/admin/companies/edit.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCompanyModal">
                            <i class="bi bi-trash me-1"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($company['description'])): ?>
            <div class="mt-4">
                <h5>À propos de l'entreprise</h5>
                <p><?php echo nl2br(h($company['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Informations de contact -->
            <div class="contact-info">
                <div class="contact-title">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Informations de contact</span>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <?php if (!empty($company['contact_name'])): ?>
                        <div class="company-detail">
                            <i class="bi bi-person text-primary"></i>
                            <span><?php echo h($company['contact_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['contact_title'])): ?>
                        <div class="company-detail">
                            <i class="bi bi-briefcase text-info"></i>
                            <span><?php echo h($company['contact_title']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <?php if (!empty($company['contact_email'])): ?>
                        <div class="company-detail">
                            <i class="bi bi-envelope text-success"></i>
                            <a href="mailto:<?php echo h($company['contact_email']); ?>"><?php echo h($company['contact_email']); ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($company['contact_phone'])): ?>
                        <div class="company-detail">
                            <i class="bi bi-telephone text-danger"></i>
                            <a href="tel:<?php echo h($company['contact_phone']); ?>"><?php echo h($company['contact_phone']); ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques des stages -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $internshipStats['count']; ?></div>
                <div class="stat-label">Stages au total</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $internshipStats['available']; ?></div>
                <div class="stat-label">Stages disponibles</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $internshipStats['assigned']; ?></div>
                <div class="stat-label">Stages assignés</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $internshipStats['completed']; ?></div>
                <div class="stat-label">Stages terminés</div>
            </div>
        </div>
    </div>
    
    <!-- Stages récents -->
    <div class="card">
        <div class="card-body">
            <div class="section-header">
                <h4><i class="bi bi-clock-history me-2"></i>Stages récents</h4>
                <a href="/tutoring/views/admin/companies/company_internships.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-outline-primary">
                    Voir tous les stages
                </a>
            </div>
            
            <?php if (empty($recentInternships)): ?>
            <div class="empty-state">
                <i class="bi bi-clipboard-x d-block"></i>
                <p>Aucun stage trouvé pour cette entreprise</p>
                <a href="/tutoring/views/admin/internships/create.php?company_id=<?php echo $company['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter un stage
                </a>
            </div>
            <?php else: ?>
            <div class="list-group">
                <?php foreach ($recentInternships as $internship): ?>
                <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $internship['id']; ?>" class="list-group-item list-group-item-action recent-internship">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <h5 class="mb-1"><?php echo h($internship['title']); ?></h5>
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
                    
                    <div class="d-flex w-100 justify-content-between">
                        <p class="mb-1"><?php echo h(substr($internship['description'], 0, 150) . (strlen($internship['description']) > 150 ? '...' : '')); ?></p>
                        <small class="text-muted ms-2">
                            <?php if (isset($internship['preference_count']) && $internship['preference_count'] > 0): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-star-fill me-1"></i><?php echo $internship['preference_count']; ?> préférence<?php echo $internship['preference_count'] > 1 ? 's' : ''; ?>
                            </span>
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <div class="d-flex align-items-center mt-2">
                        <div class="me-3">
                            <i class="bi bi-geo-alt text-danger me-1"></i>
                            <small><?php echo h($internship['location']); ?></small>
                        </div>
                        <div class="me-3">
                            <i class="bi bi-calendar-date text-success me-1"></i>
                            <small><?php echo formatDate($internship['start_date']) . ' - ' . formatDate($internship['end_date']); ?></small>
                        </div>
                        <div>
                            <i class="bi bi-tag text-primary me-1"></i>
                            <small><?php echo h($internship['domain']); ?></small>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-labelledby="deleteCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCompanyModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center">Êtes-vous sûr de vouloir supprimer l'entreprise <strong><?php echo h($company['name']); ?></strong> ?</p>
                
                <?php if ($internshipStats['count'] > 0): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <strong>Attention :</strong> Cette entreprise possède <?php echo $internshipStats['count']; ?> stage<?php echo $internshipStats['count'] > 1 ? 's' : ''; ?>. 
                    La suppression de l'entreprise entraînera également la suppression de tous ses stages.
                    <?php if ($internshipStats['assigned'] > 0): ?>
                    <p class="mb-0 mt-2"><strong><?php echo $internshipStats['assigned']; ?> stage<?php echo $internshipStats['assigned'] > 1 ? 's sont assignés' : ' est assigné'; ?></strong> à des étudiants. 
                    Cela perturbera les affectations existantes.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <p class="text-danger text-center">
                    <small>Cette action est irréversible.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/tutoring/views/admin/companies/delete.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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