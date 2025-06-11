# Système d'Attribution de Tutorat - Version PHP

Un système complet de gestion des affectations de tutorat entre étudiants et enseignants, entièrement développé en PHP avec des algorithmes d'optimisation avancés.

## 🚀 Technologies Utilisées

### Backend API
- **PHP 8.2+** - Langage principal avec JIT compiler pour les performances
- **Symfony 6.4 LTS** - Framework robuste pour API REST
- **Doctrine ORM** - Gestion de la base de données et migrations
- **API Platform** - Génération automatique d'API REST et documentation
- **LexikJWTAuthenticationBundle** - Authentification JWT sécurisée
- **PHPUnit** - Tests unitaires et d'intégration
- **Symfony Serializer** - Sérialisation JSON avancée

### Frontend Web
- **PHP 8.2+** avec **Symfony Twig** - Templates dynamiques
- **Symfony UX** - Composants JavaScript intégrés
- **Stimulus** - Framework JavaScript léger pour interactivité
- **Turbo** - Navigation rapide sans rechargement de page
- **Chart.js** - Graphiques interactifs pour les tableaux de bord

### Base de Données & Infrastructure
- **MySQL 8.0+** - Base de données principale
- **Redis 6+** - Cache et sessions
- **Elasticsearch 8** (optionnel) - Recherche avancée
- **Apache** - Serveur web
- **Composer** - Gestionnaire de dépendances PHP

### Algorithmes & Performance
- **Algorithmes d'optimisation** natifs PHP pour l'affectation
- **Symfony Messenger** - Traitement asynchrone des tâches lourdes
- **Doctrine Cache** - Cache des requêtes complexes
- **Monolog** - Logging et monitoring des performances

## 📁 Structure du Projet

```

├── backend-api/                    # API REST Symfony
│   ├── config/
│   │   ├── packages/              # Configuration des bundles
│   │   ├── routes/                # Routes API
│   │   └── services.yaml          # Services et DI
│   ├── src/
│   │   ├── Algorithm/             # Algorithmes d'affectation
│   │   │   ├── AssignmentAlgorithmInterface.php
│   │   │   ├── GreedyAlgorithm.php
│   │   │   ├── HungarianAlgorithm.php
│   │   │   ├── OptimalHybridAlgorithm.php
│   │   │   └── GeneticOptimizer.php
│   │   ├── Controller/API/        # Controllers REST API
│   │   │   ├── AuthController.php
│   │   │   ├── StudentController.php
│   │   │   ├── TeacherController.php
│   │   │   ├── AssignmentController.php
│   │   │   └── DashboardController.php
│   │   ├── Entity/                # Entités Doctrine
│   │   │   ├── User.php
│   │   │   ├── Student.php
│   │   │   ├── Teacher.php
│   │   │   ├── Assignment.php
│   │   │   ├── Preference.php
│   │   │   └── Company.php
│   │   ├── Repository/            # Repositories Doctrine
│   │   ├── Service/               # Services métier
│   │   │   ├── AssignmentService.php
│   │   │   ├── AuthService.php
│   │   │   ├── ReportService.php
│   │   │   └── MetricsService.php
│   │   ├── DTO/                   # Data Transfer Objects
│   │   ├── EventListener/         # Event listeners
│   │   └── Validator/             # Validateurs personnalisés
│   ├── migrations/                # Migrations de base de données
│   ├── tests/                     # Tests PHPUnit
│   └── var/                       # Cache et logs
├── frontend-web/                   # Interface web Symfony
│   ├── assets/                    # Assets frontend
│   │   ├── controllers/           # Stimulus controllers
│   │   ├── styles/                # CSS/SCSS
│   │   └── js/                    # JavaScript
│   ├── config/
│   ├── src/
│   │   ├── Controller/Web/        # Controllers web
│   │   │   ├── HomeController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── StudentController.php
│   │   │   ├── TeacherController.php
│   │   │   └── AssignmentController.php
│   │   ├── Form/                  # Formulaires Symfony
│   │   ├── Service/               # Services frontend
│   │   └── Twig/                  # Extensions Twig
│   ├── templates/                 # Templates Twig
│   │   ├── base.html.twig
│   │   ├── dashboard/
│   │   ├── student/
│   │   ├── teacher/
│   │   └── assignment/
│   └── webpack.config.js          # Configuration Webpack Encore
├── shared/                        # Code partagé
│   ├── src/
│   │   ├── DTO/                   # DTOs partagés
│   │   └── Enum/                  # Énumérations
├── database/
│   ├── fixtures/                  # Données de test
│   └── scripts/                   # Scripts SQL utilitaires
├── docker/                        # Configuration Docker
├── docs/                          # Documentation
└── README.md
```

