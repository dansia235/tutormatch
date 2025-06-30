<?php
/**
 * Configuration du cache Redis pour TutorMatch
 */

// Configuration Redis par défaut
$redis_config = [
    'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
    'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
    'password' => $_ENV['REDIS_PASSWORD'] ?? null,
    'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
    'timeout' => (float)($_ENV['REDIS_TIMEOUT'] ?? 2.0),
    'prefix' => $_ENV['REDIS_PREFIX'] ?? 'tutormatch:',
    'default_ttl' => (int)($_ENV['CACHE_DEFAULT_TTL'] ?? 3600), // 1 heure
];

// TTLs spécifiques par type de contenu
$cache_ttls = [
    'students_list' => 300,        // 5 minutes pour les listes d'étudiants
    'assignment_result' => 1800,   // 30 minutes pour les résultats d'affectation
    'dashboard_stats' => 600,      // 10 minutes pour les stats du dashboard
    'user_session' => 3600,        // 1 heure pour les sessions utilisateur
    'api_response' => 300,         // 5 minutes pour les réponses API cachables
    'search_results' => 900,       // 15 minutes pour les résultats de recherche
];

// Configuration du rate limiting
$rate_limits = [
    'api_default' => ['requests' => 100, 'window' => 3600],      // 100 req/h par défaut
    'api_assignment' => ['requests' => 10, 'window' => 3600],    // 10 affectations/h
    'login_attempts' => ['requests' => 5, 'window' => 900],      // 5 tentatives/15min
    'search_queries' => ['requests' => 50, 'window' => 3600],    // 50 recherches/h
];

// Clés de cache importantes à réchauffer au démarrage
$cache_warmup_keys = [
    'dashboard:stats:admin',
    'dashboard:stats:teacher',
    'dashboard:stats:student',
    'students:list:' . md5(serialize([])), // Liste complète des étudiants
];

// Export de la configuration pour utilisation
return [
    'redis' => $redis_config,
    'ttls' => $cache_ttls,
    'rate_limits' => $rate_limits,
    'warmup_keys' => $cache_warmup_keys,
];