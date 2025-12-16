<?php
declare(strict_types=1);

/**
 * Security helper utilities for validation, CSRF, uploads, and logging.
 */
final class SecurityHelper
{
    /**
     * Generate or reuse the session CSRF token.
     */
    public static function generateCSRFToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Validate the incoming CSRF token against the session value.
     */
    public static function validateCSRFToken(?string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || $token === null) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Render a hidden input for CSRF protection.
     */
    public static function csrfField(): string
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Create a safe, unique filename while preserving extension.
     */
    public static function sanitizeFilename(string $filename): string
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        return uniqid('', true) . '_' . bin2hex(random_bytes(8)) . ($ext ? '.' . $ext : '');
    }

    /**
     * Validate an uploaded file's size and MIME type.
     *
     * @param array $file Superglobal entry from $_FILES
     * @return array{success:bool, message?:string, errors?:array<int,string>, mime_type?:string}
     */
    public static function validateFileUpload(array $file, array $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'], int $maxSize = 5242880): array
    {
        $errors = [];

        if (!isset($file['error']) || !isset($file['tmp_name'])) {
            return ['success' => false, 'errors' => ['Invalid upload data']];
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'message' => 'No file uploaded'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'errors' => ['File upload error: ' . $file['error']]];
        }

        if (($file['size'] ?? 0) > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size (' . ($maxSize / 1048576) . 'MB)';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, (string) $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mimeType === false || $mimeType === null || !in_array($mimeType, $allowedTypes, true)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }

        if ($mimeType && strpos($mimeType, 'image/') === 0) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = 'File is not a valid image';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true, 'mime_type' => $mimeType];
    }

    /**
     * Validate an integer identifier; returns null when invalid.
     */
    public static function validateId($id): ?int
    {
        $validated = filter_var($id, FILTER_VALIDATE_INT);
        if ($validated === false || $validated < 1) {
            return null;
        }

        return (int) $validated;
    }

    /**
     * Sanitize text input with optional max length enforcement.
     */
    public static function sanitizeInput(string $input, ?int $maxLength = null): string
    {
        $trimmed = strip_tags(trim($input));
        if ($maxLength !== null && $maxLength > 0 && strlen($trimmed) > $maxLength) {
            return substr($trimmed, 0, $maxLength);
        }

        return $trimmed;
    }

    /**
     * Validate an email string; returns the filtered email or null.
     */
    public static function validateEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $filtered = filter_var($email, FILTER_VALIDATE_EMAIL);
        return $filtered !== false ? $filtered : null;
    }

    /**
     * Simple in-memory rate limiting keyed by IP and action.
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }

        $now = time();
        $userKey = $key . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if (isset($_SESSION['rate_limit'][$userKey])) {
            $_SESSION['rate_limit'][$userKey] = array_filter(
                $_SESSION['rate_limit'][$userKey],
                static function (int $timestamp) use ($now, $timeWindow): bool {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            $_SESSION['rate_limit'][$userKey] = [];
        }

        if (count($_SESSION['rate_limit'][$userKey]) >= $maxAttempts) {
            return false;
        }

        $_SESSION['rate_limit'][$userKey][] = $now;
        return true;
    }

    /**
     * Persist an error message to a scoped log file.
     */
    public static function logError(string $message, string $file = 'error.log'): void
    {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }

        $logFile = $logDir . '/' . $file;
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";

        error_log($logMessage, 3, $logFile);
    }

    /**
     * Sanitize HTML content from rich-text fields while preserving safe tags.
     */
    public static function sanitizeHTML(string $html): string
    {
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre>';
        $clean = strip_tags($html, $allowedTags);

        $clean = preg_replace('/(<a[^>]+href=["\']?)(?:javascript|data):[^"\']+/i', '$1#', $clean);
        $clean = preg_replace('/(<img[^>]+src=["\']?)(?:javascript|data):[^"\']+/i', '$1#', $clean);
        $clean = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean);

        return $clean ?? '';
    }

    /**
     * Enforce session inactivity timeout and refresh activity timestamp.
     */
    public static function checkSessionTimeout(int $timeout = 7200): bool
    {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            session_unset();
            session_destroy();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }
}

// --- Backward-compatible procedural helpers ---
function generateCSRFToken(): string { return SecurityHelper::generateCSRFToken(); }
function validateCSRFToken($token): bool { return SecurityHelper::validateCSRFToken($token); }
function csrfField(): string { return SecurityHelper::csrfField(); }
function sanitizeFilename($filename): string { return SecurityHelper::sanitizeFilename((string) $filename); }
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'], $maxSize = 5242880): array { return SecurityHelper::validateFileUpload((array) $file, $allowedTypes, (int) $maxSize); }
function validateId($id): ?int { return SecurityHelper::validateId($id); }
function sanitizeInput($input, $maxLength = null): string { return SecurityHelper::sanitizeInput((string) $input, $maxLength !== null ? (int) $maxLength : null); }
function validateEmail($email) { return SecurityHelper::validateEmail($email); }
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300): bool { return SecurityHelper::checkRateLimit((string) $key, (int) $maxAttempts, (int) $timeWindow); }
function logError($message, $file = 'error.log'): void { SecurityHelper::logError((string) $message, (string) $file); }
function sanitizeHTML($html): string { return SecurityHelper::sanitizeHTML((string) $html); }
function checkSessionTimeout($timeout = 7200): bool { return SecurityHelper::checkSessionTimeout((int) $timeout); }
