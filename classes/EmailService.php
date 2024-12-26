<?php
namespace OnCallDutyPlanner\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        try {
            // SMTP configuration
            $this->mail->isSMTP();
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF; // Enable verbose debug output
            $this->mail->Host = 'smtp.example.com'; // Replace with your SMTP server
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'your_username';
            $this->mail->Password = 'your_password';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 587;
            
            // Sender details
            $this->mail->setFrom('noreply@oncalldutyplanner.com', 'On-Call Duty Planner');
        } catch (Exception $e) {
            // Log the error or handle it appropriately
            error_log('Email Service Initialization Error: ' . $e->getMessage());
        }
    }

    public function sendScheduleChangeNotification($recipient, $scheduleDetails) {
        try {
            // Reset previous recipients and configurations
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->addAddress($recipient);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Schedule Change Notification';
            
            $body = "
            <h1>Schedule Change Notification</h1>
            <p>Your on-call schedule has been modified:</p>
            <ul>
                <li>Start Time: {$scheduleDetails['start_datetime']}</li>
                <li>End Time: {$scheduleDetails['end_datetime']}</li>
                <li>Duty Type: {$scheduleDetails['duty_type']}</li>
            </ul>
            ";
            
            $this->mail->Body = $body;
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Email Send Error: ' . $e->getMessage());
            return false;
        }
    }
}
