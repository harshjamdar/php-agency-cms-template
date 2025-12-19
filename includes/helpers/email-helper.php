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
 * Falls back to native PHP mail() if PHPMailer is not available
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $recipientName Optional recipient name
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $recipientName = '') {
    $settings = getSMTPSettings();
    
    // Try PHPMailer if available
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Validate required settings
        if (empty($settings['smtp_host']) || empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
            error_log("SMTP settings incomplete. Configure in Admin > API & Email Settings.");
            // Fall back to native mail
            return sendEmailNative($to, $subject, $body, $recipientName, $settings);
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
            
            // Anti-spam settings
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Priority = 3; // 1 = High, 3 = Normal, 5 = Low
            
            // Recipients
            $fromEmail = $settings['smtp_from_email'] ?? $settings['smtp_username'];
            $fromName = $settings['smtp_from_name'] ?? 'My Agency';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $recipientName);
            $mail->addReplyTo($fromEmail, $fromName);
            
            // Anti-spam headers
            $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
            $mail->addCustomHeader('X-Priority', '3');
            $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
            $mail->addCustomHeader('X-MimeOLE', 'Produced By Microsoft MimeOLE V6.00.2900.2180');
            $mail->addCustomHeader('Importance', 'Normal');
            
            // Always add List-Unsubscribe header (required for bulk emails)
            $siteUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $mail->addCustomHeader('List-Unsubscribe', '<' . $siteUrl . '/newsletter-unsubscribe.php>');
            $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Generate proper plain text version
            $plainText = strip_tags($body);
            $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = str_replace(' .', '.', $plainText);
            $plainText = trim($plainText);
            
            // Ensure we have some text content
            if (strlen($plainText) < 10) {
                $plainText = "View this email in your browser.\n\n" . $subject;
            }
            
            $mail->AltBody = $plainText;
            $mail->ContentType = 'multipart/alternative';
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            // Fall back to native mail
            return sendEmailNative($to, $subject, $body, $recipientName, $settings);
        }
    } else {
        // PHPMailer not installed, use native mail()
        error_log("PHPMailer not found. Using native mail(). Install PHPMailer for better deliverability: composer require phpmailer/phpmailer");
        return sendEmailNative($to, $subject, $body, $recipientName, $settings);
    }
}

/**
 * Send email using native PHP mail() function
 * Fallback method when PHPMailer is not available
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $recipientName Optional recipient name
 * @param array $settings SMTP settings
 * @return bool Success status
 */
function sendEmailNative($to, $subject, $body, $recipientName = '', $settings = []) {
    try {
        $fromEmail = $settings['smtp_from_email'] ?? $settings['smtp_username'] ?? 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
        $fromName = $settings['smtp_from_name'] ?? 'Website';
        
        // Email headers for better deliverability
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = "Return-Path: {$fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "X-Priority: 3";
        $headers[] = "X-MSMail-Priority: Normal";
        $headers[] = "X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.2180";
        $headers[] = "Importance: Normal";
        $headers[] = "Message-ID: <" . time() . "." . md5($to . $subject) . "@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">";
        
        // Always add List-Unsubscribe (required for bulk emails)
        $siteUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $headers[] = "List-Unsubscribe: <{$siteUrl}/newsletter-unsubscribe.php>";
        $headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
        
        // Send email
        $success = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$success) {
            error_log("Native mail() failed for: {$to}");
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Native mail error: " . $e->getMessage());
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
    global $pdo;
    require_once __DIR__ . '/whitelabel-helper.php';
    
    $siteName = getSiteName();
    $siteUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    
    // Get unsubscribe token
    $unsubscribeUrl = '#';
    try {
        $stmt = $pdo->prepare("SELECT unsubscribe_token FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['unsubscribe_token']) {
            $unsubscribeUrl = $siteUrl . '/newsletter-unsubscribe.php?token=' . urlencode($result['unsubscribe_token']);
        }
    } catch (PDOException $e) {
        error_log("Newsletter token fetch error: " . $e->getMessage());
    }
    
    $subject = "Welcome to {$siteName} Newsletter!";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); color: white; padding: 40px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 40px 30px; }
            .content p { margin: 0 0 15px; }
            .features { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .features ul { margin: 10px 0; padding-left: 20px; }
            .features li { margin: 8px 0; }
            .cta-button { display: inline-block; padding: 15px 30px; background: #8b5cf6; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
            .footer { padding: 30px; background: #f9fafb; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e5e7eb; }
            .footer a { color: #8b5cf6; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to {$siteName}!</h1>
            </div>
            <div class='content'>
                <p>Hi " . ($name ? htmlspecialchars($name) : "there") . ",</p>
                <p>Thank you for subscribing to our newsletter! We're thrilled to have you as part of our community.</p>
                
                <div class='features'>
                    <p><strong>Here's what you can expect:</strong></p>
                    <ul>
                        <li>📊 Latest projects and case studies</li>
                        <li>💡 Industry insights and expert tips</li>
                        <li>🎁 Exclusive offers and early access</li>
                        <li>📰 Monthly updates and news</li>
                    </ul>
                </div>
                
                <p>We promise to only send you valuable content and never spam your inbox. You'll typically hear from us 1-2 times per month.</p>
                
                <center>
                    <a href='{$siteUrl}' class='cta-button'>Visit Our Website</a>
                </center>
                
                <p style='margin-top: 30px;'>Stay tuned for great content!</p>
                <p>Best regards,<br><strong>The {$siteName} Team</strong></p>
            </div>
            <div class='footer'>
                <p>You're receiving this because you subscribed to our newsletter at {$siteName}.</p>
                <p><a href='{$unsubscribeUrl}'>Unsubscribe</a> | <a href='{$siteUrl}'>Visit Website</a></p>
                <p>&copy; " . date('Y') . " {$siteName}. All rights reserved.</p>
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
