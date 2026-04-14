<?php
require_once __DIR__ . '/../utils/jwt.php';
class AuthMiddleware {
    private static function getAuthorizationHeader() {
        // getallheaders may not include Authorization in some Apache/PHP setups.
        $headers = [];
        if (function_exists('getallheaders')) {
            $rawHeaders = getallheaders();
            if (is_array($rawHeaders)) {
                foreach ($rawHeaders as $name => $value) {
                    $headers[strtolower($name)] = $value;
                }
            }
        }
        if (isset($headers['authorization'])) {
            return trim($headers['authorization']);
        }
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }
        if (!empty($_SERVER['Authorization'])) {
            return trim($_SERVER['Authorization']);
        }
        return null;
    }

    public static function authenticate() {
        $authHeader = self::getAuthorizationHeader();
        if (!$authHeader) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authorization header missing']);
            exit();
        }
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid authorization format']);
            exit();
        }
        $token = $matches[1];
        $payload = JWT::decode($token);
        if (!$payload) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid token']);
            exit();
        }
        return $payload;
    }
    public static function checkRole($requiredRole) {
        $payload = self::authenticate();
        if ($payload['role'] !== $requiredRole) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied']);
            exit();
        }
        return $payload;
    }
}
?>