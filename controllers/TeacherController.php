<?php
/**
 * Contrôleur pour la gestion des enseignants (tuteurs)
 */
class TeacherController {
    private $db;
    private $teacherModel;
    private $userModel;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->teacherModel = new Teacher($db);
        $this->userModel = new User($db);
    }
    
    /**
     * Récupère la liste des enseignants
     * @param bool $availableOnly Si true, ne récupère que les enseignants disponibles
     * @return array Liste des enseignants
     */
    public function getTeachers($availableOnly = false) {
        return $this->teacherModel->getAll($availableOnly);
    }
    
    /**
     * Affiche la liste des enseignants
     */
    public function index() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer les enseignants
        $teachers = $this->teacherModel->getAll();
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/teachers/index.php';
    }
    
    /**
     * Affiche le formulaire de création d'un enseignant
     */
    public function create() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/teachers/create.php';
    }
    
    /**
     * Traite la création d'un enseignant
     */
    public function store() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/teachers/create.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        // Valider les données utilisateur
        if (empty($_POST['username'])) {
            $errors[] = "Le nom d'utilisateur est requis";
        } elseif ($this->userModel->usernameExists($_POST['username'])) {
            $errors[] = "Ce nom d'utilisateur existe déjà";
        }
        
        if (empty($_POST['email'])) {
            $errors[] = "L'email est requis";
        } elseif (!isValidEmail($_POST['email'])) {
            $errors[] = "L'email n'est pas valide";
        } elseif ($this->userModel->emailExists($_POST['email'])) {
            $errors[] = "Cet email existe déjà";
        }
        
        if (empty($_POST['password'])) {
            $errors[] = "Le mot de passe est requis";
        } elseif (strlen($_POST['password']) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        
        if (empty($_POST['first_name'])) {
            $errors[] = "Le prénom est requis";
        }
        
        if (empty($_POST['last_name'])) {
            $errors[] = "Le nom de famille est requis";
        }
        
        if (empty($_POST['department'])) {
            $errors[] = "Le département est requis";
        }
        
        // Valider les données enseignant
        if (empty($_POST['specialty'])) {
            $errors[] = "La spécialité est requise";
        }
        
        if (!isset($_POST['max_students']) || $_POST['max_students'] < 1) {
            $errors[] = "Le nombre maximal d'étudiants doit être supérieur à 0";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/teachers/create.php');
            return;
        }
        
        // Traiter l'image de profil
        $profileImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $profileImage = uploadFile($_FILES['profile_image'], 'profiles', $allowedTypes);
            
            if (!$profileImage) {
                setFlashMessage('error', "Erreur lors de l'upload de l'image de profil");
                redirect('/tutoring/views/admin/teachers/create.php');
                return;
            }
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Créer l'utilisateur
            $userData = [
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'email' => $_POST['email'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'role' => 'teacher',
                'department' => $_POST['department'],
                'profile_image' => $profileImage
            ];
            
            $userId = $this->userModel->create($userData);
            
            if (!$userId) {
                throw new Exception("Erreur lors de la création de l'utilisateur");
            }
            
            // Créer l'enseignant
            $teacherData = [
                'user_id' => $userId,
                'title' => isset($_POST['title']) ? $_POST['title'] : null,
                'specialty' => $_POST['specialty'],
                'office_location' => isset($_POST['office_location']) ? $_POST['office_location'] : null,
                'max_students' => $_POST['max_students'],
                'available' => isset($_POST['available']) ? 1 : 0,
                'expertise' => isset($_POST['expertise']) ? $_POST['expertise'] : null
            ];
            
            $teacherId = $this->teacherModel->create($teacherData);
            
            if (!$teacherId) {
                throw new Exception("Erreur lors de la création de l'enseignant");
            }
            
            // Traiter les préférences si elles sont fournies
            if (isset($_POST['preferences']) && is_array($_POST['preferences'])) {
                foreach ($_POST['preferences'] as $type => $values) {
                    foreach ($values as $value => $priority) {
                        if (empty($value) || empty($priority)) {
                            continue;
                        }
                        
                        $preferenceData = [
                            'teacher_id' => $teacherId,
                            'preference_type' => $type,
                            'preference_value' => $value,
                            'priority_value' => $priority
                        ];
                        
                        $preferenceResult = $this->teacherModel->addPreference($preferenceData);
                        
                        if (!$preferenceResult) {
                            throw new Exception("Erreur lors de l'ajout d'une préférence");
                        }
                    }
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Enseignant créé avec succès');
            redirect('/tutoring/views/admin/tutors.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/teachers/create.php');
        }
    }
    
    /**
     * Recherche des enseignants
     * @param string $term Terme de recherche
     * @param bool $availableOnly Si true, ne recherche que les enseignants disponibles
     * @param bool $returnResults Si true, retourne les résultats au lieu d'inclure la vue
     * @return array|null Liste des enseignants si $returnResults est true, sinon null
     */
    public function search($term = '', $availableOnly = false, $returnResults = false) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        $teachers = $this->teacherModel->search($term, $availableOnly);
        
        if ($returnResults) {
            return $teachers;
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/teachers/index.php';
    }
    
    /**
     * Récupère les détails d'un enseignant
     * @param int $id ID de l'enseignant
     * @return array|bool Détails de l'enseignant ou false si non trouvé
     */
    public function getTeacherDetails($id) {
        return $this->teacherModel->getById($id);
    }
    
    /**
     * Récupère les préférences d'un enseignant
     * @param int $id ID de l'enseignant
     * @return array Préférences de l'enseignant
     */
    public function getTeacherPreferences($id) {
        return $this->teacherModel->getPreferences($id);
    }
    
    /**
     * Récupère les affectations d'un enseignant
     * @param int $id ID de l'enseignant
     * @return array Affectations de l'enseignant
     */
    public function getTeacherAssignments($id) {
        return $this->teacherModel->getAssignments($id);
    }
    
    /**
     * Récupère les étudiants d'un enseignant
     * @param int $id ID de l'enseignant
     * @return array Étudiants de l'enseignant
     */
    public function getTeacherStudents($id) {
        return $this->teacherModel->getStudents($id);
    }
    
    /**
     * [DEPRECATED] Affiche les détails d'un enseignant
     * Cette méthode est conservée pour compatibilité mais ne devrait plus être utilisée directement.
     * @param int $id ID de l'enseignant
     */
    public function show($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer l'enseignant
        $teacher = $this->teacherModel->getById($id);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/views/admin/tutors.php');
            return;
        }
        
        // Récupérer les informations complémentaires
        $preferences = $this->teacherModel->getPreferences($id);
        $assignments = $this->teacherModel->getAssignments($id);
        $students = $this->teacherModel->getStudents($id);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/teachers/show.php';
    }
    
    /**
     * Affiche le formulaire de modification d'un enseignant
     * @param int $id ID de l'enseignant
     */
    public function edit($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer l'enseignant
        $teacher = $this->teacherModel->getById($id);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/views/admin/tutors.php');
            return;
        }
        
        // Récupérer les préférences
        $preferences = $this->teacherModel->getPreferences($id);
        
        // Organiser les préférences par type
        $organizedPreferences = [];
        foreach ($preferences as $preference) {
            $organizedPreferences[$preference['preference_type']][$preference['preference_value']] = $preference['priority_value'];
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/teachers/edit.php';
    }
    
    /**
     * Traite la modification d'un enseignant
     * @param int $id ID de l'enseignant
     */
    public function update($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/teachers/edit.php?id=' . $id);
            return;
        }
        
        // Récupérer l'enseignant
        $teacher = $this->teacherModel->getById($id);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/views/admin/tutors.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        // Valider les données utilisateur
        if (empty($_POST['email'])) {
            $errors[] = "L'email est requis";
        } elseif (!isValidEmail($_POST['email'])) {
            $errors[] = "L'email n'est pas valide";
        } elseif ($_POST['email'] !== $teacher['email'] && $this->userModel->emailExists($_POST['email'])) {
            $errors[] = "Cet email existe déjà";
        }
        
        if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        
        if (empty($_POST['first_name'])) {
            $errors[] = "Le prénom est requis";
        }
        
        if (empty($_POST['last_name'])) {
            $errors[] = "Le nom de famille est requis";
        }
        
        if (empty($_POST['department'])) {
            $errors[] = "Le département est requis";
        }
        
        // Valider les données enseignant
        if (empty($_POST['specialty'])) {
            $errors[] = "La spécialité est requise";
        }
        
        if (!isset($_POST['max_students']) || $_POST['max_students'] < 1) {
            $errors[] = "Le nombre maximal d'étudiants doit être supérieur à 0";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/teachers/edit.php?id=' . $id);
            return;
        }
        
        // Traiter l'image de profil
        $profileImage = $teacher['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $newProfileImage = uploadFile($_FILES['profile_image'], 'profiles', $allowedTypes);
            
            if ($newProfileImage) {
                // Supprimer l'ancienne image si elle existe
                if ($profileImage) {
                    deleteFile($profileImage);
                }
                $profileImage = $newProfileImage;
            } else {
                setFlashMessage('error', "Erreur lors de l'upload de l'image de profil");
                redirect('/tutoring/views/admin/teachers/edit.php?id=' . $id);
                return;
            }
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour l'utilisateur
            $userData = [
                'email' => $_POST['email'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'department' => $_POST['department'],
                'profile_image' => $profileImage
            ];
            
            // Ajouter le mot de passe s'il est fourni
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password'];
            }
            
            $userSuccess = $this->userModel->update($teacher['user_id'], $userData);
            
            if (!$userSuccess) {
                throw new Exception("Erreur lors de la mise à jour de l'utilisateur");
            }
            
            // Mettre à jour l'enseignant
            $teacherData = [
                'title' => isset($_POST['title']) ? $_POST['title'] : null,
                'specialty' => $_POST['specialty'],
                'office_location' => isset($_POST['office_location']) ? $_POST['office_location'] : null,
                'max_students' => $_POST['max_students'],
                'available' => isset($_POST['available']) ? 1 : 0,
                'expertise' => isset($_POST['expertise']) ? $_POST['expertise'] : null
            ];
            
            $teacherSuccess = $this->teacherModel->update($id, $teacherData);
            
            if (!$teacherSuccess) {
                throw new Exception("Erreur lors de la mise à jour de l'enseignant");
            }
            
            // Supprimer les anciennes préférences
            $this->teacherModel->deleteAllPreferences($id);
            
            // Traiter les nouvelles préférences
            if (isset($_POST['preferences']) && is_array($_POST['preferences'])) {
                foreach ($_POST['preferences'] as $type => $values) {
                    foreach ($values as $value => $priority) {
                        if (empty($value) || empty($priority)) {
                            continue;
                        }
                        
                        $preferenceData = [
                            'teacher_id' => $id,
                            'preference_type' => $type,
                            'preference_value' => $value,
                            'priority_value' => $priority
                        ];
                        
                        $preferenceResult = $this->teacherModel->addPreference($preferenceData);
                        
                        if (!$preferenceResult) {
                            throw new Exception("Erreur lors de l'ajout d'une préférence");
                        }
                    }
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Enseignant mis à jour avec succès');
            redirect('/tutoring/views/admin/tutors.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/teachers/edit.php?id=' . $id);
        }
    }
    
    /**
     * Traite la suppression d'un enseignant
     * @param int $id ID de l'enseignant
     */
    public function delete($id) {
        // Vérifier les permissions
        requireRole(['admin']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/tutors.php');
            return;
        }
        
        // Récupérer l'enseignant
        $teacher = $this->teacherModel->getById($id);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/views/admin/tutors.php');
            return;
        }
        
        // Supprimer l'image de profil si elle existe
        if ($teacher['profile_image']) {
            deleteFile($teacher['profile_image']);
        }
        
        // Supprimer l'enseignant
        $success = $this->teacherModel->delete($id);
        
        if ($success) {
            // Supprimer l'utilisateur associé
            $this->userModel->delete($teacher['user_id']);
            
            setFlashMessage('success', 'Enseignant supprimé avec succès');
        } else {
            setFlashMessage('error', "Erreur lors de la suppression de l'enseignant");
        }
        
        redirect('/tutoring/views/admin/tutors.php');
    }
    
    /**
     * Affiche le tableau de bord enseignant
     */
    public function dashboard() {
        // Vérifier les permissions
        requireRole(['teacher']);
        
        // Récupérer l'enseignant connecté
        $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Récupérer les informations pour le tableau de bord
        $assignments = $this->teacherModel->getAssignments($teacher['id']);
        $students = $this->teacherModel->getStudents($teacher['id']);
        $preferences = $this->teacherModel->getPreferences($teacher['id']);
        
        // Récupérer les réunions
        $meetings = $this->teacherModel->getMeetings($teacher['id']);
        
        // Afficher la vue
        include ROOT_PATH . '/views/tutor/dashboard.php';
    }
    
    /**
     * Gère les préférences d'un enseignant
     */
    public function managePreferences() {
        // Vérifier les permissions
        requireRole(['teacher']);
        
        // Récupérer l'enseignant connecté
        $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Récupérer les préférences actuelles
        $preferences = $this->teacherModel->getPreferences($teacher['id']);
        
        // Organiser les préférences par type
        $organizedPreferences = [];
        foreach ($preferences as $preference) {
            $organizedPreferences[$preference['preference_type']][$preference['preference_value']] = $preference['priority_value'];
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/tutor/preferences.php';
    }
    
    /**
     * Traite la mise à jour des préférences d'un enseignant
     */
    public function updatePreferences() {
        // Vérifier les permissions
        requireRole(['teacher']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/tutor/preferences.php');
            return;
        }
        
        // Récupérer l'enseignant connecté
        $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Vérifier que les préférences ont été soumises
        if (!isset($_POST['preferences']) || !is_array($_POST['preferences'])) {
            setFlashMessage('error', 'Aucune préférence soumise');
            redirect('/tutoring/tutor/preferences.php');
            return;
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Supprimer les anciennes préférences
            $this->teacherModel->deleteAllPreferences($teacher['id']);
            
            // Traiter les nouvelles préférences
            foreach ($_POST['preferences'] as $type => $values) {
                foreach ($values as $value => $priority) {
                    if (empty($value) || empty($priority)) {
                        continue;
                    }
                    
                    $preferenceData = [
                        'teacher_id' => $teacher['id'],
                        'preference_type' => $type,
                        'preference_value' => $value,
                        'priority_value' => $priority
                    ];
                    
                    $preferenceResult = $this->teacherModel->addPreference($preferenceData);
                    
                    if (!$preferenceResult) {
                        throw new Exception("Erreur lors de l'ajout d'une préférence");
                    }
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Préférences mises à jour avec succès');
            redirect('/tutoring/tutor/preferences.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/tutor/preferences.php');
        }
    }
    
    /**
     * Affiche la liste des étudiants d'un enseignant
     */
    public function myStudents() {
        // Vérifier les permissions
        requireRole(['teacher']);
        
        // Récupérer l'enseignant connecté
        $teacher = $this->teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            setFlashMessage('error', 'Enseignant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Récupérer les étudiants
        $students = $this->teacherModel->getStudents($teacher['id']);
        
        // Afficher la vue
        include ROOT_PATH . '/views/tutor/students.php';
    }
}