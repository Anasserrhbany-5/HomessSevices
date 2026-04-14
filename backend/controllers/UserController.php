<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/auth.php';
class UserController {
    public function getProfile() {
        $payload = AuthMiddleware::authenticate();
        $userId = $payload['id'];
        $user = new User();
        $user->id = $userId;
        if (!$user->getById()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        http_response_code(200);
        echo json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at
        ]);
    }
    public function updateProfile() {
        $payload = AuthMiddleware::authenticate();
        $userId = $payload['id'];
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->name) || !isset($data->email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $user = new User();
        $user->id = $userId;
        $user->name = $data->name;
        $user->email = $data->email;
        try {
            if ($user->update()) {
                http_response_code(200);
                echo json_encode(['message' => 'Profile updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to update profile']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
    public function changePassword() {
        $payload = AuthMiddleware::authenticate();
        $userId = $payload['id'];
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->currentPassword) || !isset($data->newPassword)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        if (strlen($data->newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 8 characters']);
            return;
        }
        $user = new User();
        $user->id = $userId;
        $user->email = $payload['email']; 
        if (!$user->emailExists()) {
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        if (!password_verify($data->currentPassword, $user->password)) {
            http_response_code(401);
            echo json_encode(['error' => 'Current password is incorrect']);
            return;
        }
        $user->password = password_hash($data->newPassword, PASSWORD_BCRYPT);
        try {
            if ($user->updatePassword()) {
                http_response_code(200);
                echo json_encode(['message' => 'Password changed successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to change password']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
    }
}
?>
