<?php
/**
 * Email Helper
 * PHPMailer integration for sending emails using SMTP settings
 * 
 * Installation: Run composer require phpmailer/phpmailer
 * Or download manually from: https://github.com/PHPMailer/PHPMailer
 */

require_once __DIR__ . '/../../config.php';

/**
 * Get SMTP settings from database
 * @return array SMTP configuration
 */
function getSMTPSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM api_settings WHERE setting_key LIKE 'smtp_%'");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (PDOException $e) {
        error_log("SMTP settings error: " . $e->getMessage());
        return [];
    }
}

/**
 * Send email using PHPMailer and configured SMTP settings
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $recipientName Optional recipient name
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $recipientName = '') {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not found. Install via: composer require phpmailer/phpmailer");
        return false;
    }
    
    $settings = getSMTPSettings();
    
    // Validate required settings
    if (empty($settings['smtp_host']) || empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
        error_log("SMTP settings incomplete. Configure in Admin > API & Email Settings.");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        $mail->SMTPSecure = $settings['smtp_encryption'] ?? 'tls';
        $mail->Port = $settings['smtp_port'] ?? 587;
        
        // Recipients
        $mail->setFrom(
            $settings['smtp_from_email'] ?? $settings['smtp_username'], 
            $settings['smtp_from_name'] ?? 'My Agency'
        );
        $mail->addAddress($to, $recipientName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send newsletter welcome email
 * @param string $email Subscriber email
 * @param string $name Subscriber name
 * @return bool Success status
 */
function sendNewsletterWelcome($email, $name = '') {
    $siteName = getSetting('site_name', 'My Agency');
    
    $subject = "Welcome to {$siteName} Newsletter!";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9fafb; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to {$siteName}!</h1>
            </div>
            <div class='content'>
                <p>Hi " . ($name ? htmlspecialchars($name) : "there") . ",</p>
                <p>Thank you for subscribing to our newsletter! We're excited to have you join our community.</p>
                <p>You'll receive updates about:</p>
                <ul>
                    <li>Latest projects and case studies</li>
                    <li>Industry insights and tips</li>
                    <li>Exclusive offers and announcements</li>
                </ul>
                <p>Stay tuned for great content!</p>
                <p>Best regards,<br>The {$siteName} Team</p>
            </div>
            <div class='footer'>
                <p>You're receiving this because you subscribed at {$siteName}.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body, $name);
}

/**
 * Send booking confirmation email
 * @param array $booking Booking details
 * @return bool Success status
 */
function sendBookingConfirmation($booking) {
    $siteName = getSetting('site_name', 'My Agency');
    
    $subject = "Booking Confirmation - {$siteName}";
    
    $date = date('F j, Y', strtotime($booking['booking_date']));
    $time = date('g:i A', strtotime($booking['booking_time']));
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #10b981; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9fafb; }
            .details { background: white; padding: 15px; border-left: 4px solid #10b981; margin: 20px 0; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✓ Booking Confirmed</h1>
            </div>
            <div class='content'>
                <p>Hi " . htmlspecialchars($booking['name']) . ",</p>
                <p>Your consultation booking has been confirmed!</p>
                
                <div class='details'>
                    <h3>Booking Details:</h3>
                    <p><strong>Service:</strong> " . htmlspecialchars($booking['service_type']) . "</p>
                    <p><strong>Date:</strong> {$date}</p>
                    <p><strong>Time:</strong> {$time}</p>
                    <p><strong>Duration:</strong> " . htmlspecialchars($booking['duration'] ?? '30 minutes') . "</p>
                    " . (!empty($booking['zoom_link']) ? "<p><strong>Meeting Link:</strong> <a href='" . htmlspecialchars($booking['zoom_link']) . "'>Join Zoom</a></p>" : "") . "
                </div>
                
                <p>We look forward to speaking with you!</p>
                <p>Best regards,<br>The {$siteName} Team</p>
            </div>
            <div class='footer'>
                <p>If you need to reschedule, please contact us at " . getContactEmail() . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($booking['email'], $subject, $body, $booking['name']);
}

/**
 * Send new testimonial notification to admin
 * @param array $testimonial Testimonial details
 * @return bool Success status
 */
function sendTestimonialNotification($testimonial) {
    $adminEmail = getContactEmail();
    $siteName = getSetting('site_name', 'My Agency');
    
    $subject = "New Testimonial Submitted - {$siteName}";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #8b5cf6; color: white; padding: 20px; }
            .content { padding: 20px; background: #f9fafb; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Testimonial Awaiting Approval</h2>
            </div>
            <div class='content'>
                <p><strong>From:</strong> " . htmlspecialchars($testimonial['client_name']) . "</p>
                <p><strong>Company:</strong> " . htmlspecialchars($testimonial['client_company'] ?? 'N/A') . "</p>
                <p><strong>Rating:</strong> " . str_repeat('⭐', $testimonial['rating']) . "</p>
                <p><strong>Testimonial:</strong></p>
                <p>" . nl2br(htmlspecialchars($testimonial['testimonial'])) . "</p>
                <p><a href='" . ($_SERVER['REQUEST_SCHEME'] ?? 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/admin/testimonials-manage.php'>Review in Admin Panel</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($adminEmail, $subject, $body);
}

/**
 * Send inquiry notification to admin
 * @param array $inquiry Inquiry details
 * @return bool Success status
 */
function sendInquiryNotification($inquiry) {
    $adminEmail = getContactEmail();
    $siteName = getSetting('site_name', 'My Agency');
    
    $subject = "New Contact Inquiry - {$siteName}";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> " . htmlspecialchars($inquiry['name']) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($inquiry['email']) . "</p>
        <p><strong>Phone:</strong> " . htmlspecialchars($inquiry['phone'] ?? 'Not provided') . "</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($inquiry['message'])) . "</p>
        <hr>
        <p style='font-size: 12px; color: #666;'>Respond promptly to provide excellent customer service!</p>
    </body>
    </html>
    ";
    
    return sendEmail($adminEmail, $subject, $body);
}

// Note: This file requires PHPMailer library
// Install using: composer require phpmailer/phpmailer
// Or download from: https://github.com/PHPMailer/PHPMailer
// Place PHPMailer files in a 'vendor' folder if not using Composer
