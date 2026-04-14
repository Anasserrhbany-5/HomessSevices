<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/validation.php';
class AuthController {
    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->name) || !isset($data->email) || !isset($data->password) || !isset($data->role)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        if (!Validation::validateEmail($data->email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email']);
            return;
        }
        if (!Validation::validatePassword($data->password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters']);
            return;
        }
        if (!in_array($data->role, ['client', 'worker', 'admin'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            return;
        }
        $user = new User();
        $user->email = $data->email;
        if ($user->emailExists()) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }
        $user->name = $data->name;
        $user->password = $data->password;
        $user->role = $data->role;
        if ($user->create()) {
            http_response_code(201);
            echo json_encode(['message' => 'User created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to create user']);
        }
    }
    public function login() {
        try {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->email) || !isset($data->password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email or password']);
                return;
            }
            $user = new User();
            $user->email = $data->email;
            if (!$user->emailExists()) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }
            if (password_verify($data->password, $user->password)) {
                $payload = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 24) 
                ];
                $token = JWT::encode($payload);
                http_response_code(200);
                echo json_encode(['token' => $token, 'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role]]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } catch(Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
        }
    }
}
?>
