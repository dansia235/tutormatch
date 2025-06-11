<?php
/**
 * Contrôleur pour la gestion des utilisateurs
 */
class UserController {
    private $db;
    private $userModel;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
    }
    
    /**
     * Affiche la liste des utilisateurs
     * @param string $role Rôle pour filtrer (optionnel)
     */
    public function index($role = null) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer les utilisateurs
        $users = $this->userModel->getAll($role);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/users/index.php';
    }
    
    /**
     * Affiche le formulaire de création d'un utilisateur
     */
    public function create() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/users/create.php';
    }
    
    /**
     * Traite la création d'un utilisateur
     */
    public function store() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/users/create.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
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
        
        if (empty($_POST['role'])) {
            $errors[] = "Le rôle est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/users/create.php');
            return;
        }
        
        // Traiter l'image de profil
        $profileImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $profileImage = uploadFile($_FILES['profile_image'], 'profiles', $allowedTypes);
            
            if (!$profileImage) {
                setFlashMessage('error', "Erreur lors de l'upload de l'image de profil");
                redirect('/tutoring/views/admin/users/create.php');
                return;
            }
        }
        
        // Préparer les données
        $userData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'role' => $_POST['role'],
            'department' => isset($_POST['department']) ? $_POST['department'] : null,
            'profile_image' => $profileImage
        ];
        
        // Début d'une transaction
        $this->db->beginTransaction();
        
        try {
            // Créer l'utilisateur
            $userId = $this->userModel->create($userData);
            
            if (!$userId) {
                throw new Exception("Erreur lors de la création de l'utilisateur");
            }
            
            // Si le rôle est "student", créer une entrée dans la table students
            if ($userData['role'] === 'student') {
                // Générer un numéro d'étudiant aléatoire si non fourni
                $studentNumber = isset($_POST['student_number']) ? $_POST['student_number'] : 'STU' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                
                // Créer l'étudiant
                $studentData = [
                    'user_id' => $userId,
                    'student_number' => $studentNumber,
                    'program' => isset($_POST['program']) ? $_POST['program'] : 'Non spécifié',
                    'level' => isset($_POST['level']) ? $_POST['level'] : 'L1',
                    'status' => 'active'
                ];
                
                // Charger le modèle Student
                $studentModel = new Student($this->db);
                
                // Créer l'entrée étudiant
                $studentId = $studentModel->create($studentData);
                
                if (!$studentId) {
                    throw new Exception("Erreur lors de la création de l'entrée étudiant");
                }
            }
            
            // Si le rôle est "teacher", créer une entrée dans la table teachers
            if ($userData['role'] === 'teacher') {
                // Créer le tuteur
                $teacherData = [
                    'user_id' => $userId,
                    'title' => isset($_POST['title']) ? $_POST['title'] : null,
                    'specialty' => isset($_POST['specialty']) ? $_POST['specialty'] : null,
                    'office_location' => isset($_POST['office_location']) ? $_POST['office_location'] : null,
                    'max_students' => isset($_POST['max_students']) ? $_POST['max_students'] : 5,
                    'available' => true
                ];
                
                // Charger le modèle Teacher
                $teacherModel = new Teacher($this->db);
                
                // Créer l'entrée tuteur
                $teacherId = $teacherModel->create($teacherData);
                
                if (!$teacherId) {
                    throw new Exception("Erreur lors de la création de l'entrée tuteur");
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Utilisateur créé avec succès');
            redirect('/tutoring/views/admin/users.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/users/create.php');
        }
    }
    
    /**
     * Récupère un utilisateur par son ID
     * @param int $id ID de l'utilisateur
     * @return array|false Données de l'utilisateur ou false si non trouvé
     */
    public function getById($id) {
        return $this->userModel->getById($id);
    }

    /**
     * Affiche les détails d'un utilisateur
     * @param int $id ID de l'utilisateur
     */
    public function show($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer l'utilisateur
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            setFlashMessage('error', 'Utilisateur non trouvé');
            redirect('/tutoring/views/admin/users.php');
            return;
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/users/show.php';
    }
    
    /**
     * Affiche le formulaire de modification d'un utilisateur
     * @param int $id ID de l'utilisateur
     */
    public function edit($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer l'utilisateur
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            setFlashMessage('error', 'Utilisateur non trouvé');
            redirect('/tutoring/views/admin/users.php');
            return;
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/users/edit.php';
    }
    
    /**
     * Traite la modification d'un utilisateur
     * @param int $id ID de l'utilisateur
     */
    public function update($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/users/edit.php?id=' . $id);
            return;
        }
        
        // Récupérer l'utilisateur existant
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            setFlashMessage('error', 'Utilisateur non trouvé');
            redirect('/tutoring/views/admin/users.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['username'])) {
            $errors[] = "Le nom d'utilisateur est requis";
        } elseif ($_POST['username'] !== $user['username'] && $this->userModel->usernameExists($_POST['username'])) {
            $errors[] = "Ce nom d'utilisateur existe déjà";
        }
        
        if (empty($_POST['email'])) {
            $errors[] = "L'email est requis";
        } elseif (!isValidEmail($_POST['email'])) {
            $errors[] = "L'email n'est pas valide";
        } elseif ($_POST['email'] !== $user['email'] && $this->userModel->emailExists($_POST['email'])) {
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
        
        if (empty($_POST['role'])) {
            $errors[] = "Le rôle est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/users/edit.php?id=' . $id);
            return;
        }
        
        // Traiter l'image de profil
        $profileImage = $user['profile_image'];
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
                redirect('/tutoring/views/admin/users/edit.php?id=' . $id);
                return;
            }
        }
        
        // Préparer les données
        $userData = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'role' => $_POST['role'],
            'department' => isset($_POST['department']) ? $_POST['department'] : null,
            'profile_image' => $profileImage
        ];
        
        // Ajouter le mot de passe s'il est fourni
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        // Début d'une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour l'utilisateur
            $success = $this->userModel->update($id, $userData);
            
            if (!$success) {
                throw new Exception("Erreur lors de la mise à jour de l'utilisateur");
            }
            
            // Si le rôle est "student", vérifier s'il existe déjà dans la table students
            if ($userData['role'] === 'student') {
                $studentModel = new Student($this->db);
                $student = $studentModel->getByUserId($id);
                
                // Si l'entrée étudiant n'existe pas, la créer
                if (!$student) {
                    // Générer un numéro d'étudiant aléatoire
                    $studentNumber = isset($_POST['student_number']) ? $_POST['student_number'] : 'STU' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    
                    $studentData = [
                        'user_id' => $id,
                        'student_number' => $studentNumber,
                        'program' => isset($_POST['program']) ? $_POST['program'] : 'Non spécifié',
                        'level' => isset($_POST['level']) ? $_POST['level'] : 'L1',
                        'status' => 'active'
                    ];
                    
                    $studentId = $studentModel->create($studentData);
                    
                    if (!$studentId) {
                        throw new Exception("Erreur lors de la création de l'entrée étudiant");
                    }
                }
            }
            
            // Si le rôle est "teacher", vérifier s'il existe déjà dans la table teachers
            if ($userData['role'] === 'teacher') {
                $teacherModel = new Teacher($this->db);
                $teacher = $teacherModel->getByUserId($id);
                
                // Si l'entrée tuteur n'existe pas, la créer
                if (!$teacher) {
                    $teacherData = [
                        'user_id' => $id,
                        'title' => isset($_POST['title']) ? $_POST['title'] : null,
                        'specialty' => isset($_POST['specialty']) ? $_POST['specialty'] : null,
                        'office_location' => isset($_POST['office_location']) ? $_POST['office_location'] : null,
                        'max_students' => isset($_POST['max_students']) ? $_POST['max_students'] : 5,
                        'available' => true
                    ];
                    
                    $teacherId = $teacherModel->create($teacherData);
                    
                    if (!$teacherId) {
                        throw new Exception("Erreur lors de la création de l'entrée tuteur");
                    }
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Utilisateur mis à jour avec succès');
            redirect('/tutoring/views/admin/users.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/users/edit.php?id=' . $id);
        }
    }
    
    /**
     * Traite la suppression d'un utilisateur
     * @param int $id ID de l'utilisateur
     */
    public function delete($id) {
        // Vérifier les permissions
        requireRole(['admin']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/users.php');
            return;
        }
        
        // Récupérer l'utilisateur
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            setFlashMessage('error', 'Utilisateur non trouvé');
            redirect('/tutoring/views/admin/users.php');
            return;
        }
        
        // Supprimer l'image de profil si elle existe
        if ($user['profile_image']) {
            deleteFile($user['profile_image']);
        }
        
        // Supprimer l'utilisateur
        $success = $this->userModel->delete($id);
        
        if ($success) {
            setFlashMessage('success', 'Utilisateur supprimé avec succès');
        } else {
            setFlashMessage('error', "Erreur lors de la suppression de l'utilisateur");
        }
        
        redirect('/tutoring/views/admin/users/index.php');
    }
    
    /**
     * Affiche le profil de l'utilisateur connecté
     */
    public function profile() {
        // Vérifier que l'utilisateur est connecté
        requireLogin();
        
        // Récupérer l'utilisateur connecté
        $user = $this->userModel->getById($_SESSION['user_id']);
        
        if (!$user) {
            setFlashMessage('error', 'Utilisateur non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/common/profile.php';
    }
    
    /**
     * Traite la mise à jour du profil de l'utilisateur connecté
     */
    public function updateProfile() {
        // Vérifier que l'utilisateur est connecté
        requireLogin();
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/profile.php');
            return;
        }
        
        // Récupérer l'utilisateur connecté
        $user = $this->userModel->getById($_SESSION['user_id']);
        
        if (!$user) {
            setFlashMessage('error', 'Utilisateur non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['email'])) {
            $errors[] = "L'email est requis";
        } elseif (!isValidEmail($_POST['email'])) {
            $errors[] = "L'email n'est pas valide";
        } elseif ($_POST['email'] !== $user['email'] && $this->userModel->emailExists($_POST['email'], $user['id'])) {
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
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/profile.php');
            return;
        }
        
        // Traiter l'image de profil
        $profileImage = $user['profile_image'];
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
                redirect('/tutoring/profile.php');
                return;
            }
        }
        
        // Préparer les données
        $userData = [
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'profile_image' => $profileImage
        ];
        
        // Ajouter le mot de passe s'il est fourni
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        // Mettre à jour l'utilisateur
        $success = $this->userModel->update($user['id'], $userData);
        
        if ($success) {
            // Mettre à jour les données de session
            $_SESSION['user_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
            
            setFlashMessage('success', 'Profil mis à jour avec succès');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour du profil');
        }
        
        redirect('/tutoring/profile.php');
    }
    
    /**
     * Traite la recherche d'utilisateurs
     */
    public function search() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $role = isset($_GET['role']) ? $_GET['role'] : null;
        
        $users = $this->userModel->search($term, $role);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/users/index.php';
    }
}