<?php
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Request.php';
require_once __DIR__ . '/../models/Revenue.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../middleware/auth.php';
class WorkerController {
    public function getAvailableJobs() {
        AuthMiddleware::checkRole('worker');
        $job = new Job();
        $stmt = $job->getAvailable();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($jobs);
    }
    public function acceptJob() {
        $payload = AuthMiddleware::checkRole('worker');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing job id']);
            return;
        }
        $job = new Job();
        if ($job->accept($data->id, $payload['id'])) {
            http_response_code(200);
            echo json_encode(['message' => 'Job accepted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to accept job']);
        }
    }
    public function rejectJob() {
        $payload = AuthMiddleware::checkRole('worker');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing job id']);
            return;
        }
        $job = new Job();
        if ($job->reject($data->id)) {            
            $stmt = $job->getAssigned($payload['id']);
            $assignedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $acceptedJob = array_filter($assignedJobs, function($j) use ($data) {
                return $j['id'] == $data->id;
            });
            if (!empty($acceptedJob)) {
                $jobDetails = reset($acceptedJob);
                NotificationController::createNotification(
                    $jobDetails['client_id'],
                    'job_accepted',
                    'Job accepté',
                    'Votre demande de service "' . $jobDetails['service_name'] . '" a été acceptée par un travailleur.',
                    $data->id
                );
            }
            http_response_code(200);
            echo json_encode(['message' => 'Job rejected successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to reject job']);
        }
    }
    public function getAssignedJobs() {
        $payload = AuthMiddleware::checkRole('worker');
        $job = new Job();
        $stmt = $job->getAssigned($payload['id']);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($jobs);
    }

    public function getReviews() {
        $payload = AuthMiddleware::checkRole('worker');
        require_once __DIR__ . '/../models/Review.php';
        $review = new Review();
        $stmt = $review->getByWorker($payload['id']);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($reviews);
    }
    public function updateJobStatus() {
        $payload = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id) || !isset($data->status)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id or status']);
            return;
        }
        $jobModel = new Job();
        $jobData = $jobModel->getById($data->id);
        if (!$jobData) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
            return;
        }
        $userRole = $payload['role'];
        $userId = $payload['id'];
        $isWorker = $userRole === 'worker' && $jobData['worker_id'] == $userId;
        $isClient = $userRole === 'client' && $jobData['client_id'] == $userId;
        if (!($isWorker || $isClient)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $newStatus = $data->status;
        if (!$jobModel->updateStatus($data->id, $newStatus)) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to update job status']);
            return;
        }
        $request = new Request();
        $request->id = $jobData['request_id'];
        $request->status = $newStatus === 'completed' ? 'completed' : ($newStatus === 'pending' ? 'pending' : 'accepted');
        if ($request->status === 'completed') {
            $request->status = 'completed';
        }
        $stmt = $this->updateRequestStatus($jobData['request_id'], $request->status);
        if ($newStatus === 'completed') {
            $revenue = new Revenue();
            $revenue->recordRevenue($data->id, $jobData['worker_id'], $jobData['budget']);
        }
        if ($newStatus === 'completed') {
            $targetUser = $isWorker ? $jobData['client_id'] : $jobData['worker_id'];
            $title = 'Travail terminé';
            $message = $isWorker
                ? 'Le travail a été marqué comme terminé par le travailleur.'
                : 'Le client a confirmé que le travail est terminé.';
            NotificationController::createNotification(
                $targetUser,
                'job_completed',
                $title,
                $message,
                $data->id
            );
        }
        http_response_code(200);
        echo json_encode(['message' => 'Job status updated successfully']);
    }
    private function updateRequestStatus($requestId, $status) {
        $request = new Request();
        $request->id = $requestId;
        $request->status = $status;
        return $request->updateStatus();
    }
}
?>
