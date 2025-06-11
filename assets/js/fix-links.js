/**
 * Script pour vérifier les liens dans le menu
 * 
 * Cette version est complètement passive et ne modifie pas le comportement des liens.
 * Elle vérifie uniquement si les liens sont corrects et affiche les résultats dans la console.
 * Aucune modification n'est apportée aux liens.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("Fix-links.js: Verifying links in sidebar");
    
    // Chemins corrects pour les liens du menu étudiant
    const correctPaths = {
        "Tableau de bord": "/tutoring/views/student/dashboard.php",
        "Mon Stage": "/tutoring/views/student/internship.php",
        "Mon Tuteur": "/tutoring/views/student/tutor.php",
        "Documents": "/tutoring/views/student/documents.php",
        "Réunions": "/tutoring/views/student/meetings.php",
        "Messagerie": "/tutoring/views/student/messages.php",
        "Évaluations": "/tutoring/views/student/evaluations.php",
        "Préférences": "/tutoring/views/student/preferences.php"
    };
    
    // Vérifier tous les liens dans la barre latérale
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Vérifier que chaque lien pointe vers la bonne URL
    sidebarLinks.forEach(link => {
        // Supprimer tout attribut onclick qui pourrait interférer avec le comportement normal
        if (link.hasAttribute('onclick')) {
            console.warn(`Fix-links.js: Removing onclick attribute from link "${link.textContent.trim()}"`);
            link.removeAttribute('onclick');
        }
        
        const linkText = link.textContent.trim();
        
        if (correctPaths[linkText]) {
            const currentHref = link.getAttribute('href');
            if (currentHref !== correctPaths[linkText]) {
                console.warn(`Fix-links.js: Link "${linkText}" has incorrect href: "${currentHref}" instead of "${correctPaths[linkText]}"`);
            } else {
                console.log(`Fix-links.js: Link "${linkText}" is correct: "${currentHref}"`);
            }
        }
    });
    
    console.log("Fix-links.js: Link verification completed");
});