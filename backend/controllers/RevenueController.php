<?php
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Revenue.php';
class RevenueController {
    private $revenue;
    public function __construct() {
        $this->revenue = new Revenue();
    }
    public function getWorkerRevenueStats() {
        $payload = AuthMiddleware::authenticate();
        if (!in_array($payload['role'], ['worker', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        $worker_id = $payload['role'] === 'admin' && isset($_GET['worker_id'])
            ? $_GET['worker_id']
            : $payload['id'];
        try {
            $stats = $this->revenue->getWorkerRevenueStats($worker_id);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques'
            ]);
        }
    }
    public function getPlatformRevenueStats() {
        AuthMiddleware::checkRole('admin');
        try {
            $stats = $this->revenue->getPlatformRevenueStats();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques'
            ]);
        }
    }
    public function updatePlatformFee() {
        AuthMiddleware::checkRole('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $new_fee = $data['platform_fee_percentage'] ?? null;
        if (!$new_fee || !is_numeric($new_fee)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Taux de commission invalide'
            ]);
            return;
        }
        try {
            $success = $this->revenue->updatePlatformFee($new_fee);
            if ($success) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Taux de commission mis à jour',
                    'new_fee_percentage' => $new_fee
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Taux de commission doit être entre 5% et 30%'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour du taux'
            ]);
        }
    }
    public function getPlatformFee() {
        try {
            $fee = $this->revenue->getPlatformFee();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'platform_fee_percentage' => $fee
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération du taux'
            ]);
        }
    }
    public function calculatePotentialRevenue() {
        $data = json_decode(file_get_contents('php://input'), true);
        $amount = $data['amount'] ?? null;
        if (!$amount || !is_numeric($amount) || $amount <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Montant invalide'
            ]);
            return;
        }
        try {
            $calculation = $this->revenue->calculatePotentialRevenue($amount);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'calculation' => $calculation
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du calcul'
            ]);
        }
    }
}
