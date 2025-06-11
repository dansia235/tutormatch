<?php
/**
 * Model Company
 * Gère les opérations liées aux entreprises
 */
class Company {
    /**
     * Instance de PDO pour l'accès à la base de données
     * @var PDO
     */
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de PDO
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Récupère une entreprise par son ID
     * @param int $id ID de l'entreprise
     * @return array|false Les données de l'entreprise ou false si non trouvée
     */
    public function getById($id) {
        $stmt = $this->db->prepare('SELECT * FROM companies WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère toutes les entreprises
     * @return array Liste des entreprises
     */
    public function getAll() {
        $stmt = $this->db->query('SELECT * FROM companies ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Recherche des entreprises par nom
     * @param string $search Terme de recherche
     * @return array Liste des entreprises correspondant à la recherche
     */
    public function search($search) {
        $stmt = $this->db->prepare('SELECT * FROM companies WHERE name LIKE ? ORDER BY name');
        $stmt->execute(['%' . $search . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crée une nouvelle entreprise
     * @param array $data Données de l'entreprise à créer
     * @return int|false ID de la nouvelle entreprise ou false en cas d'échec
     */
    public function create($data) {
        $sql = 'INSERT INTO companies (name, address, city, postal_code, country, industry, website, contact_name, 
                contact_email, contact_phone, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['name'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
            $data['industry'] ?? null,
            $data['website'] ?? null,
            $data['contact_name'] ?? null,
            $data['contact_email'] ?? null,
            $data['contact_phone'] ?? null,
            $data['description'] ?? null
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Met à jour une entreprise existante
     * @param int $id ID de l'entreprise à mettre à jour
     * @param array $data Nouvelles données
     * @return bool Succès de la mise à jour
     */
    public function update($id, $data) {
        $sql = 'UPDATE companies SET 
                name = ?, 
                address = ?, 
                city = ?, 
                postal_code = ?, 
                country = ?, 
                industry = ?, 
                website = ?, 
                contact_name = ?, 
                contact_email = ?, 
                contact_phone = ?, 
                description = ?, 
                updated_at = NOW()
                WHERE id = ?';
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
            $data['industry'] ?? null,
            $data['website'] ?? null,
            $data['contact_name'] ?? null,
            $data['contact_email'] ?? null,
            $data['contact_phone'] ?? null,
            $data['description'] ?? null,
            $id
        ]);
    }
    
    /**
     * Supprime une entreprise
     * @param int $id ID de l'entreprise à supprimer
     * @return bool Succès de la suppression
     */
    public function delete($id) {
        $stmt = $this->db->prepare('DELETE FROM companies WHERE id = ?');
        return $stmt->execute([$id]);
    }
    
    /**
     * Récupère les entreprises qui ont des stages disponibles
     * @return array Liste des entreprises avec stages
     */
    public function getWithInternships() {
        $sql = 'SELECT DISTINCT c.* 
                FROM companies c
                JOIN internships i ON c.id = i.company_id
                WHERE i.status = "available"
                ORDER BY c.name';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compte le nombre d'entreprises
     * @return int Nombre d'entreprises
     */
    public function count() {
        $stmt = $this->db->query('SELECT COUNT(*) FROM companies');
        return (int)$stmt->fetchColumn();
    }
}