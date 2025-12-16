<?php

/**
 * SecurityHelper Class
 *
 * Provides security-related utilities including CSRF protection,
 * input sanitization, and XSS prevention.
 *
 * @package    CodeFiesta
 * @subpackage Admin
 * @author     CodeFiesta Development Team
 * @version    1.0.0
 */
class SecurityHelper
{
    /**
     * Session key for CSRF token
     */
    private const CSRF_TOKEN_KEY = 'csrf_token';

    /**
     * Session key for CSRF token timestamp
     */
    private const CSRF_TOKEN_TIME_KEY = 'csrf_token_time';

    /**
     * CSRF token expiration time in seconds (default: 1 hour)
     */
    private const CSRF_TOKEN_EXPIRY = 3600;

    /**
     * Generate CSRF token and store in session
     *
     * @return string Generated CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::CSRF_TOKEN_KEY] = $token;
        $_SESSION[self::CSRF_TOKEN_TIME_KEY] = time();

        return $token;
    }

    /**
     * Get current CSRF token from session or generate new one
     *
     * @return string CSRF token
     */
    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::CSRF_TOKEN_KEY]) || self::isCsrfTokenExpired()) {
            return self::generateCsrfToken();
        }

        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    /**
     * Check if CSRF token has expired
     *
     * @return bool True if expired, false otherwise
     */
    private static function isCsrfTokenExpired(): bool
    {
        if (!isset($_SESSION[self::CSRF_TOKEN_TIME_KEY])) {
            return true;
        }

        return (time() - $_SESSION[self::CSRF_TOKEN_TIME_KEY]) > self::CSRF_TOKEN_EXPIRY;
    }

    /**
     * Validate CSRF token from request
     *
     * @param string $token Token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return false;
        }

        if (self::isCsrfTokenExpired()) {
            return false;
        }

        return hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }

    /**
     * Generate CSRF token hidden input field
     *
     * @return string HTML input field
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Sanitize string for safe output in HTML
     *
     * @param string $input String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeHtml(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize string for safe use in URLs
     *
     * @param string $input String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeUrl(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize email address
     *
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Escape string for safe database queries (use prepared statements instead when possible)
     *
     * @param string $input String to escape
     * @param PDO    $pdo   PDO instance
     * @return string Escaped string
     */
    public static function escapeString(string $input, PDO $pdo): string
    {
        return $pdo->quote($input);
    }

    /**
     * Generate secure random password
     *
     * @param int $length Password length (default: 12)
     * @return string Generated password
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $password;
    }

    /**
     * Hash password securely
     *
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password against hash
     *
     * @param string $password Plain text password
     * @param string $hash     Password hash
     * @return bool True if password matches hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing (when algorithm changes)
     *
     * @param string $hash Password hash
     * @return bool True if rehashing needed
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Sanitize filename for safe file system operations
     *
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any path separators
        $filename = basename($filename);
        
        // Remove any characters that aren't alphanumeric, dots, hyphens, or underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple consecutive dots
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        
        return $filename;
    }

    /**
     * Generate secure session ID
     *
     * @return string Secure session ID
     */
    public static function generateSecureSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Clean input string (trim and remove null bytes)
     *
     * @param string $input String to clean
     * @return string Cleaned string
     */
    public static function cleanInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Trim whitespace
        return trim($input);
    }

    /**
     * Validate ID parameter (integer greater than 0)
     *
     * @param mixed $id ID to validate
     * @return int|null Valid ID or null
     */
    public static function validateId($id): ?int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        
        if ($id === false || $id <= 0) {
            return null;
        }
        
        return $id;
    }

    /**
     * Rate limiting check (simple implementation)
     *
     * @param string $key       Unique key for rate limit
     * @param int    $maxAttempts Maximum attempts allowed
     * @param int    $timeWindow Time window in seconds
     * @return bool True if within rate limit, false otherwise
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rateLimitKey = 'rate_limit_' . $key;
        $now = time();

        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 1,
                'reset_time' => $now + $timeWindow
            ];
            return true;
        }

        // Reset if time window has passed
        if ($now > $_SESSION[$rateLimitKey]['reset_time']) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 1,
                'reset_time' => $now + $timeWindow
            ];
            return true;
        }

        // Increment attempts
        $_SESSION[$rateLimitKey]['attempts']++;

        return $_SESSION[$rateLimitKey]['attempts'] <= $maxAttempts;
    }

    /**
     * Get client IP address (handles proxies)
     *
     * @return string Client IP address
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Prevent clickjacking by setting X-Frame-Options header
     *
     * @param string $option Header option (DENY, SAMEORIGIN, or ALLOW-FROM uri)
     * @return void
     */
    public static function setFrameOptions(string $option = 'DENY'): void
    {
        header("X-Frame-Options: {$option}");
    }

    /**
     * Set Content Security Policy header
     *
     * @param string $policy CSP policy string
     * @return void
     */
    public static function setContentSecurityPolicy(string $policy): void
    {
        header("Content-Security-Policy: {$policy}");
    }

    /**
     * Set security headers for enhanced protection
     *
     * @return void
     */
    public static function setSecurityHeaders(): void
    {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent clickjacking
        self::setFrameOptions('SAMEORIGIN');
        
        // Enforce HTTPS (if using SSL)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
