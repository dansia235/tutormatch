<?php
/**
 * Contrôleur pour la gestion des stages
 */
class InternshipController {
    private $db;
    private $internshipModel;
    private $companyModel;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->internshipModel = new Internship($db);
        
        // Initialiser le modèle d'entreprise si nécessaire
        if (class_exists('Company')) {
            $this->companyModel = new Company($db);
        }
    }
    
    /**
     * Affiche la liste des stages
     * @param string $status Statut pour filtrer (optionnel)
     */
    public function index($status = null) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator', 'teacher', 'student']);
        
        // Récupérer les stages
        $internships = $this->internshipModel->getAll($status);
        
        // Afficher la vue appropriée selon le rôle
        if (hasRole(['admin', 'coordinator'])) {
            include ROOT_PATH . '/views/admin/internships/index.php';
        } else {
            include ROOT_PATH . '/views/common/internships/index.php';
        }
    }
    
    /**
     * Affiche le formulaire de création d'un stage
     */
    public function create() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer les entreprises pour le formulaire
        $companies = $this->companyModel->getAll(true);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/internships/create.php';
    }
    
    /**
     * Traite la création d'un stage
     */
    public function store() {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/internships/create.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['company_id'])) {
            $errors[] = "L'entreprise est requise";
        }
        
        if (empty($_POST['title'])) {
            $errors[] = "Le titre est requis";
        }
        
        if (empty($_POST['description'])) {
            $errors[] = "La description est requise";
        }
        
        if (empty($_POST['start_date'])) {
            $errors[] = "La date de début est requise";
        }
        
        if (empty($_POST['end_date'])) {
            $errors[] = "La date de fin est requise";
        } elseif (strtotime($_POST['end_date']) <= strtotime($_POST['start_date'])) {
            $errors[] = "La date de fin doit être postérieure à la date de début";
        }
        
        if (empty($_POST['domain'])) {
            $errors[] = "Le domaine est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/internships/create.php');
            return;
        }
        
        // Préparer les données
        $internshipData = [
            'company_id' => $_POST['company_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'requirements' => isset($_POST['requirements']) ? $_POST['requirements'] : null,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'location' => isset($_POST['location']) ? $_POST['location'] : null,
            'work_mode' => isset($_POST['work_mode']) ? $_POST['work_mode'] : 'on_site',
            'compensation' => isset($_POST['compensation']) ? $_POST['compensation'] : null,
            'domain' => $_POST['domain'],
            'status' => isset($_POST['status']) ? $_POST['status'] : 'available'
        ];
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Créer le stage
            $internshipId = $this->internshipModel->create($internshipData);
            
            if (!$internshipId) {
                throw new Exception("Erreur lors de la création du stage");
            }
            
            // Traiter les compétences requises
            if (isset($_POST['skills']) && is_array($_POST['skills'])) {
                foreach ($_POST['skills'] as $skill) {
                    if (empty($skill)) {
                        continue;
                    }
                    
                    $skillResult = $this->internshipModel->addSkill($internshipId, $skill);
                    
                    if (!$skillResult) {
                        throw new Exception("Erreur lors de l'ajout d'une compétence");
                    }
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Stage créé avec succès');
            redirect('/tutoring/views/admin/internships/index.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/internships/create.php');
        }
    }
    
    /**
     * Affiche les détails d'un stage
     * @param int $id ID du stage
     */
    public function show($id) {
        // Vérifier les permissions
        requireLogin();
        
        // Récupérer le stage
        $internship = $this->internshipModel->getById($id);
        
        if (!$internship) {
            setFlashMessage('error', 'Stage non trouvé');
            redirect('/tutoring/internships/index.php');
            return;
        }
        
        // Récupérer les informations complémentaires
        $skills = $this->internshipModel->getSkills($id);
        $company = $this->companyModel->getById($internship['company_id']);
        
        // Vérifier si l'utilisateur est un étudiant et récupérer l'ordre de préférence
        $preferenceOrder = null;
        if (hasRole('student')) {
            $studentModel = new Student($this->db);
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            
            if ($student) {
                $preferences = $studentModel->getPreferences($student['id']);
                foreach ($preferences as $preference) {
                    if ($preference['internship_id'] == $id) {
                        $preferenceOrder = $preference['preference_order'];
                        break;
                    }
                }
            }
        }
        
        // Afficher la vue appropriée selon le rôle
        if (hasRole(['admin', 'coordinator'])) {
            include ROOT_PATH . '/views/admin/internships/show.php';
        } else {
            include ROOT_PATH . '/views/common/internships/show.php';
        }
    }
    
    /**
     * Affiche le formulaire de modification d'un stage
     * @param int $id ID du stage
     */
    public function edit($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Récupérer le stage
        $internship = $this->internshipModel->getById($id);
        
        if (!$internship) {
            setFlashMessage('error', 'Stage non trouvé');
            redirect('/tutoring/views/admin/internships/index.php');
            return;
        }
        
        // Récupérer les compétences
        $skills = $this->internshipModel->getSkills($id);
        
        // Récupérer les entreprises pour le formulaire
        $companies = $this->companyModel->getAll(true);
        
        // Afficher la vue
        include ROOT_PATH . '/views/admin/internships/edit.php';
    }
    
    /**
     * Traite la modification d'un stage
     * @param int $id ID du stage
     */
    public function update($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/internships/edit.php?id=' . $id);
            return;
        }
        
        // Récupérer le stage
        $internship = $this->internshipModel->getById($id);
        
        if (!$internship) {
            setFlashMessage('error', 'Stage non trouvé');
            redirect('/tutoring/views/admin/internships/index.php');
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['company_id'])) {
            $errors[] = "L'entreprise est requise";
        }
        
        if (empty($_POST['title'])) {
            $errors[] = "Le titre est requis";
        }
        
        if (empty($_POST['description'])) {
            $errors[] = "La description est requise";
        }
        
        if (empty($_POST['start_date'])) {
            $errors[] = "La date de début est requise";
        }
        
        if (empty($_POST['end_date'])) {
            $errors[] = "La date de fin est requise";
        } elseif (strtotime($_POST['end_date']) <= strtotime($_POST['start_date'])) {
            $errors[] = "La date de fin doit être postérieure à la date de début";
        }
        
        if (empty($_POST['domain'])) {
            $errors[] = "Le domaine est requis";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/internships/edit.php?id=' . $id);
            return;
        }
        
        // Préparer les données
        $internshipData = [
            'company_id' => $_POST['company_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'requirements' => isset($_POST['requirements']) ? $_POST['requirements'] : null,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'location' => isset($_POST['location']) ? $_POST['location'] : null,
            'work_mode' => isset($_POST['work_mode']) ? $_POST['work_mode'] : 'on_site',
            'compensation' => isset($_POST['compensation']) ? $_POST['compensation'] : null,
            'domain' => $_POST['domain'],
            'status' => isset($_POST['status']) ? $_POST['status'] : 'available'
        ];
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour le stage
            $success = $this->internshipModel->update($id, $internshipData);
            
            if (!$success) {
                throw new Exception("Erreur lors de la mise à jour du stage");
            }
            
            // Supprimer les anciennes compétences
            $this->internshipModel->clearSkills($id);
            
            // Traiter les nouvelles compétences
            if (isset($_POST['skills']) && is_array($_POST['skills'])) {
                foreach ($_POST['skills'] as $skill) {
                    if (empty($skill)) {
                        continue;
                    }
                    
                    $skillResult = $this->internshipModel->addSkill($id, $skill);
                    
                    if (!$skillResult) {
                        throw new Exception("Erreur lors de l'ajout d'une compétence");
                    }
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            
            setFlashMessage('success', 'Stage mis à jour avec succès');
            redirect('/tutoring/views/admin/internships/index.php');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            
            setFlashMessage('error', $e->getMessage());
            redirect('/tutoring/views/admin/internships/edit.php?id=' . $id);
        }
    }
    
    /**
     * Traite la suppression d'un stage
     * @param int $id ID du stage
     */
    public function delete($id) {
        // Vérifier les permissions
        requireRole(['admin', 'coordinator']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/internships/index.php');
            return;
        }
        
        // Récupérer le stage
        $internship = $this->internshipModel->getById($id);
        
        if (!$internship) {
            setFlashMessage('error', 'Stage non trouvé');
            redirect('/tutoring/views/admin/internships/index.php');
            return;
        }
        
        // Supprimer le stage
        $success = $this->internshipModel->delete($id);
        
        if ($success) {
            setFlashMessage('success', 'Stage supprimé avec succès');
        } else {
            setFlashMessage('error', "Erreur lors de la suppression du stage");
        }
        
        redirect('/tutoring/views/admin/internships/index.php');
    }
    
    /**
     * Traite la recherche de stages
     */
    public function search($term, $status = null) {
        // Vérifier les permissions
        requireLogin();
        
        return $this->internshipModel->search($term, $status);
    }
    
    /**
     * Affiche les stages disponibles pour les étudiants
     */
    public function available() {
        // Vérifier les permissions
        requireRole(['student']);
        
        // Récupérer les stages disponibles
        $internships = $this->internshipModel->getAvailable();
        
        // Récupérer les préférences de l'étudiant
        $studentModel = new Student($this->db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        $preferences = [];
        if ($student) {
            $preferencesList = $studentModel->getPreferences($student['id']);
            foreach ($preferencesList as $preference) {
                $preferences[$preference['internship_id']] = $preference['preference_order'];
            }
        }
        
        // Afficher la vue
        include ROOT_PATH . '/views/student/available_internships.php';
    }
    
    /**
     * Ajoute un stage aux préférences d'un étudiant
     * @param int $id ID du stage
     */
    public function addToPreferences($id) {
        // Vérifier les permissions
        requireRole(['student']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/internships/available.php');
            return;
        }
        
        // Récupérer le stage
        $internship = $this->internshipModel->getById($id);
        
        if (!$internship || $internship['status'] !== 'available') {
            setFlashMessage('error', 'Stage non disponible');
            redirect('/tutoring/internships/available.php');
            return;
        }
        
        // Récupérer l'étudiant
        $studentModel = new Student($this->db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Vérifier l'ordre de préférence
        if (empty($_POST['preference_order']) || !is_numeric($_POST['preference_order']) || $_POST['preference_order'] < 1) {
            setFlashMessage('error', "L'ordre de préférence doit être un nombre positif");
            redirect('/tutoring/internships/show.php?id=' . $id);
            return;
        }
        
        // Ajouter aux préférences
        $success = $studentModel->addPreference($student['id'], $id, $_POST['preference_order']);
        
        if ($success) {
            setFlashMessage('success', 'Stage ajouté à vos préférences');
        } else {
            setFlashMessage('error', "Erreur lors de l'ajout du stage à vos préférences");
        }
        
        redirect('/tutoring/internships/show.php?id=' . $id);
    }
    
    /**
     * Supprime un stage des préférences d'un étudiant
     * @param int $id ID du stage
     */
    public function removeFromPreferences($id) {
        // Vérifier les permissions
        requireRole(['student']);
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/student/preferences.php');
            return;
        }
        
        // Récupérer l'étudiant
        $studentModel = new Student($this->db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            setFlashMessage('error', 'Étudiant non trouvé');
            redirect('/tutoring/logout.php');
            return;
        }
        
        // Supprimer des préférences
        $success = $studentModel->removePreference($student['id'], $id);
        
        if ($success) {
            setFlashMessage('success', 'Stage retiré de vos préférences');
        } else {
            setFlashMessage('error', "Erreur lors de la suppression du stage de vos préférences");
        }
        
        redirect('/tutoring/student/preferences.php');
    }
    
    /**
     * Récupère tous les stages
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des stages
     */
    public function getAll($status = null) {
        return $this->internshipModel->getAll($status);
    }
    
    /**
     * Récupère la liste des domaines de stages
     * @return array Liste des domaines
     */
    public function getDomains() {
        return $this->internshipModel->getDomains();
    }
    
    /**
     * Récupère la liste des compétences requises par les stages
     * @return array Liste des compétences
     */
    public function getAllSkills() {
        return $this->internshipModel->getAllSkills();
    }
}