<?php
require_once __DIR__ . '/../config/database.php';
class Notification {
    private $conn;
    private $table_name = 'notifications';
    public $id;
    public $user_id;
    public $type;
    public $title;
    public $message;
    public $related_id;
    public $is_read;
    public $created_at;
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, type=:type, title=:title, message=:message, related_id=:related_id";
        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->related_id = htmlspecialchars(strip_tags($this->related_id));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':related_id', $this->related_id);
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return $this->id;
        }
        return false;
    }
    public function getByUser($user_id, $limit = 50) {
        $query = "SELECT id, user_id, type, title, message, related_id, is_read, created_at FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit;
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
    public function markAsRead($id, $user_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = TRUE WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $user_id);
        return $stmt->execute();
    }
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        return $stmt->execute();
    }
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $user_id);
        return $stmt->execute();
    }
}
?>