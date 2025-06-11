<?php
/**
 * Assignment Generation Content Template - Bootstrap Version
 * Vue pour la génération automatique d'affectations
 */
?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-magic me-2"></i>Génération automatique d'affectations
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments/index.php">Affectations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Génération automatique</li>
                </ol>
            </nav>
        </div>
        
        <a href="/tutoring/views/admin/assignments/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
    
    <!-- Affichage des erreurs du formulaire -->
    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Erreurs dans le formulaire :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($formErrors as $error): ?>
            <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Statistiques et disponibilités -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Étudiants sans affectation</h5>
                    <div class="display-4 fw-bold text-primary"><?php echo count($unassignedStudents); ?></div>
                    <p class="text-muted">sur <?php echo $totalStudents; ?> étudiants actifs</p>
                    
                    <?php if (count($unassignedStudents) > 0): ?>
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" 
                            style="width: <?php echo (count($unassignedStudents) / $totalStudents) * 100; ?>%" 
                            aria-valuenow="<?php echo count($unassignedStudents); ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="<?php echo $totalStudents; ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Capacité des tuteurs</h5>
                    <div class="display-4 fw-bold text-success"><?php echo $teacherCapacity; ?></div>
                    <p class="text-muted">places disponibles</p>
                    
                    <?php if (count($unassignedStudents) > 0 && $teacherCapacity > 0): ?>
                    <div class="progress mt-2" style="height: 10px;">
                        <?php 
                        $ratio = count($unassignedStudents) / $teacherCapacity;
                        $percent = min(100, $ratio * 100);
                        $colorClass = $ratio > 1 ? 'bg-danger' : ($ratio > 0.8 ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="progress-bar <?php echo $colorClass; ?>" role="progressbar" 
                            style="width: <?php echo $percent; ?>%" 
                            aria-valuenow="<?php echo count($unassignedStudents); ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="<?php echo $teacherCapacity; ?>">
                        </div>
                    </div>
                    <div class="text-muted mt-2">
                        <?php if ($teacherCapacity < count($unassignedStudents)): ?>
                        <span class="text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Capacité insuffisante
                        </span>
                        <?php else: ?>
                        <span class="text-success">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Capacité suffisante
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Stages disponibles</h5>
                    <div class="display-4 fw-bold text-info"><?php echo count($availableInternships); ?></div>
                    <p class="text-muted">sur <?php echo $totalInternships; ?> stages</p>
                    
                    <?php if (count($unassignedStudents) > 0 && count($availableInternships) > 0): ?>
                    <div class="progress mt-2" style="height: 10px;">
                        <?php 
                        $ratio = count($unassignedStudents) / count($availableInternships);
                        $percent = min(100, $ratio * 100);
                        $colorClass = $ratio > 1 ? 'bg-danger' : ($ratio > 0.8 ? 'bg-warning' : 'bg-info');
                        ?>
                        <div class="progress-bar <?php echo $colorClass; ?>" role="progressbar" 
                            style="width: <?php echo $percent; ?>%" 
                            aria-valuenow="<?php echo count($unassignedStudents); ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="<?php echo count($availableInternships); ?>">
                        </div>
                    </div>
                    <div class="text-muted mt-2">
                        <?php if (count($availableInternships) < count($unassignedStudents)): ?>
                        <span class="text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Stages insuffisants
                        </span>
                        <?php else: ?>
                        <span class="text-success">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Stages suffisants
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Affectations existantes</h5>
                    <div class="display-4 fw-bold text-secondary"><?php echo $totalAssignments; ?></div>
                    <p class="text-muted">affectations déjà créées</p>
                    
                    <?php if ($totalAssignments > 0 && $totalStudents > 0): ?>
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar bg-secondary" role="progressbar" 
                            style="width: <?php echo ($totalAssignments / $totalStudents) * 100; ?>%" 
                            aria-valuenow="<?php echo $totalAssignments; ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="<?php echo $totalStudents; ?>">
                        </div>
                    </div>
                    <div class="text-muted mt-2">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <?php echo round(($totalAssignments / $totalStudents) * 100); ?>% des étudiants affectés
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($unassignedStudents) === 0): ?>
    <!-- Aucun étudiant à affecter -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Information :</strong> Tous les étudiants actifs ont déjà été affectés. Aucune nouvelle affectation n'est nécessaire.
    </div>
    
    <?php elseif (count($availableInternships) === 0): ?>
    <!-- Aucun stage disponible -->
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Attention :</strong> Aucun stage disponible. Veuillez ajouter des stages ou libérer des stages existants avant de générer des affectations.
    </div>
    
    <?php elseif (count($availableTeachers) === 0 || $teacherCapacity === 0): ?>
    <!-- Aucun enseignant disponible -->
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Attention :</strong> Aucun tuteur n'a de capacité disponible. Veuillez ajuster la capacité des tuteurs existants ou ajouter de nouveaux tuteurs.
    </div>
    
    <?php else: ?>
    <!-- Formulaire de génération automatique -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Paramètres de génération</h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/assignments/generate-process.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Nom de l'exécution</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo h($formData['name'] ?? 'Exécution du ' . date('Y-m-d H:i')); ?>">
                        <div class="form-text">Un nom descriptif pour cette exécution d'algorithme (optionnel).</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="algorithm_type" class="form-label">Algorithme <span class="text-danger">*</span></label>
                        <select class="form-select" id="algorithm_type" name="algorithm_type" required>
                            <option value="">-- Sélectionner un algorithme --</option>
                            <option value="greedy" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'greedy') ? 'selected' : ''; ?>>Algorithme glouton (priorité aux préférences)</option>
                            <option value="hungarian" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'hungarian') ? 'selected' : ''; ?>>Algorithme hongrois (optimisation globale)</option>
                            <option value="genetic" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'genetic') ? 'selected' : ''; ?>>Algorithme génétique (optimisation itérative)</option>
                            <option value="hybrid" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'hybrid') ? 'selected' : ''; ?>>Algorithme hybride (combinaison des approches)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Paramètres avancés -->
                <div class="card mb-4">
                    <div class="card-header">
                        <button class="btn btn-link btn-sm text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedParams" aria-expanded="false" aria-controls="advancedParams">
                            <i class="bi bi-gear me-1"></i>Paramètres avancés
                        </button>
                    </div>
                    <div id="advancedParams" class="collapse">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 mb-3">
                                    <label for="department_weight" class="form-label">Poids du département</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="department_weight" name="department_weight" value="<?php echo h($formData['department_weight'] ?? 50); ?>" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Importance du département dans le calcul de compatibilité.</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="preference_weight" class="form-label">Poids des préférences</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="preference_weight" name="preference_weight" value="<?php echo h($formData['preference_weight'] ?? 30); ?>" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Importance des préférences de l'étudiant et du tuteur.</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="capacity_weight" class="form-label">Poids de la charge</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="capacity_weight" name="capacity_weight" value="<?php echo h($formData['capacity_weight'] ?? 20); ?>" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Importance de l'équilibrage des charges entre tuteurs.</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="allow_cross_department" name="allow_cross_department" value="1" <?php echo (isset($formData['allow_cross_department']) && $formData['allow_cross_department']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allow_cross_department">Autoriser les affectations inter-départements</label>
                                    </div>
                                    <div class="form-text">Permet d'affecter un étudiant à un tuteur d'un autre département.</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="prioritize_preferences" name="prioritize_preferences" value="1" <?php echo (isset($formData['prioritize_preferences']) && $formData['prioritize_preferences']) ? 'checked' : ''; ?> checked>
                                        <label class="form-check-label" for="prioritize_preferences">Prioriser les préférences des étudiants</label>
                                    </div>
                                    <div class="form-text">Donne la priorité aux choix des étudiants plutôt qu'aux préférences des tuteurs.</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="balance_workload" name="balance_workload" value="1" <?php echo (isset($formData['balance_workload']) && $formData['balance_workload']) ? 'checked' : ''; ?> checked>
                                        <label class="form-check-label" for="balance_workload">Équilibrer la charge des tuteurs</label>
                                    </div>
                                    <div class="form-text">Essaie de répartir les étudiants équitablement entre les tuteurs.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="set_as_default" name="set_as_default" value="1" <?php echo (isset($formData['set_as_default']) && $formData['set_as_default']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="set_as_default">Définir comme paramètres par défaut</label>
                                </div>
                                <div class="form-text">Enregistre ces paramètres comme configuration par défaut pour les futures générations.</div>
                            </div>

                            <!-- Weight warning message -->
                            <div id="weightWarning" class="alert alert-warning mt-3 d-none">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                La somme des poids doit être égale à 100%. Veuillez ajuster les valeurs.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo h($formData['notes'] ?? ''); ?></textarea>
                    <div class="form-text">Notes sur cette exécution d'algorithme (visible uniquement par les administrateurs).</div>
                </div>
                
                <!-- Résumé et avertissements -->
                <div class="alert alert-info mb-4">
                    <h5><i class="bi bi-info-circle me-2"></i>Résumé de la génération</h5>
                    <p>Vous allez générer des affectations pour <strong><?php echo count($unassignedStudents); ?> étudiants</strong> parmi <strong><?php echo count($availableInternships); ?> stages disponibles</strong>, avec <strong><?php echo $teacherCapacity; ?> places disponibles</strong> réparties sur <strong><?php echo count($availableTeachers); ?> tuteurs</strong>.</p>
                    
                    <?php if (count($availableInternships) < count($unassignedStudents)): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Il n'y a pas assez de stages disponibles pour tous les étudiants. Certains étudiants ne recevront pas d'affectation.
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($teacherCapacity < count($unassignedStudents)): ?>
                    <div class="alert alert-warning mb-0 mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> La capacité totale des tuteurs est insuffisante. Certains étudiants ne recevront pas d'affectation.
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='/tutoring/views/admin/assignments/index.php'">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-magic me-2"></i>Générer les affectations
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Scripts spécifiques -->
<script>
    // Validation du formulaire
    (function() {
        'use strict';
        
        // Fetch all forms we want to apply custom validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
        
        // Vérifier que les poids totalisent 100%
        const weightInputs = [
            document.getElementById('department_weight'),
            document.getElementById('preference_weight'),
            document.getElementById('capacity_weight')
        ];
        
        const weightWarning = document.getElementById('weightWarning');
        
        weightInputs.forEach(input => {
            if (input) {
                input.addEventListener('change', validateWeights);
                input.addEventListener('input', validateWeights);
            }
        });
        
        function validateWeights() {
            if (!weightWarning) return;
            
            const sum = weightInputs.reduce((total, input) => {
                return total + (parseInt(input.value) || 0);
            }, 0);
            
            if (sum !== 100) {
                weightWarning.classList.remove('d-none');
                weightInputs.forEach(input => {
                    if (input) input.classList.add('is-invalid');
                });
            } else {
                weightWarning.classList.add('d-none');
                weightInputs.forEach(input => {
                    if (input) input.classList.remove('is-invalid');
                });
            }
        }
        
        // Vérifier initialement
        if (weightWarning) {
            validateWeights();
        }
    })();
</script>