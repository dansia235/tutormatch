<?php
/**
 * API pour l'état du système
 * Endpoint: /api/dashboard/system-status
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin']);

// Fonction pour vérifier l'état d'un service
function checkServiceStatus($serviceName) {
    switch ($serviceName) {
        case 'database':
            try {
                global $db;
                $stmt = $db->query("SELECT 1");
                return $stmt !== false;
            } catch (PDOException $e) {
                return false;
            }
            break;
            
        case 'uploads':
            $uploadDir = __DIR__ . '/../../uploads';
            return is_dir($uploadDir) && is_writable($uploadDir);
            break;
            
        case 'api':
            // Vérifier que l'API est accessible
            return true; // Si nous sommes ici, l'API fonctionne
            break;
            
        case 'sessions':
            return session_status() === PHP_SESSION_ACTIVE;
            break;
            
        case 'email':
            // À adapter selon votre configuration d'envoi d'email
            return function_exists('mail');
            break;
            
        default:
            return false;
    }
}

// Vérifier l'état des services
$services = [
    'database' => [
        'name' => 'Base de données',
        'status' => checkServiceStatus('database') ? 'operational' : 'down',
        'last_checked' => date('Y-m-d H:i:s')
    ],
    'uploads' => [
        'name' => 'Système de fichiers',
        'status' => checkServiceStatus('uploads') ? 'operational' : 'degraded',
        'last_checked' => date('Y-m-d H:i:s')
    ],
    'api' => [
        'name' => 'API REST',
        'status' => checkServiceStatus('api') ? 'operational' : 'down',
        'last_checked' => date('Y-m-d H:i:s')
    ],
    'sessions' => [
        'name' => 'Gestion des sessions',
        'status' => checkServiceStatus('sessions') ? 'operational' : 'degraded',
        'last_checked' => date('Y-m-d H:i:s')
    ],
    'email' => [
        'name' => 'Service d\'emails',
        'status' => checkServiceStatus('email') ? 'operational' : 'degraded',
        'last_checked' => date('Y-m-d H:i:s')
    ]
];

// Collecter les métriques système
function getDiskUsage() {
    $total = disk_total_space('/');
    $free = disk_free_space('/');
    
    return [
        'total' => $total,
        'free' => $free,
        'used' => $total - $free,
        'percent_used' => round(($total - $free) / $total * 100, 2)
    ];
}

function getDatabaseSize() {
    global $db;
    
    try {
        // Récupérer la taille de la base de données (spécifique à MySQL)
        $stmt = $db->query("
            SELECT 
                table_schema AS 'database',
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            GROUP BY table_schema
        ");
        
        if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'size_mb' => (float) $result['size_mb'],
                'tables' => countTables()
            ];
        }
        
        return [
            'size_mb' => 0,
            'tables' => 0
        ];
    } catch (PDOException $e) {
        return [
            'size_mb' => 0,
            'tables' => 0,
            'error' => $e->getMessage()
        ];
    }
}

function countTables() {
    global $db;
    
    try {
        $stmt = $db->query("
            SELECT COUNT(*) AS table_count
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ");
        
        if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return (int) $result['table_count'];
        }
        
        return 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getSystemLoad() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return [
            'load_1min' => $load[0],
            'load_5min' => $load[1],
            'load_15min' => $load[2]
        ];
    }
    
    return [
        'load_1min' => 0,
        'load_5min' => 0,
        'load_15min' => 0
    ];
}

function getMemoryUsage() {
    if (function_exists('memory_get_usage')) {
        $memoryUsed = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        // Convertir la limite de mémoire en octets
        $unit = strtolower(substr($memoryLimit, -1));
        $memoryLimit = (int) $memoryLimit;
        
        switch ($unit) {
            case 'g':
                $memoryLimit *= 1024;
                // intentional fall-through
            case 'm':
                $memoryLimit *= 1024;
                // intentional fall-through
            case 'k':
                $memoryLimit *= 1024;
        }
        
        return [
            'used' => $memoryUsed,
            'limit' => $memoryLimit,
            'percent_used' => $memoryLimit > 0 ? round(($memoryUsed / $memoryLimit) * 100, 2) : 0
        ];
    }
    
    return [
        'used' => 0,
        'limit' => 0,
        'percent_used' => 0
    ];
}

// Collecter les informations sur les dernières activités d'administration
function getAdminActivities() {
    global $db;
    
    try {
        $stmt = $db->query("
            SELECT 
                'user_creation' AS action_type,
                u.username AS action_subject,
                u.created_at AS action_date,
                CONCAT('Utilisateur créé par ', creator.username) AS action_description
            FROM users u
            LEFT JOIN users creator ON u.created_by = creator.id
            WHERE u.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION
            
            SELECT 
                'assignment_creation' AS action_type,
                CONCAT(s.first_name, ' ', s.last_name) AS action_subject,
                a.created_at AS action_date,
                CONCAT('Affectation créée pour ', s.first_name, ' ', s.last_name, ' avec ', t.first_name, ' ', t.last_name) AS action_description
            FROM assignments a
            JOIN students s ON a.student_id = s.id
            JOIN teachers t ON a.teacher_id = t.id
            WHERE a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            ORDER BY action_date DESC
            LIMIT 5
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les informations de version du système
function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_type' => $GLOBALS['db']->getAttribute(PDO::ATTR_DRIVER_NAME),
        'database_version' => $GLOBALS['db']->getAttribute(PDO::ATTR_SERVER_VERSION),
        'system_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
}

// Collecter toutes les métriques
$metrics = [
    'disk' => getDiskUsage(),
    'database' => getDatabaseSize(),
    'system_load' => getSystemLoad(),
    'memory' => getMemoryUsage(),
    'activities' => getAdminActivities(),
    'system_info' => getSystemInfo()
];

// Préparer la réponse
$response = [
    'services' => $services,
    'metrics' => $metrics,
    'updated_at' => date('Y-m-d H:i:s')
];

// Envoyer la réponse
sendJsonResponse($response);
?>