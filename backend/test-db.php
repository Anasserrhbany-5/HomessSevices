<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/database.php';
    
    // Test basic connection
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'database' => 'home_services'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
