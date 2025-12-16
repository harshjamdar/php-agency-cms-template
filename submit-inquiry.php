<?php
require_once 'config.php';
require_once 'admin/security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
?>