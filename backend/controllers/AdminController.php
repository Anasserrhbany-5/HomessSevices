<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Request.php';
require_once __DIR__ . '/../middleware/auth.php';
class AdminController {
    public function getUsers() {
        AuthMiddleware::checkRole('admin');
        $user = new User();
        $stmt = $user->getAll();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($users);
    }
    public function deleteUser($id) {
        AuthMiddleware::checkRole('admin');
        $user = new User();
        $user->id = $id;
        if ($user->delete()) {
            http_response_code(200);
            echo json_encode(['message' => 'User deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to delete user']);
        }
    }
    public function updateUser($id) {
        AuthMiddleware::checkRole('admin');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->name) || !isset($data->email) || !isset($data->role)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $user = new User();
        $user->id = $id;
        $user->name = $data->name;
        $user->email = $data->email;
        $user->role = $data->role;
        if ($user->update()) {
            http_response_code(200);
            echo json_encode(['message' => 'User updated successfully']);
        } else {
            $error = 'Unable to update user';
            if (property_exists($user, 'errorMessage') && strpos($user->errorMessage, 'SQLSTATE[23000]') !== false) {
                $error = 'Impossible de mettre à jour : cet email est déjà utilisé.';
            }
            http_response_code(500);
            echo json_encode(['error' => $error]);
        }
    }
    public function getServices() {
        $service = new Service();
        $stmt = $service->getAll();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($services);
    }
    public function createService() {
        AuthMiddleware::checkRole('admin');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->name) || !isset($data->description) || !isset($data->price)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $service = new Service();
        $service->name = $data->name;
        $service->description = $data->description;
        $service->price = $data->price;
        $service->is_hourly = isset($data->is_hourly) ? $data->is_hourly : true;
        if ($service->create()) {
            http_response_code(201);
            echo json_encode(['message' => 'Service created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to create service']);
        }
    }
    public function updateService($id) {
        AuthMiddleware::checkRole('admin');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->name) || !isset($data->description) || !isset($data->price)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $service = new Service();
        $service->id = $id;
        $service->name = $data->name;
        $service->description = $data->description;
        $service->price = $data->price;
        $service->is_hourly = isset($data->is_hourly) ? $data->is_hourly : true;
        if ($service->update()) {
            http_response_code(200);
            echo json_encode(['message' => 'Service updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to update service']);
        }
    }
    public function deleteService($id) {
        AuthMiddleware::checkRole('admin');
        $service = new Service();
        $service->id = $id;
        if ($service->delete()) {
            http_response_code(200);
            echo json_encode(['message' => 'Service deleted successfully']);
        } else {
            $error = $service->errorMessage ?? 'Unable to delete service';
            if (strpos($error, 'SQLSTATE[23000]') !== false) {
                $error = 'Impossible de supprimer ce service : il est utilisé par une ou plusieurs demandes.';
            }
            http_response_code(500);
            echo json_encode(['error' => $error]);
        }
    }
    public function getRequests() {
        AuthMiddleware::checkRole('admin');
        $request = new Request();
        $stmt = $request->getAll();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($requests);
    }
    public function getJobs() {
        AuthMiddleware::checkRole('admin');
        require_once __DIR__ . '/../models/Job.php';
        $job = new Job();
        $stmt = $job->getAllForAdmin();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($jobs);
    }
}
?>
