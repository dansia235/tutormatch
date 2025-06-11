<?php
/**
 * Contrôleur pour la gestion des affectations
 */
class AssignmentController {
    private $db;
    private $assignmentModel;
    private $studentModel;
    private $teacherModel;
    private $internshipModel;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->assignmentModel = new Assignment($db);
        $this->studentModel = new Student($db);
        $this->teacherModel = new Teacher($db);
        $this->internshipModel = new Internship($db);
    }
    
    /**
     * Affiche la liste des affectations
     * @param string $status Statut pour filtrer (optionnel)
     */
    public function index($status = null) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer les affectations
        $assignments = $this->assignmentModel->getAll($status);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/assignments.php';
    }
    
    /**
     * Affiche le formulaire de création d'une affectation
     */
    public function create() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer les données pour le formulaire
        $students = $this->studentModel->getAll('active');
        $teachers = $this->teacherModel->getAll(true);
        $internships = $this->internshipModel->getAll('available');
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/assignments/create.php';
    }
    
    /**
     * Traite la création d'une affectation
     */
    public function store() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/assignments/create.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['student_id'])) {
            $errors[] = "L'étudiant est requis";
        }
        
        if (empty($_POST['teacher_id'])) {
            $errors[] = "L'enseignant est requis";
        }
        
        if (empty($_POST['internship_id'])) {
            $errors[] = "Le stage est requis";
        }
        
        // Vérifier si l'étudiant a déjà une affectation
        if (!empty($_POST['student_id'])) {
            $existingAssignment = $this->assignmentModel->getByStudentId($_POST['student_id']);
            if ($existingAssignment) {
                $errors[] = "Cet étudiant a déjà une affectation";
            }
        }
        
        // Vérifier si le stage est déjà affecté
        if (!empty($_POST['internship_id'])) {
            $existingAssignment = $this->assignmentModel->getByInternshipId($_POST['internship_id']);
            if ($existingAssignment) {
                $errors[] = "Ce stage est déjà affecté";
            }
        }
        
        // Vérifier si l'enseignant a atteint sa capacité maximale
        if (!empty($_POST['teacher_id'])) {
            $teacher = $this->teacherModel->getById($_POST['teacher_id']);
            $currentAssignments = $this->assignmentModel->countByTeacherId($_POST['teacher_id']);
            
            if ($teacher && $currentAssignments >= $teacher['max_students']) {
                $errors[] = "Cet enseignant a atteint sa capacité maximale d'étudiants";
            }
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/assignments/create.php');
            return;
        }
        
        // Calculer le score de compatibilité (utilisant l'approche cohérente à 0-10)
        
        // Poids par défaut (similaires à ceux de l'algorithme de génération automatique)
        $departmentWeight = 50; // Poids pour les préférences du tuteur (50%)
        $preferenceWeight = 30; // Poids pour les préférences de l'étudiant (30%)
        $workloadWeight = 20;   // Poids pour la charge de travail (20%)
        
        // Calculer le score de préférence de l'étudiant pour ce stage
        $studentPrefScore = 0;
        $studentPreferences = $this->studentModel->getPreferences($_POST['student_id']);
        foreach ($studentPreferences as $preference) {
            if ($preference['internship_id'] == $_POST['internship_id']) {
                // Plus l'ordre de préférence est bas, plus le score est élevé (échelle 0-10)
                $studentPrefScore = max(0, 10 - $preference['preference_order']);
                break;
            }
        }
        
        // Calculer le score de préférence de l'enseignant
        $teacherPrefScore = 0;
        $teacherPreferences = $this->teacherModel->getPreferences($_POST['teacher_id']);
        $internship = $this->internshipModel->getById($_POST['internship_id']);
        $student = $this->studentModel->getById($_POST['student_id']);
        
        if ($internship && $student) {
            foreach ($teacherPreferences as $preference) {
                switch ($preference['preference_type']) {
                    case 'DOMAIN':
                        if ($preference['preference_value'] == $internship['domain']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'COMPANY':
                        if ($preference['preference_value'] == $internship['company_id']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'DEPARTMENT':
                        if ($preference['preference_value'] == $student['department']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'LEVEL':
                        if ($preference['preference_value'] == $student['level']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'PROGRAM':
                        if ($preference['preference_value'] == $student['program']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                }
            }
        }
        
        // Normaliser le score de l'enseignant (échelle 0-10)
        $teacherPrefScore = min(10, $teacherPrefScore / 5);
        
        // Calculer le score de charge de travail (équilibrage simple)
        $workloadScore = 0;
        $teacher = $this->teacherModel->getById($_POST['teacher_id']);
        if ($teacher) {
            $currentAssignments = $this->assignmentModel->countByTeacherId($_POST['teacher_id']);
            $workloadScore = 10 * (1 - ($currentAssignments / max(1, $teacher['max_students'])));
        }
        
        // Calculer le score total (sur une échelle de 0-10)
        $compatibilityScore = (
            ($departmentWeight / 100 * $teacherPrefScore) +
            ($preferenceWeight / 100 * $studentPrefScore) +
            ($workloadWeight / 100 * $workloadScore)
        );
        
        // Garantir que le score est dans la plage 0-10
        $compatibilityScore = min(10, max(0, $compatibilityScore));
        
        // Préparer les données
        $assignmentData = [
            'student_id' => $_POST['student_id'],
            'teacher_id' => $_POST['teacher_id'],
            'internship_id' => $_POST['internship_id'],
            'status' => isset($_POST['status']) ? $_POST['status'] : 'pending',
            'compatibility_score' => $compatibilityScore,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null
        ];
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Créer l'affectation
            $assignmentId = $this->assignmentModel->create($assignmentData);
            
            if (!$assignmentId) {
                throw new Exception("Erreur lors de la création de l'affectation");
            }
            
            // Mettre à jour le statut du stage
            $this->internshipModel->update($_POST['internship_id'], ['status' => 'assigned']);
            
            // Valider la transaction
            $this->db->commit();
            
            // Envoyer des notifications
            $this->sendAssignmentNotifications($assignmentId);
            
            setFlashMessage('success', 'Affectation créée avec succès');
            redirect('/tutoring/views/admin/assignments.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/assignments/create.php');
        }
    }
    
    /**
     * Affiche les détails d'une affectation
     * @param int $id ID de l'affectation
     */
    public function show($id) {
        // Vérifier les permissions
        requireLogin();
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/views/admin/assignments.php');
            return;
        }
        
        // Vérifier les autorisations spécifiques
        if (hasRole('student')) {
            // Un étudiant ne peut voir que sa propre affectation
            $student = $this->studentModel->getByUserId($_SESSION['user_id']);
            if (!$student || $student['id'] != $assignment['student_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à voir cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
        } elseif (hasRole('teacher')) {
            // Un enseignant ne peut voir que les affectations de ses étudiants
            $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
            if (!$teacher || $teacher['id'] != $assignment['teacher_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à voir cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
        }
        
        // Récupérer les informations complémentaires
        $student = $this->studentModel->getById($assignment['student_id']);
        $teacher = $this->teacherModel->getById($assignment['teacher_id']);
        $internship = $this->internshipModel->getById($assignment['internship_id']);
        
        // Afficher la vue appropriée selon le rôle
        if (hasRole(['admin', 'coordinator'])) {
            include ROOT_PATH . '/views/admin/assignments/show.php';
        } elseif (hasRole('teacher')) {
            include ROOT_PATH . '/views/tutor/assignments/show.php';
        } else {
            include ROOT_PATH . '/views/student/assignments/show.php';
        }
    }
    
    /**
     * Affiche le formulaire de modification d'une affectation
     * @param int $id ID de l'affectation
     */
    public function edit($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/views/admin/assignments.php');
            return;
        }
        
        // Récupérer les données pour le formulaire
        $students = $this->studentModel->getAll('active');
        $teachers = $this->teacherModel->getAll(true);
        
        // Pour l'édition, inclure le stage actuel même s'il est déjà affecté
        $internships = $this->internshipModel->getAll();
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/assignments/edit.php';
    }
    
    /**
     * Traite la modification d'une affectation
     * @param int $id ID de l'affectation
     */
    public function update($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/assignments/edit.php?id=' . $id);
            return;
        }
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/views/admin/assignments.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['student_id'])) {
            $errors[] = "L'étudiant est requis";
        }
        
        if (empty($_POST['teacher_id'])) {
            $errors[] = "L'enseignant est requis";
        }
        
        if (empty($_POST['internship_id'])) {
            $errors[] = "Le stage est requis";
        }
        
        // Vérifier si l'étudiant a déjà une autre affectation
        if (!empty($_POST['student_id']) && $_POST['student_id'] != $assignment['student_id']) {
            $existingAssignment = $this->assignmentModel->getByStudentId($_POST['student_id']);
            if ($existingAssignment) {
                $errors[] = "Cet étudiant a déjà une affectation";
            }
        }
        
        // Vérifier si le stage est déjà affecté à un autre étudiant
        if (!empty($_POST['internship_id']) && $_POST['internship_id'] != $assignment['internship_id']) {
            $existingAssignment = $this->assignmentModel->getByInternshipId($_POST['internship_id']);
            if ($existingAssignment) {
                $errors[] = "Ce stage est déjà affecté";
            }
        }
        
        // Vérifier si l'enseignant a atteint sa capacité maximale
        if (!empty($_POST['teacher_id']) && $_POST['teacher_id'] != $assignment['teacher_id']) {
            $teacher = $this->teacherModel->getById($_POST['teacher_id']);
            $currentAssignments = $this->assignmentModel->countByTeacherId($_POST['teacher_id']);
            
            if ($teacher && $currentAssignments >= $teacher['max_students']) {
                $errors[] = "Cet enseignant a atteint sa capacité maximale d'étudiants";
            }
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/assignments/edit.php?id=' . $id);
            return;
        }
        
        // Calculer le score de compatibilité (utilisant l'approche cohérente à 0-10)
        
        // Poids par défaut (similaires à ceux de l'algorithme de génération automatique)
        $departmentWeight = 50; // Poids pour les préférences du tuteur (50%)
        $preferenceWeight = 30; // Poids pour les préférences de l'étudiant (30%)
        $workloadWeight = 20;   // Poids pour la charge de travail (20%)
        
        // Calculer le score de préférence de l'étudiant pour ce stage
        $studentPrefScore = 0;
        $studentPreferences = $this->studentModel->getPreferences($_POST['student_id']);
        foreach ($studentPreferences as $preference) {
            if ($preference['internship_id'] == $_POST['internship_id']) {
                // Plus l'ordre de préférence est bas, plus le score est élevé (échelle 0-10)
                $studentPrefScore = max(0, 10 - $preference['preference_order']);
                break;
            }
        }
        
        // Calculer le score de préférence de l'enseignant
        $teacherPrefScore = 0;
        $teacherPreferences = $this->teacherModel->getPreferences($_POST['teacher_id']);
        $internship = $this->internshipModel->getById($_POST['internship_id']);
        $student = $this->studentModel->getById($_POST['student_id']);
        
        if ($internship && $student) {
            foreach ($teacherPreferences as $preference) {
                switch ($preference['preference_type']) {
                    case 'DOMAIN':
                        if ($preference['preference_value'] == $internship['domain']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'COMPANY':
                        if ($preference['preference_value'] == $internship['company_id']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'DEPARTMENT':
                        if ($preference['preference_value'] == $student['department']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'LEVEL':
                        if ($preference['preference_value'] == $student['level']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                    case 'PROGRAM':
                        if ($preference['preference_value'] == $student['program']) {
                            $teacherPrefScore += $preference['priority_value'];
                        }
                        break;
                }
            }
        }
        
        // Normaliser le score de l'enseignant (échelle 0-10)
        $teacherPrefScore = min(10, $teacherPrefScore / 5);
        
        // Calculer le score de charge de travail (équilibrage simple)
        $workloadScore = 0;
        $teacher = $this->teacherModel->getById($_POST['teacher_id']);
        if ($teacher) {
            $currentAssignments = $this->assignmentModel->countByTeacherId($_POST['teacher_id']);
            $workloadScore = 10 * (1 - ($currentAssignments / max(1, $teacher['max_students'])));
        }
        
        // Calculer le score total (sur une échelle de 0-10)
        $compatibilityScore = (
            ($departmentWeight / 100 * $teacherPrefScore) +
            ($preferenceWeight / 100 * $studentPrefScore) +
            ($workloadWeight / 100 * $workloadScore)
        );
        
        // Garantir que le score est dans la plage 0-10
        $compatibilityScore = min(10, max(0, $compatibilityScore));
        
        // Préparer les données
        $assignmentData = [
            'student_id' => $_POST['student_id'],
            'teacher_id' => $_POST['teacher_id'],
            'internship_id' => $_POST['internship_id'],
            'status' => isset($_POST['status']) ? $_POST['status'] : 'pending',
            'compatibility_score' => $compatibilityScore,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null
        ];
        
        // Si le statut est confirmé ou complété, ajouter la date de confirmation
        if ($_POST['status'] === 'confirmed' || $_POST['status'] === 'completed') {
            $assignmentData['confirmation_date'] = date('Y-m-d H:i:s');
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Libérer l'ancien stage si changé
            if ($_POST['internship_id'] != $assignment['internship_id']) {
                $this->internshipModel->update($assignment['internship_id'], ['status' => 'available']);
            }
            
            // Mettre à jour l'affectation
            $success = $this->assignmentModel->update($id, $assignmentData);
            
            if (!$success) {
                throw new Exception("Erreur lors de la mise à jour de l'affectation");
            }
            
            // Mettre à jour le statut du nouveau stage
            $this->internshipModel->update($_POST['internship_id'], ['status' => 'assigned']);
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Affectation mise à jour avec succès');
            redirect('/tutoring/views/admin/assignments.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/assignments/edit.php?id=' . $id);
        }
    }
    
    /**
     * Traite la suppression d'une affectation
     * @param int $id ID de l'affectation
     */
    public function delete($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/assignments.php');
            return;
        }
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/views/admin/assignments.php');
            return;
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Supprimer l'affectation
            $success = $this->assignmentModel->delete($id);
            
            if (!$success) {
                throw new Exception("Erreur lors de la suppression de l'affectation");
            }
            
            // Libérer le stage
            $this->internshipModel->update($assignment['internship_id'], ['status' => 'available']);
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Affectation supprimée avec succès');
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
        }
        
        redirect('/tutoring/views/admin/assignments.php');
    }
    
    /**
     * Traite la recherche d'affectations
     */
    public function search() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        $assignments = $this->assignmentModel->search($term, $status);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/assignments.php';
    }
    
    /**
     * Confirme une affectation (pour les enseignants)
     * @param int $id ID de l'affectation
     */
    public function confirm($id) {
        // Vérifier les permissions
        requireRole(['teacher']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/tutor/assignments/show.php?id=' . $id);
            return;
        }
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/dashboard.php');
            return;
        }
        
        // Vérifier que l'enseignant est bien associé à cette affectation
        $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher || $teacher['id'] != $assignment['teacher_id']) {
            setFlashMessage('error', 'Vous n\'êtes pas autorisé à confirmer cette affectation');
            redirect('/tutoring/dashboard.php');
            return;
        }
        
        // Mettre à jour le statut
        $assignmentData = [
            'status' => 'confirmed',
            'confirmation_date' => date('Y-m-d H:i:s'),
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : $assignment['notes']
        ];
        
        $success = $this->assignmentModel->update($id, $assignmentData);
        
        if ($success) {
            // Envoyer une notification à l'étudiant
            $this->sendConfirmationNotification($id);
            
            setFlashMessage('success', 'Affectation confirmée avec succès');
        } else {
            setFlashMessage('error', "Erreur lors de la confirmation de l'affectation");
        }
        
        redirect('/tutoring/tutor/assignments/show.php?id=' . $id);
    }
    
    /**
     * Rejette une affectation (pour les enseignants)
     * @param int $id ID de l'affectation
     */
    public function reject($id) {
        // Vérifier les permissions
        requireRole(['teacher']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/tutor/assignments/show.php?id=' . $id);
            return;
        }
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/dashboard.php');
            return;
        }
        
        // Vérifier que l'enseignant est bien associé à cette affectation
        $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher || $teacher['id'] != $assignment['teacher_id']) {
            setFlashMessage('error', 'Vous n\'êtes pas autorisé à rejeter cette affectation');
            redirect('/tutoring/dashboard.php');
            return;
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour le statut
            $assignmentData = [
                'status' => 'rejected',
                'notes' => isset($_POST['notes']) ? $_POST['notes'] : $assignment['notes']
            ];
            
            $success = $this->assignmentModel->update($id, $assignmentData);
            
            if (!$success) {
                throw new Exception("Erreur lors du rejet de l'affectation");
            }
            
            // Libérer le stage
            $this->internshipModel->update($assignment['internship_id'], ['status' => 'available']);
            
            // Valider la transaction
            $this->db->commit();
            
            // Envoyer une notification à l'administrateur
            $this->sendRejectionNotification($id);
            
            setFlashMessage('success', 'Affectation rejetée avec succès');
            redirect('/tutoring/dashboard.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/tutor/assignments/show.php?id=' . $id);
        }
    }
    
    /**
     * Marque une affectation comme terminée
     * @param int $id ID de l'affectation
     */
    public function complete($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator', 'teacher']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/assignments/show.php?id=' . $id);
            return;
        }
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect(hasRole(['admin', 'coordinator']) ? '/tutoring/views/admin/assignments.php' : '/tutoring/dashboard.php');
            return;
        }
        
        // Vérifier les autorisations spécifiques pour les enseignants
        if (hasRole('teacher')) {
            $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
            
            if (!$teacher || $teacher['id'] != $assignment['teacher_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à modifier cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour le statut
            $assignmentData = [
                'status' => 'completed',
                'notes' => isset($_POST['notes']) ? $_POST['notes'] : $assignment['notes']
            ];
            
            $success = $this->assignmentModel->update($id, $assignmentData);
            
            if (!$success) {
                throw new Exception("Erreur lors de la mise à jour de l'affectation");
            }
            
            // Mettre à jour le statut du stage
            $this->internshipModel->update($assignment['internship_id'], ['status' => 'completed']);
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Affectation marquée comme terminée avec succès');
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
        }
        
        // Rediriger selon le rôle
        if (hasRole(['admin', 'coordinator'])) {
            redirect('/tutoring/views/admin/assignments.php');
        } else {
            redirect('/tutoring/tutor/assignments/show.php?id=' . $id);
        }
    }
    
    /**
     * Affiche le formulaire d'évaluation d'une affectation
     * @param int $id ID de l'affectation
     */
    public function evaluationForm($id) {
        // Vérifier les permissions
        requireLogin();
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/dashboard.php');
            return;
        }
        
        // Vérifier les autorisations spécifiques
        if (hasRole('student')) {
            // Un étudiant ne peut évaluer que sa propre affectation
            $student = $this->studentModel->getByUserId($_SESSION['user_id']);
            if (!$student || $student['id'] != $assignment['student_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à évaluer cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
            
            // Déterminer le type d'évaluation
            $evaluationType = 'student';
            $evaluateeId = $assignment['teacher_id']; // L'étudiant évalue l'enseignant
            
        } elseif (hasRole('teacher')) {
            // Un enseignant ne peut évaluer que les affectations de ses étudiants
            $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
            if (!$teacher || $teacher['id'] != $assignment['teacher_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à évaluer cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
            
            // Déterminer le type d'évaluation
            $evaluationType = 'teacher';
            $evaluateeId = $assignment['student_id']; // L'enseignant évalue l'étudiant
        } else {
            // Administrateurs et coordinateurs
            $evaluationType = isset($_GET['type']) ? $_GET['type'] : 'mid_term';
            $evaluateeId = isset($_GET['evaluatee_id']) ? $_GET['evaluatee_id'] : $assignment['student_id'];
        }
        
        // Récupérer les informations de l'évaluateur et de l'évalué
        $evaluator = $_SESSION['user_id'];
        
        // Vérifier si une évaluation existe déjà
        $evaluationModel = new Evaluation($this->db);
        $existingEvaluation = $evaluationModel->getByAssignmentAndType($id, $evaluationType);
        
        // Afficher la vue appropriée
        if (hasRole(['admin', 'coordinator'])) {
            include ROOT_PATH . '/views/admin/assignments/evaluation_form.php';
        } elseif (hasRole('teacher')) {
            include ROOT_PATH . '/views/tutor/assignments/evaluation_form.php';
        } else {
            include ROOT_PATH . '/views/student/assignments/evaluation_form.php';
        }
    }
    
    /**
     * Traite la soumission d'une évaluation
     * @param int $id ID de l'affectation
     */
    public function submitEvaluation($id) {
        // Vérifier les permissions
        requireLogin();
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/assignments/evaluation_form.php?id=' . $id);
            return;
        }
        
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($id);
        
        if (!$assignment) {
            setFlashMessage('error', 'Affectation non trouvée');
            redirect('/tutoring/dashboard.php');
            return;
        }
        
        // Vérifier les autorisations spécifiques
        $evaluationType = $_POST['type'];
        $evaluateeId = $_POST['evaluatee_id'];
        
        if (hasRole('student')) {
            // Un étudiant ne peut évaluer que sa propre affectation
            $student = $this->studentModel->getByUserId($_SESSION['user_id']);
            if (!$student || $student['id'] != $assignment['student_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à évaluer cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
            
            // Forcer le type d'évaluation pour un étudiant
            $evaluationType = 'student';
            $evaluateeId = $assignment['teacher_id']; // L'étudiant évalue l'enseignant
            
        } elseif (hasRole('teacher')) {
            // Un enseignant ne peut évaluer que les affectations de ses étudiants
            $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
            if (!$teacher || $teacher['id'] != $assignment['teacher_id']) {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à évaluer cette affectation');
                redirect('/tutoring/dashboard.php');
                return;
            }
            
            // Forcer le type d'évaluation pour un enseignant
            $evaluationType = 'teacher';
            $evaluateeId = $assignment['student_id']; // L'enseignant évalue l'étudiant
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['score']) || !is_numeric($_POST['score']) || $_POST['score'] < 0 || $_POST['score'] > 10) {
            $errors[] = "Le score doit être un nombre entre 0 et 10";
        }
        
        if (empty($_POST['feedback'])) {
            $errors[] = "Le feedback est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/assignments/evaluation_form.php?id=' . $id);
            return;
        }
        
        // Préparer les données
        $evaluationData = [
            'assignment_id' => $id,
            'evaluator_id' => $_SESSION['user_id'],
            'evaluatee_id' => $evaluateeId,
            'type' => $evaluationType,
            'score' => $_POST['score'],
            'feedback' => $_POST['feedback'],
            'strengths' => isset($_POST['strengths']) ? $_POST['strengths'] : null,
            'areas_to_improve' => isset($_POST['areas_to_improve']) ? $_POST['areas_to_improve'] : null
        ];
        
        // Créer ou mettre à jour l'évaluation
        $evaluationModel = new Evaluation($this->db);
        $existingEvaluation = $evaluationModel->getByAssignmentAndType($id, $evaluationType);
        
        if ($existingEvaluation) {
            $success = $evaluationModel->update($existingEvaluation['id'], $evaluationData);
        } else {
            $evaluationId = $evaluationModel->create($evaluationData);
            $success = ($evaluationId !== false);
        }
        
        // Mettre à jour le score de satisfaction de l'affectation
        if ($success && $evaluationType === 'student') {
            $this->assignmentModel->update($id, ['satisfaction_score' => $_POST['score']]);
        }
        
        if ($success) {
            setFlashMessage('success', 'Évaluation soumise avec succès');
        } else {
            setFlashMessage('error', "Erreur lors de la soumission de l'évaluation");
        }
        
        // Rediriger selon le rôle
        if (hasRole(['admin', 'coordinator'])) {
            redirect('/tutoring/views/admin/assignments/show.php?id=' . $id);
        } elseif (hasRole('teacher')) {
            redirect('/tutoring/tutor/assignments/show.php?id=' . $id);
        } else {
            redirect('/tutoring/student/assignments/show.php?id=' . $id);
        }
    }
    
    /**
     * Génère des affectations automatiques
     */
    public function generateAssignments() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/assignments/generate.php');
            return;
        }
        
        // Valider les paramètres
        $errors = [];
        
        if (empty($_POST['algorithm_type'])) {
            $errors[] = "Le type d'algorithme est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/assignments/generate.php');
            return;
        }
        
        // Récupérer les étudiants sans affectation
        $students = $this->studentModel->getAll('active');
        $unassignedStudents = [];
        
        foreach ($students as $student) {
            $existingAssignment = $this->assignmentModel->getByStudentId($student['id']);
            if (!$existingAssignment) {
                $unassignedStudents[] = $student;
            }
        }
        
        // Récupérer les enseignants disponibles
        $teachers = $this->teacherModel->getAll(true);
        $availableTeachers = [];
        
        foreach ($teachers as $teacher) {
            $assignmentCount = $this->assignmentModel->countByTeacherId($teacher['id']);
            if ($assignmentCount < $teacher['max_students']) {
                $teacher['remaining_capacity'] = $teacher['max_students'] - $assignmentCount;
                $availableTeachers[] = $teacher;
            }
        }
        
        // Récupérer les stages disponibles
        $availableInternships = $this->internshipModel->getAll('available');
        
        // Vérifier s'il y a assez d'étudiants, d'enseignants et de stages
        if (empty($unassignedStudents)) {
            setFlashMessage('error', "Aucun étudiant sans affectation n'a été trouvé");
            redirect('/tutoring/views/admin/assignments/generate.php');
            return;
        }
        
        if (empty($availableTeachers)) {
            setFlashMessage('error', "Aucun enseignant disponible n'a été trouvé");
            redirect('/tutoring/views/admin/assignments/generate.php');
            return;
        }
        
        if (empty($availableInternships)) {
            setFlashMessage('error', "Aucun stage disponible n'a été trouvé");
            redirect('/tutoring/views/admin/assignments/generate.php');
            return;
        }
        
        // Vérifier s'il y a assez de stages pour tous les étudiants
        if (count($availableInternships) < count($unassignedStudents)) {
            setFlashMessage('warning', "Il n'y a pas assez de stages pour tous les étudiants");
            // Continuer quand même, certains étudiants resteront sans affectation
        }
        
        // Vérifier s'il y a assez de capacité d'enseignants pour tous les étudiants
        $totalCapacity = 0;
        foreach ($availableTeachers as $teacher) {
            $totalCapacity += $teacher['remaining_capacity'];
        }
        
        if ($totalCapacity < count($unassignedStudents)) {
            setFlashMessage('warning', "Il n'y a pas assez de capacité d'enseignants pour tous les étudiants");
            // Continuer quand même, certains étudiants resteront sans affectation
        }
        
        // Stocker les paramètres de l'algorithme
        $algorithmParams = [
            'name' => isset($_POST['name']) ? $_POST['name'] : 'Exécution du ' . date('Y-m-d H:i:s'),
            'description' => isset($_POST['notes']) ? $_POST['notes'] : null,
            'algorithm_type' => $_POST['algorithm_type'],
            'department_weight' => isset($_POST['department_weight']) ? $_POST['department_weight'] : 50,
            'preference_weight' => isset($_POST['preference_weight']) ? $_POST['preference_weight'] : 30,
            'capacity_weight' => isset($_POST['capacity_weight']) ? $_POST['capacity_weight'] : 20,
            'allow_cross_department' => isset($_POST['allow_cross_department']) ? 1 : 0,
            'prioritize_preferences' => isset($_POST['prioritize_preferences']) ? 1 : 0,
            'balance_workload' => isset($_POST['balance_workload']) ? 1 : 0,
            'is_default' => isset($_POST['set_as_default']) ? 1 : 0
        ];
        
        try {
            // Vérifier s'il y a déjà une transaction active et l'annuler si nécessaire
            try {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                    error_log("Une transaction non terminée a été annulée avant de générer les affectations");
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de la vérification de transaction: " . $e->getMessage());
            }
            
            // Commencer une nouvelle transaction
            $this->db->beginTransaction();
            
            // Sauvegarder les paramètres
            $parametersModel = new AlgorithmParameters($this->db);
            $parametersId = $parametersModel->create($algorithmParams);
            
            if ($parametersId === false) {
                throw new Exception("Erreur lors de la sauvegarde des paramètres de l'algorithme");
            }
            
            // Si défini comme par défaut, mettre à jour les autres paramètres
            if (isset($_POST['set_as_default']) && $_POST['set_as_default']) {
                $parametersModel->resetDefaultFlag($parametersId);
            }
            
            // Exécuter l'algorithme
            $startTime = microtime(true);
            $assignments = $this->executeAssignmentAlgorithm(
                $_POST['algorithm_type'],
                $unassignedStudents,
                $availableTeachers,
                $availableInternships,
                $algorithmParams
            );
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // Créer les affectations
            $assignmentsCount = 0;
            $totalSatisfaction = 0;
            
            foreach ($assignments as $assignment) {
                // Créer l'affectation
                $assignmentId = $this->assignmentModel->create([
                    'student_id' => $assignment['student_id'],
                    'teacher_id' => $assignment['teacher_id'],
                    'internship_id' => $assignment['internship_id'],
                    'status' => 'pending',
                    'compatibility_score' => $assignment['compatibility_score'],
                    'notes' => "Affectation générée automatiquement par l'algorithme " . $_POST['algorithm_type']
                ]);
                
                if ($assignmentId === false) {
                    throw new Exception("Erreur lors de la création d'une affectation");
                }
                
                // Mettre à jour le statut du stage
                $this->internshipModel->update($assignment['internship_id'], ['status' => 'assigned']);
                
                $assignmentsCount++;
                $totalSatisfaction += $assignment['compatibility_score'];
                
                // Stocker l'ID pour les notifications
                $assignment['id'] = $assignmentId;
            }
            
            // Calculer la moyenne de satisfaction
            $averageSatisfaction = ($assignmentsCount > 0) ? $totalSatisfaction / $assignmentsCount : 0;
            
            // Enregistrer l'exécution de l'algorithme
            $executionsModel = new AlgorithmExecution($this->db);
            $executionData = [
                'parameters_id' => $parametersId,
                'executed_by' => $_SESSION['user_id'],
                'execution_time' => $executionTime,
                'students_count' => count($unassignedStudents),
                'teachers_count' => count($availableTeachers),
                'assignments_count' => $assignmentsCount,
                'unassigned_count' => count($unassignedStudents) - $assignmentsCount,
                'average_satisfaction' => $averageSatisfaction,
                'notes' => isset($_POST['notes']) ? $_POST['notes'] : null
            ];
            
            $executionId = $executionsModel->create($executionData);
            
            if ($executionId === false) {
                throw new Exception("Erreur lors de l'enregistrement de l'exécution de l'algorithme");
            }
            
            // Valider la transaction
            $this->db->commit();
            
            // Envoyer des notifications pour chaque affectation
            foreach ($assignments as $assignment) {
                if (isset($assignment['id'])) {
                    $this->sendAssignmentNotifications($assignment['id']);
                }
            }
            
            setFlashMessage('success', "Génération d'affectations réussie : $assignmentsCount affectations créées sur " . count($unassignedStudents) . " étudiants");
            redirect('/tutoring/views/admin/assignments.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            try {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (PDOException $e2) {
                error_log("Erreur lors de l'annulation de la transaction: " . $e2->getMessage());
            }
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/assignments/generate.php');
        }
    }
    
    /**
     * Exécute l'algorithme d'affectation choisi
     * @param string $algorithmType Type d'algorithme
     * @param array $students Liste des étudiants
     * @param array $teachers Liste des enseignants
     * @param array $internships Liste des stages
     * @param array $params Paramètres de l'algorithme
     * @return array Liste des affectations générées
     */
    private function executeAssignmentAlgorithm($algorithmType, $students, $teachers, $internships, $params) {
        $assignments = [];
        
        switch ($algorithmType) {
            case 'greedy':
                $assignments = $this->greedyAlgorithm($students, $teachers, $internships, $params);
                break;
                
            case 'hungarian':
                $assignments = $this->hungarianAlgorithm($students, $teachers, $internships, $params);
                break;
                
            case 'genetic':
                $assignments = $this->geneticAlgorithm($students, $teachers, $internships, $params);
                break;
                
            case 'hybrid':
                $assignments = $this->hybridAlgorithm($students, $teachers, $internships, $params);
                break;
                
            default:
                // Par défaut, utiliser l'algorithme greedy
                $assignments = $this->greedyAlgorithm($students, $teachers, $internships, $params);
        }
        
        return $assignments;
    }
    
    /**
     * Algorithme glouton d'affectation
     * Assigne les stages en fonction des préférences des étudiants et des enseignants
     */
    private function greedyAlgorithm($students, $teachers, $internships, $params) {
        $assignments = [];
        $usedInternships = [];
        $teacherAssignments = [];
        
        // Initialiser les compteurs d'affectation pour chaque enseignant
        foreach ($teachers as $teacher) {
            $teacherAssignments[$teacher['id']] = 0;
        }
        
        // Trier les étudiants par moyenne (optionnel)
        usort($students, function($a, $b) {
            return $b['average_grade'] <=> $a['average_grade'];
        });
        
        // Pour chaque étudiant
        foreach ($students as $student) {
            // Récupérer les préférences de l'étudiant
            $preferences = $this->studentModel->getPreferences($student['id']);
            
            $bestScore = -1;
            $bestInternship = null;
            $bestTeacher = null;
            
            // Pour chaque stage disponible
            foreach ($internships as $internship) {
                // Vérifier si le stage est déjà utilisé
                if (in_array($internship['id'], $usedInternships)) {
                    continue;
                }
                
                // Calculer le score de préférence de l'étudiant pour ce stage
                $studentPrefScore = 0;
                foreach ($preferences as $preference) {
                    if ($preference['internship_id'] == $internship['id']) {
                        $studentPrefScore = max(0, 10 - $preference['preference_order']);
                        break;
                    }
                }
                
                // Trouver le meilleur enseignant pour ce stage
                foreach ($teachers as $teacher) {
                    // Vérifier si l'enseignant a atteint sa capacité
                    if ($teacherAssignments[$teacher['id']] >= $teacher['remaining_capacity']) {
                        continue;
                    }
                    
                    // Vérifier le département si cross-department n'est pas autorisé
                    if (!$params['allow_cross_department'] && $student['department'] != $teacher['department']) {
                        continue;
                    }
                    
                    // Calculer le score de préférence de l'enseignant
                    $teacherPrefScore = 0;
                    $teacherPreferences = $this->teacherModel->getPreferences($teacher['id']);
                    
                    foreach ($teacherPreferences as $preference) {
                        switch ($preference['preference_type']) {
                            case 'DOMAIN':
                                if ($preference['preference_value'] == $internship['domain']) {
                                    $teacherPrefScore += $preference['priority_value'];
                                }
                                break;
                            case 'COMPANY':
                                if ($preference['preference_value'] == $internship['company_id']) {
                                    $teacherPrefScore += $preference['priority_value'];
                                }
                                break;
                            case 'DEPARTMENT':
                                if ($preference['preference_value'] == $student['department']) {
                                    $teacherPrefScore += $preference['priority_value'];
                                }
                                break;
                            case 'LEVEL':
                                if ($preference['preference_value'] == $student['level']) {
                                    $teacherPrefScore += $preference['priority_value'];
                                }
                                break;
                            case 'PROGRAM':
                                if ($preference['preference_value'] == $student['program']) {
                                    $teacherPrefScore += $preference['priority_value'];
                                }
                                break;
                        }
                    }
                    
                    // Normaliser le score de l'enseignant
                    $teacherPrefScore = min(10, $teacherPrefScore / 5);
                    
                    // Calculer le score de charge de travail
                    $workloadScore = 0;
                    if ($params['balance_workload']) {
                        $workloadScore = 10 * (1 - ($teacherAssignments[$teacher['id']] / $teacher['remaining_capacity']));
                    }
                    
                    // Calculer le score total (sur une échelle de 0-10)
                    // Les poids department_weight, preference_weight et capacity_weight sont des pourcentages (somme = 100%)
                    $totalScore = (
                        ($params['department_weight'] / 100 * $teacherPrefScore) +
                        ($params['preference_weight'] / 100 * $studentPrefScore) +
                        ($params['capacity_weight'] / 100 * $workloadScore)
                    );
                    
                    // Mettre à jour le meilleur score
                    if ($totalScore > $bestScore) {
                        $bestScore = $totalScore;
                        $bestInternship = $internship;
                        $bestTeacher = $teacher;
                    }
                }
            }
            
            // Créer l'affectation avec le meilleur score
            if ($bestScore >= 0 && $bestInternship && $bestTeacher) {
                $assignments[] = [
                    'student_id' => $student['id'],
                    'teacher_id' => $bestTeacher['id'],
                    'internship_id' => $bestInternship['id'],
                    'compatibility_score' => min(10, max(0, $bestScore)) // Score sur 0-10, garantir qu'il est bien dans cette plage
                ];
                
                // Mettre à jour les compteurs
                $usedInternships[] = $bestInternship['id'];
                $teacherAssignments[$bestTeacher['id']]++;
            }
        }
        
        return $assignments;
    }
    
    /**
     * Algorithme hongrois d'affectation (optimisation globale)
     * Implémentation simplifiée pour ce contexte
     */
    private function hungarianAlgorithm($students, $teachers, $internships, $params) {
        // Pour simplifier, nous utilisons l'algorithme glouton comme base
        // Une vraie implémentation de l'algorithme hongrois serait plus complexe
        return $this->greedyAlgorithm($students, $teachers, $internships, $params);
    }
    
    /**
     * Algorithme génétique d'affectation
     * Implémentation simplifiée pour ce contexte
     */
    private function geneticAlgorithm($students, $teachers, $internships, $params) {
        // Pour simplifier, nous utilisons l'algorithme glouton comme base
        // Une vraie implémentation de l'algorithme génétique serait plus complexe
        return $this->greedyAlgorithm($students, $teachers, $internships, $params);
    }
    
    /**
     * Algorithme hybride d'affectation
     * Implémentation simplifiée pour ce contexte
     */
    private function hybridAlgorithm($students, $teachers, $internships, $params) {
        // Pour simplifier, nous utilisons l'algorithme glouton comme base
        // Une vraie implémentation d'un algorithme hybride serait plus complexe
        return $this->greedyAlgorithm($students, $teachers, $internships, $params);
    }
    
    /**
     * Envoie des notifications pour une nouvelle affectation
     * @param int $assignmentId ID de l'affectation
     */
    private function sendAssignmentNotifications($assignmentId) {
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($assignmentId);
        
        if (!$assignment) {
            return;
        }
        
        // Récupérer les informations complémentaires
        $student = $this->studentModel->getById($assignment['student_id']);
        $teacher = $this->teacherModel->getById($assignment['teacher_id']);
        $internship = $this->internshipModel->getById($assignment['internship_id']);
        
        if (!$student || !$teacher || !$internship) {
            return;
        }
        
        // Créer une notification pour l'étudiant
        $notificationModel = new Notification($this->db);
        
        $notificationModel->create([
            'user_id' => $student['user_id'],
            'title' => 'Nouvelle affectation de stage',
            'message' => "Vous avez été affecté au stage \"" . $internship['title'] . "\" avec " . $teacher['first_name'] . " " . $teacher['last_name'] . " comme tuteur.",
            'type' => 'assignment',
            'link' => '/tutoring/student/assignments/show.php?id=' . $assignmentId
        ]);
        
        // Créer une notification pour l'enseignant
        $notificationModel->create([
            'user_id' => $teacher['user_id'],
            'title' => 'Nouvel étudiant affecté',
            'message' => "L'étudiant " . $student['first_name'] . " " . $student['last_name'] . " a été affecté à vous pour le stage \"" . $internship['title'] . "\".",
            'type' => 'assignment',
            'link' => '/tutoring/tutor/assignments/show.php?id=' . $assignmentId
        ]);
    }
    
    /**
     * Envoie une notification de confirmation d'affectation
     * @param int $assignmentId ID de l'affectation
     */
    private function sendConfirmationNotification($assignmentId) {
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($assignmentId);
        
        if (!$assignment) {
            return;
        }
        
        // Récupérer les informations complémentaires
        $student = $this->studentModel->getById($assignment['student_id']);
        $teacher = $this->teacherModel->getById($assignment['teacher_id']);
        $internship = $this->internshipModel->getById($assignment['internship_id']);
        
        if (!$student || !$teacher || !$internship) {
            return;
        }
        
        // Créer une notification pour l'étudiant
        $notificationModel = new Notification($this->db);
        
        $notificationModel->create([
            'user_id' => $student['user_id'],
            'title' => 'Affectation confirmée',
            'message' => "Votre affectation au stage \"" . $internship['title'] . "\" a été confirmée par votre tuteur " . $teacher['first_name'] . " " . $teacher['last_name'] . ".",
            'type' => 'assignment_confirmation',
            'link' => '/tutoring/student/assignments/show.php?id=' . $assignmentId
        ]);
        
        // Créer une notification pour les administrateurs
        $adminUsers = $this->userModel->getAll('admin');
        
        foreach ($adminUsers as $admin) {
            $notificationModel->create([
                'user_id' => $admin['id'],
                'title' => 'Affectation confirmée',
                'message' => "L'affectation de " . $student['first_name'] . " " . $student['last_name'] . " au stage \"" . $internship['title'] . "\" a été confirmée par " . $teacher['first_name'] . " " . $teacher['last_name'] . ".",
                'type' => 'assignment_confirmation',
                'link' => '/tutoring/admin/assignments/show.php?id=' . $assignmentId
            ]);
        }
    }
    
    /**
     * Envoie une notification de rejet d'affectation
     * @param int $assignmentId ID de l'affectation
     */
    private function sendRejectionNotification($assignmentId) {
        // Récupérer l'affectation
        $assignment = $this->assignmentModel->getById($assignmentId);
        
        if (!$assignment) {
            return;
        }
        
        // Récupérer les informations complémentaires
        $student = $this->studentModel->getById($assignment['student_id']);
        $teacher = $this->teacherModel->getById($assignment['teacher_id']);
        $internship = $this->internshipModel->getById($assignment['internship_id']);
        
        if (!$student || !$teacher || !$internship) {
            return;
        }
        
        // Créer une notification pour les administrateurs
        $notificationModel = new Notification($this->db);
        $adminUsers = $this->userModel->getAll('admin');
        
        foreach ($adminUsers as $admin) {
            $notificationModel->create([
                'user_id' => $admin['id'],
                'title' => 'Affectation rejetée',
                'message' => "L'affectation de " . $student['first_name'] . " " . $student['last_name'] . " au stage \"" . $internship['title'] . "\" a été rejetée par " . $teacher['first_name'] . " " . $teacher['last_name'] . ".",
                'type' => 'assignment_rejection',
                'link' => '/tutoring/admin/assignments/show.php?id=' . $assignmentId
            ]);
        }
    }
    
    /**
     * Récupère toutes les affectations
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des affectations
     */
    public function getAll($status = null) {
        return $this->assignmentModel->getAll($status);
    }
    
    /**
     * Recherche des affectations sans afficher la vue
     * @param string $term Terme de recherche
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des affectations correspondantes
     */
    public function searchAssignments($term, $status = null) {
        return $this->assignmentModel->search($term, $status);
    }
}