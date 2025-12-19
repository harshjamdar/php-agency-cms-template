<?php
/**
 * White Label Helper
 * Functions to apply customized branding throughout the site
 */

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../../config.php';
}

/**
 * Get a white label setting value
 * @param string $key Setting key
 * @param string $default Default value if not set
 * @return string Setting value
 */
function getSetting($key, $default = '') {
    global $pdo;
    
    if (!$pdo) {
        return $default;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("White label setting error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Get all white label settings as an associative array
 * @return array All settings
 */
function getAllSettings() {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (PDOException $e) {
        error_log("White label settings error: " . $e->getMessage());
        return [];
    }
}

/**
 * Output CSS variables for white label colors
 */
function outputColorVariables() {
    $primary = getSetting('primary_color', '#3b82f6');
    $secondary = getSetting('secondary_color', '#8b5cf6');
    
    echo "<style>
        :root {
            --color-primary: {$primary};
            --color-secondary: {$secondary};
        }
        .bg-primary { background-color: var(--color-primary) !important; }
        .text-primary { color: var(--color-primary) !important; }
        .border-primary { border-color: var(--color-primary) !important; }
    </style>";
}

/**
 * Get site name with fallback
 * @return string Site name
 */
function getSiteName() {
    return getSetting('site_name', 'My Agency');
}

/**
 * Get site tagline with fallback
 * @return string Site tagline
 */
function getSiteTagline() {
    return getSetting('site_tagline', 'Creative Solutions for Digital Success');
}

/**
 * Get contact email with fallback
 * @return string Contact email
 */
function getContactEmail() {
    return getSetting('contact_email', 'admin@example.com');
}

/**
 * Get contact phone with fallback
 * @return string Contact phone
 */
function getContactPhone() {
    return getSetting('contact_phone', '+1 (555) 123-4567');
}

/**
 * Get footer text with fallback
 * @return string Footer copyright text
 */
function getFooterText() {
    $siteName = getSiteName();
    return getSetting('footer_text', "© " . date('Y') . " {$siteName}. All rights reserved.");
}

/**
 * Get logo URL with fallback
 * @return string Logo URL
 */
function getLogoUrl() {
    return getSetting('logo_url', '/assets/images/logo.png');
}

/**
 * Get favicon URL with fallback
 * @return string Favicon URL
 */
function getFaviconUrl() {
    return getSetting('favicon_url', '/assets/images/favicon.ico');
}