## 🏗️ Plan de Développement

### Phase 1: Base Infrastructure (2-3 semaines)

#### Semaine 1: Setup Backend API
```bash
# Installation Symfony API
composer create-project symfony/skeleton backend-api
cd backend-api

# Bundles essentiels
composer require symfony/orm-pack
composer require api-platform/api-platform
composer require lexik/jwt-authentication-bundle
composer require symfony/security-bundle
composer require doctrine/doctrine-fixtures-bundle
composer require symfony/validator
composer require symfony/serializer-pack
```

**Configuration à implémenter:**
- **JWT Authentication** avec clés RSA
- **Entités de base**: User, Student, Teacher, Role
- **API REST** avec API Platform
- **Validation** et sérialisation
- **CORS** pour communication frontend-backend

#### Semaine 2: Setup Frontend Web
```bash
# Installation Symfony Web
composer create-project symfony/skeleton frontend-web
cd frontend-web

# Bundles frontend
composer require symfony/twig-bundle
composer require symfony/asset
composer require symfony/webpack-encore-bundle
composer require symfony/ux-turbo
composer require symfony/ux-stimulus-bundle
composer require symfony/form
composer require symfony/http-client
```

**Configuration à implémenter:**
- **Templates Twig** avec layout responsive
- **Stimulus controllers** pour interactivité
- **Tailwind CSS** configuration
- **Client HTTP** pour communication avec l'API
- **System d'authentification** web

#### Semaine 3: Intégration Base
- **Tests unitaires** PHPUnit
- **Documentation** API avec API Platform
- **Fixtures** de données de test
- **Validation** de l'architecture

### Phase 2: Algorithmes d'Affectation (3-4 semaines)

#### Semaine 4-5: Algorithmes Core

**Algorithme Glouton (O(n×m)):**
```php
<?php
// src/Algorithm/GreedyAlgorithm.php
namespace App\Algorithm;

class GreedyAlgorithm implements AssignmentAlgorithmInterface
{
    public function execute(
        array $students, 
        array $teachers, 
        AssignmentParameters $parameters
    ): AssignmentResult {
        $result = new AssignmentResult();
        $availableCapacity = $this->initializeCapacity($teachers);
        
        // Trier étudiants par priorité (moyenne décroissante)
        usort($students, fn($a, $b) => 
            ($b->getAverageGrade() ?? 0) <=> ($a->getAverageGrade() ?? 0)
        );
        
        foreach ($students as $student) {
            $bestTeacher = $this->findBestTeacher(
                $student, 
                $teachers, 
                $availableCapacity, 
                $parameters
            );
            
            if ($bestTeacher) {
                $result->addAssignment($bestTeacher, $student);
                $availableCapacity[$bestTeacher->getId()]--;
            } else {
                $result->addUnassignedStudent($student);
            }
        }
        
        return $result;
    }
    
    private function calculateScore(
        Student $student, 
        Teacher $teacher, 
        AssignmentParameters $params
    ): float {
        $score = 0.0;
        
        // Score département
        if ($student->getDepartment() === $teacher->getDepartment()) {
            $score += $params->getDepartmentWeight();
        }
        
        // Score préférences
        if ($params->isPrioritizePreferences()) {
            $score += $this->calculatePreferenceScore($student, $teacher) 
                * $params->getPreferenceWeight() / 100;
        }
        
        // Score charge de travail
        if ($params->isBalanceWorkload()) {
            $workloadScore = 1.0 - ($teacher->getCurrentStudentCount() / $teacher->getMaxStudents());
            $score += $workloadScore * $params->getCapacityWeight();
        }
        
        return min(100, max(0, $score)) / 100;
    }
}
```

