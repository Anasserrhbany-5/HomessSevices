<?php
require_once __DIR__ . '/../middleware/auth.php';
class AdminSupportController {
    private $logsDir = __DIR__ . '/../logs';
    public function getSupportRequests() {
        AuthMiddleware::checkRole('admin');
        try {
            $logFile = $this->logsDir . '/support-requests.log';
            if (!file_exists($logFile)) {
                http_response_code(200);
                echo json_encode([
                    'requests' => [],
                    'count' => 0,
                    'message' => 'Aucune demande de support'
                ]);
                return;
            }
            $requests = [];
            $handle = fopen($logFile, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $request = json_decode($line, true);
                        if ($request) {
                            $requests[] = $request;
                        }
                    }
                }
                fclose($handle);
            }
            $requests = array_reverse($requests);
            http_response_code(200);
            echo json_encode([
                'requests' => $requests,
                'count' => count($requests)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error reading support requests: ' . $e->getMessage()]);
        }
    }
    public function getSupportRequestsExport() {
        AuthMiddleware::checkRole('admin');
        try {
            $logFile = $this->logsDir . '/support-requests.log';
            if (!file_exists($logFile)) {
                http_response_code(404);
                echo json_encode(['error' => 'Aucune demande trouvée']);
                return;
            }
            $requests = [];
            $handle = fopen($logFile, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $request = json_decode($line, true);
                        if ($request) {
                            $requests[] = $request;
                        }
                    }
                }
                fclose($handle);
            }
            ob_clean();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="support-requests-' . date('Y-m-d-H-i-s') . '.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['Date/Heure', 'User ID', 'Nom', 'Email', 'Catégorie', 'Priorité', 'Sujet', 'Message', 'Email Envoyé']);
            foreach ($requests as $request) {
                fputcsv($output, [
                    $request['timestamp'] ?? '',
                    $request['userId'] ?? '',
                    $request['name'] ?? '',
                    $request['email'] ?? '',
                    $request['category'] ?? '',
                    $request['priority'] ?? '',
                    $request['subject'] ?? '',
                    $request['message'] ?? '',
                    ($request['emailSent'] ? 'Oui' : 'Non')
                ]);
            }
            fclose($output);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error exporting support requests']);
        }
    }
    public function clearSupportRequests() {
        AuthMiddleware::checkRole('admin');
        try {
            $logFile = $this->logsDir . '/support-requests.log';
            if (file_exists($logFile)) {
                unlink($logFile);
            }
            http_response_code(200);
            echo json_encode(['message' => 'Demandes d\'assistance supprimées']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error clearing support requests']);
        }
    }
}
?>
