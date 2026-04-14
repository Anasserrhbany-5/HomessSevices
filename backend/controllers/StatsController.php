<?php
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Request.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Revenue.php';
class StatsController {
    public function getStats() {
        $payload = AuthMiddleware::authenticate();
        $role = $payload['role'];
        $userId = $payload['id'];
        $stats = [];
        if ($role === 'admin') {
            $user = new User();
            $stats['totalUsers'] = $user->count();
            $service = new Service();
            $stats['totalServices'] = $service->count();
            $request = new Request();
            $stats['totalRequests'] = $request->count();
            $stats['requestsByStatus'] = $request->countByStatusGrouped();
            $stats['revenue'] = $request->sumBudgetByStatus('completed');
            $job = new Job();
            $stats['totalJobs'] = $job->count();
            $stats['jobsByStatus'] = $job->countByStatusGrouped();
            $revenueModel = new Revenue();
            $platformStats = $revenueModel->getPlatformRevenueStats();
            $stats['platformRevenue'] = (float) ($platformStats['summary']['platform_total_revenue'] ?? 0);
            $stats['workersRevenue'] = (float) ($platformStats['summary']['workers_total_revenue'] ?? 0);
            $stats['platformFeePercentage'] = (float) ($platformStats['platform_fee_percentage'] ?? 0);
        } elseif ($role === 'client') {
            $request = new Request();
            $stats['totalRequests'] = $request->countByClient($userId);
            $stats['requestsByStatus'] = $request->countByClientStatus($userId);
            $message = new Message();
            $stats['conversations'] = $message->countConversations($userId);
        } elseif ($role === 'worker') {
            $job = new Job();
            $stats['assignedJobs'] = $job->countAssigned($userId);
            $stats['assignedByStatus'] = $job->countAssignedByStatus($userId);
            $revenue = new Revenue();
            $stats['earnings'] = $revenue->getWorkerEarnings($userId);
            $review = new Review();
            $stats['averageRating'] = $review->getAverageRatingForWorker($userId);
            $stats['availableJobs'] = $job->countAvailable();
        }
        http_response_code(200);
        echo json_encode($stats);
    }
    public function getPublicStats() {
        $stats = [];
        $user = new User();
        $stats['totalUsers'] = $user->count();
        $request = new Request();
        $stats['totalRequests'] = $request->count();
        $job = new Job();
        $stats['totalJobs'] = $job->count();
        $revenueModel = new Revenue();
        $platformStats = $revenueModel->getPlatformRevenueStats();
        $stats['totalRevenue'] = (float) ($platformStats['summary']['platform_total_revenue'] ?? 0);
        http_response_code(200);
        echo json_encode($stats);
    }
}
?>
