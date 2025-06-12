<?php
/**
 * Générateur de PDF pour l'application TutorMatch
 * Utilise la bibliothèque TCPDF pour générer des documents PDF
 */

// Définir le chemin de la bibliothèque TCPDF
define('TCPDF_PATH', '/mnt/c/xampp/phpMyAdmin/vendor/tecnickcom/tcpdf/');

/**
 * Génère un PDF à partir de HTML
 * 
 * @param string $html Contenu HTML à convertir en PDF
 * @param string $filename Nom du fichier PDF à générer
 * @param string $orientation Orientation de la page (P pour portrait, L pour paysage)
 * @param boolean $download True pour télécharger, false pour afficher dans le navigateur
 * @return void
 */
function generatePDF($html, $filename = 'document.pdf', $orientation = 'P', $download = true) {
    // Activer l'affichage des erreurs pour le débogage
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Journal de débogage
    error_log("Tentative de génération de PDF : " . $filename);
    error_log("Chemin TCPDF : " . TCPDF_PATH);
    error_log("Fichier TCPDF existe : " . (file_exists(TCPDF_PATH . 'tcpdf.php') ? 'Oui' : 'Non'));
    
    // Vérifier si TCPDF est disponible
    if (file_exists(TCPDF_PATH . 'tcpdf.php')) {
        error_log("TCPDF trouvé, tentative d'inclusion...");
        
        try {
            // Utiliser TCPDF
            require_once(TCPDF_PATH . 'tcpdf.php');
            error_log("TCPDF inclus avec succès");
            
            // Créer une nouvelle instance de TCPDF
            error_log("Création d'une instance TCPDF...");
            $pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
            error_log("Instance TCPDF créée avec succès");

        // Configurer le document
        $pdf->SetCreator('TutorMatch');
        $pdf->SetAuthor('TutorMatch');
        $pdf->SetTitle($filename);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Ajouter une page
        $pdf->AddPage();

        // Ajouter le contenu HTML
        $pdf->writeHTML($html, true, false, true, false, '');

        // Sortie du PDF
        error_log("Génération du PDF terminée, envoi...");
        if ($download) {
            $pdf->Output($filename, 'D'); // Download
        } else {
            $pdf->Output($filename, 'I'); // Inline display
        }
        error_log("PDF envoyé avec succès");
        exit;
    } catch (Exception $e) {
        error_log("Erreur lors de la génération du PDF avec TCPDF: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        // Continuer vers la méthode de secours
    }
    } else {
        // Essayer d'utiliser la fonction native PHP pour convertir HTML en PDF (si disponible)
        if (extension_loaded('dompdf')) {
            // Utiliser DomPDF
            require_once('dompdf/autoload.inc.php');
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', $orientation == 'P' ? 'portrait' : 'landscape');
            $dompdf->render();
            
            if ($download) {
                $dompdf->stream($filename, ['Attachment' => true]);
            } else {
                $dompdf->stream($filename, ['Attachment' => false]);
            }
            exit;
        } else {
            error_log("Aucune bibliothèque PDF n'est disponible, utilisation du mode de secours HTML");
            // Si aucune bibliothèque PDF n'est disponible, retourner le HTML
            header('Content-Type: text/html; charset=utf-8');
            if ($download) {
                header('Content-Disposition: attachment; filename="' . basename($filename, '.pdf') . '.html"');
            }

            // Ajouter un message d'avertissement
            $warningHtml = '
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 5px;">
                <strong>Note:</strong> La génération directe de PDF n\'est pas disponible. Veuillez utiliser la fonction d\'impression 
                de votre navigateur (Ctrl+P ou Cmd+P) et sélectionner "Enregistrer au format PDF" comme destination d\'impression.
            </div>
            ';
            
            // Insérer l'avertissement au début du document
            $html = preg_replace('/<body[^>]*>/', '$0' . $warningHtml, $html);
            
            echo $html;
            exit;
        }
    }
}

/**
 * Vérifie si la génération de PDF est disponible
 * 
 * @return boolean True si la génération de PDF est disponible
 */
function isPdfGenerationAvailable() {
    return file_exists(TCPDF_PATH . 'tcpdf.php') || extension_loaded('dompdf');
}