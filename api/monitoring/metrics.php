<?php
/**
 * Endpoint pour exposer les métriques système avec interface visuelle
 */
require_once '../../includes/init.php';
require_once '../../includes/Monitor.php';

// Déterminer le format de sortie
$format = $_GET['format'] ?? 'html';

if ($format === 'prometheus' || $format === 'text') {
    header('Content-Type: text/plain; charset=utf-8');
} elseif ($format === 'json') {
    header('Content-Type: application/json');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

// Authentification pour les métriques (optionnel)
$authToken = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$expectedToken = $_ENV['METRICS_TOKEN'] ?? '';

if ($expectedToken && $authToken !== "Bearer {$expectedToken}" && $format !== 'html') {
    http_response_code(401);
    echo "Unauthorized\n";
    exit;
}

try {
    $monitor = Monitor::getInstance();
    
    // Collecter les métriques en temps réel
    $metrics = [];
    
    // Ajouter quelques métriques système en temps réel
    $monitor->gauge('system.current_memory', memory_get_usage(true));
    $monitor->gauge('system.current_time', time());
    
    // Métriques de base de données
    $dbMetrics = [];
    try {
        // Utiliser les constantes définies dans database.php
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, 
            DB_USER, 
            DB_PASS
        );
        
        // Compter les entités principales
        $tables = ['users', 'students', 'teachers', 'internships', 'assignments', 'evaluations'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
                $count = $stmt->fetchColumn();
                $monitor->gauge("database.table.count", $count, ['table' => $table]);
                $dbMetrics[$table] = $count;
            } catch (Exception $e) {
                $dbMetrics[$table] = 0;
            }
        }
        
        // Métriques de connexion
        try {
            $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $monitor->gauge('database.connections', (int)$result['Value']);
                $dbMetrics['connections'] = (int)$result['Value'];
            }
        } catch (Exception $e) {
            // Ignorer si pas d'accès aux status
        }
        
        $dbMetrics['status'] = 'healthy';
        
    } catch (Exception $e) {
        $monitor->increment('database.connection_errors', 1);
        $dbMetrics['status'] = 'error';
        $dbMetrics['error'] = $e->getMessage();
    }
    
    // Métriques Redis si disponible
    $redisMetrics = [];
    if (class_exists('Redis')) {
        try {
            $redis = new Redis();
            // Utiliser localhost par défaut si Redis n'est pas configuré
            $redisHost = defined('REDIS_HOST') ? REDIS_HOST : 'localhost';
            $redisPort = defined('REDIS_PORT') ? REDIS_PORT : 6379;
            $redis->connect($redisHost, $redisPort);
            
            $info = $redis->info();
            $monitor->gauge('redis.used_memory', $info['used_memory'] ?? 0);
            $monitor->gauge('redis.connected_clients', $info['connected_clients'] ?? 0);
            
            $redisMetrics = [
                'status' => 'healthy',
                'used_memory' => $info['used_memory'] ?? 0,
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            ];
            
            $redis->close();
        } catch (Exception $e) {
            $monitor->increment('redis.connection_errors', 1);
            $redisMetrics = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $redisMetrics = [
            'status' => 'unavailable',
            'message' => 'Redis extension not available'
        ];
    }
    
    // Métriques système
    $systemMetrics = [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'php_version' => phpversion(),
        'disk_free' => disk_free_space(ROOT_DIR),
        'disk_total' => disk_total_space(ROOT_DIR),
        'uptime' => time() - $_SERVER['REQUEST_TIME'],
    ];
    
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $systemMetrics['load_1min'] = $load[0];
        $systemMetrics['load_5min'] = $load[1];
        $systemMetrics['load_15min'] = $load[2];
    }
    
    // Récupérer toutes les métriques du monitor
    $allMetrics = $monitor->getAllMetrics();
    
    // Format de sortie
    if ($format === 'prometheus' || $format === 'text') {
        echo $monitor->getPrometheusMetrics();
        exit;
    } elseif ($format === 'json') {
        echo json_encode([
            'timestamp' => time(),
            'system' => $systemMetrics,
            'database' => $dbMetrics,
            'redis' => $redisMetrics,
            'metrics' => $allMetrics
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
} catch (Exception $e) {
    if ($format === 'prometheus' || $format === 'text') {
        echo "Error generating metrics: " . $e->getMessage() . "\n";
        exit;
    } elseif ($format === 'json') {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    
    // Pour l'HTML, continuer avec des métriques d'erreur
    $systemMetrics = ['error' => $e->getMessage()];
    $dbMetrics = ['status' => 'error'];
    $redisMetrics = ['status' => 'error'];
    $allMetrics = [];
}

// Fonctions utilitaires pour l'affichage
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatNumber($number) {
    if ($number >= 1000000000) {
        return round($number / 1000000000, 2) . 'B';
    } elseif ($number >= 1000000) {
        return round($number / 1000000, 2) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 2) . 'K';
    }
    return number_format($number);
}

function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    $parts = [];
    if ($days > 0) $parts[] = $days . 'd';
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    if ($seconds > 0 || empty($parts)) $parts[] = $seconds . 's';
    
    return implode(' ', $parts);
}

$currentTime = date('Y-m-d H:i:s');
$refreshInterval = 10; // secondes
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TutorMatch - Métriques système</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --muted-color: #95a5a6;
            --background-color: #f5f7fa;
            --card-background: #ffffff;
            --text-color: #2c3e50;
        }

        [data-theme="dark"] {
            --primary-color: #4d96f0;
            --secondary-color: #1e272e;
            --accent-color: #ff6b6b;
            --background-color: #121212;
            --card-background: #1e1e1e;
            --text-color: #ffffff;
            --light-color: #262626;
            --muted-color: #b0b8c4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .metrics-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .nav-item {
            padding: 0.75rem 1.5rem;
            background: var(--card-background);
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-item.active,
        .nav-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .grid-wide {
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }

        .card {
            background: var(--card-background);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-color);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .metric-big {
            text-align: center;
            padding: 1rem 0;
        }

        .metric-big-value {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .metric-big-label {
            font-size: 1rem;
            color: var(--muted-color);
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--light-color);
        }

        .metric:last-child {
            border-bottom: none;
        }

        .metric-label {
            font-weight: 500;
            color: var(--muted-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .metric-value {
            font-weight: 600;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-healthy {
            background: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
        }

        .status-error {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }

        .status-unavailable {
            background: rgba(149, 165, 166, 0.2);
            color: var(--muted-color);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        .realtime-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--success-color);
            font-weight: 600;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--success-color);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background: var(--muted-color);
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--card-background);
            border: 2px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .auto-refresh {
            text-align: center;
            margin: 2rem 0;
            padding: 1rem;
            background: var(--card-background);
            border-radius: 8px;
            border: 2px dashed var(--primary-color);
        }

        .timestamp {
            text-align: center;
            color: var(--muted-color);
            font-size: 0.875rem;
            margin-top: 2rem;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .table-metric {
            text-align: center;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .table-metric:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        .table-count {
            font-size: 2rem;
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .table-name {
            font-size: 0.875rem;
            text-transform: capitalize;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .metrics-nav {
                flex-direction: column;
            }
            
            .metric-big-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="theme-toggle" onclick="toggleTheme()" title="Changer de thème">
        <i class="fas fa-moon" id="theme-icon"></i>
    </div>

    <div class="header">
        <h1>
            <i class="fas fa-chart-line"></i>
            TutorMatch Metrics
        </h1>
        <div class="subtitle">Métriques système et performance en temps réel</div>
        <div class="realtime-indicator">
            <div class="pulse-dot"></div>
            Données en temps réel
        </div>
    </div>

    <div class="container">
        <div class="metrics-nav">
            <a href="#system" class="nav-item active" onclick="showSection('system')">
                <i class="fas fa-server"></i>
                Système
            </a>
            <a href="#database" class="nav-item" onclick="showSection('database')">
                <i class="fas fa-database"></i>
                Base de données
            </a>
            <a href="#redis" class="nav-item" onclick="showSection('redis')">
                <i class="fab fa-redis"></i>
                Redis Cache
            </a>
            <a href="#performance" class="nav-item" onclick="showSection('performance')">
                <i class="fas fa-tachometer-alt"></i>
                Performance
            </a>
        </div>

        <div class="auto-refresh">
            <i class="fas fa-sync-alt"></i>
            <strong>Actualisation automatique</strong> - Prochaine mise à jour dans <span id="countdown"><?php echo $refreshInterval; ?></span> secondes
        </div>

        <!-- Section Système -->
        <div id="system-section" class="metrics-section">
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-memory card-icon"></i>
                            Mémoire
                        </h3>
                    </div>
                    <div class="metric-big">
                        <div class="metric-big-value"><?php echo formatBytes($systemMetrics['memory_usage'] ?? 0); ?></div>
                        <div class="metric-big-label">Mémoire utilisée</div>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Pic mémoire</span>
                        <span class="metric-value"><?php echo formatBytes($systemMetrics['memory_peak'] ?? 0); ?></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-hdd card-icon"></i>
                            Stockage
                        </h3>
                    </div>
                    <div class="metric-big">
                        <div class="metric-big-value"><?php echo formatBytes($systemMetrics['disk_free'] ?? 0); ?></div>
                        <div class="metric-big-label">Espace libre</div>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Espace total</span>
                        <span class="metric-value"><?php echo formatBytes($systemMetrics['disk_total'] ?? 0); ?></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock card-icon"></i>
                            Uptime
                        </h3>
                    </div>
                    <div class="metric-big">
                        <div class="metric-big-value"><?php echo formatUptime($systemMetrics['uptime'] ?? 0); ?></div>
                        <div class="metric-big-label">Temps de fonctionnement</div>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Version PHP</span>
                        <span class="metric-value"><?php echo $systemMetrics['php_version'] ?? 'N/A'; ?></span>
                    </div>
                </div>

                <?php if (isset($systemMetrics['load_1min'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-microchip card-icon"></i>
                            Charge système
                        </h3>
                    </div>
                    <div class="metric">
                        <span class="metric-label">1 minute</span>
                        <span class="metric-value"><?php echo round($systemMetrics['load_1min'], 2); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">5 minutes</span>
                        <span class="metric-value"><?php echo round($systemMetrics['load_5min'], 2); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">15 minutes</span>
                        <span class="metric-value"><?php echo round($systemMetrics['load_15min'], 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section Base de données -->
        <div id="database-section" class="metrics-section" style="display: none;">
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-database card-icon"></i>
                            État de la base
                        </h3>
                        <div class="status-indicator status-<?php echo $dbMetrics['status']; ?>">
                            <?php if ($dbMetrics['status'] === 'healthy'): ?>
                                <i class="fas fa-check-circle"></i> Opérationnelle
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle"></i> Erreur
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (isset($dbMetrics['connections'])): ?>
                    <div class="metric-big">
                        <div class="metric-big-value"><?php echo formatNumber($dbMetrics['connections']); ?></div>
                        <div class="metric-big-label">Connexions actives</div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($dbMetrics['error'])): ?>
                    <div class="metric">
                        <span style="color: var(--danger-color);"><?php echo htmlspecialchars($dbMetrics['error']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="grid-column: 1 / -1;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table card-icon"></i>
                            Nombre d'enregistrements par table
                        </h3>
                    </div>
                    <div class="tables-grid">
                        <?php 
                        $tables = ['users', 'students', 'teachers', 'internships', 'assignments', 'evaluations'];
                        foreach ($tables as $table): 
                        ?>
                        <div class="table-metric">
                            <div class="table-count"><?php echo formatNumber($dbMetrics[$table] ?? 0); ?></div>
                            <div class="table-name"><?php echo $table; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Redis -->
        <div id="redis-section" class="metrics-section" style="display: none;">
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fab fa-redis card-icon"></i>
                            État Redis
                        </h3>
                        <div class="status-indicator status-<?php echo $redisMetrics['status']; ?>">
                            <?php if ($redisMetrics['status'] === 'healthy'): ?>
                                <i class="fas fa-check-circle"></i> Opérationnel
                            <?php elseif ($redisMetrics['status'] === 'error'): ?>
                                <i class="fas fa-exclamation-triangle"></i> Erreur
                            <?php else: ?>
                                <i class="fas fa-minus-circle"></i> Indisponible
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($redisMetrics['status'] === 'healthy'): ?>
                    <div class="metric">
                        <span class="metric-label">Mémoire utilisée</span>
                        <span class="metric-value"><?php echo formatBytes($redisMetrics['used_memory']); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Clients connectés</span>
                        <span class="metric-value"><?php echo formatNumber($redisMetrics['connected_clients']); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Commandes traitées</span>
                        <span class="metric-value"><?php echo formatNumber($redisMetrics['total_commands_processed']); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Cache hits</span>
                        <span class="metric-value"><?php echo formatNumber($redisMetrics['keyspace_hits']); ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Cache misses</span>
                        <span class="metric-value"><?php echo formatNumber($redisMetrics['keyspace_misses']); ?></span>
                    </div>
                    <?php elseif (isset($redisMetrics['error'])): ?>
                    <div class="metric">
                        <span style="color: var(--danger-color);"><?php echo htmlspecialchars($redisMetrics['error']); ?></span>
                    </div>
                    <?php elseif (isset($redisMetrics['message'])): ?>
                    <div class="metric">
                        <span style="color: var(--muted-color);"><?php echo htmlspecialchars($redisMetrics['message']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($redisMetrics['status'] === 'healthy' && isset($redisMetrics['keyspace_hits'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie card-icon"></i>
                            Performance Cache
                        </h3>
                    </div>
                    <?php 
                    $totalRequests = $redisMetrics['keyspace_hits'] + $redisMetrics['keyspace_misses'];
                    $hitRate = $totalRequests > 0 ? ($redisMetrics['keyspace_hits'] / $totalRequests) * 100 : 0;
                    ?>
                    <div class="metric-big">
                        <div class="metric-big-value"><?php echo round($hitRate, 1); ?>%</div>
                        <div class="metric-big-label">Taux de réussite du cache</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section Performance -->
        <div id="performance-section" class="metrics-section" style="display: none;">
            <div class="grid grid-wide">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-area card-icon"></i>
                            Utilisation mémoire
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="memoryChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line card-icon"></i>
                            Activité base de données
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="databaseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="?format=json" class="btn btn-primary">
                <i class="fas fa-code"></i>
                Format JSON
            </a>
            <a href="?format=prometheus" class="btn btn-primary">
                <i class="fas fa-file-code"></i>
                Format Prometheus
            </a>
            <a href="health.php" class="btn btn-secondary">
                <i class="fas fa-heartbeat"></i>
                Health Check
            </a>
            <a href="../swagger.php" class="btn btn-secondary">
                <i class="fas fa-book"></i>
                Documentation API
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync-alt"></i>
                Actualiser
            </button>
        </div>

        <div class="timestamp">
            <i class="fas fa-clock"></i>
            Dernière mise à jour: <?php echo $currentTime; ?>
        </div>
    </div>

    <script>
        // Configuration des graphiques
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-color').trim()
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--muted-color').trim()
                    },
                    grid: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--light-color').trim()
                    }
                },
                y: {
                    ticks: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--muted-color').trim()
                    },
                    grid: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--light-color').trim()
                    }
                }
            }
        };

        // Graphique mémoire
        const memoryCtx = document.getElementById('memoryChart').getContext('2d');
        const memoryChart = new Chart(memoryCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Mémoire utilisée (MB)',
                    data: [],
                    borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
                    backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() + '20',
                    tension: 0.4
                }]
            },
            options: chartOptions
        });

        // Graphique base de données
        const databaseCtx = document.getElementById('databaseChart').getContext('2d');
        const databaseChart = new Chart(databaseCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys(array_filter($dbMetrics, 'is_numeric'))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values(array_filter($dbMetrics, 'is_numeric'))); ?>,
                    backgroundColor: [
                        getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--info-color').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--accent-color').trim(),
                        getComputedStyle(document.documentElement).getPropertyValue('--muted-color').trim()
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text-color').trim()
                        }
                    }
                }
            }
        });

        // Navigation entre sections
        function showSection(section) {
            // Masquer toutes les sections
            document.querySelectorAll('.metrics-section').forEach(s => s.style.display = 'none');
            // Retirer la classe active de tous les nav-items
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            
            // Afficher la section sélectionnée
            document.getElementById(section + '-section').style.display = 'block';
            // Ajouter la classe active au nav-item correspondant
            event.target.classList.add('active');
        }

        // Thème dark/light
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('theme-icon');
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                icon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                icon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
            
            // Mettre à jour les couleurs des graphiques
            updateChartColors();
        }

        function updateChartColors() {
            // Cette fonction pourrait être étendue pour mettre à jour les couleurs des graphiques
            // en fonction du thème
        }

        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }

        // Auto-refresh
        let countdown = <?php echo $refreshInterval; ?>;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                location.reload();
            }
        }

        setInterval(updateCountdown, 1000);

        // Ajouter des données en temps réel au graphique mémoire
        function addMemoryData() {
            const now = new Date();
            const timeLabel = now.toLocaleTimeString();
            const memoryUsage = <?php echo round(($systemMetrics['memory_usage'] ?? 0) / 1024 / 1024, 2); ?>;
            
            memoryChart.data.labels.push(timeLabel);
            memoryChart.data.datasets[0].data.push(memoryUsage);
            
            // Garder seulement les 10 derniers points
            if (memoryChart.data.labels.length > 10) {
                memoryChart.data.labels.shift();
                memoryChart.data.datasets[0].data.shift();
            }
            
            memoryChart.update();
        }

        // Ajouter des données initiales
        addMemoryData();

        // Détection système pour le thème
        if (!savedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }
    </script>
</body>
</html>