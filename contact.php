<?php
session_start();
require_once 'db_connect.php';
require_once 'config/email_config.php';
require_once 'config/EmailService.php';
require_once 'settings.php';

$settings = new SiteSettings();
$site_name = $settings->get('site_name', 'LensCraft');
$contact_email = $settings->get('contact_email', 'info@example.com');

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate input
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Sanitize input
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        try {
            // Send email using EmailService
            $emailService = new EmailService();
            
            if ($emailService->sendContactMessage($email, $name, $subject, $message)) {
                $success_message = 'Thank you for contacting us! We will get back to you soon.';
                // Clear form fields
                $name = $email = $subject = $message = '';
            } else {
                $error_message = 'An error occurred while sending your message. Please try again later.';
                if ($emailService->getLastError()) {
                    error_log("Contact Form Error: " . $emailService->getLastError());
                }
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred while sending your message. Please try again later.';
            error_log("Contact Form Exception: " . $e->getMessage());
        }
    }
}

$page_title = 'Contact Us';
$page_css = 'style.css';
include 'header.php';
?>

<section class="contact-section">
    <div class="container">
        <h2 class="section-title">Get In Touch</h2>
        <div class="contact-content">
            <div class="contact-form">
                <?php if ($success_message): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer_logged_in.php'; ?>
