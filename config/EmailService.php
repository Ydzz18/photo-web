<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService Class
 * Handles all email operations using PHPMailer and Gmail SMTP
 */
class EmailService {
    private $mailer;
    private $errors = [];

    /**
     * Constructor - Initialize PHPMailer with Gmail SMTP
     */
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    /**
     * Configure SMTP settings for Gmail
     */
    private function configureSMTP() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = GMAIL_SMTP_HOST;
            $this->mailer->Port = GMAIL_SMTP_PORT;
            $this->mailer->SMTPSecure = GMAIL_SMTP_ENCRYPTION;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = GMAIL_ADDRESS;
            $this->mailer->Password = GMAIL_APP_PASSWORD;
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Set debug level
            if (EMAIL_DEBUG) {
                $this->mailer->SMTPDebug = 2;
                $this->mailer->Debugoutput = 'html';
            }

            // Set default from address
            $this->mailer->setFrom(FROM_EMAIL, FROM_NAME);
            $this->mailer->addReplyTo(REPLY_TO_EMAIL, FROM_NAME);
        } catch (Exception $e) {
            $this->errors[] = "SMTP Configuration Error: " . $e->getMessage();
        }
    }

    /**
     * Send Email Confirmation
     * 
     * @param string $recipient_email User's email address
     * @param string $user_name User's name
     * @param string $confirmation_link Confirmation link with token
     * @return bool
     */
    public function sendEmailConfirmation($recipient_email, $user_name, $confirmation_link) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient_email, $user_name);
            $this->mailer->Subject = 'Confirm Your Email - LensCraft Photography';
            
            $email_body = $this->getEmailTemplate('email_confirmation', [
                'user_name' => $user_name,
                'confirmation_link' => $confirmation_link,
                'site_name' => 'LensCraft Photography'
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $email_body;
            $this->mailer->AltBody = strip_tags($email_body);

            return $this->mailer->send();
        } catch (Exception $e) {
            $this->errors[] = "Email Confirmation Error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Send 2FA Code
     * 
     * @param string $recipient_email User's email address
     * @param string $user_name User's name
     * @param string $code 2FA code
     * @return bool
     */
    public function send2FACode($recipient_email, $user_name, $code) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient_email, $user_name);
            $this->mailer->Subject = 'Your Two-Factor Authentication Code - LensCraft Photography';
            
            $email_body = $this->getEmailTemplate('2fa_code', [
                'user_name' => $user_name,
                '2fa_code' => $code,
                'site_name' => 'LensCraft Photography'
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $email_body;
            $this->mailer->AltBody = strip_tags($email_body);

            return $this->mailer->send();
        } catch (Exception $e) {
            $this->errors[] = "2FA Code Error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Send Contact Form Email
     * 
     * @param string $visitor_email Visitor's email
     * @param string $visitor_name Visitor's name
     * @param string $subject Message subject
     * @param string $message Message content
     * @return bool
     */
    public function sendContactMessage($visitor_email, $visitor_name, $subject, $message) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress(GMAIL_ADDRESS, FROM_NAME);
            $this->mailer->Subject = 'New Contact Form Submission: ' . $subject;
            
            $email_body = $this->getEmailTemplate('contact_form', [
                'visitor_name' => $visitor_name,
                'visitor_email' => $visitor_email,
                'subject' => $subject,
                'message' => $message
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $email_body;
            $this->mailer->AltBody = strip_tags($email_body);

            return $this->mailer->send();
        } catch (Exception $e) {
            $this->errors[] = "Contact Form Error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Send Password Reset Email
     * 
     * @param string $recipient_email User's email address
     * @param string $user_name User's name
     * @param string $reset_link Password reset link with token
     * @return bool
     */
    public function sendPasswordReset($recipient_email, $user_name, $reset_link) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient_email, $user_name);
            $this->mailer->Subject = 'Reset Your Password - LensCraft Photography';
            
            $email_body = $this->getEmailTemplate('password_reset', [
                'user_name' => $user_name,
                'reset_link' => $reset_link,
                'site_name' => 'LensCraft Photography'
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $email_body;
            $this->mailer->AltBody = strip_tags($email_body);

            return $this->mailer->send();
        } catch (Exception $e) {
            $this->errors[] = "Password Reset Error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get email template with variables replaced
     * 
     * @param string $template_name Template name (without extension)
     * @param array $variables Variables to replace in template
     * @return string HTML email content
     */
    private function getEmailTemplate($template_name, $variables = []) {
        $template_path = EMAIL_TEMPLATES_DIR . $template_name . '.html';

        if (!file_exists($template_path)) {
            return $this->getFallbackTemplate($template_name, $variables);
        }

        $template = file_get_contents($template_path);

        // Replace variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', htmlspecialchars($value), $template);
        }

        return $template;
    }

    /**
     * Get fallback template if template file doesn't exist
     * 
     * @param string $template_name Template name
     * @param array $variables Variables array
     * @return string HTML content
     */
    private function getFallbackTemplate($template_name, $variables) {
        switch ($template_name) {
            case 'email_confirmation':
                return $this->emailConfirmationFallback($variables);
            case '2fa_code':
                return $this->twoFACodeFallback($variables);
            case 'contact_form':
                return $this->contactFormFallback($variables);
            case 'password_reset':
                return $this->passwordResetFallback($variables);
            default:
                return '';
        }
    }

    /**
     * Fallback template for email confirmation
     */
    private function emailConfirmationFallback($vars) {
        return "
        <html>
        <head><style>body{font-family:Arial,sans-serif;background:#f4f4f4;}
        .container{max-width:600px;margin:0 auto;background:white;padding:20px;border-radius:8px;}
        .button{background:#667eea;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:20px 0;}
        </style></head>
        <body>
        <div class='container'>
            <h2>Welcome, {$vars['user_name']}!</h2>
            <p>Thank you for registering with {$vars['site_name']}. Please confirm your email address to complete your registration.</p>
            <a href='{$vars['confirmation_link']}' class='button'>Confirm Email</a>
            <p>If you didn't create this account, please ignore this email.</p>
        </div>
        </body>
        </html>";
    }

    /**
     * Fallback template for 2FA code
     */
    private function twoFACodeFallback($vars) {
        return "
        <html>
        <head><style>body{font-family:Arial,sans-serif;background:#f4f4f4;}
        .container{max-width:600px;margin:0 auto;background:white;padding:20px;border-radius:8px;}
        .code{font-size:32px;font-weight:bold;color:#667eea;text-align:center;margin:20px 0;letter-spacing:5px;}
        </style></head>
        <body>
        <div class='container'>
            <h2>Your {$vars['site_name']} Login Code</h2>
            <p>Hi {$vars['user_name']},</p>
            <p>Your two-factor authentication code is:</p>
            <div class='code'>{$vars['2fa_code']}</div>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
        </div>
        </body>
        </html>";
    }

    /**
     * Fallback template for contact form
     */
    private function contactFormFallback($vars) {
        return "
        <html>
        <head><style>body{font-family:Arial,sans-serif;background:#f4f4f4;}
        .container{max-width:600px;margin:0 auto;background:white;padding:20px;border-radius:8px;}
        .info{background:#f9f9f9;padding:15px;border-left:4px solid #667eea;}
        </style></head>
        <body>
        <div class='container'>
            <h2>New Contact Form Submission</h2>
            <div class='info'>
                <p><strong>From:</strong> {$vars['visitor_name']}</p>
                <p><strong>Email:</strong> {$vars['visitor_email']}</p>
                <p><strong>Subject:</strong> {$vars['subject']}</p>
                <p><strong>Message:</strong></p>
                <p>{$vars['message']}</p>
            </div>
        </div>
        </body>
        </html>";
    }

    /**
     * Fallback template for password reset
     */
    private function passwordResetFallback($vars) {
        return "
        <html>
        <head><style>body{font-family:Arial,sans-serif;background:#f4f4f4;}
        .container{max-width:600px;margin:0 auto;background:white;padding:20px;border-radius:8px;}
        .button{background:#667eea;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:20px 0;}
        </style></head>
        <body>
        <div class='container'>
            <h2>Password Reset Request</h2>
            <p>Hi {$vars['user_name']},</p>
            <p>We received a request to reset your {$vars['site_name']} password. Click the button below to reset it.</p>
            <a href='{$vars['reset_link']}' class='button'>Reset Password</a>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn't request this, please ignore this email.</p>
        </div>
        </body>
        </html>";
    }

    /**
     * Get errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get last error
     * 
     * @return string
     */
    public function getLastError() {
        return end($this->errors);
    }
}
?>
