<?php
/**
 * Contrôleur pour la gestion des étudiants
 */
class StudentController {
    private $db;
    private $studentModel;
    private $userModel;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->studentModel = new Student($db);
        $this->userModel = new User($db);
    }
    
    /**
     * Récupère la liste des étudiants
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des étudiants
     */
    public function getStudents($status = null) {
        return $this->studentModel->getAll($status);
    }
    
    /**
     * Affiche la liste des étudiants
     * @param string $status Statut pour filtrer (optionnel)
     */
    public function index($status = null) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator', 'teacher']);
        
        // Récupérer les étudiants
        $students = $this->studentModel->getAll($status);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/students/index.php';
    }
    
    /**
     * Affiche le formulaire de création d'un étudiant
     */
    public function create() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/students/create.php';
    }
    
    /**
     * Traite la création d'un étudiant
     */
    public function store() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/students/create.php');
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
        
        // Valider les données étudiant
        if (empty($_POST['student_number'])) {
            $errors[] = "Le numéro d'étudiant est requis";
        } elseif ($this->studentModel->studentNumberExists($_POST['student_number'])) {
            $errors[] = "Ce numéro d'étudiant existe déjà";
        }
        
        if (empty($_POST['program'])) {
            $errors[] = "Le programme d'études est requis";
        }
        
        if (empty($_POST['level'])) {
            $errors[] = "Le niveau d'études est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/students/create.php');
            return;
        }
        
        // Traiter le CV
        $cvPath = null;
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $cvPath = uploadFile($_FILES['cv'], 'cvs', $allowedTypes);
            
            if (!$cvPath) {
                setFlashMessage('error', "Erreur lors de l'upload du CV");
                redirect('/tutoring/views/admin/students/create.php');
                return;
            }
        }
        
        // Traiter l'image de profil
        $profileImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $profileImage = uploadFile($_FILES['profile_image'], 'profiles', $allowedTypes);
            
            if (!$profileImage) {
                setFlashMessage('error', "Erreur lors de l'upload de l'image de profil");
                redirect('/tutoring/views/admin/students/create.php');
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
                'role' => 'student',
                'department' => isset($_POST['department']) ? $_POST['department'] : null,
                'profile_image' => $profileImage
            ];
            
            $userId = $this->userModel->create($userData);
            
            if (!$userId) {
                throw new Exception("Erreur lors de la création de l'utilisateur");
            }
            
            // Créer l'étudiant
            $studentData = [
                'user_id' => $userId,
                'student_number' => $_POST['student_number'],
                'program' => $_POST['program'],
                'level' => $_POST['level'],
                'average_grade' => isset($_POST['average_grade']) ? $_POST['average_grade'] : null,
                'graduation_year' => isset($_POST['graduation_year']) ? $_POST['graduation_year'] : null,
                'skills' => isset($_POST['skills']) ? $_POST['skills'] : null,
                'cv_path' => $cvPath,
                'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
            ];
            
            $studentId = $this->studentModel->create($studentData);
            
            if (!$studentId) {
                throw new Exception("Erreur lors de la création de l'étudiant");
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Étudiant créé avec succès');
            redirect('/tutoring/views/admin/students.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/students/create.php');
        }
    }
    
    /**
     * Récupère les détails d'un étudiant
     * @param int $id ID de l'étudiant
     * @return array|bool Détails de l'étudiant ou false si non trouvé
     */
    public function getStudentDetails($id) {
        return $this->studentModel->getById($id);
    }
    
    /**
     * Récupère les préférences d'un étudiant
     * @param int $id ID de l'étudiant
     * @return array Préférences de l'étudiant
     */
    public function getStudentPreferences($id) {
        return $this->studentModel->getPreferences($id);
    }
    
    /**
     * Récupère l'affectation d'un étudiant
     * @param int $id ID de l'étudiant
     * @return array|bool Affectation de l'étudiant ou false si aucune
     */
    public function getStudentAssignment($id) {
        return $this->studentModel->getAssignment($id);
    }
    
    /**
     * Récupère les documents d'un étudiant
     * @param int $id ID de l'étudiant
     * @return array Documents de l'étudiant
     */
    public function getStudentDocuments($id) {
        return $this->studentModel->getDocuments($id);
    }
    
    /**
     * Récupère les réunions d'un étudiant
     * @param int $id ID de l'étudiant
     * @return array Réunions de l'étudiant
     */
    public function getStudentMeetings($id) {
        return $this->studentModel->getMeetings($id);
    }
    
    /**
     * [DEPRECATED] Affiche les détails d'un étudiant
     * Cette méthode est conservée pour compatibilité mais ne devrait plus être utilisée directement.
     * @param int $id ID de l'étudiant
     */
    public function show($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator', 'teacher']);
        
        // Récupérer l'étudiant
        $student = $this->studentModel->getById($id);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/views/admin/students.php');
            return;
        }
        
        // Récupérer les informations complémentaires
        $preferences = $this->studentModel->getPreferences($id);
        $assignment = $this->studentModel->getAssignment($id);
        $documents = $this->studentModel->getDocuments($id);
        $meetings = $this->studentModel->getMeetings($id);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/students/show.php';
    }
    
    /**
     * Affiche le formulaire de modification d'un étudiant
     * @param int $id ID de l'étudiant
     */
    public function edit($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer l'étudiant
        $student = $this->studentModel->getById($id);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/views/admin/students.php');
            return;
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/students/edit.php';
    }
    
    /**
     * Traite la modification d'un étudiant
     * @param int $id ID de l'étudiant
     */
    public function update($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/students/edit.php?id=' . $id);
            return;
        }
        
        // Récupérer l'étudiant
        $student = $this->studentModel->getById($id);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/views/admin/students.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        // Valider les données utilisateur
        if (empty($_POST['email'])) {
            $errors[] = "L'email est requis";
        } elseif (!isValidEmail($_POST['email'])) {
            $errors[] = "L'email n'est pas valide";
        } elseif ($_POST['email'] !== $student['email'] && $this->userModel->emailExists($_POST['email'])) {
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
        
        // Valider les données étudiant
        if (empty($_POST['student_number'])) {
            $errors[] = "Le numéro d'étudiant est requis";
        } elseif ($_POST['student_number'] !== $student['student_number'] && $this->studentModel->studentNumberExists($_POST['student_number'])) {
            $errors[] = "Ce numéro d'étudiant existe déjà";
        }
        
        if (empty($_POST['program'])) {
            $errors[] = "Le programme d'études est requis";
        }
        
        if (empty($_POST['level'])) {
            $errors[] = "Le niveau d'études est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/students/edit.php?id=' . $id);
            return;
        }
        
        // Traiter le CV
        $cvPath = $student['cv_path'];
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $newCvPath = uploadFile($_FILES['cv'], 'cvs', $allowedTypes);
            
            if ($newCvPath) {
                // Supprimer l'ancien CV si il existe
                if ($cvPath) {
                    deleteFile($cvPath);
                }
                $cvPath = $newCvPath;
            } else {
                setFlashMessage('error', "Erreur lors de l'upload du CV");
                redirect('/tutoring/views/admin/students/edit.php?id=' . $id);
                return;
            }
        }
        
        // Traiter l'image de profil
        $profileImage = $student['profile_image'];
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
                redirect('/tutoring/views/admin/students/edit.php?id=' . $id);
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
                'department' => isset($_POST['department']) ? $_POST['department'] : null,
                'profile_image' => $profileImage
            ];
            
            // Ajouter le mot de passe s'il est fourni
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password'];
            }
            
            $userSuccess = $this->userModel->update($student['user_id'], $userData);
            
            if (!$userSuccess) {
                throw new Exception("Erreur lors de la mise à jour de l'utilisateur");
            }
            
            // Mettre à jour l'étudiant
            $studentData = [
                'student_number' => $_POST['student_number'],
                'program' => $_POST['program'],
                'level' => $_POST['level'],
                'average_grade' => isset($_POST['average_grade']) ? $_POST['average_grade'] : null,
                'graduation_year' => isset($_POST['graduation_year']) ? $_POST['graduation_year'] : null,
                'skills' => isset($_POST['skills']) ? $_POST['skills'] : null,
                'cv_path' => $cvPath,
                'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
            ];
            
            $studentSuccess = $this->studentModel->update($id, $studentData);
            
            if (!$studentSuccess) {
                throw new Exception("Erreur lors de la mise à jour de l'étudiant");
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Étudiant mis à jour avec succès');
            redirect('/tutoring/views/admin/students.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/students/edit.php?id=' . $id);
        }
    }
    
    /**
     * Traite la suppression d'un étudiant
     * @param int $id ID de l'étudiant
     */
    public function delete($id) {
        // Vérifier les permissions
        requireRole(['admin']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/students.php');
            return;
        }
        
        // Récupérer l'étudiant
        $student = $this->studentModel->getById($id);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/views/admin/students.php');
            return;
        }
        
        // Supprimer le CV si il existe
        if ($student['cv_path']) {
            deleteFile($student['cv_path']);
        }
        
        // Supprimer l'image de profil si elle existe
        if ($student['profile_image']) {
            deleteFile($student['profile_image']);
        }
        
        // Supprimer l'étudiant
        $success = $this->studentModel->delete($id);
        
        if ($success) {
            // Supprimer l'utilisateur associé
            $this->userModel->delete($student['user_id']);
            
            setFlashMessage('success', 'Étudiant supprimé avec succès');
        } else {
            setFlashMessage('error', "Erreur lors de la suppression de l'étudiant");
        }
        
        redirect('/tutoring/views/admin/students.php');
    }
    
    /**
     * Traite la recherche d'étudiants
     * @param string $term Terme de recherche
     * @param string $status Statut pour filtrer (optionnel)
     * @param bool $returnResults Si true, retourne les résultats au lieu d'inclure la vue
     * @return array|null Liste des étudiants si $returnResults est true, sinon null
     */
    public function search($term = '', $status = null, $returnResults = false) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator', 'teacher']);
        
        $students = $this->studentModel->search($term, $status);
        
        if ($returnResults) {
            return $students;
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/students/index.php';
    }
    
    /**
     * Affiche le tableau de bord étudiant
     */
    public function dashboard() {
        // Vérifier les permissions
        requireRole(['student']);
        
        // Récupérer l'étudiant connecté
        $student = $this->studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Récupérer les informations pour le tableau de bord
        $assignment = $this->studentModel->getAssignment($student['id']);
        $preferences = $this->studentModel->getPreferences($student['id']);
        $documents = $this->studentModel->getDocuments($student['id']);
        $meetings = $this->studentModel->getMeetings($student['id']);
        
        // Afficher la vue
        include ROOT_PATH . '/views/student/dashboard.php';
    }
    
    /**
     * Gère les préférences de stage d'un étudiant
     */
    public function managePreferences() {
        // Vérifier les permissions
        requireRole(['student']);
        
        // Récupérer l'étudiant connecté
        $student = $this->studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Récupérer les préférences actuelles
        $preferences = $this->studentModel->getPreferences($student['id']);
        
        // Récupérer les stages disponibles
        $internshipModel = new Internship($this->db);
        $availableInternships = $internshipModel->getAvailable();
        
        // Afficher la vue
        include ROOT_PATH . '/views/student/preferences.php';
    }
    
    /**
     * Traite la mise à jour des préférences de stage
     */
    public function updatePreferences() {
        // Vérifier les permissions
        requireRole(['student']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/student/preferences.php');
            return;
        }
        
        // Récupérer l'étudiant connecté
        $student = $this->studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Vérifier que les préférences ont été soumises
        if (!isset($_POST['preferences']) || !is_array($_POST['preferences'])) {
            setFlashMessage('error', 'Aucune préférence soumise');
            redirect('/tutoring/student/preferences.php');
            return;
        }
        
        // Traiter les préférences
        $success = true;
        
        foreach ($_POST['preferences'] as $internshipId => $order) {
            if (empty($order)) {
                continue; // Ignorer les stages sans ordre de préférence
            }
            
            $result = $this->studentModel->addPreference($student['id'], $internshipId, $order);
            
            if (!$result) {
                $success = false;
            }
        }
        
        if ($success) {
            setFlashMessage('success', 'Préférences mises à jour avec succès');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour des préférences');
        }
        
        redirect('/tutoring/student/preferences.php');
    }
}