<?php
require_once 'config.php';
require_once 'admin/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Basic rate limiting to reduce spam (5 attempts per 10 minutes per IP/session)
if (!checkRateLimit('newsletter_subscribe', 5, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Please try again later.']);
    exit;
}

// Support JSON and form-encoded bodies
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }
    $input = $decoded;
} else {
    $input = $_POST;
}

// Get input
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$name = sanitizeInput($input['name'] ?? '', 100);
$source = sanitizeInput($input['source'] ?? 'website', 50);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address']);
    exit;
}

// Check if already subscribed
try {
    $stmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
            exit;
        } else {
            // Reactivate subscription
            $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active', unsubscribed_at = NULL WHERE id = ?");
            $stmt->execute([$existing['id']]);
            echo json_encode(['success' => true, 'message' => 'Welcome back! Your subscription has been reactivated']);
            exit;
        }
    }
    
    // Get IP address
    $ip = getVisitorIP();
    
    // Add new subscriber
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, ip_address, source) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $name, $ip, $source]);
    
    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing! Check your email for confirmation']);
    
} catch (PDOException $e) {
    logError("Newsletter subscription error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later']);
}

// Helper function
function getVisitorIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
