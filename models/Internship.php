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
            // Vérifier si $status est un tableau ou une chaîne
            if (is_array($status)) {
                // Si c'est un tableau d'options, on vérifie s'il y a un statut spécifié
                if (isset($status['status']) && !empty($status['status'])) {
                    $query .= " WHERE i.status = :status";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':status', $status['status']);
                } else {
                    $stmt = $this->db->prepare($query);
                }
            } else {
                // Si c'est une chaîne, on l'utilise directement
                $query .= " WHERE i.status = :status";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':status', $status);
            }
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
        // S'assurer qu'il n'y a pas de transaction déjà active
        $transactionStartedHere = false;
        
        try {
            // Vérifier si une transaction est déjà active
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $transactionStartedHere = true;
            }
            
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
            
            // Committer uniquement si nous avons démarré la transaction
            if ($transactionStartedHere) {
                $this->db->commit();
            }
            
            return true;
            
        } catch (Exception $e) {
            // Rollback uniquement si nous avons démarré la transaction
            if ($transactionStartedHere && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            // En production, loggez l'erreur au lieu de l'afficher
            error_log("Erreur lors de la mise à jour d'un stage: " . $e->getMessage());
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
     * @param array $filters Filtres additionnels (optionnel)
     * @param int $limit Nombre de résultats à retourner (optionnel, défaut 20)
     * @param int $offset Décalage pour la pagination (optionnel, défaut 0)
     * @return array Liste des stages correspondants
     */
    public function search($term, $status = null, $filters = [], $limit = 20, $offset = 0) {
        // Log pour le débogage
        error_log("Internship model search called with term: '$term', status: '$status'");
        
        try {
            // Initialisation des conditions et paramètres
            $conditions = [];
            $params = [];
            $joinSkills = false;
            
            // Base de la requête
            $baseQuery = "SELECT DISTINCT i.*, c.name as company_name, c.logo_path as company_logo 
                          FROM internships i
                          JOIN companies c ON i.company_id = c.id";
            
            // Ajouter la condition de statut si fournie
            if ($status) {
                $conditions[] = "i.status = :status";
                $params[':status'] = $status;
            }
            
            // Ajouter la recherche par terme si fourni
            if (!empty(trim($term))) {
                $searchTerm = trim($term);
                
                // Priorité aux recherches par préfixe si le terme est court (1-2 caractères)
                if (strlen($searchTerm) <= 2) {
                    // Pour les recherches courtes, chercher uniquement par préfixe dans le titre
                    $titleSearchTerm = $searchTerm . "%"; // Recherche par préfixe pour les titres
                    
                    $conditions[] = "i.title LIKE :title_term";
                    $params[':title_term'] = $titleSearchTerm;
                    
                    // Log de la recherche par préfixe uniquement
                    error_log("Recherche par titre avec préfixe uniquement: '$titleSearchTerm'");
                } else {
                    // Pour les recherches plus longues, recherche complète
                    $titleSearchTerm = $searchTerm . "%"; // Recherche par préfixe pour les titres
                    $generalSearchTerm = "%" . $searchTerm . "%"; // Recherche générale pour les autres champs
                    
                    $conditions[] = "(i.title LIKE :title_term 
                                   OR i.description LIKE :term2 
                                   OR i.requirements LIKE :term3
                                   OR i.domain LIKE :term4
                                   OR i.location LIKE :term5
                                   OR c.name LIKE :term6
                                   OR EXISTS (SELECT 1 FROM internship_skills is2 WHERE is2.internship_id = i.id AND is2.skill_name LIKE :term7))";
                    
                    $params[':title_term'] = $titleSearchTerm; // Recherche par préfixe pour les titres
                    $params[':term2'] = $generalSearchTerm;
                    $params[':term3'] = $generalSearchTerm;
                    $params[':term4'] = $generalSearchTerm;
                    $params[':term5'] = $generalSearchTerm;
                    $params[':term6'] = $generalSearchTerm;
                    $params[':term7'] = $generalSearchTerm;
                    
                    // Log de la recherche complète
                    error_log("Recherche complète - titre: '$titleSearchTerm', générale: '$generalSearchTerm'");
                }
            }
            
            // Traiter les filtres additionnels
            if (!empty($filters)) {
                // Filtrer par domaine
                if (isset($filters['domain']) && !empty($filters['domain'])) {
                    if (is_array($filters['domain'])) {
                        $domainPlaceholders = [];
                        foreach ($filters['domain'] as $index => $domain) {
                            $placeholder = ":domain{$index}";
                            $domainPlaceholders[] = $placeholder;
                            $params[$placeholder] = $domain;
                        }
                        $conditions[] = "i.domain IN (" . implode(", ", $domainPlaceholders) . ")";
                    } else {
                        $conditions[] = "i.domain = :domain";
                        $params[':domain'] = $filters['domain'];
                    }
                }
                
                // Filtrer par localisation
                if (isset($filters['location']) && !empty($filters['location'])) {
                    $conditions[] = "i.location LIKE :location";
                    $params[':location'] = "%" . $filters['location'] . "%";
                }
                
                // Filtrer par mode de travail
                if (isset($filters['work_mode']) && !empty($filters['work_mode'])) {
                    $conditions[] = "i.work_mode = :work_mode";
                    $params[':work_mode'] = $filters['work_mode'];
                }
                
                // Filtrer par compétences
                if (isset($filters['skills']) && !empty($filters['skills']) && is_array($filters['skills'])) {
                    $joinSkills = true;
                    $skillCount = count($filters['skills']);
                    
                    // Pour chaque compétence, ajouter une condition
                    for ($i = 0; $i < $skillCount; $i++) {
                        $skillParam = ":skill{$i}";
                        $params[$skillParam] = $filters['skills'][$i];
                        
                        // Utiliser EXISTS pour chaque compétence
                        $conditions[] = "EXISTS (SELECT 1 FROM internship_skills is{$i} 
                                     WHERE is{$i}.internship_id = i.id AND is{$i}.skill_name = {$skillParam})";
                    }
                }
                
                // Filtrer par date de début
                if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                    if (isset($filters['start_date']['from']) && !empty($filters['start_date']['from'])) {
                        $conditions[] = "i.start_date >= :start_date_from";
                        $params[':start_date_from'] = $filters['start_date']['from'];
                    }
                    if (isset($filters['start_date']['to']) && !empty($filters['start_date']['to'])) {
                        $conditions[] = "i.start_date <= :start_date_to";
                        $params[':start_date_to'] = $filters['start_date']['to'];
                    }
                }
                
                // Filtrer par entreprise
                if (isset($filters['company_id']) && !empty($filters['company_id'])) {
                    $conditions[] = "i.company_id = :company_id";
                    $params[':company_id'] = $filters['company_id'];
                }
            }
            
            // Construire la requête complète
            $query = $baseQuery;
            
            // Ajouter les conditions s'il y en a
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            // Tri des résultats
            if (!empty(trim($term)) && strlen(trim($term)) <= 2) {
                // Pour les recherches par préfixe, trier par titre pour obtenir un résultat plus intuitif
                $query .= " ORDER BY i.title ASC";
            } else {
                // Sinon, tri par date de début
                $query .= " ORDER BY i.start_date DESC";
            }
            
            // Ajouter la limitation pour la pagination
            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            // Préparer et exécuter la requête
            $stmt = $this->db->prepare($query);
            
            // Lier les paramètres avec leurs types appropriés
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Search found " . count($internships) . " internships");
            
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
     * Compte le nombre total de stages correspondant à une recherche
     * @param string $term Terme de recherche
     * @param string $status Statut pour filtrer (optionnel)
     * @param array $filters Filtres additionnels (optionnel)
     * @return int Nombre total de stages
     */
    public function countSearch($term, $status = null, $filters = []) {
        try {
            // Initialisation des conditions et paramètres
            $conditions = [];
            $params = [];
            
            // Base de la requête
            $baseQuery = "SELECT COUNT(DISTINCT i.id) as total 
                          FROM internships i
                          JOIN companies c ON i.company_id = c.id";
            
            // Ajouter la condition de statut si fournie
            if ($status) {
                $conditions[] = "i.status = :status";
                $params[':status'] = $status;
            }
            
            // Ajouter la recherche par terme si fourni
            if (!empty(trim($term))) {
                $searchTerm = trim($term);
                
                // Priorité aux recherches par préfixe si le terme est court (1-2 caractères)
                if (strlen($searchTerm) <= 2) {
                    // Pour les recherches courtes, chercher uniquement par préfixe dans le titre
                    $titleSearchTerm = $searchTerm . "%"; // Recherche par préfixe pour les titres
                    
                    $conditions[] = "i.title LIKE :title_term";
                    $params[':title_term'] = $titleSearchTerm;
                } else {
                    // Pour les recherches plus longues, recherche complète
                    $titleSearchTerm = $searchTerm . "%"; // Recherche par préfixe pour les titres
                    $generalSearchTerm = "%" . $searchTerm . "%"; // Recherche générale pour les autres champs
                    
                    $conditions[] = "(i.title LIKE :title_term 
                                   OR i.description LIKE :term2 
                                   OR i.requirements LIKE :term3
                                   OR i.domain LIKE :term4
                                   OR i.location LIKE :term5
                                   OR c.name LIKE :term6
                                   OR EXISTS (SELECT 1 FROM internship_skills is2 WHERE is2.internship_id = i.id AND is2.skill_name LIKE :term7))";
                    
                    $params[':title_term'] = $titleSearchTerm;
                    $params[':term2'] = $generalSearchTerm;
                    $params[':term3'] = $generalSearchTerm;
                    $params[':term4'] = $generalSearchTerm;
                    $params[':term5'] = $generalSearchTerm;
                    $params[':term6'] = $generalSearchTerm;
                    $params[':term7'] = $generalSearchTerm;
                }
            }
            
            // Traiter les filtres additionnels
            if (!empty($filters)) {
                // Filtrer par domaine
                if (isset($filters['domain']) && !empty($filters['domain'])) {
                    if (is_array($filters['domain'])) {
                        $domainPlaceholders = [];
                        foreach ($filters['domain'] as $index => $domain) {
                            $placeholder = ":domain{$index}";
                            $domainPlaceholders[] = $placeholder;
                            $params[$placeholder] = $domain;
                        }
                        $conditions[] = "i.domain IN (" . implode(", ", $domainPlaceholders) . ")";
                    } else {
                        $conditions[] = "i.domain = :domain";
                        $params[':domain'] = $filters['domain'];
                    }
                }
                
                // Filtrer par localisation
                if (isset($filters['location']) && !empty($filters['location'])) {
                    $conditions[] = "i.location LIKE :location";
                    $params[':location'] = "%" . $filters['location'] . "%";
                }
                
                // Filtrer par mode de travail
                if (isset($filters['work_mode']) && !empty($filters['work_mode'])) {
                    $conditions[] = "i.work_mode = :work_mode";
                    $params[':work_mode'] = $filters['work_mode'];
                }
                
                // Filtrer par compétences
                if (isset($filters['skills']) && !empty($filters['skills']) && is_array($filters['skills'])) {
                    $skillCount = count($filters['skills']);
                    
                    // Pour chaque compétence, ajouter une condition
                    for ($i = 0; $i < $skillCount; $i++) {
                        $skillParam = ":skill{$i}";
                        $params[$skillParam] = $filters['skills'][$i];
                        
                        // Utiliser EXISTS pour chaque compétence
                        $conditions[] = "EXISTS (SELECT 1 FROM internship_skills is{$i} 
                                     WHERE is{$i}.internship_id = i.id AND is{$i}.skill_name = {$skillParam})";
                    }
                }
                
                // Filtrer par date de début
                if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                    if (isset($filters['start_date']['from']) && !empty($filters['start_date']['from'])) {
                        $conditions[] = "i.start_date >= :start_date_from";
                        $params[':start_date_from'] = $filters['start_date']['from'];
                    }
                    if (isset($filters['start_date']['to']) && !empty($filters['start_date']['to'])) {
                        $conditions[] = "i.start_date <= :start_date_to";
                        $params[':start_date_to'] = $filters['start_date']['to'];
                    }
                }
                
                // Filtrer par entreprise
                if (isset($filters['company_id']) && !empty($filters['company_id'])) {
                    $conditions[] = "i.company_id = :company_id";
                    $params[':company_id'] = $filters['company_id'];
                }
            }
            
            // Construire la requête complète
            $query = $baseQuery;
            
            // Ajouter les conditions s'il y en a
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            // Préparer et exécuter la requête
            $stmt = $this->db->prepare($query);
            
            // Lier les paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?? 0;
            
        } catch (Exception $e) {
            // Logguer l'erreur pour le débogage
            error_log("Erreur dans le comptage des stages: " . $e->getMessage());
            // Retourner zéro en cas d'erreur
            return 0;
        }
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
    
    /**
     * Compte le nombre total de stages selon les filtres
     * @param mixed $options Options de filtrage (chaîne de statut ou tableau d'options)
     * @return int Nombre total de stages
     */
    public function countAll($options = null) {
        $query = "SELECT COUNT(*) FROM internships i JOIN companies c ON i.company_id = c.id";
        $where = [];
        $params = [];
        
        // Traiter les options de filtrage
        if ($options) {
            if (is_array($options)) {
                // Filtre par statut
                if (isset($options['status']) && !empty($options['status'])) {
                    $where[] = "i.status = :status";
                    $params[':status'] = $options['status'];
                }
                
                // Filtre par domaine
                if (isset($options['domain']) && !empty($options['domain'])) {
                    $where[] = "i.domain = :domain";
                    $params[':domain'] = $options['domain'];
                }
                
                // Filtre par entreprise
                if (isset($options['company_id']) && !empty($options['company_id'])) {
                    $where[] = "i.company_id = :company_id";
                    $params[':company_id'] = $options['company_id'];
                }
                
                // Filtre par mode de travail
                if (isset($options['work_mode']) && !empty($options['work_mode'])) {
                    $where[] = "i.work_mode = :work_mode";
                    $params[':work_mode'] = $options['work_mode'];
                }
                
                // Filtre par recherche
                if (isset($options['search']) && !empty($options['search'])) {
                    $search = '%' . $options['search'] . '%';
                    $where[] = "(i.title LIKE :search OR i.description LIKE :search OR c.name LIKE :search)";
                    $params[':search'] = $search;
                }
            } else {
                // Si $options est une chaîne, on l'interprète comme un statut
                $where[] = "i.status = :status";
                $params[':status'] = $options;
            }
        }
        
        // Ajouter les conditions WHERE si nécessaire
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $this->db->prepare($query);
        
        // Lier les paramètres
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}