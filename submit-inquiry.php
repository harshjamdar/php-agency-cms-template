<?php
require_once 'config.php';
require_once 'admin/security.php';

// Function to get API setting value
if (!function_exists('getApiSetting')) {
    function getApiSetting($key) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM api_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify reCAPTCHA v3
    $recaptcha_token = $_POST['recaptcha_token'] ?? '';
    $recaptcha_secret = getApiSetting('recaptcha_secret_key');
    
    if ($recaptcha_secret && $recaptcha_token) {
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = [
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $recaptcha_options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($recaptcha_data)
            ]
        ];
        
        $recaptcha_context = stream_context_create($recaptcha_options);
        $recaptcha_result = @file_get_contents($recaptcha_url, false, $recaptcha_context);
        
        if ($recaptcha_result) {
            $recaptcha_json = json_decode($recaptcha_result, true);
            if (!$recaptcha_json['success'] || $recaptcha_json['score'] < 0.5) {
                header("Location: index.php?status=error&msg=" . urlencode("Security verification failed. Please try again.") . "#contact");
                exit;
            }
        }
    }
    
    // Check rate limit
    if (!checkRateLimit('inquiry', 3, 600)) {
        header("Location: index.php?status=error&msg=" . urlencode("Too many submissions. Please try again in 10 minutes.") . "#contact");
        exit;
    }
    
    // Collect and sanitize input data
    $name = sanitizeInput($_POST['name'] ?? '', 100);
    $email = validateEmail($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '', 20);
    $message = sanitizeInput($_POST['message'] ?? '', 1000);
    $subject = "New Inquiry from Website"; // Default subject

    // Basic validation
    if (empty($name)) {
        header("Location: index.php?status=error&msg=" . urlencode("Name is required") . "#contact");
        exit;
    }
    
    if (!$email) {
        header("Location: index.php?status=error&msg=" . urlencode("Valid email is required") . "#contact");
        exit;
    }
    
    if (empty($message)) {
        header("Location: index.php?status=error&msg=" . urlencode("Message is required") . "#contact");
        exit;
    }
    
    if (strlen($message) < 10) {
        header("Location: index.php?status=error&msg=" . urlencode("Message must be at least 10 characters") . "#contact");
        exit;
    }

    try {
        // Prepare SQL statement
        $sql = "INSERT INTO inquiries (name, email, phone, subject, message, status) VALUES (:name, :email, :phone, :subject, :message, 'new')";
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        
        // Execute
        if ($stmt->execute()) {
            // Automatically subscribe to newsletter
            try {
                // Check if already subscribed
                $checkStmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
                $checkStmt->execute([$email]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if ($existing['status'] !== 'active') {
                        // Reactivate subscription
                        $updateStmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active', unsubscribed_at = NULL WHERE id = ?");
                        $updateStmt->execute([$existing['id']]);
                    }
                } else {
                    // Add new subscriber
                    $ip = getVisitorIP();
                    $source = 'contact_form';
                    $token = bin2hex(random_bytes(32));
                    $insertStmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, ip_address, source, unsubscribe_token) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$email, $name, $ip, $source, $token]);
                    
                    // Send welcome email
                    require_once __DIR__ . '/includes/helpers/email-helper.php';
                    sendNewsletterWelcome($email, $name);
                }
            } catch (PDOException $e) {
                // Log newsletter subscription error but don't fail the inquiry submission
                logError("Auto newsletter subscription error for inquiry: " . $e->getMessage());
            }
            
            // Success
            header("Location: index.php?status=success#contact");
        } else {
            // Failure
            logError("Failed to save inquiry for: " . $email);
            header("Location: index.php?status=error&msg=" . urlencode("Failed to save inquiry") . "#contact");
        }
    } catch (PDOException $e) {
        // Log error and redirect
        logError("Inquiry submission error: " . $e->getMessage());
        
        // Check for specific error about missing column
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $error_msg = "Database Error: Missing columns in 'inquiries' table. Please contact support.";
        } else {
            $error_msg = "An error occurred. Please try again later.";
        }
        header("Location: index.php?status=error&msg=" . urlencode($error_msg) . "#contact");
    }
} else {
    // Not a POST request
    header("Location: index.php");
}

// Helper function to get visitor IP
function getVisitorIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>