<?php
/**
 * Composant de pagination réutilisable
 * 
 * @param int $currentPage Page actuelle
 * @param int $totalPages Nombre total de pages
 * @param array $queryParams Paramètres GET additionnels à conserver
 * @param int $maxPages Nombre maximum de pages à afficher autour de la page courante
 */

function renderPagination($currentPage, $totalPages, $queryParams = [], $maxPages = 5) {
    if ($totalPages <= 1) {
        return;
    }
    
    // Construire la chaîne de paramètres
    $queryString = '';
    if (!empty($queryParams)) {
        $queryString = '&' . http_build_query($queryParams);
    }
    ?>
    
    <style>
        /* Forcer le texte blanc sur les boutons de pagination actifs */
        .pagination .page-item.active .page-link {
            color: #fff !important;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
    
    <nav aria-label="Navigation des pages">
        <ul class="pagination justify-content-center mb-0">
            <!-- Bouton Précédent -->
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $queryString; ?>" 
                   aria-label="Précédent" <?php echo $currentPage <= 1 ? 'tabindex="-1"' : ''; ?>>
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            
            <?php
            // Calculer les pages à afficher
            $halfPages = floor($maxPages / 2);
            $startPage = max(1, $currentPage - $halfPages);
            $endPage = min($totalPages, $currentPage + $halfPages);
            
            // Ajuster si on est proche du début ou de la fin
            if ($currentPage <= $halfPages) {
                $endPage = min($totalPages, $maxPages);
            } elseif ($currentPage > $totalPages - $halfPages) {
                $startPage = max(1, $totalPages - $maxPages + 1);
            }
            
            // Première page si nécessaire
            if ($startPage > 1) {
                ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1<?php echo $queryString; ?>">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif;
            }
            
            // Pages centrales
            for ($i = $startPage; $i <= $endPage; $i++) {
                ?>
                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                    <?php if ($i == $currentPage): ?>
                        <span class="page-link text-white"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryString; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                </li>
                <?php
            }
            
            // Dernière page si nécessaire
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php
                }
                ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>"><?php echo $totalPages; ?></a>
                </li>
                <?php
            }
            ?>
            
            <!-- Bouton Suivant -->
            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $queryString; ?>" 
                   aria-label="Suivant" <?php echo $currentPage >= $totalPages ? 'tabindex="-1"' : ''; ?>>
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php
}

/**
 * Affiche les informations de pagination
 * 
 * @param int $currentPage Page actuelle
 * @param int $perPage Nombre d'éléments par page
 * @param int $total Nombre total d'éléments
 */
function renderPaginationInfo($currentPage, $perPage, $total) {
    $start = ($currentPage - 1) * $perPage + 1;
    $end = min($currentPage * $perPage, $total);
    
    if ($total == 0) {
        $start = 0;
    }
    ?>
    <div class="text-center mt-2 text-muted">
        Affichage de <?php echo $start; ?> à <?php echo $end; ?> sur <?php echo $total; ?> 
        élément<?php echo $total > 1 ? 's' : ''; ?>
    </div>
    <?php
}
?>