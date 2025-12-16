<?php

/**
 * ValidationHelper Class
 *
 * Provides centralized validation methods for user input and data integrity.
 * Ensures consistent validation logic across the application.
 *
 * @package    CodeFiesta
 * @subpackage Admin
 * @author     CodeFiesta Development Team
 * @version    1.0.0
 */
class ValidationHelper
{
    /**
     * Validation error messages
     *
     * @var array
     */
    private array $errors = [];

    /**
     * Minimum username length
     */
    private const MIN_USERNAME_LENGTH = 3;

    /**
     * Minimum password length
     */
    private const MIN_PASSWORD_LENGTH = 6;

    /**
     * Validate username
     *
     * @param string $username Username to validate
     * @return bool True if valid, false otherwise
     */
    public function validateUsername(string $username): bool
    {
        $username = trim($username);

        if (empty($username)) {
            $this->addError('Username is required.');
            return false;
        }

        if (strlen($username) < self::MIN_USERNAME_LENGTH) {
            $this->addError('Username must be at least ' . self::MIN_USERNAME_LENGTH . ' characters.');
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->addError('Username can only contain letters, numbers, and underscores.');
            return false;
        }

        return true;
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @return bool True if valid, false otherwise
     */
    public function validatePassword(string $password): bool
    {
        if (empty($password)) {
            $this->addError('Password is required.');
            return false;
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $this->addError('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.');
            return false;
        }

        return true;
    }

    /**
     * Validate password confirmation matches
     *
     * @param string $password        Original password
     * @param string $confirmPassword Confirmation password
     * @return bool True if passwords match, false otherwise
     */
    public function validatePasswordMatch(string $password, string $confirmPassword): bool
    {
        if ($password !== $confirmPassword) {
            $this->addError('Passwords do not match.');
            return false;
        }

        return true;
    }

    /**
     * Validate email address format
     *
     * @param string $email Email address to validate
     * @return bool True if valid, false otherwise
     */
    public function validateEmail(string $email): bool
    {
        $email = trim($email);

        if (empty($email)) {
            $this->addError('Email address is required.');
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('Invalid email address format.');
            return false;
        }

        return true;
    }

    /**
     * Validate required field is not empty
     *
     * @param mixed  $value     Value to check
     * @param string $fieldName Field name for error message
     * @return bool True if not empty, false otherwise
     */
    public function validateRequired($value, string $fieldName): bool
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->addError(ucfirst($fieldName) . ' is required.');
            return false;
        }

        return true;
    }

    /**
     * Validate string length within range
     *
     * @param string $value     Value to check
     * @param int    $min       Minimum length
     * @param int    $max       Maximum length
     * @param string $fieldName Field name for error message
     * @return bool True if valid, false otherwise
     */
    public function validateLength(string $value, int $min, int $max, string $fieldName): bool
    {
        $length = strlen($value);

        if ($length < $min) {
            $this->addError(ucfirst($fieldName) . " must be at least {$min} characters.");
            return false;
        }

        if ($length > $max) {
            $this->addError(ucfirst($fieldName) . " must not exceed {$max} characters.");
            return false;
        }

        return true;
    }

    /**
     * Validate numeric value within range
     *
     * @param mixed  $value     Value to check
     * @param int    $min       Minimum value
     * @param int    $max       Maximum value
     * @param string $fieldName Field name for error message
     * @return bool True if valid, false otherwise
     */
    public function validateNumericRange($value, int $min, int $max, string $fieldName): bool
    {
        if (!is_numeric($value)) {
            $this->addError(ucfirst($fieldName) . ' must be a number.');
            return false;
        }

        if ($value < $min || $value > $max) {
            $this->addError(ucfirst($fieldName) . " must be between {$min} and {$max}.");
            return false;
        }

        return true;
    }

    /**
     * Validate URL format
     *
     * @param string $url       URL to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid, false otherwise
     */
    public function validateUrl(string $url, string $fieldName = 'URL'): bool
    {
        if (empty($url)) {
            return true; // Allow empty URLs for optional fields
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->addError(ucfirst($fieldName) . ' format is invalid.');
            return false;
        }

        return true;
    }

    /**
     * Validate phone number format (flexible pattern)
     *
     * @param string $phone     Phone number to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid, false otherwise
     */
    public function validatePhone(string $phone, string $fieldName = 'phone'): bool
    {
        if (empty($phone)) {
            return true; // Allow empty for optional fields
        }

        // Remove common separators for validation
        $cleanPhone = preg_replace('/[\s\-\(\)\.]+/', '', $phone);

        if (!preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/', $cleanPhone)) {
            $this->addError(ucfirst($fieldName) . ' number format is invalid.');
            return false;
        }

        return true;
    }

    /**
     * Add validation error message
     *
     * @param string $error Error message
     * @return void
     */
    private function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Get all validation errors
     *
     * @return array Array of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     *
     * @return string|null First error or null if no errors
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Check if validation has errors
     *
     * @return bool True if errors exist, false otherwise
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Clear all validation errors
     *
     * @return void
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Sanitize input string
     *
     * @param string $input Input string to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize output for HTML display
     *
     * @param string $output Output string to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeOutput(string $output): string
    {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
}
