<?php
/**
 * Modèle pour la gestion des réunions
 */
class Meeting {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère une réunion par son ID
     * @param int $id ID de la réunion
     * @return array|false Données de la réunion si trouvée, sinon false
     */
    public function getById($id) {
        $query = "SELECT m.*, 
                  a.id as assignment_id, a.status as assignment_status,
                  s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name
                  FROM meetings m
                  LEFT JOIN assignments a ON m.assignment_id = a.id
                  LEFT JOIN students s ON a.student_id = s.id
                  LEFT JOIN users u_s ON s.user_id = u_s.id
                  LEFT JOIN teachers t ON a.teacher_id = t.id
                  LEFT JOIN users u_t ON t.user_id = u_t.id
                  WHERE m.id = :id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les réunions
     * @param array $options Options de filtre
     * @return array Liste des réunions
     */
    public function getAll($options = []) {
        $query = "SELECT m.*, 
                  a.id as assignment_id, a.status as assignment_status,
                  s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name
                  FROM meetings m
                  LEFT JOIN assignments a ON m.assignment_id = a.id
                  LEFT JOIN students s ON a.student_id = s.id
                  LEFT JOIN users u_s ON s.user_id = u_s.id
                  LEFT JOIN teachers t ON a.teacher_id = t.id
                  LEFT JOIN users u_t ON t.user_id = u_t.id
                  WHERE 1=1";
        
        $params = [];
        
        // Filtre par affectation
        if (isset($options['assignment_id'])) {
            $query .= " AND m.assignment_id = :assignment_id";
            $params[':assignment_id'] = $options['assignment_id'];
        }
        
        // Filtre par plusieurs affectations
        if (isset($options['assignment_ids']) && is_array($options['assignment_ids']) && !empty($options['assignment_ids'])) {
            $placeholders = implode(',', array_map(function($i) { return ':assignment_id'.$i; }, array_keys($options['assignment_ids'])));
            $query .= " AND m.assignment_id IN ($placeholders)";
            
            foreach ($options['assignment_ids'] as $i => $id) {
                $params[':assignment_id'.$i] = $id;
            }
        }
        
        // Filtre par status
        if (isset($options['status'])) {
            $query .= " AND m.status = :status";
            $params[':status'] = $options['status'];
        }
        
        // Filtre par date
        if (isset($options['from_date'])) {
            $query .= " AND m.date >= :from_date";
            $params[':from_date'] = $options['from_date'];
        }
        
        if (isset($options['to_date'])) {
            $query .= " AND m.date <= :to_date";
            $params[':to_date'] = $options['to_date'];
        }
        
        // Filtre par participant
        if (isset($options['participant_id'])) {
            $query .= " AND EXISTS (SELECT 1 FROM meeting_participants mp WHERE mp.meeting_id = m.id AND mp.user_id = :participant_id)";
            $params[':participant_id'] = $options['participant_id'];
        }
        
        // Filtre par étudiant
        if (isset($options['student_id'])) {
            $query .= " AND s.id = :student_id";
            $params[':student_id'] = $options['student_id'];
        }
        
        // Filtre par tuteur
        if (isset($options['teacher_id'])) {
            $query .= " AND t.id = :teacher_id";
            $params[':teacher_id'] = $options['teacher_id'];
        }
        
        // Tri par date et heure
        $query .= " ORDER BY m.date DESC, m.start_time DESC";
        
        // Pagination
        if (isset($options['page']) && isset($options['limit'])) {
            $offset = ($options['page'] - 1) * $options['limit'];
            $query .= " LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $options['limit'];
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            if (strpos($key, 'limit') !== false || strpos($key, 'offset') !== false) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre total de réunions
     * @param array $options Options de filtre
     * @return int Nombre total de réunions
     */
    public function countAll($options = []) {
        $query = "SELECT COUNT(*) FROM meetings m
                  LEFT JOIN assignments a ON m.assignment_id = a.id
                  LEFT JOIN students s ON a.student_id = s.id
                  LEFT JOIN teachers t ON a.teacher_id = t.id
                  WHERE 1=1";
        
        $params = [];
        
        // Filtre par affectation
        if (isset($options['assignment_id'])) {
            $query .= " AND m.assignment_id = :assignment_id";
            $params[':assignment_id'] = $options['assignment_id'];
        }
        
        // Filtre par plusieurs affectations
        if (isset($options['assignment_ids']) && is_array($options['assignment_ids']) && !empty($options['assignment_ids'])) {
            $placeholders = implode(',', array_map(function($i) { return ':assignment_id'.$i; }, array_keys($options['assignment_ids'])));
            $query .= " AND m.assignment_id IN ($placeholders)";
            
            foreach ($options['assignment_ids'] as $i => $id) {
                $params[':assignment_id'.$i] = $id;
            }
        }
        
        // Filtre par status
        if (isset($options['status'])) {
            $query .= " AND m.status = :status";
            $params[':status'] = $options['status'];
        }
        
        // Filtre par date
        if (isset($options['from_date'])) {
            $query .= " AND m.date >= :from_date";
            $params[':from_date'] = $options['from_date'];
        }
        
        if (isset($options['to_date'])) {
            $query .= " AND m.date <= :to_date";
            $params[':to_date'] = $options['to_date'];
        }
        
        // Filtre par participant
        if (isset($options['participant_id'])) {
            $query .= " AND EXISTS (SELECT 1 FROM meeting_participants mp WHERE mp.meeting_id = m.id AND mp.user_id = :participant_id)";
            $params[':participant_id'] = $options['participant_id'];
        }
        
        // Filtre par étudiant
        if (isset($options['student_id'])) {
            $query .= " AND s.id = :student_id";
            $params[':student_id'] = $options['student_id'];
        }
        
        // Filtre par tuteur
        if (isset($options['teacher_id'])) {
            $query .= " AND t.id = :teacher_id";
            $params[':teacher_id'] = $options['teacher_id'];
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Crée une nouvelle réunion
     * @param array $data Données de la réunion
     * @return int|false ID de la réunion créée, sinon false
     * @throws Exception Si des données requises sont manquantes ou invalides
     */
    public function create($data) {
        try {
            // Vérifier les champs obligatoires
            if (empty($data['title'])) {
                throw new Exception("Le titre de la réunion est requis");
            }
            
            // Gérer la date et l'heure
            if (isset($data['meeting_date']) && !isset($data['date_time'])) {
                // Convert meeting_date and meeting_time to date_time
                if (isset($data['meeting_time'])) {
                    $data['date_time'] = $data['meeting_date'] . ' ' . $data['meeting_time'];
                } else {
                    $data['date_time'] = $data['meeting_date'];
                }
            }
            
            // Vérifier que date_time est défini
            if (empty($data['date_time'])) {
                throw new Exception("La date et l'heure de la réunion sont requises");
            }
            
            // Map meeting_type to a field in description if needed
            $description = $data['description'] ?? '';
            if (isset($data['meeting_type']) && !empty($data['meeting_type'])) {
                $description = 'Type: ' . $data['meeting_type'] . "\n\n" . $description;
            }
            
            // Map meeting_url or meeting_link
            $meetingLink = $data['meeting_link'] ?? $data['meeting_url'] ?? null;
            
            // Déterminer l'ID de l'organisateur
            $organizerId = null;
            if (isset($data['organizer_id']) && !empty($data['organizer_id'])) {
                $organizerId = $data['organizer_id'];
            } elseif (isset($data['created_by']) && !empty($data['created_by'])) {
                $organizerId = $data['created_by'];
            } else {
                throw new Exception("L'ID de l'organisateur est requis");
            }
            
            // Autres variables
            $assignmentId = $data['assignment_id'] ?? null;
            $createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
            $duration = $data['duration'] ?? 60; // Default to 60 minutes if not specified
            $location = $data['location'] ?? 'Non spécifié';
            $status = $data['status'] ?? 'scheduled';
            
            $query = "INSERT INTO meetings (title, description, date_time, duration, location, meeting_link, organizer_id, status, assignment_id, created_at) 
                      VALUES (:title, :description, :date_time, :duration, :location, :meeting_link, :organizer_id, :status, :assignment_id, :created_at)";
                      
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':date_time', $data['date_time']);
            $stmt->bindParam(':duration', $duration);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':meeting_link', $meetingLink);
            $stmt->bindParam(':organizer_id', $organizerId);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':assignment_id', $assignmentId);
            $stmt->bindParam(':created_at', $createdAt);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la création de la réunion: " . $e->getMessage());
        }
    }

    /**
     * Met à jour une réunion existante
     * @param int $id ID de la réunion
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        $fields = [];
        $values = [':id' => $id];
        
        // Construire dynamiquement les champs à mettre à jour
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false; // Rien à mettre à jour
        }
        
        $query = "UPDATE meetings SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime une réunion
     * @param int $id ID de la réunion
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        // Supprimer d'abord les participants
        $this->removeAllParticipants($id);
        
        // Puis supprimer la réunion
        $query = "DELETE FROM meetings WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Récupère les participants d'une réunion
     * @param int $meetingId ID de la réunion
     * @return array Liste des participants
     */
    public function getParticipants($meetingId) {
        $query = "SELECT mp.*, u.first_name, u.last_name, u.email, u.role
                  FROM meeting_participants mp
                  JOIN users u ON mp.user_id = u.id
                  WHERE mp.meeting_id = :meeting_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':meeting_id', $meetingId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un participant à une réunion
     * @param array $data Données du participant
     * @return int|false ID du participant ajouté, sinon false
     */
    public function addParticipant($data) {
        $query = "INSERT INTO meeting_participants (meeting_id, user_id, status, is_organizer, joined_at) 
                  VALUES (:meeting_id, :user_id, :status, :is_organizer, :joined_at)";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':meeting_id', $data['meeting_id']);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':is_organizer', $data['is_organizer']);
        $stmt->bindParam(':joined_at', $data['joined_at']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Met à jour le statut d'un participant
     * @param int $participantId ID du participant
     * @param string $status Nouveau statut
     * @return bool Succès de l'opération
     */
    public function updateParticipantStatus($participantId, $status) {
        $query = "UPDATE meeting_participants SET status = :status, joined_at = :joined_at
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        $joinedAt = ($status === 'accepted') ? date('Y-m-d H:i:s') : null;
        
        $stmt->bindParam(':id', $participantId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':joined_at', $joinedAt);
        
        return $stmt->execute();
    }

    /**
     * Supprime un participant d'une réunion
     * @param int $participantId ID du participant
     * @return bool Succès de l'opération
     */
    public function removeParticipant($participantId) {
        $query = "DELETE FROM meeting_participants WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $participantId);
        
        return $stmt->execute();
    }

    /**
     * Supprime tous les participants d'une réunion
     * @param int $meetingId ID de la réunion
     * @return bool Succès de l'opération
     */
    public function removeAllParticipants($meetingId) {
        $query = "DELETE FROM meeting_participants WHERE meeting_id = :meeting_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':meeting_id', $meetingId);
        
        return $stmt->execute();
    }

    /**
     * Récupère les réunions à venir pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre de réunions à récupérer
     * @return array Liste des réunions
     */
    public function getUpcomingByUser($userId, $limit = 5) {
        $today = date('Y-m-d');
        $now = date('H:i:s');
        
        $query = "SELECT m.*, mp.status as participant_status, mp.is_organizer,
                  a.id as assignment_id, a.status as assignment_status,
                  s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name
                  FROM meetings m
                  JOIN meeting_participants mp ON m.id = mp.meeting_id
                  LEFT JOIN assignments a ON m.assignment_id = a.id
                  LEFT JOIN students s ON a.student_id = s.id
                  LEFT JOIN users u_s ON s.user_id = u_s.id
                  LEFT JOIN teachers t ON a.teacher_id = t.id
                  LEFT JOIN users u_t ON t.user_id = u_t.id
                  WHERE mp.user_id = :user_id
                  AND ((m.date = :today AND m.start_time > :now) OR m.date > :today)
                  AND m.status = 'scheduled'
                  ORDER BY m.date ASC, m.start_time ASC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':today', $today);
        $stmt->bindParam(':now', $now);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les réunions d'un jour spécifique pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param string $date Date au format YYYY-MM-DD
     * @return array Liste des réunions
     */
    public function getByUserAndDate($userId, $date) {
        $query = "SELECT m.*, mp.status as participant_status, mp.is_organizer,
                  a.id as assignment_id, a.status as assignment_status,
                  s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name
                  FROM meetings m
                  JOIN meeting_participants mp ON m.id = mp.meeting_id
                  LEFT JOIN assignments a ON m.assignment_id = a.id
                  LEFT JOIN students s ON a.student_id = s.id
                  LEFT JOIN users u_s ON s.user_id = u_s.id
                  LEFT JOIN teachers t ON a.teacher_id = t.id
                  LEFT JOIN users u_t ON t.user_id = u_t.id
                  WHERE mp.user_id = :user_id
                  AND m.date = :date
                  ORDER BY m.start_time ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie s'il y a des conflits d'horaire pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param string $date Date de la réunion
     * @param string $startTime Heure de début
     * @param string $endTime Heure de fin
     * @param int $excludeMeetingId ID de la réunion à exclure (pour les mises à jour)
     * @return bool True s'il y a des conflits, sinon false
     */
    public function hasScheduleConflicts($userId, $date, $startTime, $endTime, $excludeMeetingId = null) {
        $query = "SELECT COUNT(*) FROM meetings m
                  JOIN meeting_participants mp ON m.id = mp.meeting_id
                  WHERE mp.user_id = :user_id
                  AND m.date = :date
                  AND (
                      (m.start_time <= :start_time AND m.end_time > :start_time) OR
                      (m.start_time < :end_time AND m.end_time >= :end_time) OR
                      (m.start_time >= :start_time AND m.end_time <= :end_time)
                  )";
        
        if ($excludeMeetingId) {
            $query .= " AND m.id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        
        if ($excludeMeetingId) {
            $stmt->bindParam(':exclude_id', $excludeMeetingId);
        }
        
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Récupère les réunions d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @param array $options Options de filtre (ex: status, date)
     * @return array Liste des réunions
     */
    public function getByStudentId($studentId, $options = []) {
        // Get student's user ID
        $studentQuery = "SELECT user_id FROM students WHERE id = :student_id";
        $stmt = $this->db->prepare($studentQuery);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            return [];
        }
        
        $userId = $student['user_id'];
        
        // Get meetings where the student is a participant
        $query = "SELECT m.*, 
                  mp.status as participant_status,
                  u.first_name as organizer_first_name, u.last_name as organizer_last_name,
                  CONCAT(u.first_name, ' ', u.last_name) as tutor_name,
                  DATE(m.date_time) as meeting_date,
                  TIME(m.date_time) as meeting_time
                  FROM meetings m
                  JOIN meeting_participants mp ON m.id = mp.meeting_id
                  JOIN users u ON m.organizer_id = u.id
                  WHERE mp.user_id = :user_id";
        
        // Ajouter des filtres si nécessaire
        if (isset($options['status'])) {
            $query .= " AND m.status = :status";
        }
        
        if (isset($options['from_date'])) {
            $query .= " AND DATE(m.date_time) >= :from_date";
        }
        
        if (isset($options['to_date'])) {
            $query .= " AND DATE(m.date_time) <= :to_date";
        }
        
        // Trier par date et heure
        $query .= " ORDER BY m.date_time DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if (isset($options['status'])) {
            $stmt->bindParam(':status', $options['status']);
        }
        
        if (isset($options['from_date'])) {
            $stmt->bindParam(':from_date', $options['from_date']);
        }
        
        if (isset($options['to_date'])) {
            $stmt->bindParam(':to_date', $options['to_date']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les réunions par mois pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param int $year Année
     * @param int $month Mois
     * @return array Réunions groupées par jour
     */
    public function getMonthCalendar($userId, $year, $month) {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $query = "SELECT m.*, mp.status as participant_status, mp.is_organizer,
                  DAY(m.date) as day_of_month
                  FROM meetings m
                  JOIN meeting_participants mp ON m.id = mp.meeting_id
                  WHERE mp.user_id = :user_id
                  AND m.date BETWEEN :start_date AND :end_date
                  ORDER BY m.date ASC, m.start_time ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les réunions par jour
        $calendar = [];
        foreach ($meetings as $meeting) {
            $day = $meeting['day_of_month'];
            if (!isset($calendar[$day])) {
                $calendar[$day] = [];
            }
            $calendar[$day][] = $meeting;
        }
        
        return $calendar;
    }
    
    /**
     * Récupère les prochaines réunions pour une affectation
     * @param int $assignmentId ID de l'affectation
     * @param int $limit Nombre maximum de réunions à récupérer (par défaut 5)
     * @return array Liste des réunions à venir
     */
    public function getUpcomingByAssignmentId($assignmentId, $limit = 5) {
        $today = date('Y-m-d');
        
        // Récupérer les IDs des utilisateurs associés à cette affectation (étudiant et enseignant)
        $query = "SELECT s.user_id as student_user_id, t.user_id as teacher_user_id
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  WHERE a.id = :assignment_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId);
        $stmt->execute();
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assignment) {
            return [];
        }
        
        // Récupérer les réunions où l'étudiant ou l'enseignant participe
        $query = "SELECT m.*, 
                  u_org.first_name as organizer_first_name, u_org.last_name as organizer_last_name,
                  :assignment_id as assignment_id
                  FROM meetings m
                  JOIN meeting_participants mp1 ON m.id = mp1.meeting_id
                  JOIN meeting_participants mp2 ON m.id = mp2.meeting_id
                  JOIN users u_org ON m.organizer_id = u_org.id
                  WHERE mp1.user_id = :student_user_id
                  AND mp2.user_id = :teacher_user_id
                  AND DATE(m.date_time) >= :today
                  AND m.status = 'scheduled'
                  ORDER BY m.date_time ASC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId);
        $stmt->bindParam(':student_user_id', $assignment['student_user_id']);
        $stmt->bindParam(':teacher_user_id', $assignment['teacher_user_id']);
        $stmt->bindParam(':today', $today);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les données pour correspondre à ce qui est attendu
        foreach ($meetings as &$meeting) {
            // Extraire la date et l'heure de date_time
            $dateTime = new DateTime($meeting['date_time']);
            $meeting['date'] = $dateTime->format('Y-m-d');
            $meeting['start_time'] = $dateTime->format('H:i:s');
            
            // Calculer l'heure de fin en fonction de la durée
            $endDateTime = clone $dateTime;
            $endDateTime->add(new DateInterval('PT' . $meeting['duration'] . 'M'));
            $meeting['end_time'] = $endDateTime->format('H:i:s');
            
            // Ajouter d'autres champs qui pourraient être attendus
            $meeting['location'] = $meeting['location'] ?? '';
            $meeting['meeting_link'] = $meeting['meeting_link'] ?? '';
        }
        
        return $meetings;
    }


/**
 * Met à jour le statut d'une réunion
 * @param int $id ID de la réunion
 * @param string $status Nouveau statut
 * @return bool Succès de l'opération
 */
public function updateStatus($id, $status) {
    // Vérifier si la colonne updated_at existe dans la table
    try {
        $checkQuery = "SHOW COLUMNS FROM meetings LIKE 'updated_at'";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute();
        $hasUpdatedAt = $checkStmt->rowCount() > 0;
        
        if ($hasUpdatedAt) {
            $query = "UPDATE meetings SET status = :status, updated_at = :updated_at WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            $updatedAt = date('Y-m-d H:i:s');
            $stmt->bindParam(':updated_at', $updatedAt);
        } else {
            $query = "UPDATE meetings SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        // Fallback simple en cas d'erreur
        $query = "UPDATE meetings SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
}

/**
 * Marque une réunion comme terminée et enregistre les informations associées
 * @param int $id ID de la réunion
 * @param int $studentAttended 1 si l'étudiant était présent, 0 sinon
 * @param string $notes Notes de la réunion
 * @return bool Succès de l'opération
 */
public function complete($id, $studentAttended, $notes) {
    // Vérifier si la colonne updated_at existe dans la table
    try {
        $checkQuery = "SHOW COLUMNS FROM meetings LIKE 'updated_at'";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute();
        $hasUpdatedAt = $checkStmt->rowCount() > 0;
        
        $now = date('Y-m-d H:i:s');
        $status = 'completed';
        
        if ($hasUpdatedAt) {
            $query = "UPDATE meetings SET 
                        status = :status, 
                        student_attended = :student_attended, 
                        notes = :notes, 
                        completed_at = :completed_at,
                        updated_at = :updated_at 
                      WHERE id = :id";
                      
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':updated_at', $now);
        } else {
            $query = "UPDATE meetings SET 
                        status = :status, 
                        student_attended = :student_attended, 
                        notes = :notes, 
                        completed_at = :completed_at
                      WHERE id = :id";
                      
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':student_attended', $studentAttended, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':completed_at', $now);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        // Fallback en cas d'erreur : version simplifiée sans updated_at
        $query = "UPDATE meetings SET 
                    status = :status, 
                    student_attended = :student_attended, 
                    notes = :notes, 
                    completed_at = :completed_at
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $now = date('Y-m-d H:i:s');
        $status = 'completed';
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':student_attended', $studentAttended, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':completed_at', $now);
        
        return $stmt->execute();
    }
}

/**
 * Crée une nouvelle réunion selon la structure exacte de la base de données
 * @param array $data Données de la réunion
 * @return int|false ID de la réunion créée, sinon false
 */
public function createMeeting($data) {
    // Colonnes obligatoires selon la structure de la base
    $requiredFields = ['title', 'date_time', 'duration', 'organizer_id', 'status'];
    
    // Vérifier les champs obligatoires
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Champ obligatoire manquant: $field");
        }
    }
    
    // Valeurs par défaut pour les champs optionnels
    $defaults = [
        'description' => '',
        'location' => '',
        'meeting_link' => '',
        'assignment_id' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Fusionner avec les valeurs par défaut
    $data = array_merge($defaults, $data);
    
    // Requête d'insertion
    $query = "INSERT INTO meetings (
                title, description, date_time, duration, 
                location, meeting_link, organizer_id, 
                status, created_at, assignment_id
              ) VALUES (
                :title, :description, :date_time, :duration,
                :location, :meeting_link, :organizer_id,
                :status, :created_at, :assignment_id
              )";
    
    try {
        $stmt = $this->db->prepare($query);
        
        // Bind des paramètres
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':date_time', $data['date_time']);
        $stmt->bindParam(':duration', $data['duration'], PDO::PARAM_INT);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':meeting_link', $data['meeting_link']);
        $stmt->bindParam(':organizer_id', $data['organizer_id'], PDO::PARAM_INT);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':created_at', $data['created_at']);
        
        // Gestion de assignment_id qui peut être NULL
        if ($data['assignment_id'] !== null) {
            $stmt->bindParam(':assignment_id', $data['assignment_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':assignment_id', null, PDO::PARAM_NULL);
        }
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la création de la réunion: " . $e->getMessage());
    }
}

}