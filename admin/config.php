<?php
/**
 * Load environment variables from .env file
 *
 * @param string $path Path to .env file
 * @return bool True if loaded successfully
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            if (!empty($name) && !array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
    return true;
}

// Load .env file
$envLoaded = loadEnv(__DIR__ . '/.env');

// Set Environment and Error Reporting
if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
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
    ini_set('error_log', __DIR__ . '/../logs/php-error.log');
}

// Check if this is the setup script
$isSetupScript = (defined('IS_SETUP') && IS_SETUP) || (basename($_SERVER['SCRIPT_FILENAME']) === 'setup.php');

// CRITICAL: Require .env file in production
if (!$envLoaded) {
    if (!$isSetupScript) {
        error_log("CRITICAL: .env file not found at " . __DIR__ . '/.env');
        die("Configuration Error: Environment file not found. Please create 'admin/.env' from 'admin/.env.example' template.");
    }
}

// Session Security Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Database Credentials - Load from environment variables only
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
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 7200);
}

// Validate required configuration
if ((empty(DB_NAME) || empty(DB_USER)) && !$isSetupScript) {
    error_log("CRITICAL: Missing required database configuration in .env file");
    die("Configuration Error: Missing database credentials. Please check 'admin/.env' file.");
}

// Attempt to connect to MySQL database
try {
    if (!empty(DB_NAME) && !empty(DB_USER)) {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        // Set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } else {
        $pdo = null;
    }
} catch(PDOException $e) {
    if (!$isSetupScript) {
        // Log error instead of displaying
        error_log("Database Connection Error: " . $e->getMessage());
        die("ERROR: Could not connect to database. Please contact support.");
    } else {
        $pdo = null;
        $dbConnectionError = $e->getMessage();
    }
}

// Load autoloader for helper classes
require_once __DIR__ . '/autoload.php';

// Include security helpers and headers
require_once __DIR__ . '/security-headers.php';
require_once __DIR__ . '/security.php';

// Admin Credentials
// Username: admin
// Password: admin123
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa');

// Function to check if user is logged in
function checkLogin() {
    // Check session timeout
    if (!checkSessionTimeout(SESSION_LIFETIME)) {
        header("Location: index.php?error=session_expired");
        exit;
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: index.php");
        exit;
    }
}

// Function to login
function login($username, $password) {
    global $pdo;
    
    // Rate limiting
    if (!checkRateLimit('login', 5, 300)) {
        return ['success' => false, 'error' => 'Too many login attempts. Please try again in 5 minutes.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role, email, full_name FROM users WHERE username = :username AND status = 'active'");
        $stmt->execute(['username' => $username]);
        
        if ($row = $stmt->fetch()) {
            if (password_verify($password, $row['password'])) {
                // Regenerate session ID on successful login
                session_regenerate_id(true);
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $row['id'];
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['user_role'] = $row['role'] ?? 'editor';
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_fullname'] = $row['full_name'] ?? $row['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['created'] = time();
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$row['id']]);
                
                // Clear rate limit on successful login
                unset($_SESSION['rate_limit']['login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')]);
                
                return ['success' => true];
            }
        }
    } catch (PDOException $e) {
        logError("Login database error: " . $e->getMessage());
        
        // Fallback to hardcoded credentials if DB fails or table doesn't exist
        if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['created'] = time();
            return ['success' => true];
        }
    }
    
    // Fallback for initial setup before DB is ready
    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        return ['success' => true];
    }

    return ['success' => false, 'error' => 'Invalid username or password'];
}

// Function to logout
function logout() {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    header("Location: index.php");
    exit;
}
?>