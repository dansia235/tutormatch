<?php
/**
 * Endpoint de health check avec interface visuelle
 */
require_once '../../includes/init.php';
require_once '../../includes/Monitor.php';

// Déterminer le format de sortie
$format = $_GET['format'] ?? 'html';

if ($format === 'json') {
    header('Content-Type: application/json');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

try {
    $monitor = Monitor::getInstance();
    $health = $monitor->getHealthStatus();
    
    // Définir le code de statut HTTP selon l'état de santé
    switch ($health['status']) {
        case 'healthy':
            http_response_code(200);
            break;
        case 'degraded':
            http_response_code(200); // Toujours 200 mais signaler la dégradation
            break;
        case 'unhealthy':
            http_response_code(503); // Service Unavailable
            break;
        default:
            http_response_code(500);
    }
    
    // Ajouter des informations supplémentaires
    $health['application'] = [
        'name' => 'TutorMatch',
        'version' => '1.0.0',
        'uptime' => time() - $_SERVER['REQUEST_TIME'],
        'environment' => $_ENV['APP_ENV'] ?? 'production'
    ];
    
    // Métriques système détaillées
    $health['system'] = [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'php_version' => phpversion(),
        'disk_free' => disk_free_space(ROOT_DIR),
        'disk_total' => disk_total_space(ROOT_DIR)
    ];
    
    // Tests de connectivité approfondis
    $health['dependencies'] = [];
    
    // Test base de données avec timing
    $start = microtime(true);
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, 
            DB_USER, 
            DB_PASS,
            [PDO::ATTR_TIMEOUT => 5]
        );
        $pdo->query("SELECT 1")->fetchColumn();
        $dbTime = microtime(true) - $start;
        
        $health['dependencies']['database'] = [
            'status' => 'healthy',
            'response_time_ms' => round($dbTime * 1000, 2),
            'host' => DB_HOST,
            'database' => DB_NAME
        ];
    } catch (Exception $e) {
        $health['dependencies']['database'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'host' => DB_HOST
        ];
    }
    
    // Test Redis (optionnel)
    if (class_exists('Redis')) {
        $start = microtime(true);
        try {
            $redis = new Redis();
            $redisHost = defined('REDIS_HOST') ? REDIS_HOST : 'localhost';
            $redisPort = defined('REDIS_PORT') ? REDIS_PORT : 6379;
            $redis->connect($redisHost, $redisPort, 5);
            $redis->ping();
            $redisTime = microtime(true) - $start;
            
            $health['dependencies']['redis'] = [
                'status' => 'healthy',
                'response_time_ms' => round($redisTime * 1000, 2),
                'host' => $redisHost,
                'port' => $redisPort
            ];
            $redis->close();
        } catch (Exception $e) {
            $health['dependencies']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'host' => $redisHost ?? 'localhost'
            ];
        }
    }
    
    // Test des répertoires critiques
    $criticalDirs = [
        'logs' => ROOT_DIR . '/logs',
        'uploads' => ROOT_DIR . '/uploads',
        'temp' => sys_get_temp_dir()
    ];
    
    foreach ($criticalDirs as $name => $path) {
        $health['dependencies'][$name . '_directory'] = [
            'status' => is_writable($path) ? 'healthy' : 'unhealthy',
            'path' => $path,
            'writable' => is_writable($path),
            'exists' => is_dir($path)
        ];
    }
    
    if ($format === 'json') {
        echo json_encode($health, JSON_PRETTY_PRINT);
        exit;
    }
    
} catch (Exception $e) {
    if ($format === 'json') {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Health check failed',
            'error' => $e->getMessage(),
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    $health = [
        'status' => 'error',
        'message' => 'Health check failed',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ];
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

function getStatusColor($status) {
    switch ($status) {
        case 'healthy': return '#2ecc71';
        case 'degraded': return '#f39c12';
        case 'unhealthy': return '#e74c3c';
        default: return '#95a5a6';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'healthy': return '✅';
        case 'degraded': return '⚠️';
        case 'unhealthy': return '❌';
        default: return '❓';
    }
}

$currentTime = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TutorMatch - Health Check</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .status-healthy {
            background: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border: 2px solid #27ae60;
        }

        .status-degraded {
            background: rgba(243, 156, 18, 0.2);
            color: #e67e22;
            border: 2px solid #e67e22;
        }

        .status-unhealthy {
            background: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border: 2px solid #c0392b;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
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
        }

        .metric-value {
            font-weight: 600;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .dependency-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            margin: 0.25rem 0;
            border-radius: 6px;
            background: var(--light-color);
            transition: all 0.3s ease;
        }

        .dependency-healthy {
            background: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
        }

        .dependency-unhealthy {
            background: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--danger-color);
        }

        .dependency-info {
            flex: 1;
        }

        .dependency-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .dependency-details {
            font-size: 0.75rem;
            color: var(--muted-color);
            line-height: 1.3;
        }

        .dependency-status {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--light-color);
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .progress-danger {
            background: var(--danger-color);
        }

        .progress-warning {
            background: var(--warning-color);
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
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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
            
            .actions {
                flex-direction: column;
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
            <i class="fas fa-heartbeat pulse"></i>
            TutorMatch Health Check
        </h1>
        <div class="subtitle">État de santé du système en temps réel</div>
        <div class="status-badge status-<?php echo $health['status']; ?>">
            <?php echo getStatusIcon($health['status']); ?>
            <?php echo strtoupper($health['status']); ?>
        </div>
    </div>

    <div class="container">
        <div class="auto-refresh">
            <i class="fas fa-sync-alt"></i>
            <strong>Actualisation automatique activée</strong> - Prochaine mise à jour dans <span id="countdown">30</span> secondes
        </div>

        <div class="grid">
            <!-- Application Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle card-icon"></i>
                        Application
                    </h3>
                </div>
                <div class="metric">
                    <span class="metric-label">Nom</span>
                    <span class="metric-value"><?php echo $health['application']['name']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Version</span>
                    <span class="metric-value"><?php echo $health['application']['version']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Environnement</span>
                    <span class="metric-value"><?php echo $health['application']['environment']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Uptime</span>
                    <span class="metric-value"><?php echo formatUptime($health['application']['uptime']); ?></span>
                </div>
            </div>

            <!-- System Metrics -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-server card-icon"></i>
                        Système
                    </h3>
                </div>
                <div class="metric">
                    <span class="metric-label">Mémoire utilisée</span>
                    <span class="metric-value"><?php echo formatBytes($health['system']['memory_usage']); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Pic mémoire</span>
                    <span class="metric-value"><?php echo formatBytes($health['system']['memory_peak']); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Version PHP</span>
                    <span class="metric-value"><?php echo $health['system']['php_version']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Espace libre</span>
                    <span class="metric-value"><?php echo formatBytes($health['system']['disk_free']); ?></span>
                </div>
                <?php 
                $diskUsage = (($health['system']['disk_total'] - $health['system']['disk_free']) / $health['system']['disk_total']) * 100;
                $progressClass = $diskUsage > 90 ? 'progress-danger' : ($diskUsage > 80 ? 'progress-warning' : '');
                ?>
                <div class="metric">
                    <span class="metric-label">Utilisation disque</span>
                    <span class="metric-value"><?php echo round($diskUsage, 1); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $progressClass; ?>" style="width: <?php echo $diskUsage; ?>%"></div>
                </div>
            </div>

            <!-- Dependencies -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-network-wired card-icon"></i>
                        Dépendances
                    </h3>
                </div>
                <?php foreach ($health['dependencies'] as $name => $dep): ?>
                    <div class="dependency-item dependency-<?php echo $dep['status']; ?>">
                        <div class="dependency-info">
                            <div class="dependency-name">
                                <?php 
                                $icons = [
                                    'database' => 'fas fa-database',
                                    'redis' => 'fab fa-redis',
                                    'logs_directory' => 'fas fa-folder',
                                    'uploads_directory' => 'fas fa-cloud-upload-alt',
                                    'temp_directory' => 'fas fa-folder-open'
                                ];
                                $icon = $icons[$name] ?? 'fas fa-cog';
                                ?>
                                <i class="<?php echo $icon; ?>" style="margin-right: 0.5rem;"></i>
                                <?php echo str_replace('_', ' ', ucfirst($name)); ?>
                            </div>
                            <div class="dependency-details">
                                <?php if (isset($dep['host'])): ?>
                                    Host: <?php echo $dep['host']; ?>
                                    <?php if (isset($dep['port'])): ?>:<?php echo $dep['port']; ?><?php endif; ?>
                                <?php endif; ?>
                                <?php if (isset($dep['database'])): ?>
                                    | Database: <?php echo $dep['database']; ?>
                                <?php endif; ?>
                                <?php if (isset($dep['response_time_ms'])): ?>
                                    | Response: <?php echo $dep['response_time_ms']; ?>ms
                                <?php endif; ?>
                                <?php if (isset($dep['path'])): ?>
                                    Path: <?php echo $dep['path']; ?>
                                <?php endif; ?>
                                <?php if (isset($dep['error'])): ?>
                                    <br><span style="color: var(--danger-color);">Error: <?php echo $dep['error']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dependency-status">
                            <?php echo getStatusIcon($dep['status']); ?>
                            <?php echo ucfirst($dep['status']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="actions">
            <a href="?format=json" class="btn btn-primary">
                <i class="fas fa-code"></i>
                Format JSON
            </a>
            <a href="../swagger.php" class="btn btn-secondary">
                <i class="fas fa-book"></i>
                Documentation API
            </a>
            <a href="metrics.php" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i>
                Métriques détaillées
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
        }

        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }

        // Auto-refresh
        let countdown = 30;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                location.reload();
            }
        }

        setInterval(updateCountdown, 1000);

        // Détection système pour le thème
        if (!savedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
        }
    </script>
</body>
</html>