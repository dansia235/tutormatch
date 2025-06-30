<?php
/**
 * Système de monitoring et métriques pour TutorMatch
 * Collecte et expose des métriques système et métier
 */
class Monitor
{
    private static ?Monitor $instance = null;
    private array $metrics = [];
    private array $counters = [];
    private array $timers = [];
    private float $startTime;
    
    private function __construct()
    {
        $this->startTime = microtime(true);
        
        // Métriques système de base
        $this->initSystemMetrics();
        
        // Enregistrer les métriques en fin de requête
        register_shutdown_function([$this, 'flushMetrics']);
    }
    
    public static function getInstance(): Monitor
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Incrémente un compteur
     */
    public function increment(string $name, int $value = 1, array $tags = []): void
    {
        $key = $this->buildMetricKey($name, $tags);
        $this->counters[$key] = ($this->counters[$key] ?? 0) + $value;
        
        logger()->metric($name, $this->counters[$key], 'count', $tags);
    }
    
    /**
     * Décrémente un compteur
     */
    public function decrement(string $name, int $value = 1, array $tags = []): void
    {
        $this->increment($name, -$value, $tags);
    }
    
    /**
     * Définit une valeur de gauge
     */
    public function gauge(string $name, $value, array $tags = []): void
    {
        $key = $this->buildMetricKey($name, $tags);
        $this->metrics[$key] = [
            'type' => 'gauge',
            'value' => $value,
            'timestamp' => time(),
            'tags' => $tags
        ];
        
        logger()->metric($name, $value, 'gauge', $tags);
    }
    
    /**
     * Démarre un timer
     */
    public function startTimer(string $name, array $tags = []): string
    {
        $timerId = uniqid($name . '_', true);
        $this->timers[$timerId] = [
            'name' => $name,
            'start' => microtime(true),
            'tags' => $tags
        ];
        
        return $timerId;
    }
    
    /**
     * Arrête un timer et enregistre la durée
     */
    public function stopTimer(string $timerId): float
    {
        if (!isset($this->timers[$timerId])) {
            return 0.0;
        }
        
        $timer = $this->timers[$timerId];
        $duration = microtime(true) - $timer['start'];
        
        $this->timing($timer['name'], $duration, $timer['tags']);
        unset($this->timers[$timerId]);
        
        return $duration;
    }
    
    /**
     * Enregistre une durée directement
     */
    public function timing(string $name, float $duration, array $tags = []): void
    {
        $key = $this->buildMetricKey($name, $tags);
        $this->metrics[$key] = [
            'type' => 'timing',
            'value' => $duration,
            'timestamp' => time(),
            'tags' => $tags
        ];
        
        logger()->metric($name, round($duration * 1000, 2), 'ms', $tags);
    }
    
