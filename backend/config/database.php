<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'home_services';
    private $username = 'root';
    private $password = '';
    private $conn;
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}
?>