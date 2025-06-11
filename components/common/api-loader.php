<?php
/**
 * Composant API Loader
 * Affiche un élément avec chargement de données depuis l'API
 * 
 * @param string $endpoint Point de terminaison API
 * @param string $method Méthode HTTP (GET, POST, etc.)
 * @param array $params Paramètres de la requête (optionnel)
 * @param bool $autoload Charger automatiquement les données (optionnel, défaut: true)
 * @param string $targetId ID de l'élément cible à remplir avec les données (optionnel)
 * @param string $loadingText Texte à afficher pendant le chargement (optionnel)
 * @param string $errorText Texte à afficher en cas d'erreur (optionnel)
 * @param string $emptyText Texte à afficher si aucune donnée n'est retournée (optionnel)
 * @param string $loadingClass Classes CSS pour l'état de chargement (optionnel)
 * @param string $errorClass Classes CSS pour l'état d'erreur (optionnel)
 * @param string $containerClass Classes CSS pour le conteneur (optionnel)
 * @param string $contentClass Classes CSS pour le contenu (optionnel)
 * @param string $id ID de l'élément (optionnel)
 */

// Valeurs par défaut
$endpoint = $endpoint ?? '';
$method = $method ?? 'GET';
$params = $params ?? [];
$autoload = $autoload ?? true;
$targetId = $targetId ?? '';
$loadingText = $loadingText ?? 'Chargement...';
$errorText = $errorText ?? 'Une erreur est survenue lors du chargement des données';
$emptyText = $emptyText ?? 'Aucune donnée disponible';
$loadingClass = $loadingClass ?? 'bg-gray-50 p-4 rounded animate-pulse';
$errorClass = $errorClass ?? 'bg-danger-50 text-danger-700 p-4 rounded border border-danger-200';
$containerClass = $containerClass ?? '';
$contentClass = $contentClass ?? '';
$id = $id ?? 'api-loader-' . uniqid();

// Convertir les paramètres en JSON
$paramsJson = htmlspecialchars(json_encode($params), ENT_QUOTES, 'UTF-8');

?>

<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="api-loader <?php echo htmlspecialchars($containerClass); ?>"
    data-controller="api"
    data-api-endpoint-value="<?php echo htmlspecialchars($endpoint); ?>"
    data-api-method-value="<?php echo htmlspecialchars($method); ?>"
    data-api-params-value='<?php echo $paramsJson; ?>'
    data-api-autoload-value="<?php echo $autoload ? 'true' : 'false'; ?>"
    <?php if ($targetId): ?>
    data-api-target-value="<?php echo htmlspecialchars($targetId); ?>"
    <?php endif; ?>
>
    <!-- Loading state -->
    <div data-api-target="loading" class="<?php echo htmlspecialchars($loadingClass); ?> <?php echo $autoload ? '' : 'hidden'; ?>">
        <?php echo $loadingText; ?>
    </div>

    <!-- Error state -->
    <div data-api-target="error" class="<?php echo htmlspecialchars($errorClass); ?> hidden">
        <?php echo $errorText; ?>
    </div>

    <!-- Content container -->
    <div data-api-target="content" class="<?php echo htmlspecialchars($contentClass); ?> <?php echo $autoload ? 'hidden' : ''; ?>">
        <!-- Content will be populated by controller -->
        <?php if (isset($slot)) echo $slot; ?>
    </div>
</div>