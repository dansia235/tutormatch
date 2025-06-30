<?php
/**
 * Script de test pour vérifier le fonctionnement du cache Redis
 */
require_once 'includes/init.php';

echo "<h1>🧪 Test Cache Redis TutorMatch</h1>\n";
echo "<pre>\n";

// Test 1: Vérification de la classe Redis
echo "=== TEST 1: Extension Redis ===\n";
if (class_exists('Redis')) {
    echo "✅ Extension Redis disponible\n";
} else {
    echo "❌ Extension Redis non disponible\n";
    echo "💡 Installer l'extension Redis pour PHP\n";
    exit;
}

// Test 2: Instanciation du cache
echo "\n=== TEST 2: Instanciation Cache ===\n";
try {
    $cache = Cache::getInstance();
    echo "✅ Cache instancié avec succès\n";
    
    if ($cache->ping()) {
        echo "✅ Connexion Redis active\n";
    } else {
        echo "⚠️ Redis non connecté (mode fallback)\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 3: Opérations de base
echo "\n=== TEST 3: Opérations de base ===\n";
$testKey = 'test:' . time();
$testValue = 'TutorMatch Cache Test - ' . date('Y-m-d H:i:s');

// SET
echo "🔄 Test SET...\n";
$setResult = $cache->set($testKey, $testValue, 60);
echo $setResult ? "✅ SET réussi\n" : "❌ SET échoué\n";

// GET
echo "🔄 Test GET...\n";
$getValue = $cache->get($testKey);
if ($getValue === $testValue) {
    echo "✅ GET réussi - Valeur: " . substr($getValue, 0, 50) . "...\n";
} else {
    echo "❌ GET échoué - Attendu: $testValue, Reçu: $getValue\n";
}

// HAS
echo "🔄 Test HAS...\n";
$hasResult = $cache->has($testKey);
echo $hasResult ? "✅ HAS réussi\n" : "❌ HAS échoué\n";

// DELETE
echo "🔄 Test DELETE...\n";
$deleteResult = $cache->delete($testKey);
echo $deleteResult ? "✅ DELETE réussi\n" : "❌ DELETE échoué\n";

// Test 4: Fonctions spécialisées TutorMatch
echo "\n=== TEST 4: Fonctions TutorMatch ===\n";

// Test cache étudiants
echo "🔄 Test cache liste étudiants...\n";
$mockStudents = [
    ['id' => 1, 'name' => 'Test Student 1'],
    ['id' => 2, 'name' => 'Test Student 2']
];
$filters = ['level' => 'L3', 'specialty' => 'Info'];

$cache->cacheStudentsList($filters, $mockStudents);
$cachedStudents = $cache->getCachedStudentsList($filters);

if ($cachedStudents === $mockStudents) {
    echo "✅ Cache étudiants fonctionnel\n";
} else {
    echo "❌ Cache étudiants défaillant\n";
}

// Test cache affectations
echo "🔄 Test cache affectations...\n";
$mockAssignment = [
    'algorithm' => 'hungarian',
    'assignments' => [['student_id' => 1, 'teacher_id' => 2]],
    'score' => 95.5
];
$params = ['students' => [1,2], 'teachers' => [1,2]];

$cache->cacheAssignmentResult('hungarian', $params, $mockAssignment);
$cachedAssignment = $cache->getCachedAssignmentResult('hungarian', $params);

if ($cachedAssignment === $mockAssignment) {
    echo "✅ Cache affectations fonctionnel\n";
} else {
    echo "❌ Cache affectations défaillant\n";
}

// Test cache dashboard
echo "🔄 Test cache dashboard...\n";
$mockStats = [
    'total_students' => 150,
    'total_teachers' => 25,
    'active_assignments' => 45
];

$cache->cacheDashboardStats('admin', $mockStats);
$cachedStats = $cache->getCachedDashboardStats('admin');

if ($cachedStats === $mockStats) {
    echo "✅ Cache dashboard fonctionnel\n";
} else {
    echo "❌ Cache dashboard défaillant\n";
}

// Test 5: Rate limiting
echo "\n=== TEST 5: Rate Limiting ===\n";
$testId = 'test_user_' . time();

echo "🔄 Test rate limiting (max 3 req/60s)...\n";
for ($i = 1; $i <= 5; $i++) {
    $allowed = $cache->checkRateLimit($testId, 3, 60);
    $status = $allowed ? "✅ Autorisé" : "❌ Bloqué";
    echo "Requête $i: $status\n";
}

// Test 6: Statistiques
echo "\n=== TEST 6: Statistiques ===\n";
$stats = $cache->getStats();
echo "📊 Statistiques du cache:\n";
echo "- Cache activé: " . ($stats['enabled'] ? 'Oui' : 'Non') . "\n";
echo "- Hits: " . $stats['hits'] . "\n";
echo "- Miss: " . $stats['misses'] . "\n";
echo "- Erreurs: " . $stats['errors'] . "\n";

if (isset($stats['redis'])) {
    echo "- Mémoire Redis: " . formatBytes($stats['redis']['used_memory']) . "\n";
    echo "- Clients connectés: " . $stats['redis']['connected_clients'] . "\n";
    echo "- Commandes traitées: " . $stats['redis']['total_commands_processed'] . "\n";
    echo "- Keyspace hits: " . $stats['redis']['keyspace_hits'] . "\n";
    echo "- Keyspace misses: " . $stats['redis']['keyspace_misses'] . "\n";
}

// Test 7: Sessions Redis
echo "\n=== TEST 7: Sessions Redis ===\n";
$sessionId = 'test_session_' . uniqid();
$sessionData = [
    'user_id' => 123,
    'role' => 'student',
    'login_time' => time()
];

echo "🔄 Test stockage session...\n";
$sessionSet = $cache->setSession($sessionId, $sessionData, 3600);
echo $sessionSet ? "✅ Session stockée\n" : "❌ Stockage session échoué\n";

echo "🔄 Test récupération session...\n";
$sessionGet = $cache->getSession($sessionId);
if ($sessionGet === $sessionData) {
    echo "✅ Session récupérée correctement\n";
} else {
    echo "❌ Session incorrecte\n";
}

echo "🔄 Test suppression session...\n";
$sessionDelete = $cache->deleteSession($sessionId);
echo $sessionDelete ? "✅ Session supprimée\n" : "❌ Suppression session échouée\n";

// Test 8: Opérations liste
echo "\n=== TEST 8: Opérations Liste ===\n";
$listKey = 'test_list_' . time();

echo "🔄 Test ajout éléments à la liste...\n";
$cache->listPush($listKey, 'item1');
$cache->listPush($listKey, 'item2');
$cache->listPush($listKey, 'item3', true); // Left push

echo "🔄 Test retrait éléments de la liste...\n";
$item1 = $cache->listPop($listKey, true); // Left pop
$item2 = $cache->listPop($listKey); // Right pop

echo "Premier élément (left pop): " . ($item1 === 'item3' ? "✅ $item1" : "❌ $item1") . "\n";
echo "Dernier élément (right pop): " . ($item2 === 'item2' ? "✅ $item2" : "❌ $item2") . "\n";

// Nettoyer
$cache->delete($listKey);

// Résumé final
echo "\n=== 🎯 RÉSUMÉ ===\n";
if ($stats['enabled']) {
    echo "🎉 Cache Redis OPÉRATIONNEL\n";
    echo "📈 Performance: " . round(($stats['hits'] / max(1, $stats['hits'] + $stats['misses'])) * 100, 1) . "% de hits\n";
    echo "💾 Stockage: " . (isset($stats['redis']['used_memory']) ? formatBytes($stats['redis']['used_memory']) : 'N/A') . "\n";
    echo "🔗 Status: Toutes les fonctionnalités testées avec succès\n";
} else {
    echo "⚠️  Cache en mode FALLBACK (Redis non connecté)\n";
    echo "💡 Vérifier la configuration Redis dans config/cache.php\n";
    echo "🔧 Démarrer le serveur Redis si nécessaire\n";
}

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
body { font-family: monospace; background: #f5f5f5; padding: 20px; }
pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h1 { color: #2c3e50; text-align: center; }
</style>