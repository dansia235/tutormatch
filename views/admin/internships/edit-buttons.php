<?php
/**
 * Boutons de secours pour la page d'édition des stages
 */

// Récupérer l'ID du stage depuis l'URL
$internshipId = isset($_GET['id']) ? intval($_GET['id']) : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutons de secours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .button-container a, 
        .button-container button {
            padding: 10px 20px;
            font-size: 16px;
            min-width: 200px;
            text-align: center;
        }
        .btn-primary {
            background-color: #0d6efd;
            color: white;
            border: none;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert alert-info">
            <h4>Boutons de secours</h4>
            <p>Utilisez ces boutons si les boutons d'origine ne sont pas visibles sur la page d'édition.</p>
        </div>
        
        <div class="button-container">
            <a href="/tutoring/views/admin/internships.php" class="btn btn-secondary">
                Annuler et revenir à la liste
            </a>
            
            <form action="/tutoring/views/admin/internships/update.php" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                <input type="hidden" name="id" value="<?php echo $internshipId; ?>">
                <input type="hidden" name="backup_submit" value="1">
                <button type="button" class="btn btn-primary" onclick="submitMainForm()">
                    Enregistrer les modifications
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Cette fonction tente de soumettre le formulaire principal sur la page d'édition
        function submitMainForm() {
            try {
                // Essayer de récupérer le formulaire sur la page parente
                window.opener.document.getElementById('edit-internship-form').submit();
                // Fermer cette fenêtre
                window.close();
            } catch (e) {
                // Si ça ne fonctionne pas, afficher un message d'erreur
                alert("Impossible de soumettre le formulaire. Veuillez utiliser les boutons sur la page principale si possible, ou contactez le support technique.");
            }
        }
    </script>
</body>
</html>