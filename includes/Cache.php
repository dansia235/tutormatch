<?php
/**
 * Système de cache Redis pour TutorMatch
 * Gestion centralisée du cache avec fallback et monitoring
 */
class Cache
{
    private static ?Cache $instance = null;
    private ?Redis $redis = null;
    private bool $enabled = false;
    private array $config;
    private array $stats = ['hits' => 0, 'misses' => 0, 'errors' => 0];
    
    private function __construct()
    {
        $this->config = [
            'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
            'timeout' => (float)($_ENV['REDIS_TIMEOUT'] ?? 2.0),
            'prefix' => $_ENV['REDIS_PREFIX'] ?? 'tutormatch:',
            'default_ttl' => (int)($_ENV['CACHE_DEFAULT_TTL'] ?? 3600), // 1 heure
        ];
        
        $this->connect();
    }
    
    public static function getInstance(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Établit la connexion Redis
     */
    private function connect(): void
    {
        if (!class_exists('Redis')) {
            logger()->warning('Redis extension not available, cache disabled');
            return;
        }
        
        try {
            $this->redis = new Redis();
            $connected = $this->redis->connect(
                $this->config['host'], 
                $this->config['port'], 
                $this->config['timeout']
            );
            
            if (!$connected) {
                throw new Exception('Failed to connect to Redis');
            }
            
            if ($this->config['password']) {
                $this->redis->auth($this->config['password']);
            }
            
            $this->redis->select($this->config['database']);
            $this->enabled = true;
            
            logger()->info('Redis cache enabled', [
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'database' => $this->config['database']
            ]);
            
        } catch (Exception $e) {
            logger()->error('Failed to connect to Redis: ' . $e->getMessage(), [
                'host' => $this->config['host'],
                'port' => $this->config['port']
            ]);
            $this->enabled = false;
        }
    }
    
    /**
     * Récupère une valeur du cache
     */
    public function get(string $key, $default = null)
    {
        if (!$this->enabled) {
            $this->stats['misses']++;
            monitor()->recordCacheOperation('get', false);
            return $default;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $value = $this->redis->get($fullKey);
            
            if ($value === false) {
                $this->stats['misses']++;
                monitor()->recordCacheOperation('get', false);
                return $default;
            }
            
            $this->stats['hits']++;
            monitor()->recordCacheOperation('get', true);
            
            // Désérialiser si nécessaire
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            
            return $value;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache get error: ' . $e->getMessage(), ['key' => $key]);
            monitor()->recordCacheOperation('get', false);
            return $default;
        }
    }
    
    /**
     * Stocke une valeur dans le cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $ttl = $ttl ?? $this->config['default_ttl'];
            
            // Sérialiser si nécessaire
            if (!is_string($value)) {
                $value = json_encode($value);
            }
            
            $result = $this->redis->setex($fullKey, $ttl, $value);
            
            monitor()->recordCacheOperation('set');
            logger()->debug('Cache set', ['key' => $key, 'ttl' => $ttl]);
            
            return $result;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache set error: ' . $e->getMessage(), ['key' => $key]);
            monitor()->recordCacheOperation('set');
            return false;
        }
    }
    
    /**
     * Supprime une valeur du cache
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $result = $this->redis->del($fullKey) > 0;
            
            monitor()->recordCacheOperation('delete');
            logger()->debug('Cache delete', ['key' => $key]);
            
            return $result;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache delete error: ' . $e->getMessage(), ['key' => $key]);
            monitor()->recordCacheOperation('delete');
            return false;
        }
    }
    
    /**
     * Vérifie si une clé existe
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            return $this->redis->exists($fullKey) > 0;
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache has error: ' . $e->getMessage(), ['key' => $key]);
            return false;
        }
    }
    
    /**
     * Vide complètement le cache
     */
    public function flush(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $result = $this->redis->flushDB();
            monitor()->recordCacheOperation('flush');
            logger()->info('Cache flushed');
            return $result;
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache flush error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère ou génère une valeur avec callback
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        // Générer la valeur
        $value = $callback();
        
        // La stocker en cache
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Incrémente une valeur atomiquement
     */
    public function increment(string $key, int $value = 1): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $result = $this->redis->incrBy($fullKey, $value);
            monitor()->recordCacheOperation('increment');
            return $result;
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache increment error: ' . $e->getMessage(), ['key' => $key]);
            return 0;
        }
    }
    
