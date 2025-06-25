<?php
/**
 * Vue pour créer une nouvelle affectation
 */

// Initialiser les variables
$pageTitle = 'Créer une affectation';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// S'assurer que la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    $db = getDBConnection();
}

// Instancier le contrôleur (pour référence future)
$assignmentController = new AssignmentController($db);

// Récupérer les données pour le formulaire directement
$studentModel = new Student($db);
$allStudents = $studentModel->getAll('active');

$teacherModel = new Teacher($db);
$teachers = $teacherModel->getAll(true);

$internshipModel = new Internship($db);
$allInternships = $internshipModel->getAll('available');

// Récupérer les affectations existantes pour filtrer
$assignmentModel = new Assignment($db);
$existingAssignments = $assignmentModel->getAll(); // Récupérer toutes les affectations

// Filtrer pour ne garder que les affectations actives
$activeAssignments = array_filter($existingAssignments, function($assignment) {
    return in_array($assignment['status'], ['pending', 'confirmed', 'active']);
});

// Créer des listes des étudiants et stages déjà affectés
$assignedStudentIds = [];
$assignedInternshipIds = [];

foreach ($activeAssignments as $assignment) {
    if (!in_array($assignment['student_id'], $assignedStudentIds)) {
        $assignedStudentIds[] = $assignment['student_id'];
    }
    if (!in_array($assignment['internship_id'], $assignedInternshipIds)) {
        $assignedInternshipIds[] = $assignment['internship_id'];
    }
}

// Filtrer les étudiants disponibles (non affectés)
$students = array_filter($allStudents, function($student) use ($assignedStudentIds) {
    return !in_array($student['id'], $assignedStudentIds);
});

// Filtrer les stages disponibles (non pris)
$internships = array_filter($allInternships, function($internship) use ($assignedInternshipIds) {
    return !in_array($internship['id'], $assignedInternshipIds);
});