**Algorithme Hongrois (O(n³)):**
```php
<?php
// src/Algorithm/HungarianAlgorithm.php
namespace App\Algorithm;

class HungarianAlgorithm implements AssignmentAlgorithmInterface
{
    private const INFINITY = PHP_FLOAT_MAX;
    
    public function execute(
        array $students, 
        array $teachers, 
        AssignmentParameters $parameters
    ): AssignmentResult {
        // Créer matrice de coûts
        $costMatrix = $this->createCostMatrix($students, $teachers, $parameters);
        
        // Créer slots virtuels pour capacités multiples
        $teacherSlots = $this->createTeacherSlots($teachers);
        
        // Ajuster matrice pour slots
        $adjustedMatrix = $this->adjustMatrixForSlots(
            $costMatrix, 
            count($students), 
            count($teacherSlots)
        );
        
        // Exécuter algorithme Hongrois
        $assignment = $this->executeHungarianCore($adjustedMatrix);
        
        // Construire résultat
        return $this->buildResult($assignment, $students, $teacherSlots);
    }
    
    private function executeHungarianCore(array $costMatrix): array
    {
        $n = count($costMatrix);
        $u = array_fill(0, $n, 0.0);
        $v = array_fill(0, $n, 0.0);
        $p = array_fill(0, $n, -1);
        $way = array_fill(0, $n, -1);
        
        for ($i = 0; $i < $n; $i++) {
            $j0 = 0;
            $way = array_fill(0, $n, -1);
            $minv = array_fill(0, $n, self::INFINITY);
            $used = array_fill(0, $n, false);
            
            do {
                $used[$j0] = true;
                $i0 = $p[$j0];
                $delta = self::INFINITY;
                $j1 = -1;
                
                for ($j = 0; $j < $n; $j++) {
                    if (!$used[$j]) {
                        $cur = $costMatrix[$i0 === -1 ? $i : $i0][$j] 
                            - $u[$i0 === -1 ? $i : $i0] - $v[$j];
                        
                        if ($cur < $minv[$j]) {
                            $minv[$j] = $cur;
                            $way[$j] = $j0;
                        }
                        
                        if ($minv[$j] < $delta) {
                            $delta = $minv[$j];
                            $j1 = $j;
                        }
                    }
                }
                
                for ($j = 0; $j < $n; $j++) {
                    if ($used[$j]) {
                        $u[$p[$j]] += $delta;
                        $v[$j] -= $delta;
                    } else {
                        $minv[$j] -= $delta;
                    }
                }
                
                $j0 = $j1;
            } while ($p[$j0] !== -1);
            
            // Reconstruction du chemin
            do {
                $j1 = $way[$j0];
                $p[$j0] = $p[$j1];
                $j0 = $j1;
            } while ($j0 !== -1);
        }
        
        $result = array_fill(0, $n, -1);
        for ($j = 0; $j < $n; $j++) {
            if ($p[$j] !== -1) {
                $result[$p[$j]] = $j;
            }
        }
        
        return $result;
    }
}
```

#### Semaine 6-7: Algorithme Génétique & Optimisation
```php
<?php
// src/Algorithm/GeneticOptimizer.php
namespace App\Algorithm;

class GeneticOptimizer
{
    private const POPULATION_SIZE = 50;
    private const GENERATIONS = 100;
    private const MUTATION_RATE = 0.1;
    private const CROSSOVER_RATE = 0.7;
    
    public function optimize(
        AssignmentResult $initial,
        array $students,
        array $teachers,
        AssignmentParameters $parameters
    ): AssignmentResult {
        // Initialiser population
        $population = $this->initializePopulation($initial, $students, $teachers);
        
        for ($generation = 0; $generation < self::GENERATIONS; $generation++) {
            // Évaluer fitness
            $this->evaluateFitness($population, $parameters);
            
            // Sélection
            $selected = $this->selection($population);
            
            // Croisement
            $offspring = $this->crossover($selected);
            
            // Mutation
            $this->mutate($offspring, $teachers);
            
            // Nouvelle génération
            $population = $this->createNewPopulation($population, $offspring);
        }
        
        return $this->getBestSolution($population[0], $students, $teachers);
    }
    
    private function evaluateFitness(
        array &$population, 
        AssignmentParameters $parameters
    ): void {
        foreach ($population as &$chromosome) {
            $chromosome['fitness'] = $this->calculateChromosomeFitness(
                $chromosome, 
                $parameters
            );
        }
        
        // Trier par fitness décroissante
        usort($population, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
    }
}
```

