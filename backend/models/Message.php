<?php
require_once __DIR__ . '/../config/database.php';
class Message {
    private $conn;
    private $table_name = 'messages';
    public $id;
    public $sender_id;
    public $receiver_id;
    public $message;
    public $created_at;
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getConversation($user1, $user2) {
        $query = "SELECT id, sender_id, receiver_id, message, created_at FROM " . $this->table_name . " WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user1);
        $stmt->bindParam(2, $user2);
        $stmt->bindParam(3, $user2);
        $stmt->bindParam(4, $user1);
        $stmt->execute();
        return $stmt;
    }
    public function getConversations($userId) {
        $query = "SELECT u.id, u.name, u.role, m.message AS last_message, m.created_at AS last_at " .
                 "FROM users u " .
                 "JOIN (" .
                 "  SELECT partner_id, message, created_at FROM (" .
                 "    SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id, " .
                 "           message, created_at, " .
                 "           ROW_NUMBER() OVER (PARTITION BY CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END ORDER BY created_at DESC) AS rn " .
                 "    FROM " . $this->table_name . " " .
                 "    WHERE sender_id = ? OR receiver_id = ?" .
                 "  ) t WHERE rn = 1" .
                 ") m ON u.id = m.partner_id " .
                 "ORDER BY m.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId);
        $stmt->bindParam(2, $userId);
        $stmt->bindParam(3, $userId);
        $stmt->bindParam(4, $userId);
        $stmt->execute();
        return $stmt;
    }
    public function countConversations($userId) {
        $query = "SELECT COUNT(DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END) as count " .
                 "FROM " . $this->table_name . " " .
                 "WHERE sender_id = ? OR receiver_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId);
        $stmt->bindParam(2, $userId);
        $stmt->bindParam(3, $userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET sender_id=:sender_id, receiver_id=:receiver_id, message=:message";
        $stmt = $this->conn->prepare($query);
        $this->sender_id = htmlspecialchars(strip_tags($this->sender_id));
        $this->receiver_id = htmlspecialchars(strip_tags($this->receiver_id));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $stmt->bindParam(':sender_id', $this->sender_id);
        $stmt->bindParam(':receiver_id', $this->receiver_id);
        $stmt->bindParam(':message', $this->message);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
