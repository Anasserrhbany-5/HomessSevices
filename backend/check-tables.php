<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/database.php';
    
    // Create database instance
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to connect to database");
    }
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'database' => 'home_services',
        'tables' => $tables,
        'table_count' => count($tables)
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
