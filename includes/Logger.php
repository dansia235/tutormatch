<?php
/**
 * Système de logging structuré pour TutorMatch
 * Support des logs JSON avec niveaux, contexte et métriques
 */
class Logger
{
    // Niveaux de log selon PSR-3
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';
    
    private static ?Logger $instance = null;
    private string $logPath;
    private string $appName;
    private array $context;
    
    private function __construct()
    {
        $this->logPath = ROOT_DIR . '/logs';
        $this->appName = 'TutorMatch';
        $this->context = [
            'app' => $this->appName,
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production'
        ];
        
        // Créer le dossier de logs s'il n'existe pas
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Log d'urgence système inutilisable
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Action doit être prise immédiatement
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Conditions critiques
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Erreurs runtime qui ne nécessitent pas d'action immédiate
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Événements exceptionnels qui ne sont pas des erreurs
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Événements normaux mais significatifs
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Événements informatifs intéressants
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Information de débogage détaillée
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log avec niveau arbitraire
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $logEntry = $this->formatLogEntry($level, $message, $context);
        $this->writeLog($level, $logEntry);
        
        // Envoyer à des services externes si configuré
        $this->sendToExternalServices($level, $logEntry);
    }
    
    /**
     * Log spécifique pour les performances
     */
    public function performance(string $operation, float $duration, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['duration_ms'] = round($duration * 1000, 2);
        $context['memory_usage'] = memory_get_usage(true);
        $context['peak_memory'] = memory_get_peak_usage(true);
        
        $this->info("Performance metric: {$operation}", $context);
    }
    
    /**
     * Log pour les métriques métier
     */
    public function metric(string $name, $value, string $unit = 'count', array $tags = []): void
    {
        $context = [
            'metric_name' => $name,
            'metric_value' => $value,
            'metric_unit' => $unit,
            'metric_tags' => $tags,
            'timestamp' => time()
        ];
        
        $this->info("Business metric: {$name}", $context);
    }
    
    /**
     * Log pour les événements de sécurité
     */
    public function security(string $event, array $context = []): void
    {
        $context['security_event'] = true;
        $context['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['request_id'] = $this->getRequestId();
        
        $this->warning("Security event: {$event}", $context);
    }
    
    /**
     * Log pour les erreurs d'API
     */
    public function apiError(string $endpoint, int $statusCode, string $error, array $context = []): void
    {
        $context['api_endpoint'] = $endpoint;
        $context['http_status'] = $statusCode;
        $context['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $context['request_id'] = $this->getRequestId();
        
        $this->error("API Error: {$endpoint} - {$error}", $context);
    }
    
    /**
     * Log pour les événements utilisateur
     */
    public function userAction(string $action, int $userId, array $context = []): void
    {
        $context['user_id'] = $userId;
        $context['user_action'] = $action;
        $context['session_id'] = session_id();
        $context['request_id'] = $this->getRequestId();
        
        $this->info("User action: {$action}", $context);
    }
    
    /**
     * Formate une entrée de log en JSON structuré
     */
    private function formatLogEntry(string $level, string $message, array $context = []): array
    {
        $entry = [
            'timestamp' => date('c'), // ISO 8601
            'level' => strtoupper($level),
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'extra' => [
                'request_id' => $this->getRequestId(),
                'process_id' => getmypid(),
                'memory_usage' => memory_get_usage(true),
                'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
            ]
        ];
        
        // Ajouter informations de la requête si disponible
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $entry['extra']['http'] = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
        }
        
        return $entry;
    }
    
    /**
     * Écrit le log dans le fichier approprié
     */
    private function writeLog(string $level, array $logEntry): void
    {
        $filename = $this->getLogFilename($level);
        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Écriture thread-safe
        file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);
        
        // Rotation des logs si nécessaire
        $this->rotateLogIfNeeded($filename);
    }
    
    /**
     * Détermine le nom du fichier de log
     */
    private function getLogFilename(string $level): string
    {
        $date = date('Y-m-d');
        
        // Logs d'erreur dans un fichier séparé
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            return "{$this->logPath}/error-{$date}.log";
        }
        
        // Logs de debug dans un fichier séparé
        if ($level === self::DEBUG) {
            return "{$this->logPath}/debug-{$date}.log";
        }
        
        // Autres logs dans le fichier principal
        return "{$this->logPath}/app-{$date}.log";
    }
    
    /**
     * Rotation des logs si le fichier devient trop gros
     */
    private function rotateLogIfNeeded(string $filename): void
    {
        if (!file_exists($filename)) {
            return;
        }
        
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        if (filesize($filename) > $maxSize) {
            $rotatedName = $filename . '.' . time();
            rename($filename, $rotatedName);
            
            // Compresser le fichier rotaté
            if (function_exists('gzencode')) {
                $content = file_get_contents($rotatedName);
                file_put_contents($rotatedName . '.gz', gzencode($content));
                unlink($rotatedName);
            }
        }
    }
    
    /**
     * Génère ou récupère l'ID de requête
     */
    private function getRequestId(): string
    {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req_', true);
        }
        
        return $requestId;
    }
    
    /**
     * Envoie les logs vers des services externes (Elasticsearch, Sentry, etc.)
     */
    private function sendToExternalServices(string $level, array $logEntry): void
    {
        // Envoyer seulement les erreurs importantes en externe en production
        if ($this->context['environment'] === 'production' && 
            in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            
            // Exemple : Sentry pour les erreurs
            if (isset($_ENV['SENTRY_DSN'])) {
                $this->sendToSentry($logEntry);
            }
            
            // Exemple : Elasticsearch pour l'analyse
            if (isset($_ENV['ELASTICSEARCH_URL'])) {
                $this->sendToElasticsearch($logEntry);
            }
        }
    }
    
    /**
     * Envoie vers Sentry (exemple)
     */
    private function sendToSentry(array $logEntry): void
    {
        // Implementation Sentry ici
        // Utiliser library officielle Sentry
    }
    
    /**
     * Envoie vers Elasticsearch (exemple)
     */
    private function sendToElasticsearch(array $logEntry): void
    {
        // Implementation Elasticsearch ici
        // Utiliser curl ou library officielle
    }
    
    /**
     * Nettoie les anciens logs (à exécuter périodiquement)
     */
    public function cleanOldLogs(int $daysToKeep = 30): void
    {
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        $files = glob($this->logPath . '/*.log*');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->info("Deleted old log file: " . basename($file));
            }
        }
    }
}

// Fonction globale pour faciliter l'utilisation
function logger(): Logger
{
    return Logger::getInstance();
}

// Fonctions de raccourci
function log_error(string $message, array $context = []): void
{
    Logger::getInstance()->error($message, $context);
}

function log_info(string $message, array $context = []): void
{
    Logger::getInstance()->info($message, $context);
}

function log_debug(string $message, array $context = []): void
{
    Logger::getInstance()->debug($message, $context);
}

function log_performance(string $operation, float $duration, array $context = []): void
{
    Logger::getInstance()->performance($operation, $duration, $context);
}

function log_security(string $event, array $context = []): void
{
    Logger::getInstance()->security($event, $context);
}

function log_user_action(string $action, int $userId, array $context = []): void
{
    Logger::getInstance()->userAction($action, $userId, $context);
}