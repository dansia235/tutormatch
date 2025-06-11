<?php
/**
 * Modèle pour la gestion des utilisateurs
 */
class User {
    private $db;
    
    // Propriétés de l'utilisateur
    private $id;
    private $username;
    private $email;
    private $firstName;
    private $lastName;
    private $role;
    private $department;
    private $profileImage;
    private $createdAt;
    private $updatedAt;
    private $lastLogin;

    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Authentifie un utilisateur
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @return array|false Données utilisateur si authentification réussie, sinon false
     */
    public function authenticate($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Mise à jour de la date de dernière connexion
            $this->updateLastLogin($user['id']);
            return $user;
        }
        
        return false;
    }

    /**
     * Met à jour la date de dernière connexion
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    /**
     * Récupère un utilisateur par son ID
     * @param int $id ID de l'utilisateur
     * @return array|false Données utilisateur si trouvé, sinon false
     */
    public function getById($id) {
        $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un utilisateur par son nom d'utilisateur
     * @param string $username Nom d'utilisateur
     * @return array|false Données utilisateur si trouvé, sinon false
     */
    public function getByUsername($username) {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les utilisateurs avec filtrage par rôle (optionnel)
     * @param string|array $role Rôle(s) pour filtrer (optionnel)
     * @return array Liste des utilisateurs
     */
    public function getAll($role = null) {
        $query = "SELECT * FROM users";
        
        if ($role) {
            if (is_array($role)) {
                $placeholders = array_map(function($i) { return ":role_$i"; }, array_keys($role));
                $query .= " WHERE role IN (" . implode(', ', $placeholders) . ")";
            } else {
                $query .= " WHERE role = :role";
            }
        }
        
        $query .= " ORDER BY last_name, first_name";
        
        $stmt = $this->db->prepare($query);
        
        if ($role) {
            if (is_array($role)) {
                foreach ($role as $i => $r) {
                    $stmt->bindValue(":role_$i", $r);
                }
            } else {
                $stmt->bindParam(':role', $role);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouvel utilisateur
     * @param array $data Données utilisateur
     * @return int|false ID de l'utilisateur créé, sinon false
     */
    public function create($data) {
        try {
            // Hashage du mot de passe
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (username, password, email, first_name, last_name, role, department, profile_image) 
                      VALUES (:username, :password, :email, :first_name, :last_name, :role, :department, :profile_image)";
            
            $stmt = $this->db->prepare($query);
            
            // Liaison des paramètres
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':department', $data['department']);
            $stmt->bindParam(':profile_image', $data['profile_image']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error in User::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un utilisateur existant
     * @param int $id ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [':id' => $id];
            
            // Construire dynamiquement les champs à mettre à jour
            foreach ($data as $key => $value) {
                if ($key !== 'id' && $key !== 'password') {
                    $fields[] = "$key = :$key";
                    $values[":$key"] = $value;
                }
            }
            
            // Traitement spécial pour le mot de passe
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = :password";
                $values[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($fields)) {
                return false; // Rien à mettre à jour
            }
            
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error in User::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un utilisateur
     * @param int $id ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in User::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recherche des utilisateurs
     * @param string $term Terme de recherche
     * @param string|array $role Rôle(s) pour filtrer (optionnel)
     * @return array Liste des utilisateurs correspondants
     */
    public function search($term, $role = null) {
        try {
            $term = "%$term%";
            
            $query = "SELECT * FROM users 
                      WHERE (username LIKE :term1 
                      OR email LIKE :term2 
                      OR first_name LIKE :term3 
                      OR last_name LIKE :term4
                      OR CONCAT(first_name, ' ', last_name) LIKE :term5)";
            
            if ($role) {
                if (is_array($role)) {
                    $placeholders = array_map(function($i) { return ":role_$i"; }, array_keys($role));
                    $query .= " AND role IN (" . implode(', ', $placeholders) . ")";
                } else {
                    $query .= " AND role = :role";
                }
            }
            
            $query .= " ORDER BY last_name, first_name";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':term1', $term);
            $stmt->bindParam(':term2', $term);
            $stmt->bindParam(':term3', $term);
            $stmt->bindParam(':term4', $term);
            $stmt->bindParam(':term5', $term);
            
            if ($role) {
                if (is_array($role)) {
                    foreach ($role as $i => $r) {
                        $stmt->bindValue(":role_$i", $r);
                    }
                } else {
                    $stmt->bindParam(':role', $role);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in User::search: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un nom d'utilisateur existe déjà
     * @param string $username Nom d'utilisateur
     * @param int $excludeId ID utilisateur à exclure (optionnel, pour les mises à jour)
     * @return bool True si le nom d'utilisateur existe, sinon false
     */
    public function usernameExists($username, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM users WHERE username = :username";
        
        if ($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        
        if ($excludeId) {
            $stmt->bindParam(':id', $excludeId);
        }
        
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Vérifie si un email existe déjà
     * @param string $email Email
     * @param int $excludeId ID utilisateur à exclure (optionnel, pour les mises à jour)
     * @return bool True si l'email existe, sinon false
     */
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        
        if ($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        
        if ($excludeId) {
            $stmt->bindParam(':id', $excludeId);
        }
        
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }
    
    /**
     * Récupère un utilisateur par son email
     * @param string $email Email
     * @return array|false Données utilisateur si trouvé, sinon false
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre d'utilisateurs par rôle
     * @return array Nombre d'utilisateurs par rôle
     */
    public function countByRole() {
        $query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['role']] = $row['count'];
        }
        
        return $result;
    }
    
    /**
     * Récupère les utilisateurs par rôle(s)
     * @param string|array $role Rôle(s) des utilisateurs à récupérer
     * @return array Liste des utilisateurs du/des rôle(s) spécifié(s)
     */
    public function getByRole($role) {
        return $this->getAll($role);
    }

    /**
     * Récupère les utilisateurs par rôle(s) - Alias pour compatibilité
     * @param string|array $roles Rôle(s) des utilisateurs à récupérer
     * @return array Liste des utilisateurs du/des rôle(s) spécifié(s)
     */
    public function getUsersByRole($roles) {
        return $this->getByRole($roles);
    }
}