### Phase 3: Fonctionnalités Avancées (4-5 semaines)

#### Semaine 8-9: Système de Préférences

**Entité Preference:**
```php
<?php
// src/Entity/Preference.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PreferenceRepository::class)]
class Preference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class, inversedBy: 'preferences')]
    #[ORM\JoinColumn(nullable: false)]
    private Teacher $teacher;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['DEPARTMENT', 'LEVEL', 'PROGRAM', 'GRADE_RANGE'])]
    private string $preferenceType;

    #[ORM\Column(length: 255)]
    private string $preferenceValue;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10)]
    private int $priorityValue;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // Getters et setters...
}
```

**Service de Préférences:**
```php
<?php
// src/Service/PreferenceService.php
namespace App\Service;

class PreferenceService
{
    public function calculateAdvancedPreferenceScore(
        Student $student, 
        Teacher $teacher
    ): float {
        if ($teacher->getPreferences()->isEmpty()) {
            return 50.0; // Score neutre
        }
        
        $totalScore = 0.0;
        $totalWeight = 0.0;
        
        foreach ($teacher->getPreferences() as $preference) {
            $matchScore = match ($preference->getPreferenceType()) {
                'DEPARTMENT' => $this->matchDepartment($student, $preference),
                'LEVEL' => $this->matchLevel($student, $preference),
                'PROGRAM' => $this->matchProgram($student, $preference),
                'GRADE_RANGE' => $this->matchGradeRange($student, $preference),
                default => 0.0
            };
            
            $weight = $preference->getPriorityValue();
            $totalScore += $matchScore * $weight;
            $totalWeight += $weight;
        }
        
        return $totalWeight > 0 ? ($totalScore / $totalWeight) * 100 : 50.0;
    }
    
    private function matchProgram(Student $student, Preference $preference): float
    {
        $studentProgram = strtolower($student->getProgram());
        $preferredProgram = strtolower($preference->getPreferenceValue());
        
        if ($studentProgram === $preferredProgram) {
            return 1.0;
        }
        
        // Similarité basée sur mots communs
        $studentWords = array_filter(explode(' ', $studentProgram));
        $preferredWords = array_filter(explode(' ', $preferredProgram));
        
        $commonWords = array_intersect($studentWords, $preferredWords);
        
        return empty($commonWords) ? 0.0 : 
            count($commonWords) / max(count($studentWords), count($preferredWords));
    }
}
```

#### Semaine 10-11: Tableaux de Bord & Métriques

**Controller Dashboard:**
```php
<?php
// src/Controller/Web/DashboardController.php
namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private MetricsService $metricsService,
        private AssignmentService $assignmentService
    ) {}

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        $dashboardData = match ($user->getRole()) {
            'ROLE_ADMIN' => $this->getAdminDashboard(),
            'ROLE_TEACHER' => $this->getTeacherDashboard($user),
            'ROLE_STUDENT' => $this->getStudentDashboard($user),
            default => throw new \InvalidArgumentException('Role non reconnu')
        };

        return $this->render('dashboard/index.html.twig', [
            'data' => $dashboardData,
            'charts' => $this->getChartsData($user)
        ]);
    }
    
    private function getAdminDashboard(): array
    {
        return [
            'totalStudents' => $this->metricsService->getTotalStudents(),
            'totalTeachers' => $this->metricsService->getTotalTeachers(),
            'assignmentRate' => $this->metricsService->getAssignmentRate(),
            'satisfactionScore' => $this->metricsService->getAverageSatisfaction(),
            'recentAssignments' => $this->assignmentService->getRecentAssignments(10),
            'systemAlerts' => $this->metricsService->getSystemAlerts(),
            'workloadDistribution' => $this->metricsService->getWorkloadDistribution()
        ];
    }
}
```