    /**
     * Décrémente une valeur atomiquement
     */
    public function decrement(string $key, int $value = 1): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $result = $this->redis->decrBy($fullKey, $value);
            monitor()->recordCacheOperation('decrement');
            return $result;
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache decrement error: ' . $e->getMessage(), ['key' => $key]);
            return 0;
        }
    }
    
    /**
     * Ajoute un élément à une liste
     */
    public function listPush(string $key, $value, bool $left = false): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $serializedValue = is_string($value) ? $value : json_encode($value);
            
            $result = $left ? 
                $this->redis->lPush($fullKey, $serializedValue) : 
                $this->redis->rPush($fullKey, $serializedValue);
                
            monitor()->recordCacheOperation('list_push');
            return $result;
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache list push error: ' . $e->getMessage(), ['key' => $key]);
            return 0;
        }
    }
    
    /**
     * Retire un élément d'une liste
     */
    public function listPop(string $key, bool $left = false)
    {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            
            $value = $left ? 
                $this->redis->lPop($fullKey) : 
                $this->redis->rPop($fullKey);
                
            if ($value === false) {
                return null;
            }
            
            monitor()->recordCacheOperation('list_pop');
            
            // Désérialiser si nécessaire
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            
            return $value;
        } catch (Exception $e) {
            $this->stats['errors']++;
            logger()->error('Cache list pop error: ' . $e->getMessage(), ['key' => $key]);
            return null;
        }
    }
    
    /**
     * Stockage de sessions en Redis
     */
    public function setSession(string $sessionId, array $data, int $ttl = 3600): bool
    {
        $key = "session:{$sessionId}";
        return $this->set($key, $data, $ttl);
    }
    
    public function getSession(string $sessionId): ?array
    {
        $key = "session:{$sessionId}";
        return $this->get($key);
    }
    
    public function deleteSession(string $sessionId): bool
    {
        $key = "session:{$sessionId}";
        return $this->delete($key);
    }
    
    /**
     * Cache spécialisé pour TutorMatch
     */
    
    // Cache des listes d'étudiants
    public function cacheStudentsList(array $filters, array $students, int $ttl = 300): void
    {
        $key = 'students:list:' . md5(serialize($filters));
        $this->set($key, $students, $ttl);
    }
    
    public function getCachedStudentsList(array $filters): ?array
    {
        $key = 'students:list:' . md5(serialize($filters));
        return $this->get($key);
    }
    
    // Cache des résultats d'algorithmes d'affectation
    public function cacheAssignmentResult(string $algorithm, array $parameters, array $result): void
    {
        $key = "assignment:{$algorithm}:" . md5(serialize($parameters));
        $this->set($key, $result, 1800); // 30 minutes
    }
    
    public function getCachedAssignmentResult(string $algorithm, array $parameters): ?array
    {
        $key = "assignment:{$algorithm}:" . md5(serialize($parameters));
        return $this->get($key);
    }
    
    // Cache des métriques de dashboard
    public function cacheDashboardStats(string $userRole, array $stats): void
    {
        $key = "dashboard:stats:{$userRole}";
        $this->set($key, $stats, 600); // 10 minutes
    }
    
    public function getCachedDashboardStats(string $userRole): ?array
    {
        $key = "dashboard:stats:{$userRole}";
        return $this->get($key);
    }
    
    // Rate limiting
    public function checkRateLimit(string $identifier, int $maxRequests, int $windowSeconds): bool
    {
        $key = "ratelimit:{$identifier}";
        $current = $this->increment($key);
        
        if ($current === 1) {
            // Premier appel, définir l'expiration
            $this->redis->expire($this->buildKey($key), $windowSeconds);
        }
        
        return $current <= $maxRequests;
    }
    
    /**
     * Construit la clé complète avec préfixe
     */
    private function buildKey(string $key): string
    {
        return $this->config['prefix'] . $key;
    }
    
    /**
     * Retourne les statistiques du cache
     */
    public function getStats(): array
    {
        $stats = $this->stats;
        $stats['enabled'] = $this->enabled;
        
        if ($this->enabled && $this->redis) {
            try {
                $info = $this->redis->info();
                $stats['redis'] = [
                    'used_memory' => $info['used_memory'] ?? 0,
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                ];
            } catch (Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * Test de connexion
     */
    public function ping(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            return $this->redis->ping() === '+PONG';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Nettoie les connexions en fin de script
     */
    public function __destruct()
    {
        if ($this->redis) {
            try {
                $this->redis->close();
            } catch (Exception $e) {
                // Ignorer les erreurs de fermeture
            }
        }
    }
}

// Fonction globale pour faciliter l'utilisation
function cache(): Cache
{
    return Cache::getInstance();
}

// Wrapper pour cache remember simple
function cache_remember(string $key, callable $callback, ?int $ttl = null)
{
    return Cache::getInstance()->remember($key, $callback, $ttl);
}