<?php
/**
 * Script simple pour vérifier et diagnostiquer Redis
 */

echo "<h2>🔍 Diagnostic Redis TutorMatch</h2>\n";
echo "<pre>\n";

// 1. Vérifier l'extension Redis
echo "=== Extension Redis ===\n";
if (extension_loaded('redis')) {
    echo "✅ Extension Redis chargée\n";
    echo "Version: " . phpversion('redis') . "\n";
} else {
    echo "❌ Extension Redis non disponible\n";
    echo "💡 Installer php-redis: apt-get install php-redis\n";
    echo "   Ou télécharger: https://pecl.php.net/package/redis\n";
    exit;
}

// 2. Test de connexion basique
echo "\n=== Test Connexion ===\n";
try {
    $redis = new Redis();
    
    echo "🔄 Tentative de connexion à 127.0.0.1:6379...\n";
    $connected = $redis->connect('127.0.0.1', 6379, 2.0);
    
    if ($connected) {
        echo "✅ Connexion réussie!\n";
        
        // Test ping
        $pong = $redis->ping();
        echo "🏓 Ping: " . ($pong ? "✅ PONG" : "❌ Pas de réponse") . "\n";
        
        // Informations serveur
        $info = $redis->info('server');
        echo "📊 Version Redis: " . ($info['redis_version'] ?? 'N/A') . "\n";
        echo "📊 Mode: " . ($info['redis_mode'] ?? 'N/A') . "\n";
        echo "📊 OS: " . ($info['os'] ?? 'N/A') . "\n";
        
        // Test basique set/get
        echo "\n=== Test Opérations ===\n";
        $testKey = 'test_' . time();
        $testValue = 'Hello Redis!';
        
        $setResult = $redis->set($testKey, $testValue);
        echo "SET: " . ($setResult ? "✅" : "❌") . "\n";
        
        $getValue = $redis->get($testKey);
        echo "GET: " . ($getValue === $testValue ? "✅" : "❌") . " ($getValue)\n";
        
        $delResult = $redis->del($testKey);
        echo "DEL: " . ($delResult ? "✅" : "❌") . "\n";
        
        // Statistiques
        $infoStats = $redis->info('stats');
        echo "\n=== Statistiques ===\n";
        echo "Connexions totales: " . ($infoStats['total_connections_received'] ?? 0) . "\n";
        echo "Commandes traitées: " . ($infoStats['total_commands_processed'] ?? 0) . "\n";
        echo "Keyspace hits: " . ($infoStats['keyspace_hits'] ?? 0) . "\n";
        echo "Keyspace misses: " . ($infoStats['keyspace_misses'] ?? 0) . "\n";
        
        // Mémoire
        $infoMemory = $redis->info('memory');
        echo "\n=== Mémoire ===\n";
        echo "Mémoire utilisée: " . formatBytes($infoMemory['used_memory'] ?? 0) . "\n";
        echo "Mémoire pic: " . formatBytes($infoMemory['used_memory_peak'] ?? 0) . "\n";
        
        $redis->close();
        
    } else {
        echo "❌ Connexion échouée\n";
        throw new Exception("Impossible de se connecter à Redis");
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n\n";
    
    echo "🔧 SOLUTIONS:\n\n";
    
    echo "1️⃣ INSTALLER REDIS:\n";
    echo "   Windows: choco install redis-64\n";
    echo "   Docker:  docker run -d -p 6379:6379 redis\n";
    echo "   WSL:     sudo apt install redis-server\n\n";
    
    echo "2️⃣ DÉMARRER REDIS:\n";
    echo "   Windows: redis-server\n";
    echo "   Docker:  docker start redis\n";
    echo "   WSL:     sudo service redis-server start\n\n";
    
    echo "3️⃣ VÉRIFIER LE PORT:\n";
    echo "   netstat -an | findstr 6379\n";
    echo "   Ou: telnet 127.0.0.1 6379\n\n";
    
    echo "4️⃣ CONFIGURATION:\n";
    echo "   Vérifier le fichier .env\n";
    echo "   REDIS_HOST=127.0.0.1\n";
    echo "   REDIS_PORT=6379\n\n";
}

// 3. Recommandations
echo "\n=== 💡 RECOMMANDATIONS ===\n";
echo "✅ Mode fallback: L'application fonctionne sans Redis\n";
echo "⚡ Performance: Redis améliore drastiquement les performances\n";
echo "📈 Monitoring: Utiliser /api/monitoring/metrics.php\n";
echo "🧪 Tests: Utiliser /test_redis_cache.php\n";

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

echo "</pre>\n";
?>

<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; padding: 20px; }
pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); line-height: 1.6; }
h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
</style>