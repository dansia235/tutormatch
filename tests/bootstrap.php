<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Define the root directory
define('ROOT_DIR', dirname(__DIR__));

// Define test environment
define('TESTING', true);

// Set up autoloading
require_once ROOT_DIR . '/includes/init.php';

// Override database configuration for testing
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Test database helper functions
class TestDatabaseHelper
{
    private static ?PDO $connection = null;
    
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = new PDO('sqlite::memory:');
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::setupTestSchema();
        }
        
        return self::$connection;
    }
    
    private static function setupTestSchema(): void
    {
        $sql = file_get_contents(ROOT_DIR . '/database/tutoring_system.sql');
        if ($sql) {
            // Adapter pour SQLite
            $sql = str_replace('AUTO_INCREMENT', '', $sql);
            $sql = str_replace('ENGINE=InnoDB', '', $sql);
            $sql = preg_replace('/INT\(\d+\)/', 'INTEGER', $sql);
            
            self::$connection->exec($sql);
        }
    }
    
    public static function resetDatabase(): void
    {
        if (self::$connection) {
            self::setupTestSchema();
        }
    }
    
    public static function seedTestData(): void
    {
        $pdo = self::getConnection();
        
        // Seed basic test data
        $pdo->exec("
            INSERT INTO users (id, username, email, password, role, first_name, last_name) VALUES
            (1, 'admin', 'admin@test.com', 'password_hash', 'admin', 'Admin', 'User'),
            (2, 'teacher1', 'teacher1@test.com', 'password_hash', 'teacher', 'Teacher', 'One'),
            (3, 'teacher2', 'teacher2@test.com', 'password_hash', 'teacher', 'Teacher', 'Two'),
            (4, 'student1', 'student1@test.com', 'password_hash', 'student', 'Student', 'One'),
            (5, 'student2', 'student2@test.com', 'password_hash', 'student', 'Student', 'Two');
        ");
        
        $pdo->exec("
            INSERT INTO teachers (id, user_id, department, specialization, max_students) VALUES
            (1, 2, 'Computer Science', 'AI/ML', 5),
            (2, 3, 'Computer Science', 'Software Engineering', 4);
        ");
        
        $pdo->exec("
            INSERT INTO students (id, user_id, program, level, department) VALUES
            (1, 4, 'Computer Science', 'Master', 'Computer Science'),
            (2, 5, 'Computer Science', 'Master', 'Computer Science');
        ");
        
        $pdo->exec("
            INSERT INTO companies (id, name, sector, email, phone, address) VALUES
            (1, 'Tech Corp', 'Technology', 'contact@techcorp.com', '123456789', '123 Tech Street'),
            (2, 'AI Solutions', 'Artificial Intelligence', 'info@aisolutions.com', '987654321', '456 AI Avenue');
        ");
        
        $pdo->exec("
            INSERT INTO internships (id, company_id, title, description, requirements, duration_weeks, available_spots) VALUES
            (1, 1, 'Software Developer Intern', 'Develop web applications', 'PHP, JavaScript', 12, 2),
            (2, 2, 'AI Research Intern', 'Research machine learning algorithms', 'Python, ML', 16, 1);
        ");
    }
}

// Test helper functions
function createMockStudent(array $data = []): object
{
    $defaults = [
        'id' => 1,
        'user_id' => 4,
        'program' => 'Computer Science',
        'level' => 'Master',
        'department' => 'Computer Science'
    ];
    
    $data = array_merge($defaults, $data);
    
    return (object) $data;
}

function createMockTeacher(array $data = []): object
{
    $defaults = [
        'id' => 1,
        'user_id' => 2,
        'department' => 'Computer Science',
        'specialization' => 'Software Engineering',
        'max_students' => 5,
        'remaining_capacity' => 5
    ];
    
    $data = array_merge($defaults, $data);
    
    $teacher = (object) $data;
    
    // Add required methods
    $teacher->getId = function() use ($teacher) { return $teacher->id; };
    $teacher->getDepartment = function() use ($teacher) { return $teacher->department; };
    $teacher->getMaxStudents = function() use ($teacher) { return $teacher->max_students; };
    $teacher->getRemainingCapacity = function() use ($teacher) { return $teacher->remaining_capacity; };
    $teacher->setRemainingCapacity = function($capacity) use ($teacher) { $teacher->remaining_capacity = $capacity; };
    
    return $teacher;
}

function createMockStudentWithMethods(array $data = []): object
{
    $student = createMockStudent($data);
    
    // Add required methods
    $student->getId = function() use ($student) { return $student->id; };
    $student->getDepartment = function() use ($student) { return $student->department; };
    
    return $student;
}

// Mock HTTP client for API testing
class MockHttpClient
{
    private array $responses = [];
    private array $requests = [];
    
    public function addResponse(string $method, string $url, array $response): void
    {
        $this->responses[$method][$url] = $response;
    }
    
    public function request(string $method, string $url, array $options = []): array
    {
        $this->requests[] = compact('method', 'url', 'options');
        
        if (isset($this->responses[$method][$url])) {
            return $this->responses[$method][$url];
        }
        
        return ['status' => 404, 'body' => 'Not Found'];
    }
    
    public function getRequests(): array
    {
        return $this->requests;
    }
    
    public function reset(): void
    {
        $this->responses = [];
        $this->requests = [];
    }
}

// Global test configuration
$GLOBALS['test_config'] = [
    'api_base_url' => 'http://localhost/tutoring/api',
    'test_user_token' => 'test_jwt_token_here',
];

echo "Test environment initialized successfully\n";