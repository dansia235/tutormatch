<?php
/**
 * Helper pour faciliter les téléchargements de fichiers
 * 
 * Cette classe offre des méthodes pour faciliter le téléchargement de différents types de fichiers
 * en gérant correctement les en-têtes HTTP et le buffer de sortie.
 */
class DownloadHelper {
    
    /**
     * Force le téléchargement d'un contenu HTML
     *
     * @param string $html Le contenu HTML à télécharger
     * @param string $filename Nom du fichier à télécharger
     * @return void
     */
    public static function forceDownloadHTML($html, $filename) {
        // Nettoyer tout output précédent
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Définir les en-têtes pour forcer le téléchargement
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($html));
        
        // Envoyer le contenu et terminer
        echo $html;
        exit;
    }
    
    /**
     * Force le téléchargement d'un contenu CSV
     *
     * @param array $data Les données à convertir en CSV
     * @param string $filename Nom du fichier à télécharger
     * @param array $headers En-têtes des colonnes (optionnel)
     * @return void
     */
    public static function forceDownloadCSV($data, $filename, $headers = null) {
        // Nettoyer tout output précédent
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Définir les en-têtes pour forcer le téléchargement
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Ouvrir le flux de sortie
        $output = fopen('php://output', 'w');
        
        // Écrire l'en-tête UTF-8 BOM pour Excel
        fputs($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Écrire les en-têtes s'ils sont fournis
        if ($headers !== null) {
            fputcsv($output, $headers);
        }
        
        // Écrire les données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Fermer le flux et terminer
        fclose($output);
        exit;
    }
    
    /**
     * Vérifie si les en-têtes HTTP peuvent être envoyés
     * 
     * @return bool True si les en-têtes peuvent être envoyés, false sinon
     */
    public static function canSendHeaders() {
        return !headers_sent();
    }
    
    /**
     * Aide à déboguer les problèmes de téléchargement
     * 
     * @param string $message Message à journaliser
     * @return void
     */
    public static function debugLog($message) {
        if (defined('DEBUG') && DEBUG) {
            error_log('[Download Debug] ' . $message);
        }
    }
}