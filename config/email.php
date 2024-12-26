<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            // Server settings
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.yourcompany.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'noreply@yourcompany.com';
            $this->mail->Password   = getenv('SMTP_PASSWORD');
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;

            // Sender details
            $this->mail->setFrom('noreply@yourcompany.com', 'On-Call Duty Planner');
        } catch (Exception $e) {
            error_log("Email Configuration Error: " . $e->getMessage());
        }
    }

    public function sendEmail($to, $subject, $body, $html = false) {
        try {
            // Recipients
            $this->mail->addAddress($to);

            // Content
            $this->mail->isHTML($html);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;

            // Send email
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email Send Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    public function sendScheduleChangeNotification($recipient, $requester, $details) {
        $subject = 'Schedule Change Request';
        $body = $html = true;
        $htmlBody = "
            <h2>Schedule Change Request</h2>
            <p>Hello {$recipient['name']},</p>
            <p>{$requester['name']} has requested a change to your on-call schedule:</p>
            <ul>
                <li>Original Start: {$details['original_start']}</li>
                <li>Original End: {$details['original_end']}</li>
                <li>Proposed Start: {$details['new_start']}</li>
                <li>Proposed End: {$details['new_end']}</li>
                <li>Reason: {$details['reason']}</li>
            </ul>
            <p>Please log in to approve or reject this request.</p>
        ";

        return $this->sendEmail($recipient['email'], $subject, $htmlBody, $html);
    }
}

// Utility function to securely get environment variables
function getEnvSecure($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