**Templates Twig avec Charts:**
```twig
{# templates/dashboard/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Tableau de Bord{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{% endblock %}

{% block body %}
<div class="container mx-auto px-4 py-8">
    <!-- Métriques principales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Étudiants</h3>
            <p class="text-3xl font-bold text-blue-600">{{ data.totalStudents }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Enseignants</h3>
            <p class="text-3xl font-bold text-green-600">{{ data.totalTeachers }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Taux d'Affectation</h3>
            <p class="text-3xl font-bold text-purple-600">{{ (data.assignmentRate * 100)|round(1) }}%</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Satisfaction</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ (data.satisfactionScore * 100)|round(1) }}%</p>
        </div>
    </div>
    
    <!-- Graphiques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Distribution des Charges</h3>
            <canvas id="workloadChart" {{ stimulus_controller('chart', {
                type: 'doughnut',
                data: charts.workloadData
            }) }}></canvas>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Évolution des Affectations</h3>
            <canvas id="assignmentChart" {{ stimulus_controller('chart', {
                type: 'line',
                data: charts.assignmentTrend
            }) }}></canvas>
        </div>
    </div>
    
    <!-- Affectations récentes -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Affectations Récentes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Étudiant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enseignant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    {% for assignment in data.recentAssignments %}
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ assignment.student.firstName }} {{ assignment.student.lastName }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ assignment.teacher.firstName }} {{ assignment.teacher.lastName }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                {{ (assignment.satisfactionScore * 100)|round(1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ assignment.createdAt|date('d/m/Y H:i') }}
                        </td>
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
</div>
{% endblock %}
```

#### Semaine 12: Génération de Rapports

**Service de Rapports:**
```php
<?php
// src/Service/ReportService.php
namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportService
{
    public function __construct(
        private AssignmentService $assignmentService,
        private MetricsService $metricsService
    ) {}

    public function generateAssignmentReport(
        string $academicYear, 
        string $format = 'pdf'
    ): string {
        $assignments = $this->assignmentService->getByAcademicYear($academicYear);
        $metrics = $this->metricsService->getMetricsForYear($academicYear);
        
        return match ($format) {
            'pdf' => $this->generatePdfReport($assignments, $metrics),
            'excel' => $this->generateExcelReport($assignments, $metrics),
            'csv' => $this->generateCsvReport($assignments),
            default => throw new \InvalidArgumentException('Format non supporté')
        };
    }
    
    private function generatePdfReport(array $assignments, array $metrics): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->renderReportTemplate('reports/assignment_pdf.html.twig', [
            'assignments' => $assignments,
            'metrics' => $metrics,
            'generatedAt' => new \DateTime()
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'assignment_report_' . date('Y-m-d_H-i-s') . '.pdf';
        file_put_contents($filename, $dompdf->output());
        
        return $filename;
    }
    
    private function generateExcelReport(array $assignments, array $metrics): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $sheet->setCellValue('A1', 'Étudiant');
        $sheet->setCellValue('B1', 'Enseignant');
        $sheet->setCellValue('C1', 'Département');
        $sheet->setCellValue('D1', 'Score de Satisfaction');
        $sheet->setCellValue('E1', 'Date d\'Affectation');
        
        // Données
        $row = 2;
        foreach ($assignments as $assignment) {
            $sheet->setCellValue('A' . $row, $assignment->getStudent()->getFullName());
            $sheet->setCellValue('B' . $row, $assignment->getTeacher()->getFullName());
            $sheet->setCellValue('C' . $row, $assignment->getStudent()->getDepartment());
            $sheet->setCellValue('D' . $row, $assignment->getSatisfactionScore() * 100 . '%');
            $sheet->setCellValue('E' . $row, $assignment->getCreatedAt()->format('d/m/Y'));
            $row++;
        }
        
        // Style
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'assignment_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $writer->save($filename);
        
        return $filename;
    }
}
```

### Phase 4: Interface Avancée & Tests (2-3 semaines)

#### Semaine 13: Composants Stimulus

