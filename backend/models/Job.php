<?php
require_once __DIR__ . '/../config/database.php';
class Job {
    private $conn;
    private $table_name = 'jobs';
    public $id;
    public $request_id;
    public $worker_id;
    public $status;
    public $updated_at;
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function create($request_id) {
        $query = "INSERT INTO " . $this->table_name . " SET request_id=:request_id, status='pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function getAvailable() {
        $query = "SELECT j.id, r.client_id, r.service_id, s.name as service_name, r.description, r.location, r.budget, r.preferred_time, r.created_at FROM " . $this->table_name . " j JOIN requests r ON j.request_id = r.id JOIN services s ON r.service_id = s.id WHERE j.worker_id IS NULL AND j.status = 'pending' AND r.status = 'pending'";
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
    public function countAssigned($worker_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE worker_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $worker_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    public function countAssignedByStatus($worker_id) {
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " WHERE worker_id = ? GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $worker_id);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['count'];
        }
        return $result;
    }
    public function sumEarnings($worker_id) {
        $query = "SELECT SUM(r.budget) as total FROM " . $this->table_name . " j JOIN requests r ON j.request_id = r.id WHERE j.worker_id = ? AND j.status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $worker_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }
    public function countAvailable() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE worker_id IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    public function getAssigned($worker_id) {
        $query = "SELECT j.id, r.client_id, r.service_id, s.name as service_name, r.description, r.location, r.budget, r.preferred_time, j.status, j.updated_at FROM " . $this->table_name . " j JOIN requests r ON j.request_id = r.id JOIN services s ON r.service_id = s.id WHERE j.worker_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $worker_id);
        $stmt->execute();
        return $stmt;
    }
    public function getById($id) {
        $query = "SELECT j.id, j.request_id, j.worker_id, j.status, j.updated_at, r.client_id, r.service_id, r.description, r.location, r.budget, r.preferred_time, s.name as service_name, s.price as service_price, s.is_hourly FROM " . $this->table_name . " j JOIN requests r ON j.request_id = r.id JOIN services s ON r.service_id = s.id WHERE j.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }
    public function getAllForAdmin() {
        $query = "SELECT j.id as job_id, j.status as job_status, j.updated_at, r.id as request_id, r.status as request_status, r.budget, r.preferred_time, " .
                 "s.name AS service_name, u_client.name AS client_name, u_client.email AS client_email, " .
                 "u_worker.name AS worker_name, u_worker.email AS worker_email " .
                 "FROM " . $this->table_name . " j " .
                 "JOIN requests r ON j.request_id = r.id " .
                 "JOIN services s ON r.service_id = s.id " .
                 "JOIN users u_client ON r.client_id = u_client.id " .
                 "LEFT JOIN users u_worker ON j.worker_id = u_worker.id " .
                 "ORDER BY j.updated_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    public function accept($id, $worker_id) {
        $query1 = "SELECT request_id FROM " . $this->table_name . " WHERE id = ?";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->bindParam(1, $id);
        $stmt1->execute();
        $row = $stmt1->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $request_id = $row['request_id'];
        $query2 = "UPDATE " . $this->table_name . " SET worker_id=:worker_id, status='in_progress', updated_at=NOW() WHERE id=:id";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bindParam(':worker_id', $worker_id);
        $stmt2->bindParam(':id', $id);
        if($stmt2->execute()) {
            $query3 = "UPDATE requests SET status='accepted' WHERE id = ?";
            $stmt3 = $this->conn->prepare($query3);
            $stmt3->bindParam(1, $request_id);
            $result = $stmt3->execute();
            if ($result) {
                return true;
            } else {
                $query4 = "UPDATE " . $this->table_name . " SET worker_id=NULL, status='pending', updated_at=NOW() WHERE id=:id";
                $stmt4 = $this->conn->prepare($query4);
                $stmt4->bindParam(':id', $id);
                $stmt4->execute();
                return false;
            }
        }
        return false;
    }
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $id);
        return $stmt->execute();
    }
    public function reject($id) {
        $query = "UPDATE " . $this->table_name . " SET worker_id=NULL, status='pending', updated_at=NOW() WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
