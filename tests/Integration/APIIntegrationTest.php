<?php

use PHPUnit\Framework\TestCase;

class APIIntegrationTest extends TestCase
{
    private string $baseUrl;
    private array $headers;
    
    protected function setUp(): void
    {
        $this->baseUrl = $GLOBALS['test_config']['api_base_url'];
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Reset test database
        TestDatabaseHelper::resetDatabase();
        TestDatabaseHelper::seedTestData();
    }
    
    public function testStudentsAPIEndpoints(): void
    {
        // Test GET /api/students/admin-list.php
        $response = $this->makeAPIRequest('GET', '/students/admin-list.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('students', $response['data']);
        $this->assertArrayHasKey('total', $response['data']);
    }
    
    public function testTeachersAPIEndpoints(): void
    {
        // Test GET /api/teachers/admin-list.php
        $response = $this->makeAPIRequest('GET', '/teachers/admin-list.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('teachers', $response['data']);
    }
    
    public function testInternshipsAPIEndpoints(): void
    {
        // Test GET /api/internships/index.php
        $response = $this->makeAPIRequest('GET', '/internships/index.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
    }
    
    public function testCompaniesAPIEndpoints(): void
    {
        // Test GET /api/companies/admin-list.php
        $response = $this->makeAPIRequest('GET', '/companies/admin-list.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('companies', $response['data']);
        
        // Test CREATE company
        $newCompany = [
            'name' => 'Test Company',
            'sector' => 'Technology',
            'email' => 'test@company.com',
            'phone' => '123456789',
            'address' => '123 Test Street'
        ];
        
        $response = $this->makeAPIRequest('POST', '/companies/create.php', $newCompany);
        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('id', $response['data']);
    }
    
    public function testAssignmentsAPIEndpoints(): void
    {
        // Test GET /api/assignments/index.php
        $response = $this->makeAPIRequest('GET', '/assignments/index.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        
        // Test assignment matrix
        $response = $this->makeAPIRequest('GET', '/assignments/matrix.php');
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('matrix', $response['data']);
    }
    
    public function testMessagesAPIEndpoints(): void
    {
        // Test GET conversations
        $response = $this->makeAPIRequest('GET', '/messages/conversations.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        
        // Test send message
        $message = [
            'recipient_id' => 2,
            'content' => 'Test message',
            'subject' => 'Test Subject'
        ];
        
        $response = $this->makeAPIRequest('POST', '/messages/send.php', $message);
        $this->assertContains($response['status'], [200, 201]);
    }
    
    public function testDocumentsAPIEndpoints(): void
    {
        // Test GET documents
        $response = $this->makeAPIRequest('GET', '/documents/index.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
    }
    
    public function testEvaluationsAPIEndpoints(): void
    {
        // Test GET evaluations
        $response = $this->makeAPIRequest('GET', '/evaluations/admin-list.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        
        // Test create evaluation
        $evaluation = [
            'student_id' => 1,
            'teacher_id' => 1,
            'evaluation_type' => 'midterm',
            'technical_skills' => 85,
            'professional_skills' => 80,
            'overall_score' => 82,
            'comments' => 'Good progress'
        ];
        
        $response = $this->makeAPIRequest('POST', '/evaluations/create.php', $evaluation);
        $this->assertContains($response['status'], [200, 201]);
    }
    
    public function testDashboardAPIEndpoints(): void
    {
        // Test GET dashboard stats
        $response = $this->makeAPIRequest('GET', '/dashboard/stats.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('total_students', $response['data']);
        $this->assertArrayHasKey('total_teachers', $response['data']);
        
        // Test GET activity
        $response = $this->makeAPIRequest('GET', '/dashboard/activity.php');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
    }
    
    public function testSearchAndPaginationFeatures(): void
    {
        // Test search in students
        $response = $this->makeAPIRequest('GET', '/students/admin-list.php?search=Student&page=1&limit=10');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('students', $response['data']);
        $this->assertArrayHasKey('pagination', $response['data']);
        
        // Test sorting
        $response = $this->makeAPIRequest('GET', '/students/admin-list.php?sort=first_name&order=asc');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']['students']);
        
        // Test filtering
        $response = $this->makeAPIRequest('GET', '/students/admin-list.php?filter_program=Computer Science');
        
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']['students']);
    }
    
    public function testErrorHandling(): void
    {
        // Test invalid endpoint
        $response = $this->makeAPIRequest('GET', '/invalid/endpoint.php');
        $this->assertEquals(404, $response['status']);
        
        // Test invalid data
        $response = $this->makeAPIRequest('POST', '/companies/create.php', ['invalid' => 'data']);
        $this->assertContains($response['status'], [400, 422]);
        
        // Test missing required fields
        $response = $this->makeAPIRequest('POST', '/evaluations/create.php', []);
        $this->assertContains($response['status'], [400, 422]);
    }
    
    public function testRateLimitingAndSecurity(): void
    {
        // Test multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->makeAPIRequest('GET', '/students/admin-list.php');
        }
        
        // All should succeed with proper rate limiting
        foreach ($responses as $response) {
            $this->assertContains($response['status'], [200, 429]); // OK or Too Many Requests
        }
    }
    
    private function makeAPIRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = [];
        if ($response) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $responseData = $decoded;
            }
        }
        
        return [
            'status' => $httpCode,
            'data' => $responseData,
            'raw' => $response
        ];
    }
}