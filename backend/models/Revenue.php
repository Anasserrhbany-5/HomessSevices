<?php
require_once __DIR__ . '/../config/database.php';
class Revenue {
    private $conn;
    private $platform_fee = 0.20; 
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function calculateRevenue($job_id, $total_amount) {
        $platform_revenue = $total_amount * $this->platform_fee;
        $worker_revenue = $total_amount - $platform_revenue;
        return [
            'total_amount' => $total_amount,
            'platform_revenue' => $platform_revenue,
            'worker_revenue' => $worker_revenue,
            'platform_fee_percentage' => $this->platform_fee * 100
        ];
    }
    public function recordRevenue($job_id, $worker_id, $total_amount) {
        $revenues = $this->calculateRevenue($job_id, $total_amount);
        $query = "INSERT INTO job_revenues
                  (job_id, worker_id, total_amount, platform_revenue, worker_revenue, platform_fee_percentage, created_at)
                  VALUES (:job_id, :worker_id, :total_amount, :platform_revenue, :worker_revenue, :platform_fee_percentage, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':worker_id', $worker_id);
        $stmt->bindParam(':total_amount', $revenues['total_amount']);
        $stmt->bindParam(':platform_revenue', $revenues['platform_revenue']);
        $stmt->bindParam(':worker_revenue', $revenues['worker_revenue']);
        $stmt->bindParam(':platform_fee_percentage', $revenues['platform_fee_percentage']);
        return $stmt->execute();
    }
    public function getWorkerRevenueStats($worker_id) {
        $query = "SELECT
                    COUNT(*) as total_jobs,
                    SUM(total_amount) as total_earned,
                    SUM(worker_revenue) as worker_total,
                    SUM(platform_revenue) as platform_total,
                    AVG(worker_revenue) as average_job_revenue,
                    MAX(worker_revenue) as best_job_revenue,
                    MIN(worker_revenue) as worst_job_revenue
                  FROM job_revenues
                  WHERE worker_id = :worker_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':worker_id', $worker_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthly_query = "SELECT
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            SUM(worker_revenue) as monthly_revenue,
                            COUNT(*) as jobs_count
                          FROM job_revenues
                          WHERE worker_id = :worker_id
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month DESC
                          LIMIT 12";
        $stmt2 = $this->conn->prepare($monthly_query);
        $stmt2->bindParam(':worker_id', $worker_id);
        $stmt2->execute();
        $monthly_revenues = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        return [
            'summary' => $result ?: [
                'total_jobs' => 0,
                'total_earned' => 0,
                'worker_total' => 0,
                'platform_total' => 0,
                'average_job_revenue' => 0,
                'best_job_revenue' => 0,
                'worst_job_revenue' => 0
            ],
            'monthly' => $monthly_revenues,
            'platform_fee' => $this->platform_fee * 100
        ];
    }
    public function getWorkerEarnings($worker_id) {
        $query = "SELECT SUM(worker_revenue) as total_earnings FROM job_revenues WHERE worker_id = :worker_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':worker_id', $worker_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_earnings'] ?? 0);
    }
    public function getPlatformRevenueStats() {
        $query = "SELECT
                    COUNT(*) as total_jobs_completed,
                    COUNT(DISTINCT worker_id) as total_workers,
                    SUM(total_amount) as total_revenue,
                    SUM(platform_revenue) as platform_total_revenue,
                    SUM(worker_revenue) as workers_total_revenue,
                    AVG(total_amount) as average_job_value,
                    AVG(platform_revenue) as average_platform_fee
                  FROM job_revenues";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthly_query = "SELECT
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            SUM(platform_revenue) as monthly_platform_revenue,
                            SUM(total_amount) as monthly_total_revenue,
                            COUNT(*) as jobs_count
                          FROM job_revenues
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month DESC
                          LIMIT 12";
        $stmt2 = $this->conn->prepare($monthly_query);
        $stmt2->execute();
        $monthly_revenues = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $top_workers_query = "SELECT
                                worker_id,
                                u.name,
                                COUNT(*) as jobs_count,
                                SUM(worker_revenue) as total_revenue
                              FROM job_revenues jr
                              JOIN users u ON jr.worker_id = u.id
                              GROUP BY worker_id, u.name
                              ORDER BY total_revenue DESC
                              LIMIT 10";
        $stmt3 = $this->conn->prepare($top_workers_query);
        $stmt3->execute();
        $top_workers = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        return [
            'summary' => $result ?: [
                'total_jobs_completed' => 0,
                'total_workers' => 0,
                'total_revenue' => 0,
                'platform_total_revenue' => 0,
                'workers_total_revenue' => 0,
                'average_job_value' => 0,
                'average_platform_fee' => 0
            ],
            'monthly' => $monthly_revenues,
            'top_workers' => $top_workers,
            'platform_fee_percentage' => $this->platform_fee * 100
        ];
    }
    public function updatePlatformFee($new_fee_percentage) {
        if ($new_fee_percentage < 5 || $new_fee_percentage > 30) {
            return false;
        }
        $this->platform_fee = $new_fee_percentage / 100;
        return true;
    }
    public function getPlatformFee() {
        return $this->platform_fee * 100;
    }
    public function calculatePotentialRevenue($amount) {
        return $this->calculateRevenue(null, $amount);
    }
}
