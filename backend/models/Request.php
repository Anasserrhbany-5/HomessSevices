<?php
require_once __DIR__ . '/../config/database.php';
class Request {
    private $conn;
    private $table_name = 'requests';
    public $id;
    public $client_id;
    public $service_id;
    public $description;
    public $location;
    public $budget;
    public $preferred_time;
    public $status;
    public $created_at;
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getAll() {
        $query = "SELECT id, client_id, service_id, description, status, created_at FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    public function count() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    public function countByStatusGrouped() {
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['count'];
        }
        return $result;
    }
    public function countByClient($client_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE client_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    public function countByClientStatus($client_id) {
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " WHERE client_id = ? GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['count'];
        }
        return $result;
    }
    public function sumBudgetByStatus($status) {
        $query = "SELECT SUM(budget) as total FROM " . $this->table_name . " WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }
    public function getByClient($client_id) {
        $query = "SELECT r.id, r.client_id, r.service_id, s.name AS service_name, r.description, r.location, r.budget, r.preferred_time, r.status, r.created_at FROM " . $this->table_name . " r JOIN services s ON r.service_id = s.id WHERE r.client_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->execute();
        return $stmt;
    }
    public function getByClientWithWorker($client_id) {
        $query = "SELECT r.id, r.client_id, r.service_id, s.name AS service_name, r.description, r.location, r.budget, r.preferred_time, r.status, r.created_at, 
                    j.id AS job_id, j.status AS job_status, j.worker_id, u.name as worker_name, u.email as worker_email
                  FROM " . $this->table_name . " r 
                  JOIN services s ON r.service_id = s.id 
                  LEFT JOIN jobs j ON r.id = j.request_id
                  LEFT JOIN users u ON j.worker_id = u.id
                  WHERE r.client_id = ?
                  ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->execute();
        return $stmt;
    }
    public function getOne($id) {
        $query = "SELECT id, client_id, service_id, description, status, created_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET client_id=:client_id, service_id=:service_id, description=:description, location=:location, budget=:budget, preferred_time=:preferred_time, status=:status";
        $stmt = $this->conn->prepare($query);
        $this->client_id = htmlspecialchars(strip_tags($this->client_id));
        $this->service_id = htmlspecialchars(strip_tags($this->service_id));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->budget = htmlspecialchars(strip_tags($this->budget));
        $this->preferred_time = htmlspecialchars(strip_tags($this->preferred_time));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':service_id', $this->service_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':budget', $this->budget);
        $stmt->bindParam(':preferred_time', $this->preferred_time);
        $stmt->bindParam(':status', $this->status);
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return $this->id;
        }
        return false;
    }
}
?>
