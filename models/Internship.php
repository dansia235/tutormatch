<?php
/**
 * Modèle pour la gestion des stages
 */
class Internship {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère un stage par son ID
     * @param int $id ID du stage
     * @return array|false Données du stage si trouvé, sinon false
     */
    public function getById($id) {
        $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                  FROM internships i
                  JOIN companies c ON i.company_id = c.id
                  WHERE i.id = :id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $internship = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($internship) {
            // Récupérer les compétences requises
            $internship['skills'] = $this->getInternshipSkills($id);
        }
        
        return $internship;
    }

    /**
     * Récupère tous les stages
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des stages
     */
    public function getAll($status = null) {
        $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                  FROM internships i
                  JOIN companies c ON i.company_id = c.id";
                  
        if ($status) {
            $query .= " WHERE i.status = :status";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les compétences pour chaque stage
        foreach ($internships as &$internship) {
            $internship['skills'] = $this->getInternshipSkills($internship['id']);
        }
        
        return $internships;
    }

    /**
     * Récupère les compétences requises pour un stage
     * @param int $internshipId ID du stage
     * @return array Liste des compétences
     */
    public function getInternshipSkills($internshipId) {
        $query = "SELECT skill_name FROM internship_skills WHERE internship_id = :internship_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère les compétences requises pour un stage (alias pour getInternshipSkills)
     * @param int $internshipId ID du stage
     * @return array Liste des compétences
     */
    public function getSkills($internshipId) {
        return $this->getInternshipSkills($internshipId);
    }

    /**
     * Crée un nouveau stage
     * @param array $data Données du stage
     * @return int|false ID du stage créé, sinon false
     */
    public function create($data) {
        $this->db->beginTransaction();
        
        try {
            $query = "INSERT INTO internships (company_id, title, description, requirements, start_date, end_date, 
                     location, work_mode, compensation, domain, status) 
                     VALUES (:company_id, :title, :description, :requirements, :start_date, :end_date, 
                     :location, :work_mode, :compensation, :domain, :status)";
                     
            $stmt = $this->db->prepare($query);
            
            // Liaison des paramètres
            $stmt->bindParam(':company_id', $data['company_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':requirements', $data['requirements']);
            $stmt->bindParam(':start_date', $data['start_date']);
            $stmt->bindParam(':end_date', $data['end_date']);
            $stmt->bindParam(':location', $data['location']);
            $stmt->bindParam(':work_mode', $data['work_mode']);
            $stmt->bindParam(':compensation', $data['compensation']);
            $stmt->bindParam(':domain', $data['domain']);
            $stmt->bindParam(':status', $data['status']);
            
            $stmt->execute();
            $internshipId = $this->db->lastInsertId();
            
            // Ajouter les compétences requises
            if (isset($data['skills']) && is_array($data['skills'])) {
                foreach ($data['skills'] as $skill) {
                    $this->addSkill($internshipId, $skill);
                }
            }
            
            $this->db->commit();
            return $internshipId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // En production, loggez l'erreur au lieu de l'afficher
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute une compétence requise pour un stage
     * @param int $internshipId ID du stage
     * @param string $skillName Nom de la compétence
     * @return bool Succès de l'opération
     */
    public function addSkill($internshipId, $skillName) {
        $query = "INSERT INTO internship_skills (internship_id, skill_name) VALUES (:internship_id, :skill_name)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->bindParam(':skill_name', $skillName);
        
        return $stmt->execute();
    }

    /**
     * Supprime toutes les compétences d'un stage
     * @param int $internshipId ID du stage
     * @return bool Succès de l'opération
     */
    public function clearSkills($internshipId) {
        $query = "DELETE FROM internship_skills WHERE internship_id = :internship_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':internship_id', $internshipId);
        
        return $stmt->execute();
    }

    /**
     * Met à jour un stage existant
     * @param int $id ID du stage
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        $this->db->beginTransaction();
        
        try {
            $fields = [];
            $values = [':id' => $id];
            
            // Construire dynamiquement les champs à mettre à jour
            foreach ($data as $key => $value) {
                if ($key !== 'id' && $key !== 'skills') {
                    $fields[] = "$key = :$key";
                    $values[":$key"] = $value;
                }
            }
            
            if (!empty($fields)) {
                $query = "UPDATE internships SET " . implode(', ', $fields) . " WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute($values);
            }
            
            // Mettre à jour les compétences
            if (isset($data['skills']) && is_array($data['skills'])) {
                $this->clearSkills($id);
                foreach ($data['skills'] as $skill) {
                    $this->addSkill($id, $skill);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // En production, loggez l'erreur au lieu de l'afficher
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un stage
     * @param int $id ID du stage
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $this->db->beginTransaction();
        
        try {
            // Supprimer d'abord les compétences
            $this->clearSkills($id);
            
            // Supprimer les préférences des étudiants
            $query = "DELETE FROM student_preferences WHERE internship_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Supprimer le stage
            $query = "DELETE FROM internships WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // En production, loggez l'erreur au lieu de l'afficher
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Recherche des stages
     * @param string $term Terme de recherche
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des stages correspondants
     */
    public function search($term, $status = null) {
        // Log pour le débogage
        error_log("Internship model search called with term: '$term', status: '$status'");
        
        try {
            // Si le terme est vide, retourner un tableau vide ou limiter à quelques résultats
            if (empty(trim($term))) {
                // Requête pour terme vide
                $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                          FROM internships i
                          JOIN companies c ON i.company_id = c.id";
                
                if ($status) {
                    $query .= " WHERE i.status = :status";
                }
                
                $query .= " LIMIT 10";
                
                $stmt = $this->db->prepare($query);
                
                if ($status) {
                    $stmt->bindValue(':status', $status);
                }
            } else {
                // Recherche avec terme non vide
                $searchTerm = "%" . trim($term) . "%";
                
                // Requête simplifiée sans la jointure problématique
                $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                          FROM internships i
                          JOIN companies c ON i.company_id = c.id
                          WHERE (i.title LIKE :term 
                          OR i.description LIKE :term 
                          OR i.domain LIKE :term
                          OR c.name LIKE :term)";
                
                if ($status) {
                    $query .= " AND i.status = :status";
                }
                
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':term', $searchTerm);
                
                if ($status) {
                    $stmt->bindValue(':status', $status);
                }
            }
            
            // Exécuter la requête
            $stmt->execute();
            $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Search found " . count($internships) . " internships");
            
            // Si aucun résultat et le terme n'est pas vide, essayer une recherche plus large
            if (count($internships) === 0 && !empty(trim($term))) {
                error_log("No results found with specific search, trying broader search");
                $searchTerm = "%" . trim($term) . "%";
                
                $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                          FROM internships i
                          JOIN companies c ON i.company_id = c.id
                          WHERE i.title LIKE :term";
                
                if ($status) {
                    $query .= " AND i.status = :status";
                }
                
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':term', $searchTerm);
                
                if ($status) {
                    $stmt->bindValue(':status', $status);
                }
                
                $stmt->execute();
                $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Broader search found " . count($internships) . " internships");
            }
            
            // Récupérer les compétences pour chaque stage
            foreach ($internships as &$internship) {
                $internship['skills'] = $this->getInternshipSkills($internship['id']);
            }
            
            return $internships;
            
        } catch (Exception $e) {
            // Logguer l'erreur pour le débogage
            error_log("Erreur dans la recherche de stages: " . $e->getMessage());
            // Retourner un tableau vide en cas d'erreur
            return [];
        }
    }

    /**
     * Récupère les stages par domaine
     * @param string $domain Domaine
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des stages
     */
    public function getByDomain($domain, $status = null) {
        $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                  FROM internships i
                  JOIN companies c ON i.company_id = c.id
                  WHERE i.domain = :domain";
                  
        if ($status) {
            $query .= " AND i.status = :status";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':domain', $domain);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les compétences pour chaque stage
        foreach ($internships as &$internship) {
            $internship['skills'] = $this->getInternshipSkills($internship['id']);
        }
        
        return $internships;
    }

    /**
     * Récupère les stages par entreprise
     * @param int $companyId ID de l'entreprise
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des stages
     */
    public function getByCompany($companyId, $status = null) {
        $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo 
                  FROM internships i
                  JOIN companies c ON i.company_id = c.id
                  WHERE i.company_id = :company_id";
                  
        if ($status) {
            $query .= " AND i.status = :status";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':company_id', $companyId);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les compétences pour chaque stage
        foreach ($internships as &$internship) {
            $internship['skills'] = $this->getInternshipSkills($internship['id']);
        }
        
        return $internships;
    }

    /**
     * Compte le nombre de stages par statut
     * @return array Nombre de stages par statut
     */
    public function countByStatus() {
        $query = "SELECT status, COUNT(*) as count FROM internships GROUP BY status";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['status']] = $row['count'];
        }
        
        return $result;
    }

    /**
     * Récupère la liste des domaines de stages
     * @return array Liste des domaines
     */
    public function getDomains() {
        $query = "SELECT DISTINCT domain FROM internships ORDER BY domain";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupère la liste des compétences requises par les stages
     * @return array Liste des compétences
     */
    public function getAllSkills() {
        $query = "SELECT DISTINCT skill_name FROM internship_skills ORDER BY skill_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère les stages disponibles
     * @return array Liste des stages disponibles
     */
    public function getAvailable() {
        $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo
                  FROM internships i
                  JOIN companies c ON i.company_id = c.id
                  WHERE i.status = 'available'
                  AND i.start_date > CURRENT_DATE
                  ORDER BY i.start_date ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les compétences pour chaque stage
        foreach ($internships as &$internship) {
            $internship['skills'] = $this->getInternshipSkills($internship['id']);
        }
        
        return $internships;
    }
    
    /**
     * Récupère les stages disponibles pour un étudiant
     * @param int $studentId ID de l'étudiant
     * @return array Liste des stages disponibles
     */
    public function getAvailableForStudent($studentId) {
        $query = "SELECT i.*, c.name as company_name, c.logo_path as company_logo,
                  (SELECT COUNT(*) FROM student_preferences sp WHERE sp.student_id = :student_id AND sp.internship_id = i.id) as is_preferred
                  FROM internships i
                  JOIN companies c ON i.company_id = c.id
                  WHERE i.status = 'available'
                  AND i.start_date > CURRENT_DATE
                  ORDER BY is_preferred DESC, i.start_date ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les compétences pour chaque stage
        foreach ($internships as &$internship) {
            $internship['skills'] = $this->getInternshipSkills($internship['id']);
            // Convertir is_preferred en booléen
            $internship['is_preferred'] = (bool)$internship['is_preferred'];
        }
        
        return $internships;
    }
}