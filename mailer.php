<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Path to PHPMailer autoload

class Mailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.example.com'; // Your SMTP server
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'no-reply@example.com'; // SMTP username
        $this->mail->Password = 'yourpassword'; // SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        
        // Sender info
        $this->mail->setFrom('no-reply@example.com', 'JIGJIGAHOMES');
        $this->mail->addReplyTo('support@example.com', 'JIGJIGAHOMES Support');
    }
    
    public function send($to, $subject, $body, $altBody = '') {
        try {
            // Recipient
            $this->mail->addAddress($to);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}