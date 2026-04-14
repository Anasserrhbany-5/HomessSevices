<?php
require_once __DIR__ . '/../config/database.php';
class Review {
    private $conn;
    private $table_name = 'reviews';
    public $id;
    public $client_id;
    public $worker_id;
    public $rating;
    public $comment;
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET client_id=:client_id, worker_id=:worker_id, rating=:rating, comment=:comment";
        $stmt = $this->conn->prepare($query);
        $this->client_id = htmlspecialchars(strip_tags($this->client_id));
        $this->worker_id = htmlspecialchars(strip_tags($this->worker_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':worker_id', $this->worker_id);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':comment', $this->comment);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function getAverageRatingForWorker($worker_id) {
        $query = "SELECT AVG(rating) as average_rating FROM " . $this->table_name . " WHERE worker_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $worker_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['average_rating'] !== null ? round((float)$row['average_rating'], 2) : null;
    }

    public function getByWorker($worker_id) {
        $query = "SELECT r.id, r.rating, r.comment, r.created_at, u.id as client_id, u.name as client_name " .
                 "FROM " . $this->table_name . " r " .
                 "JOIN users u ON r.client_id = u.id " .
                 "WHERE r.worker_id = ? " .
                 "ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $worker_id);
        $stmt->execute();
        return $stmt;
    }
}
?>
