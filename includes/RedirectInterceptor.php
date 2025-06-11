<?php
/**
 * Classe pour intercepter et rediriger les redirections
 */
class RedirectInterceptor {
    /**
     * Ajuste les URLs de redirection pour les différentes vues et contrôleurs
     * @param string $url URL de redirection initiale
     * @return string URL de redirection ajustée
     */
    public static function adjustUrl($url) {
        // Vérifier s'il existe des mappings de redirection en session
        if (isset($_SESSION['redirect_mappings']) && is_array($_SESSION['redirect_mappings'])) {
            foreach ($_SESSION['redirect_mappings'] as $from => $to) {
                if (strpos($url, $from) !== false) {
                    return str_replace($from, $to, $url);
                }
            }
        }
        
        // Si l'URL concerne la gestion des utilisateurs, ajuster les chemins
        if (strpos($url, '/tutoring/admin/users/') !== false) {
            // Rediriger l'index vers la page des utilisateurs
            if (strpos($url, 'index.php') !== false) {
                return '/tutoring/views/admin/users.php';
            }
            
            // Rediriger create.php vers la page de création
            if (strpos($url, 'create.php') !== false) {
                return '/tutoring/views/admin/user/create.php';
            }
            
            // Rediriger edit.php vers la page d'édition
            if (strpos($url, 'edit.php') !== false) {
                // Préserver l'ID si présent
                $id = isset($_GET['id']) ? '?id=' . $_GET['id'] : '';
                return '/tutoring/views/admin/user/edit.php' . $id;
            }
            
            // Rediriger show.php vers la page de détails
            if (strpos($url, 'show.php') !== false) {
                // Préserver l'ID si présent
                $id = isset($_GET['id']) ? '?id=' . $_GET['id'] : '';
                return '/tutoring/views/admin/user/show.php' . $id;
            }
        }
        
        // Retourner l'URL originale si aucune règle ne s'applique
        return $url;
    }
    
    /**
     * Intercepte la fonction de redirection native
     * Cette fonction est appelée par le hook d'interception injecté dans la fonction redirect()
     */
    public static function interceptRedirect() {
        // Configurer une fonction pour intercepter les appels à redirect()
        $redirectHook = function($url) {
            // Ajuster l'URL
            $adjustedUrl = self::adjustUrl($url);
            
            // Rediriger vers l'URL ajustée
            header("Location: $adjustedUrl");
            exit;
        };
        
        // Attacher ce hook à un point où il peut être utilisé
        $_SESSION['redirect_hook'] = $redirectHook;
    }
}