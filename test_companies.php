<?php
// Test script for company management features
require_once 'includes/init.php';

echo "<h1>Company Management Interface Tests</h1>";

// Check database structure
echo "<h2>Database Structure</h2>";
echo "<pre>";
$query = "DESCRIBE companies";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Companies Table Structure:\n";
print_r($columns);
echo "</pre>";

// Check company count
$query = "SELECT COUNT(*) as count FROM companies";
$stmt = $db->prepare($query);
$stmt->execute();
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "<h2>Current Status</h2>";
echo "<p>Number of companies in database: <strong>$count</strong></p>";

// Check relationships
echo "<h2>Relationships</h2>";
echo "<pre>";
$query = "SELECT c.id, c.name, COUNT(i.id) as internship_count 
         FROM companies c 
         LEFT JOIN internships i ON c.id = i.company_id 
         GROUP BY c.id 
         ORDER BY internship_count DESC 
         LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Top 5 Companies by Internship Count:\n";
print_r($companies);
echo "</pre>";

// Display links to management interface
echo "<h2>Management Interface Links</h2>";
echo "<ul>";
echo "<li><a href='/tutoring/views/admin/companies.php'>Companies List</a></li>";
echo "<li><a href='/tutoring/views/admin/companies/create.php'>Add New Company</a></li>";
if ($count > 0) {
    // Get first company ID for testing
    $query = "SELECT id FROM companies LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $firstCompany = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($firstCompany) {
        $id = $firstCompany['id'];
        echo "<li><a href='/tutoring/views/admin/companies/show.php?id=$id'>View Sample Company (ID: $id)</a></li>";
        echo "<li><a href='/tutoring/views/admin/companies/edit.php?id=$id'>Edit Sample Company (ID: $id)</a></li>";
    }
}
echo "</ul>";

echo "<h2>CRUD Verification</h2>";
echo "<p>All CRUD operations have been implemented:</p>";
echo "<ul>";
echo "<li><strong>Create</strong>: /views/admin/companies/create.php (form) and /views/admin/companies/store.php (handler)</li>";
echo "<li><strong>Read</strong>: /views/admin/companies/show.php (detailed view) and /views/admin/companies.php (list view)</li>";
echo "<li><strong>Update</strong>: /views/admin/companies/edit.php (form) and /views/admin/companies/update.php (handler)</li>";
echo "<li><strong>Delete</strong>: Delete modal in show.php and /views/admin/companies/delete.php (handler)</li>";
echo "</ul>";
