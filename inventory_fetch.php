<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This file creates the $pdo variable
require_once 'includes/db.php'; 

try {
    // Select all columns from the inventory table
    $stmt = $pdo->query("SELECT * FROM inventory");
    
    // Fetch all rows into an associative array
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the data in the format DataTables expects: { "data": [...] }
    echo json_encode(['data' => $data]);

} catch (PDOException $e) {
    // If the query fails, return a JSON error
    http_response_code(500); // Set HTTP status to 500 Internal Server Error
    echo json_encode(['error' => 'Failed to fetch inventory data: ' . $e->getMessage()]);
}
?>