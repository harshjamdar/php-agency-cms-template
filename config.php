<?php
/**
 * Frontend Configuration File
 * Provides database connection for public-facing pages
 * Does NOT enforce authentication or require .env file
 * 
 * @author Harsh Jamdar
 */

// Session Security Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load environment variables from .env file
 */
function loadFrontendEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, '"\'');
            
            if (!empty($name) && !array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
    return true;
}

// Try to load environment file
$envLoaded = loadFrontendEnv(__DIR__ . '/admin/.env');

// Set Environment and Error Reporting
if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'development'); // Changed to development to see errors
}

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-error.log');
}

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $_ENV['DB_NAME'] ?? '');
}
if (!defined('DB_USER')) {
    define('DB_USER', $_ENV['DB_USER'] ?? '');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}

// Attempt database connection with graceful fallback
if (!isset($pdo)) {
    $pdo = null;
    try {
        if (!empty(DB_NAME) && !empty(DB_USER)) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
    } catch(PDOException $e) {
        // Log error but don't die - allow frontend to display gracefully
        error_log("Frontend Database Connection Warning: " . $e->getMessage());
        $pdo = null;
    }
}

/**
 * Helper function to safely fetch data from database
 * Returns empty array if connection fails
 */
function safeQuery($sql, $params = []) {
    global $pdo;
    
    if ($pdo === null) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return [];
    }
}
?>
