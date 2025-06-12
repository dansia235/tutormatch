<?php
/**
 * Générateur de PDF simple qui utilise les capacités natives de PHP
 */

/**
 * Génère un PDF simple à partir de données textuelles
 * 
 * @param string $title Titre du document
 * @param array $sections Sections du document (tableau de tableaux associatifs title => contenu)
 * @param string $filename Nom du fichier PDF à générer
 * @return void
 */
function generateSimplePDF($title, $sections, $filename = 'document.pdf') {
    // Créer un fichier temporaire
    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
    
    // Ouvrir le fichier en écriture
    $fp = fopen($tempFile, 'w');
    
    // Écrire l'en-tête du fichier PDF
    $header = "%PDF-1.4\n";
    fwrite($fp, $header);
    
    // Écrire les objets du PDF
    $offset = strlen($header);
    $objects = [];
    
    // Objet 1: Catalogue
    $objects[1] = ["offset" => $offset];
    $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    fwrite($fp, $obj1);
    $offset += strlen($obj1);
    
    // Objet 2: Pages
    $objects[2] = ["offset" => $offset];
    $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    fwrite($fp, $obj2);
    $offset += strlen($obj2);
    
    // Objet 3: Page
    $objects[3] = ["offset" => $offset];
    $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
    fwrite($fp, $obj3);
    $offset += strlen($obj3);
    
    // Objet 4: Police
    $objects[4] = ["offset" => $offset];
    $obj4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    fwrite($fp, $obj4);
    $offset += strlen($obj4);
    
    // Objet 5: Contenu
    $content = "BT\n/F1 12 Tf\n50 700 Td\n(" . addslashes($title) . ") Tj\nET\n";
    
    $y = 680;
    foreach ($sections as $section) {
        $content .= "BT\n/F1 10 Tf\n50 $y Td\n(" . addslashes($section['title']) . ") Tj\nET\n";
        $y -= 20;
        
        $lines = explode("\n", $section['content']);
        foreach ($lines as $line) {
            $content .= "BT\n/F1 8 Tf\n70 $y Td\n(" . addslashes($line) . ") Tj\nET\n";
            $y -= 15;
            
            if ($y < 50) {
                // Nouvelle page (non implémenté dans cet exemple simple)
                $y = 700;
            }
        }
        
        $y -= 10;
    }
    
    $objects[5] = ["offset" => $offset];
    $obj5 = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
    fwrite($fp, $obj5);
    $offset += strlen($obj5);
    
    // Écrire le xref
    $xrefOffset = $offset;
    $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($objects as $num => $obj) {
        $xref .= sprintf("%010d 00000 n \n", $obj["offset"]);
    }
    fwrite($fp, $xref);
    
    // Écrire le trailer
    $trailer = "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";
    fwrite($fp, $trailer);
    
    // Fermer le fichier
    fclose($fp);
    
    // Envoyer le fichier au navigateur
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    
    readfile($tempFile);
    
    // Supprimer le fichier temporaire
    unlink($tempFile);
    exit;
}

/**
 * Convertit un HTML simple en sections pour generateSimplePDF
 * 
 * @param string $html HTML simple à convertir
 * @param string $title Titre du document
 * @param string $filename Nom du fichier PDF à générer
 * @return void
 */
function generateSimplePDFFromHTML($html, $title, $filename = 'document.pdf') {
    // Nettoyer le HTML
    $html = strip_tags($html, '<h1><h2><h3><p><br><strong><em><ul><li><ol>');
    
    // Remplacer les balises par des caractères spéciaux
    $html = str_replace(['<br>', '<br/>'], "\n", $html);
    $html = str_replace(['<p>', '</p>'], ["\n", "\n\n"], $html);
    $html = str_replace(['<strong>', '</strong>'], ['', ''], $html);
    $html = str_replace(['<em>', '</em>'], ['', ''], $html);
    
    // Extraire les sections
    $sections = [];
    $pattern = '/<h[1-3]>(.*?)<\/h[1-3]>(.*?)(?=<h[1-3]>|$)/s';
    
    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $sections[] = [
                'title' => trim($match[1]),
                'content' => trim(strip_tags($match[2]))
            ];
        }
    } else {
        // Si pas de sections trouvées, créer une seule section
        $sections[] = [
            'title' => $title,
            'content' => trim(strip_tags($html))
        ];
    }
    
    // Générer le PDF
    generateSimplePDF($title, $sections, $filename);
}