// Calculer la capacité restante pour chaque tuteur
foreach ($teachers as &$teacher) {
    $currentAssignments = $assignmentModel->countByTeacherId($teacher['id']);
    $teacher['remaining_capacity'] = $teacher['max_students'] - $currentAssignments;
}
unset($teacher); // Important pour éviter des problèmes avec la référence

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Récupérer les IDs pré-sélectionnés depuis l'URL
$selectedStudentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : ($formData['student_id'] ?? null);
$selectedTeacherId = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : ($formData['teacher_id'] ?? null);
$selectedInternshipId = isset($_GET['internship_id']) ? intval($_GET['internship_id']) : ($formData['internship_id'] ?? null);
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-diagram-3 me-2"></i>Créer une affectation
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments.php">Affectations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Créer</li>
                </ol>
            </nav>
        </div>
        
        <a href="/tutoring/views/admin/assignments.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
    
    <!-- Affichage des erreurs du formulaire -->
    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Erreurs dans le formulaire :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($formErrors as $error): ?>
            <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Alertes de disponibilité -->
    <?php if (empty($students) && empty($internships)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Aucune affectation possible :</strong>
        Il n'y a ni étudiants ni stages disponibles. Tous sont déjà affectés.
        <div class="mt-2">
            <a href="/tutoring/views/admin/assignments.php" class="btn btn-sm btn-outline-warning me-2">
                <i class="bi bi-list me-1"></i>Voir les affectations existantes
            </a>
            <a href="/tutoring/views/admin/students.php" class="btn btn-sm btn-outline-primary me-2">
                <i class="bi bi-people me-1"></i>Gérer les étudiants
            </a>
            <a href="/tutoring/views/admin/internships.php" class="btn btn-sm btn-outline-success">
                <i class="bi bi-building me-1"></i>Gérer les stages
            </a>
        </div>
    </div>
    <?php elseif (empty($students)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-person-x me-2"></i>
        <strong>Aucun étudiant disponible :</strong>
        Tous les étudiants sont déjà affectés à un stage.
        <a href="/tutoring/views/admin/assignments.php" class="btn btn-sm btn-outline-warning ms-2">
            <i class="bi bi-list me-1"></i>Voir les affectations
        </a>
    </div>
    <?php elseif (empty($internships)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-building-x me-2"></i>
        <strong>Aucun stage disponible :</strong>
        Tous les stages sont déjà pris.
        <a href="/tutoring/views/admin/internships.php" class="btn btn-sm btn-outline-success ms-2">
            <i class="bi bi-building me-1"></i>Ajouter des stages
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Formulaire de création d'affectation -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations de l'affectation</h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/assignments/store.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="student_id" class="form-label">
                            Étudiant <span class="text-danger">*</span>
                            <small class="text-muted">(<?php echo count($students); ?> disponible(s))</small>
                        </label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Sélectionner un étudiant --</option>
                            <?php if (empty($students)): ?>
                            <option value="" disabled>Aucun étudiant disponible (tous déjà affectés)</option>
                            <?php else: ?>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" 
                                <?php echo ($selectedStudentId == $student['id']) ? 'selected' : ''; ?>
                                data-department="<?php echo h($student['department']); ?>"
                                data-level="<?php echo h($student['level']); ?>"
                                data-program="<?php echo h($student['program']); ?>">
                                <?php echo h($student['first_name'] . ' ' . $student['last_name']); ?> 
                                (<?php echo h($student['program']); ?>)
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($students)): ?>
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Tous les étudiants sont déjà affectés. Modifiez ou supprimez des affectations existantes pour en créer de nouvelles.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="teacher_id" class="form-label">Tuteur <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">-- Sélectionner un tuteur --</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" 
                                <?php echo ($selectedTeacherId == $teacher['id']) ? 'selected' : ''; ?>
                                data-department="<?php echo h($teacher['department']); ?>"
                                data-remaining="<?php echo h($teacher['remaining_capacity']); ?>"
                                data-specialty="<?php echo h(cleanSpecialty($teacher['specialty'])); ?>">
                                <?php echo h($teacher['first_name'] . ' ' . $teacher['last_name']); ?> 
                                (<?php echo h($teacher['department']); ?>, <?php echo h($teacher['remaining_capacity']); ?> places)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="internship_id" class="form-label">
                            Stage <span class="text-danger">*</span>
                            <small class="text-muted">(<?php echo count($internships); ?> disponible(s))</small>
                        </label>
                        <select class="form-select" id="internship_id" name="internship_id" required>
                            <option value="">-- Sélectionner un stage --</option>
                            <?php if (empty($internships)): ?>
                            <option value="" disabled>Aucun stage disponible (tous déjà pris)</option>
                            <?php else: ?>
                            <?php foreach ($internships as $internship): ?>
                            <option value="<?php echo $internship['id']; ?>" 
                                <?php echo ($selectedInternshipId == $internship['id']) ? 'selected' : ''; ?>
                                data-domain="<?php echo h($internship['domain']); ?>"
                                data-company="<?php echo h($internship['company_id']); ?>"
                                data-title="<?php echo h($internship['title']); ?>">
                                <?php echo h($internship['title']); ?> 
                                (<?php echo h($internship['company_name']); ?>)
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($internships)): ?>
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Tous les stages sont déjà pris. Ajoutez de nouveaux stages ou modifiez des affectations existantes.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" <?php echo (!isset($formData['status']) || $formData['status'] === 'pending') ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmed" <?php echo (isset($formData['status']) && $formData['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmée</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo h($formData['notes'] ?? ''); ?></textarea>
                    <div class="form-text">Notes sur l'affectation (visible par l'administrateur, le tuteur et l'étudiant).</div>
                </div>
                
                <!-- Section d'aperçu de compatibilité -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-subtitle mb-0">Aperçu de compatibilité</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <h6>Compatibilité étudiant-stage</h6>
                                <div id="student-compatibility">
                                    <div class="alert alert-info">
                                        Sélectionnez un étudiant et un stage pour voir la compatibilité.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h6>Compatibilité tuteur-stage</h6>
                                <div id="teacher-compatibility">
                                    <div class="alert alert-info">
                                        Sélectionnez un tuteur et un stage pour voir la compatibilité.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h6>Compatibilité tuteur-étudiant</h6>
                                <div id="teacher-student-compatibility">
                                    <div class="alert alert-info">
                                        Sélectionnez un tuteur et un étudiant pour voir la compatibilité.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="reset" class="btn btn-secondary me-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary" <?php echo (empty($students) || empty($internships)) ? 'disabled title="Étudiants ou stages manquants"' : ''; ?>>
                        <i class="bi bi-save me-2"></i>Créer l'affectation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts spécifiques -->
<script>
    // Fonctions d'initialisation
    document.addEventListener('DOMContentLoaded', function() {
        // Éléments de formulaire
        const studentSelect = document.getElementById('student_id');
        const teacherSelect = document.getElementById('teacher_id');
        const internshipSelect = document.getElementById('internship_id');
        
        // Éléments d'affichage de compatibilité
        const studentCompatibility = document.getElementById('student-compatibility');
        const teacherCompatibility = document.getElementById('teacher-compatibility');
        const teacherStudentCompatibility = document.getElementById('teacher-student-compatibility');
        
        // Événements de changement
        studentSelect.addEventListener('change', updateCompatibility);
        teacherSelect.addEventListener('change', updateCompatibility);
        internshipSelect.addEventListener('change', updateCompatibility);
        
        // Mise à jour initiale
        updateCompatibility();
        
        // Fonction de mise à jour de la compatibilité
        function updateCompatibility() {
            updateStudentInternshipCompatibility();
            updateTeacherInternshipCompatibility();
            updateTeacherStudentCompatibility();
        }
        
        // Compatibilité étudiant-stage
        function updateStudentInternshipCompatibility() {
            const studentId = studentSelect.value;
            const internshipId = internshipSelect.value;
            
            if (!studentId || !internshipId) {
                studentCompatibility.innerHTML = `
                    <div class="alert alert-info">
                        Sélectionnez un étudiant et un stage pour voir la compatibilité.
                    </div>
                `;
                return;
            }
            
            // Simuler la compatibilité (à remplacer par une requête AJAX)
            // Vérifier si le stage est dans les préférences de l'étudiant
            const isPreferred = Math.random() > 0.5;
            const match = Math.floor(Math.random() * 10) + 1;
            
            let compatibilityClass = match >= 7 ? 'success' : (match >= 4 ? 'warning' : 'danger');
            
            studentCompatibility.innerHTML = `
                <div class="mb-2">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 10px;">
                            <div class="progress-bar bg-${compatibilityClass}" role="progressbar" 
                                style="width: ${match * 10}%" 
                                aria-valuenow="${match}" 
                                aria-valuemin="0" aria-valuemax="10">
                            </div>
                        </div>
                        <span class="fw-bold">${match}/10</span>
                    </div>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span>Préférence de l'étudiant</span>
                        <span class="badge bg-${isPreferred ? 'success' : 'secondary'}">
                            ${isPreferred ? 'Oui' : 'Non'}
                        </span>
                    </li>
                </ul>
            `;
        }
        
        // Compatibilité tuteur-stage
        function updateTeacherInternshipCompatibility() {
            const teacherId = teacherSelect.value;
            const internshipId = internshipSelect.value;
            
            if (!teacherId || !internshipId) {
                teacherCompatibility.innerHTML = `
                    <div class="alert alert-info">
                        Sélectionnez un tuteur et un stage pour voir la compatibilité.
                    </div>
                `;
                return;
            }
            
            // Simuler la compatibilité (à remplacer par une requête AJAX)
            const match = Math.floor(Math.random() * 10) + 1;
            const isDomainMatch = Math.random() > 0.3;
            const isCompanyMatch = Math.random() > 0.7;
            
            let compatibilityClass = match >= 7 ? 'success' : (match >= 4 ? 'warning' : 'danger');
            
            teacherCompatibility.innerHTML = `
                <div class="mb-2">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 10px;">
                            <div class="progress-bar bg-${compatibilityClass}" role="progressbar" 
                                style="width: ${match * 10}%" 
                                aria-valuenow="${match}" 
                                aria-valuemin="0" aria-valuemax="10">
                            </div>
                        </div>
                        <span class="fw-bold">${match}/10</span>
                    </div>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span>Domaine du stage</span>
                        <span class="badge bg-${isDomainMatch ? 'success' : 'secondary'}">
                            ${isDomainMatch ? 'Préféré' : 'Non préféré'}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span>Entreprise</span>
                        <span class="badge bg-${isCompanyMatch ? 'success' : 'secondary'}">
                            ${isCompanyMatch ? 'Préférée' : 'Non préférée'}
                        </span>
                    </li>
                </ul>
            `;
        }
        
        // Compatibilité tuteur-étudiant
        function updateTeacherStudentCompatibility() {
            const teacherId = teacherSelect.value;
            const studentId = studentSelect.value;
            
            if (!teacherId || !studentId) {
                teacherStudentCompatibility.innerHTML = `
                    <div class="alert alert-info">
                        Sélectionnez un tuteur et un étudiant pour voir la compatibilité.
                    </div>
                `;
                return;
            }
            
            // Simuler la compatibilité (à remplacer par une requête AJAX)
            const match = Math.floor(Math.random() * 10) + 1;
            
            const isDepartmentMatch = teacherSelect.options[teacherSelect.selectedIndex].dataset.department === 
                                    studentSelect.options[studentSelect.selectedIndex].dataset.department;
            
            const teacherLoad = parseInt(teacherSelect.options[teacherSelect.selectedIndex].dataset.remaining);
            const loadClass = teacherLoad >= 3 ? 'success' : (teacherLoad > 0 ? 'warning' : 'danger');
            
            let compatibilityClass = match >= 7 ? 'success' : (match >= 4 ? 'warning' : 'danger');
            
            teacherStudentCompatibility.innerHTML = `
                <div class="mb-2">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 10px;">
                            <div class="progress-bar bg-${compatibilityClass}" role="progressbar" 
                                style="width: ${match * 10}%" 
                                aria-valuenow="${match}" 
                                aria-valuemin="0" aria-valuemax="10">
                            </div>
                        </div>
                        <span class="fw-bold">${match}/10</span>
                    </div>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span>Même département</span>
                        <span class="badge bg-${isDepartmentMatch ? 'success' : 'warning'}">
                            ${isDepartmentMatch ? 'Oui' : 'Non'}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span>Charge actuelle</span>
                        <span class="badge bg-${loadClass}">
                            ${teacherLoad} places disponibles
                        </span>
                    </li>
                </ul>
            `;
        }
    });
    
    // Validation du formulaire
    (function() {
        'use strict';
        
        // Fetch all forms we want to apply custom validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>