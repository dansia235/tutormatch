<?php
/**
 * Formulaire de secours pour l'édition des stages
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de stage invalide');
    redirect('/tutoring/views/admin/internships.php');
}

// Instancier le modèle
$internshipModel = new Internship($db);
$companyModel = new Company($db);

// Récupérer le stage
$internship = $internshipModel->getById($_GET['id']);

if (!$internship) {
    setFlashMessage('error', 'Stage non trouvé');
    redirect('/tutoring/views/admin/internships.php');
}

// Récupérer les compétences
$skills = $internshipModel->getSkills($internship['id']);

// Récupérer les entreprises pour le formulaire
$companies = $companyModel->getAll(true);

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? $internship;
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Liste des domaines (à remplacer par une vraie liste)
$domains = [
    'Informatique',
    'Réseaux',
    'Intelligence Artificielle',
    'Développement Web',
    'Développement Mobile',
    'Base de données',
    'Sécurité Informatique',
    'Cloud Computing',
    'IoT',
    'Robotique',
    'Électronique',
    'Automatique',
    'Mécanique',
    'Génie Civil',
    'Génie Industriel',
    'Logistique',
    'Marketing',
    'Finance',
    'Ressources Humaines',
    'Communication'
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de secours - Modifier un stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .btn-primary, .btn-secondary {
            font-weight: 500;
            padding: 10px 20px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h5 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert alert-info">
            <h4>Formulaire de secours</h4>
            <p>Utilisez ce formulaire si le formulaire principal ne fonctionne pas correctement.</p>
            <a href="/tutoring/views/admin/internships.php" class="btn btn-outline-primary mt-2">Retour à la liste des stages</a>
        </div>
        
        <?php if (!empty($formErrors)): ?>
        <div class="alert alert-danger">
            <strong>Erreurs dans le formulaire :</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($formErrors as $error): ?>
                <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Modifier le stage</h5>
            </div>
            <div class="card-body">
                <form action="/tutoring/views/admin/internships/update.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $internship['id']; ?>">
                    
                    <div class="form-section">
                        <h5>Informations générales</h5>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label">Titre du stage <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo h($formData['title']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="company_id" class="form-label">Entreprise <span class="text-danger">*</span></label>
                                <select class="form-select" id="company_id" name="company_id" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo h($company['id']); ?>" <?php echo ($formData['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                                        <?php echo h($company['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="domain" class="form-label">Domaine <span class="text-danger">*</span></label>
                                <select class="form-select" id="domain" name="domain" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($domains as $domain): ?>
                                    <option value="<?php echo h($domain); ?>" <?php echo ($formData['domain'] === $domain) ? 'selected' : ''; ?>>
                                        <?php echo h($domain); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available" <?php echo ($formData['status'] === 'available') ? 'selected' : ''; ?>>Disponible</option>
                                    <option value="assigned" <?php echo ($formData['status'] === 'assigned') ? 'selected' : ''; ?>>Affecté</option>
                                    <option value="completed" <?php echo ($formData['status'] === 'completed') ? 'selected' : ''; ?>>Terminé</option>
                                    <option value="cancelled" <?php echo ($formData['status'] === 'cancelled') ? 'selected' : ''; ?>>Annulé</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Période et localisation</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo h($formData['start_date']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo h($formData['end_date']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Localisation</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo h($formData['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="work_mode" class="form-label">Mode de travail <span class="text-danger">*</span></label>
                                <select class="form-select" id="work_mode" name="work_mode" required>
                                    <option value="on_site" <?php echo ($formData['work_mode'] === 'on_site') ? 'selected' : ''; ?>>Sur site</option>
                                    <option value="remote" <?php echo ($formData['work_mode'] === 'remote') ? 'selected' : ''; ?>>À distance</option>
                                    <option value="hybrid" <?php echo ($formData['work_mode'] === 'hybrid') ? 'selected' : ''; ?>>Hybride</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="compensation" class="form-label">Compensation (€/mois)</label>
                                <input type="number" class="form-control" id="compensation" name="compensation" value="<?php echo h($formData['compensation'] ?? ''); ?>" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Description et prérequis</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo h($formData['description']); ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="requirements" class="form-label">Prérequis</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="3"><?php echo h($formData['requirements'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Compétences requises</label>
                                <div id="skills-container">
                                    <?php if (!empty($skills)): ?>
                                        <?php foreach ($skills as $key => $skill): ?>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" name="skills[]" value="<?php echo h($skill['skill_name']); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="skills[]" value="">
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="skills[]" value="">
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="skills[]" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="/tutoring/views/admin/internships.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>