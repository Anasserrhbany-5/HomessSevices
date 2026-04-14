<?php
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../middleware/auth.php';
class ChatController {
    public function sendMessage() {
        try {
            $payload = AuthMiddleware::authenticate();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->receiver_id) || !isset($data->message)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            $message = new Message();
            $message->sender_id = $payload['id'];
            $message->receiver_id = $data->receiver_id;
            $message->message = $data->message;
            if (!$message->create()) {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to send message']);
                return;
            }
            $user = new User();
            $stmt = $user->getAll();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sender = array_filter($users, function($u) use ($payload) {
                return $u['id'] == $payload['id'];
            });
            if (!empty($sender)) {
                $senderName = reset($sender)['name'];
                NotificationController::createNotification(
                    $data->receiver_id,
                    'new_message',
                    'Nouveau message',
                    'Vous avez reçu un nouveau message de ' . $senderName,
                    $message->id
                );
            }
            http_response_code(201);
            echo json_encode(['message' => 'Message sent successfully']);
        } catch (\Throwable $e) {
            error_log('ChatController::sendMessage error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Unable to send message']);
        }
    }
    public function getMessages($userId) {
        $payload = AuthMiddleware::authenticate();
        $message = new Message();
        $stmt = $message->getConversation($payload['id'], $userId);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($messages);
    }
    public function getConversations() {
        $payload = AuthMiddleware::authenticate();
        $userId = $payload['id'];
        $message = new Message();
        $stmt = $message->getConversations($userId);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($conversations);
    }
    public function getWorkers() {
        AuthMiddleware::authenticate();
        $user = new User();
        $stmt = $user->getByRole('worker');
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($workers);
    }
}
?>