**Stimulus Controllers:**
```javascript
// assets/controllers/assignment_matrix_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["cell", "student", "teacher"]
    static values = { 
        students: Array, 
        teachers: Array,
        preferences: Object 
    }

    connect() {
        this.renderMatrix()
    }

    renderMatrix() {
        const matrix = this.buildPreferenceMatrix()
        this.updateDisplay(matrix)
    }

    buildPreferenceMatrix() {
        const matrix = []
        
        this.studentsValue.forEach((student, i) => {
            matrix[i] = []
            this.teachersValue.forEach((teacher, j) => {
                matrix[i][j] = this.calculateScore(student, teacher)
            })
        })
        
        return matrix
    }

    calculateScore(student, teacher) {
        let score = 0
        
        // Score département
        if (student.department === teacher.department) {
            score += 30
        }
        
        // Score préférences
        if (this.preferencesValue[teacher.id]) {
            score += this.getPreferenceScore(student, teacher)
        }
        
        return Math.min(100, score)
    }

    cellClicked(event) {
        const studentId = event.target.dataset.studentId
        const teacherId = event.target.dataset.teacherId
        
        // Simuler affectation
        this.simulateAssignment(studentId, teacherId)
    }

    async simulateAssignment(studentId, teacherId) {
        const response = await fetch('/api/assignments/simulate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                studentId: studentId,
                teacherId: teacherId
            })
        })
        
        const result = await response.json()
        this.updateCellScore(studentId, teacherId, result.score)
    }
}
```

```javascript
// assets/controllers/chart_controller.js
import { Controller } from "@hotwired/stimulus"
import Chart from "chart.js/auto"

export default class extends Controller {
    static values = { 
        type: String, 
        data: Object, 
        options: Object 
    }

    connect() {
        this.chart = new Chart(this.element, {
            type: this.typeValue,
            data: this.dataValue,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: this.dataValue.title || ''
                    }
                },
                ...this.optionsValue
            }
        })
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy()
        }
    }

    updateData(event) {
        const newData = event.detail.data
        this.chart.data = newData
        this.chart.update()
    }
}
```

#### Semaine 14: Tests & Documentation

**Tests PHPUnit:**
```php
<?php
// tests/Algorithm/GreedyAlgorithmTest.php
namespace App\Tests\Algorithm;

use App\Algorithm\GreedyAlgorithm;
use App\Entity\Student;
use App\Entity\Teacher;
use PHPUnit\Framework\TestCase;

class GreedyAlgorithmTest extends TestCase
{
    private GreedyAlgorithm $algorithm;

    protected function setUp(): void
    {
        $this->algorithm = new GreedyAlgorithm();
    }

    public function testAssignStudentsWithPerfectMatch(): void
    {
        $students = $this->createStudents([
            ['name' => 'John Doe', 'department' => 'INFO', 'grade' => 15.5],
            ['name' => 'Jane Smith', 'department' => 'MATH', 'grade' => 14.0]
        ]);
        
        $teachers = $this->createTeachers([
            ['name' => 'Prof. Martin', 'department' => 'INFO', 'capacity' => 2],
            ['name' => 'Prof. Dupont', 'department' => 'MATH', 'capacity' => 2]
        ]);
        
        $parameters = new AssignmentParameters();
        $parameters->setDepartmentWeight(50);
        $parameters->setAllowCrossDepartment(false);
        
        $result = $this->algorithm->execute($students, $teachers, $parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(2, $result->getAssignments());
        $this->assertCount(0, $result->getUnassignedStudents());
        
        // Vérifier que chaque étudiant est dans le bon département
        foreach ($result->getAssignments() as $assignment) {
            $this->assertEquals(
                $assignment->getStudent()->getDepartment(),
                $assignment->getTeacher()->getDepartment()
            );
        }
    }
    
    public function testAssignStudentsWithInsufficientCapacity(): void
    {
        $students = $this->createStudents([
            ['name' => 'Student 1', 'department' => 'INFO', 'grade' => 15],
            ['name' => 'Student 2', 'department' => 'INFO', 'grade' => 14],
            ['name' => 'Student 3', 'department' => 'INFO', 'grade' => 13]
        ]);
        
        $teachers = $this->createTeachers([
            ['name' => 'Teacher 1', 'department' => 'INFO', 'capacity' => 2]
        ]);
        
        $parameters = new AssignmentParameters();
        $result = $this->algorithm->execute($students, $teachers, $parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(2, $result->getAssignments());
        $this->assertCount(1, $result->getUnassignedStudents());
    }
    
    public function testPerformanceWithLargeDataset(): void
    {
        $students = $this->createStudents(
            array_fill(0, 1000, ['department' => 'INFO', 'grade' => 15])
        );
        $teachers = $this->createTeachers(
            array_fill(0, 100, ['department' => 'INFO', 'capacity' => 10])
        );
        
        $startTime = microtime(true);
        
        $result = $this->algorithm->execute($students, $teachers, new AssignmentParameters());
        
        $executionTime = microtime(true) - $startTime;
        
        $this->assertTrue($result->isSuccessful());
        $this->assertLessThan(5.0, $executionTime); // Moins de 5 secondes
    }
    
    private function createStudents(array $data): array
    {
        return array_map(fn($item) => (new Student())
            ->setFirstName($item['name'] ?? 'Student')
            ->setDepartment($item['department'])
            ->setAverageGrade($item['grade']), $data);
    }
    
    private function createTeachers(array $data): array
    {
        return array_map(fn($item) => (new Teacher())
            ->setFirstName($item['name'] ?? 'Teacher')
            ->setDepartment($item['department'])
            ->setMaxStudents($item['capacity']), $data);
    }
}
```

