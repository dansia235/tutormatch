<?php
/**
 * API pour récupérer les réunions d'un étudiant
 * Endpoint: /api/meetings/student-meetings
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonError('Accès non autorisé', 403);
}

try {
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Récupérer les réunions de l'étudiant
    $meetingModel = new Meeting($db);
    $allMeetings = $meetingModel->getByStudentId($student['id']);
    
    // Catégoriser les réunions
    $meetings = [
        'upcoming' => [],
        'past' => [],
        'cancelled' => []
    ];
    
    // Compteurs et statistiques
    $totalMeetings = count($allMeetings);
    $upcomingCount = 0;
    $pastCount = 0;
    $attendedCount = 0;
    
    // Date actuelle pour comparer
    $currentDate = new DateTime();
    
    // Organiser les réunions par catégorie
    foreach ($allMeetings as $meeting) {
        $meetingDate = new DateTime($meeting['meeting_date']);
        
        // Vérifier si la réunion est passée ou à venir
        if ($meeting['status'] === 'cancelled') {
            $meetings['cancelled'][] = $meeting;
        } elseif ($meetingDate < $currentDate) {
            $meetings['past'][] = $meeting;
            $pastCount++;
            
            // Vérifier si l'étudiant a assisté à la réunion
            if ($meeting['student_attended'] === 1) {
                $attendedCount++;
            }
        } else {
            $meetings['upcoming'][] = $meeting;
            $upcomingCount++;
        }
    }
    
    // Calculer le taux de participation
    $participationRate = $pastCount > 0 ? round(($attendedCount / $pastCount) * 100) : 0;
    
    // Satisfaction moyenne - simulée ici, à remplacer par une vraie logique
    $satisfactionAverage = number_format(mt_rand(30, 50) / 10, 1);
    
    // Renvoyer les données
    sendJsonResponse([
        'meetings' => $meetings,
        'stats' => [
            'total' => $totalMeetings,
            'upcoming' => $upcomingCount,
            'past' => $pastCount,
            'attended' => $attendedCount,
            'participation_rate' => $participationRate,
            'satisfaction_average' => $satisfactionAverage
        ]
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des réunions: ' . $e->getMessage(), 500);
}
?>