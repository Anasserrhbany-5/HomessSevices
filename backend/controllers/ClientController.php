<?php
require_once __DIR__ . '/../models/Request.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../middleware/auth.php';
class ClientController {
    public function createRequest() {
        $payload = AuthMiddleware::checkRole('client');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->service_id) || !isset($data->description)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $request = new Request();
        $request->client_id = $payload['id'];
        $request->service_id = $data->service_id;
        $request->description = $data->description;
        $request->location = $data->location ?? null;
        $request->budget = $data->budget ?? null;
        $request->preferred_time = $data->preferred_time ?? null;
        $request->status = 'pending';
        if ($request_id = $request->create()) {
            $job = new Job();
            $job->create($request_id);
            http_response_code(201);
            echo json_encode(['message' => 'Request created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to create request']);
        }
    }
    public function getRequests() {
        $payload = AuthMiddleware::checkRole('client');
        $request = new Request();
        $stmt = $request->getByClientWithWorker($payload['id']);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($requests);
    }
    public function getRequest($id) {
        $payload = AuthMiddleware::checkRole('client');
        $request = new Request();
        $req = $request->getOne($id);
        if ($req && $req['client_id'] == $payload['id']) {
            http_response_code(200);
            echo json_encode($req);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Request not found']);
        }
    }
    public function createReview() {
        $payload = AuthMiddleware::checkRole('client');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->worker_id) || !isset($data->rating) || !isset($data->comment)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        $review = new Review();
        $review->client_id = $payload['id'];
        $review->worker_id = $data->worker_id;
        $review->rating = $data->rating;
        $review->comment = $data->comment;
        if ($review->create()) {
            http_response_code(201);
            echo json_encode(['message' => 'Review created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to create review']);
        }
    }
}
?>