    /**
     * Enregistre un histogramme de valeurs
     */
    public function histogram(string $name, $value, array $tags = []): void
    {
        $key = $this->buildMetricKey($name, $tags);
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'type' => 'histogram',
                'values' => [],
                'tags' => $tags
            ];
        }
        
        $this->metrics[$key]['values'][] = $value;
        $this->metrics[$key]['timestamp'] = time();
        
        logger()->metric($name, $value, 'histogram', $tags);
    }
    
    /**
     * Mesure l'exécution d'une fonction
     */
    public function time(string $name, callable $callback, array $tags = [])
    {
        $start = microtime(true);
        $result = $callback();
        $duration = microtime(true) - $start;
        
        $this->timing($name, $duration, $tags);
        
        return $result;
    }
    
    /**
     * Métriques métier spécifiques à TutorMatch
     */
    public function recordUserLogin(int $userId, string $userRole): void
    {
        $this->increment('user.login.total', 1, ['role' => $userRole]);
        logger()->userAction('login', $userId, ['role' => $userRole]);
    }
    
    public function recordAssignmentCreated(string $algorithm, float $executionTime): void
    {
        $this->increment('assignment.created.total', 1, ['algorithm' => $algorithm]);
        $this->timing('assignment.algorithm.execution_time', $executionTime, ['algorithm' => $algorithm]);
    }
    
    public function recordAPIRequest(string $endpoint, string $method, int $statusCode, float $duration): void
    {
        $tags = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => (string)$statusCode
        ];
        
        $this->increment('api.requests.total', 1, $tags);
        $this->timing('api.request.duration', $duration, $tags);
        
        if ($statusCode >= 400) {
            $this->increment('api.errors.total', 1, $tags);
        }
    }
    
    public function recordDatabaseQuery(string $table, string $operation, float $duration): void
    {
        $tags = ['table' => $table, 'operation' => $operation];
        
        $this->increment('database.queries.total', 1, $tags);
        $this->timing('database.query.duration', $duration, $tags);
        
        if ($duration > 1.0) { // Requête lente > 1 seconde
            $this->increment('database.slow_queries.total', 1, $tags);
            logger()->warning("Slow database query detected", [
                'table' => $table,
                'operation' => $operation,
                'duration' => $duration
            ]);
        }
    }
    
    public function recordCacheOperation(string $operation, bool $hit = null): void
    {
        $tags = ['operation' => $operation];
        
        $this->increment('cache.operations.total', 1, $tags);
        
        if ($hit !== null) {
            $result = $hit ? 'hit' : 'miss';
            $this->increment("cache.{$result}.total", 1, $tags);
        }
    }
    
    public function recordFileUpload(string $type, int $size): void
    {
        $tags = ['type' => $type];
        
        $this->increment('file.uploads.total', 1, $tags);
        $this->histogram('file.upload.size', $size, $tags);
    }
    
    /**
     * Métriques système
     */
    private function initSystemMetrics(): void
    {
        // Utilisation mémoire
        $this->gauge('system.memory.usage', memory_get_usage(true), ['type' => 'current']);
        $this->gauge('system.memory.peak', memory_get_peak_usage(true), ['type' => 'peak']);
        
        // Charge système (si disponible)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $this->gauge('system.load.1min', $load[0]);
            $this->gauge('system.load.5min', $load[1]);
            $this->gauge('system.load.15min', $load[2]);
        }
        
        // Informations PHP
        $this->gauge('php.version', phpversion(), ['type' => 'version']);
    }
    
    /**
     * Métriques de fin de requête
     */
    public function flushMetrics(): void
    {
        $endTime = microtime(true);
        $executionTime = $endTime - $this->startTime;
        
        // Métriques de requête
        $this->timing('request.execution_time', $executionTime);
        $this->gauge('system.memory.final', memory_get_usage(true));
        
        // Écrire les métriques dans un fichier dédié
        $this->writeMetricsToFile();
        
        // Nettoyer les métriques en mémoire
        $this->metrics = [];
        $this->counters = [];
    }
    
    /**
     * Retourne toutes les métriques collectées
     */
    public function getAllMetrics(): array
    {
        return [
            'counters' => $this->counters,
            'metrics' => $this->metrics,
            'timestamp' => time(),
            'hostname' => gethostname(),
            'pid' => getmypid()
        ];
    }
    
    /**
     * Retourne les métriques au format Prometheus
     */
    public function getPrometheusMetrics(): string
    {
        $output = [];
        
        // Compteurs
        foreach ($this->counters as $key => $value) {
            $output[] = "# TYPE {$key} counter";
            $output[] = "{$key} {$value}";
        }
        
        // Métriques
        foreach ($this->metrics as $key => $metric) {
            $type = $metric['type'];
            $value = $metric['value'];
            
            if ($type === 'histogram' && is_array($value)) {
                $value = count($value); // Nombre d'échantillons
            }
            
            $output[] = "# TYPE {$key} {$type}";
            $output[] = "{$key} {$value}";
        }
        
        return implode("\n", $output) . "\n";
    }
    
    /**
     * Écrit les métriques dans un fichier pour analyse
     */
    private function writeMetricsToFile(): void
    {
        $metricsPath = ROOT_DIR . '/logs/metrics';
        if (!is_dir($metricsPath)) {
            mkdir($metricsPath, 0755, true);
        }
        
        $filename = $metricsPath . '/metrics-' . date('Y-m-d') . '.json';
        $metrics = $this->getAllMetrics();
        
        $line = json_encode($metrics) . "\n";
        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Construit une clé de métrique avec tags
     */
    private function buildMetricKey(string $name, array $tags = []): string
    {
        if (empty($tags)) {
            return $name;
        }
        
        $tagString = [];
        foreach ($tags as $key => $value) {
            $tagString[] = "{$key}={$value}";
        }
        
        return $name . '{' . implode(',', $tagString) . '}';
    }
    
    /**
     * Health check du système
     */
    public function getHealthStatus(): array
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'checks' => []
        ];
        
        // Check base de données
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, 
                DB_USER, 
                DB_PASS
            );
            $status['checks']['database'] = 'healthy';
        } catch (Exception $e) {
            $status['checks']['database'] = 'unhealthy';
            $status['status'] = 'unhealthy';
        }
        
        // Check Redis (optionnel)
        if (class_exists('Redis')) {
            try {
                $redis = new Redis();
                $redisHost = defined('REDIS_HOST') ? REDIS_HOST : 'localhost';
                $redisPort = defined('REDIS_PORT') ? REDIS_PORT : 6379;
                $redis->connect($redisHost, $redisPort);
                $redis->ping();
                $status['checks']['redis'] = 'healthy';
                $redis->close();
            } catch (Exception $e) {
                $status['checks']['redis'] = 'unhealthy';
                $status['status'] = 'degraded';
            }
        }
        
        // Check espace disque
        $freeSpace = disk_free_space(ROOT_DIR);
        $totalSpace = disk_total_space(ROOT_DIR);
        $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usagePercent > 90) {
            $status['checks']['disk_space'] = 'critical';
            $status['status'] = 'unhealthy';
        } elseif ($usagePercent > 80) {
            $status['checks']['disk_space'] = 'warning';
            if ($status['status'] === 'healthy') {
                $status['status'] = 'degraded';
            }
        } else {
            $status['checks']['disk_space'] = 'healthy';
        }
        
        return $status;
    }
}

// Fonction globale pour faciliter l'utilisation
function monitor(): Monitor
{
    return Monitor::getInstance();
}

// Middleware pour mesurer automatiquement les requêtes API
function monitor_api_request(): void
{
    $start = microtime(true);
    
    register_shutdown_function(function() use ($start) {
        $duration = microtime(true) - $start;
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $status = http_response_code() ?: 200;
        
        monitor()->recordAPIRequest($endpoint, $method, $status, $duration);
    });
}