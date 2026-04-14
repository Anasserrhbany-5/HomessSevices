<?php
require_once __DIR__ . '/../middleware/auth.php';
class SupportController {
    private $logsDir = __DIR__ . '/../logs';
    public function __construct() {
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0755, true);
        }
    }
    public function sendSupportEmail() {
        try {
            $payload = AuthMiddleware::authenticate();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->subject) || !isset($data->message) || !isset($data->category)) {
                http_response_code(400);
                echo json_encode(['error' => 'Champs requis manquants']);
                return;
            }
            $subject = htmlspecialchars(strip_tags($data->subject));
            $message = htmlspecialchars(strip_tags($data->message));
            $category = htmlspecialchars(strip_tags($data->category));
            $priority = isset($data->priority) ? htmlspecialchars(strip_tags($data->priority)) : 'normal';
            $senderName = isset($data->senderName) ? htmlspecialchars(strip_tags($data->senderName)) : 'Unknown User';
            $senderEmail = isset($data->senderEmail) ? filter_var($data->senderEmail, FILTER_SANITIZE_EMAIL) : '';
            $userId = $payload['id'] ?? 'unknown';
            if (strlen($message) < 20) {
                http_response_code(400);
                echo json_encode(['error' => 'Le message doit contenir au moins 20 caractères']);
                return;
            }
            $adminEmail = $this->getAdminEmail();
            if (!$adminEmail) {
                $this->logSupportRequest($userId, $senderName, $senderEmail, $category, $priority, $subject, $message);
                http_response_code(200);
                echo json_encode([
                    'message' => 'Votre demande a été enregistrée. L\'administrateur vous répondra bientôt.',
                    'ticketId' => uniqid('TICKET-'),
                    'note' => 'Email non configuré, demande enregistrée localement'
                ]);
                return;
            }
            $emailSubject = "[SUPPORT - " . strtoupper($category) . "] " . $subject;
            $emailBody = $this->buildEmailContent(
                $senderName,
                $senderEmail,
                $category,
                $priority,
                $subject,
                $message,
                $userId
            );
            $emailSent = false;
            if (function_exists('mail')) {
                $emailSent = $this->sendEmail($adminEmail, $emailSubject, $emailBody);
            }
            $this->logSupportRequest($userId, $senderName, $senderEmail, $category, $priority, $subject, $message, $emailSent);
            http_response_code(200);
            echo json_encode([
                'message' => 'Votre demande d\'assistance a été enregistrée avec succès. Vous recevrez une réponse bientôt.',
                'ticketId' => uniqid('TICKET-')
            ]);
        } catch (Exception $e) {
            error_log("Support email error: " . $e->getMessage());
            $this->logSupportRequest(
                isset($payload['id']) ? $payload['id'] : 'unknown',
                'Unknown',
                'unknown@example.com',
                'other',
                'normal',
                'Error',
                $e->getMessage(),
                false
            );
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors du traitement de votre demande']);
        }
    }
    private function getAdminEmail() {
        try {
            $adminEmail = getenv('ADMIN_EMAIL');
            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                return $adminEmail;
            }
            require_once __DIR__ . '/../models/User.php';
            $user = new User();
            $stmt = $user->getByRole('admin');
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($admins) && filter_var($admins[0]['email'], FILTER_VALIDATE_EMAIL)) {
                return $admins[0]['email'];
            }
        } catch (Exception $e) {
            error_log("Error getting admin email: " . $e->getMessage());
        }
        return null;
    }
    private function buildEmailContent($senderName, $senderEmail, $category, $priority, $subject, $message, $userId) {
        $categoryLabel = [
            'technical' => 'Problème Technique',
            'payment' => 'Problème de Paiement',
            'job' => 'Problème avec un Travail',
            'account' => 'Problème de Compte',
            'other' => 'Autre'
        ];
        $priorityLabel = [
            'low' => 'Faible',
            'normal' => 'Normal',
            'high' => 'Élevée',
            'urgent' => 'Urgent'
        ];
        $body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; background-color: 
        .container { background-color: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 20px auto; }
        .header { background-color: 
        .content { padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: 
        .value { color: 
        .message-box { background-color: 
        .footer { background-color: 
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>📧 Nouvelle Demande de Support</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Sujet:</span>
                <div class='value'>" . htmlspecialchars($subject) . "</div>
            </div>
            <div class='field'>
                <span class='label'>User ID:</span>
                <div class='value'>" . htmlspecialchars($userId) . "</div>
            </div>
            <div class='field'>
                <span class='label'>De:</span>
                <div class='value'>" . htmlspecialchars($senderName) . " (" . htmlspecialchars($senderEmail) . ")</div>
            </div>
            <div class='field'>
                <span class='label'>Catégorie:</span>
                <div class='value'>" . ($categoryLabel[$category] ?? $category) . "</div>
            </div>
            <div class='field'>
                <span class='label'>Priorité:</span>
                <div class='value'>" . ($priorityLabel[$priority] ?? $priority) . "</div>
            </div>
            <div class='message-box'>
                <div class='label'>Message:</div>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid 
                <p style='font-size: 12px; color: 
                    Répondez directement à cet email ou connectez-vous au panneau d'administration pour gérer cette demande.
                </p>
            </div>
        </div>
        <div class='footer'>
            <p>Système de Gestion des Travaux à Domiciles</p>
        </div>
    </div>
</body>
</html>
        ";
        return $body;
    }
    private function sendEmail($to, $subject, $body) {
        try {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: noreply@gestion-travaux.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            if (!function_exists('mail')) {
                error_log("Mail function not available");
                return false;
            }
            return @mail($to, $subject, $body, $headers);
        } catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            return false;
        }
    }
    private function logSupportRequest($userId, $senderName, $senderEmail, $category, $priority, $subject, $message, $emailSent = null) {
        try {
            $logFile = $this->logsDir . '/support-requests.log';
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = json_encode([
                'timestamp' => $timestamp,
                'userId' => $userId,
                'name' => $senderName,
                'email' => $senderEmail,
                'category' => $category,
                'priority' => $priority,
                'subject' => $subject,
                'message' => $message,
                'emailSent' => $emailSent
            ]) . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("Error logging support request: " . $e->getMessage());
        }
    }
}
?>