**Tests d'Intégration:**
```php
<?php
// tests/Controller/AssignmentControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssignmentControllerTest extends WebTestCase
{
    public function testGenerateAssignmentEndpoint(): void
    {
        $client = static::createClient();
        
        // Login en tant qu'admin
        $this->loginAsAdmin($client);
        
        $client->request('POST', '/api/assignments/generate', [
            'algorithm' => 'greedy',
            'parameters' => [
                'departmentWeight' => 50,
                'preferenceWeight' => 30,
                'capacityWeight' => 20,
                'allowCrossDepartment' => false
            ]
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['successful']);
        $this->assertArrayHasKey('assignments', $response);
        $this->assertArrayHasKey('metrics', $response);
    }
}
```

## 🚀 Déploiement & Production

### Configuration Docker
```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --optimize-autoloader --no-dev
RUN npm install && npm run build

CMD ["php-fpm"]
```

### Variables d'Environnement
```bash
# .env.prod
APP_ENV=prod
APP_SECRET=your-secret-key
DATABASE_URL=mysql://user:password@localhost:3306/tutoring_db
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
REDIS_URL=redis://localhost:6379
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

## 📊 Métriques & Performance

### Objectifs de Performance
- **Algorithme Glouton**: < 2 secondes pour 1000 étudiants
- **Algorithme Hongrois**: < 10 secondes pour 500 étudiants  
- **Algorithme Génétique**: < 30 secondes pour optimisation
- **Interface Web**: < 500ms temps de réponse
- **API REST**: < 200ms par endpoint

### Monitoring
- **Symfony Profiler** pour le debugging
- **Blackfire** pour l'optimisation des performances
- **Elasticsearch + Kibana** pour les logs
- **Grafana** pour les métriques système

## 🔒 Sécurité

### Authentification & Autorisation
- **JWT** avec rotation des tokens
- **RBAC** (Role-Based Access Control)
- **CSRF Protection** sur les formulaires
- **Rate Limiting** sur l'API
- **Validation** stricte des données

### Protection des Données
- **Chiffrement** des données sensibles
- **Audit Trail** des actions importantes
- **Backup** automatisé quotidien
- **RGPD** compliance

## 📚 Documentation

### API Documentation
- **API Platform** génère automatiquement la documentation OpenAPI
- **Swagger UI** accessible à `/api/docs`
- **Postman Collection** pour les tests

### Guides Utilisateur
- **Guide Administrateur**: Gestion complète du système
- **Guide Enseignant**: Gestion des préférences et consultations
- **Guide Étudiant**: Consultation des affectations et profil

---

Ce README fournit une feuille de route complète pour développer le système de tutorat en PHP avec une architecture moderne, des algorithmes optimisés et une interface utilisateur riche. Le développement peut être adapté selon les besoins spécifiques et l'équipe disponible.