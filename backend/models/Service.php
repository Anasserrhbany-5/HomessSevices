<?php
require_once __DIR__ . '/../config/database.php';
class Service {
    private $conn;
    private $table_name = 'services';
    public $id;
    public $name;
    public $description;
    public $price;
    public $is_hourly;
    public $errorMessage;
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getAll() {
        $query = "SELECT id, name, description, price, is_hourly FROM " . $this->table_name;
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
    public function getOne() {
        $query = "SELECT id, name, description, price, is_hourly FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, description=:description, price=:price, is_hourly=:is_hourly";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->is_hourly = $this->is_hourly ? 1 : 0;
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':is_hourly', $this->is_hourly);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name=:name, description=:description, price=:price, is_hourly=:is_hourly WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->is_hourly = $this->is_hourly ? 1 : 0;
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':is_hourly', $this->is_hourly);
        $stmt->bindParam(':id', $this->id);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }
}
?>
