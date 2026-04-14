<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../middleware/auth.php';
class NotificationController {
    public function getNotifications() {
        $payload = AuthMiddleware::authenticate();
        $notification = new Notification();
        $stmt = $notification->getByUser($payload['id']);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($notifications);
    }
    public function getUnreadCount() {
        $payload = AuthMiddleware::authenticate();
        $notification = new Notification();
        $count = $notification->getUnreadCount($payload['id']);
        http_response_code(200);
        echo json_encode(['count' => (int)$count]);
    }
    public function markAsRead($id) {
        $payload = AuthMiddleware::authenticate();
        $notification = new Notification();
        if ($notification->markAsRead($id, $payload['id'])) {
            http_response_code(200);
            echo json_encode(['message' => 'Notification marked as read']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to mark notification as read']);
        }
    }
    public function markAllAsRead() {
        $payload = AuthMiddleware::authenticate();
        $notification = new Notification();
        if ($notification->markAllAsRead($payload['id'])) {
            http_response_code(200);
            echo json_encode(['message' => 'All notifications marked as read']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to mark notifications as read']);
        }
    }
    public function deleteNotification($id) {
        $payload = AuthMiddleware::authenticate();
        $notification = new Notification();
        if ($notification->delete($id, $payload['id'])) {
            http_response_code(200);
            echo json_encode(['message' => 'Notification deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to delete notification']);
        }
    }
    public static function createNotification($user_id, $type, $title, $message, $related_id = null) {
        $notification = new Notification();
        $notification->user_id = $user_id;
        $notification->type = $type;
        $notification->title = $title;
        $notification->message = $message;
        $notification->related_id = $related_id;
        return $notification->create();
    }
}
?>
