<?php
/**
 * Advanced Analytics Tracking System
 * Prevents duplicate views, tracks sessions, user behavior, and location
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get visitor's IP address
 */
function getVisitorIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Determine if we have consent to run analytics with geolocation.
 * - Honor explicit analytics_consent cookie (accepted/declined)
 * - Respect Do Not Track header
 */
function hasAnalyticsConsent() {
    if (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1') {
        return false;
    }
    if (isset($_COOKIE['analytics_consent']) && strtolower($_COOKIE['analytics_consent']) !== 'accepted') {
        return false;
    }
    return true;
}

/**
 * Get location data from IP address using multiple free services
 */
function getLocationFromIP($ip) {
    // Don't track localhost
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return [
            'country' => 'Local',
            'country_code' => 'LC',
            'city' => 'Localhost',
            'region' => 'Local',
            'latitude' => 0,
            'longitude' => 0
        ];
    }

    // Method 1: Try ip-api.com (no API key, 45 req/min limit)
    $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,city,lat,lon";
    
    try {
        // Try with cURL first
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response && !$error) {
                $data = json_decode($response, true);
                if ($data && isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? 'Unknown',
                        'country_code' => $data['countryCode'] ?? 'XX',
                        'city' => $data['city'] ?? 'Unknown',
                        'region' => $data['region'] ?? 'Unknown',
                        'latitude' => $data['lat'] ?? 0,
                        'longitude' => $data['lon'] ?? 0
                    ];
                }
            }
        }
        
        // Method 2: Try with file_get_contents
        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ]);
            $response = @file_get_contents($url, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? 'Unknown',
                        'country_code' => $data['countryCode'] ?? 'XX',
                        'city' => $data['city'] ?? 'Unknown',
                        'region' => $data['region'] ?? 'Unknown',
                        'latitude' => $data['lat'] ?? 0,
                        'longitude' => $data['lon'] ?? 0
                    ];
                }
            }
        }
        
        // Method 3: Try ipapi.co as backup (1000 req/day free, no key needed)
        $backup_url = "https://ipapi.co/{$ip}/json/";
        
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $backup_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['country_name'])) {
                    return [
                        'country' => $data['country_name'] ?? 'Unknown',
                        'country_code' => $data['country_code'] ?? 'XX',
                        'city' => $data['city'] ?? 'Unknown',
                        'region' => $data['region'] ?? 'Unknown',
                        'latitude' => $data['latitude'] ?? 0,
                        'longitude' => $data['longitude'] ?? 0
                    ];
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Geolocation error: " . $e->getMessage());
    }
    
    // Return unknown if all methods fail
    return [
        'country' => 'Unknown',
        'country_code' => 'XX',
        'city' => 'Unknown',
        'region' => 'Unknown',
        'latitude' => 0,
        'longitude' => 0
    ];
}

/**
 * Get or create analytics session
 */
function getAnalyticsSession() {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    // Check if session already exists
    if (isset($_SESSION['analytics_session_id'])) {
        $session_id = $_SESSION['analytics_session_id'];
        
        // Update last activity
        try {
            $stmt = $pdo->prepare("UPDATE analytics_sessions SET last_activity = NOW(), page_count = page_count + 1 WHERE session_id = ?");
            $stmt->execute([$session_id]);
            return $session_id;
        } catch (PDOException $e) {
            // Continue to create new session if update fails
        }
    }
    
    // Create new session
    $session_id = bin2hex(random_bytes(16));
    $ip = getVisitorIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
    
    // Get location data only if consented
    $location = hasAnalyticsConsent() ? getLocationFromIP($ip) : [
        'country' => 'Unknown',
        'country_code' => 'XX',
        'city' => 'Unknown',
        'region' => 'Unknown',
        'latitude' => 0,
        'longitude' => 0
    ];
    
    // Detect device type
    $device_type = 'Desktop';
    if (preg_match('/mobile|android|iphone|ipad|phone/i', $user_agent)) {
        $device_type = 'Mobile';
    } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
        $device_type = 'Tablet';
    }
    
    // Detect browser
    $browser = 'Unknown';
    if (preg_match('/Edge/i', $user_agent)) {
        $browser = 'Edge';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Opera|OPR/i', $user_agent)) {
        $browser = 'Opera';
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO analytics_sessions 
            (session_id, ip_address, user_agent, referrer, device_type, browser, country, country_code, city, region, latitude, longitude, started_at, last_activity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->execute([
            $session_id,
            $ip,
            $user_agent,
            $referrer,
            $device_type,
            $browser,
            $location['country'],
            $location['country_code'],
            $location['city'],
            $location['region'],
            $location['latitude'],
            $location['longitude']
        ]);
        
        $_SESSION['analytics_session_id'] = $session_id;
        return $session_id;
    } catch (PDOException $e) {
        error_log("Analytics session creation failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Track page view (prevents duplicate views in same session)
 */
function trackAdvancedPageView($page_name, $page_url = null) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    if (!$page_url) {
        $page_url = $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    $session_id = getAnalyticsSession();
    if (!$session_id) {
        return false;
    }
    
    // Check if this page was already viewed in this session (within last 30 seconds to allow navigation back)
    $cache_key = 'last_view_' . $page_name;
    $last_view = $_SESSION[$cache_key] ?? 0;
    $current_time = time();
    
    // Only track if not viewed in last 30 seconds
    if ($current_time - $last_view < 30) {
        return false; // Skip duplicate view
    }
    
    $_SESSION[$cache_key] = $current_time;
    
    try {
        // Insert pageview
        $stmt = $pdo->prepare("INSERT INTO analytics_pageviews 
            (session_id, page_name, page_url, viewed_at) 
            VALUES (?, ?, ?, NOW())");
        $stmt->execute([$session_id, $page_name, $page_url]);
        
        // Update page_views summary table
        $stmt = $pdo->prepare("INSERT INTO page_views (page_name, view_count, last_viewed) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE view_count = view_count + 1, last_viewed = NOW()");
        $stmt->execute([$page_name]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Advanced page tracking failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get total unique sessions
 */
function getTotalSessions($days = 30) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM analytics_sessions WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get average session duration
 */
function getAvgSessionDuration($days = 30) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, last_activity)) as avg_duration 
            FROM analytics_sessions 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return round($result['avg_duration'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get traffic by country
 */
function getTrafficByCountry($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT country, country_code, COUNT(*) as sessions 
            FROM analytics_sessions 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY country, country_code 
            ORDER BY sessions DESC 
            LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get traffic by device
 */
function getTrafficByDevice() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT device_type, COUNT(*) as sessions 
            FROM analytics_sessions 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY device_type 
            ORDER BY sessions DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get traffic by browser
 */
function getTrafficByBrowser() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT browser, COUNT(*) as sessions 
            FROM analytics_sessions 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY browser 
            ORDER BY sessions DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get top referrers
 */
function getTopReferrers($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT referrer, COUNT(*) as sessions 
            FROM analytics_sessions 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND referrer != 'Direct'
            GROUP BY referrer 
            ORDER BY sessions DESC 
            LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get real-time active sessions (last 5 minutes)
 */
function getActiveSessions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as active 
            FROM analytics_sessions 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['active'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get recent sessions with details
 */
function getRecentSessions($limit = 20) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT session_id, country, city, device_type, browser, page_count, 
            started_at, last_activity,
            TIMESTAMPDIFF(SECOND, started_at, last_activity) as duration
            FROM analytics_sessions 
            ORDER BY started_at DESC 
            LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get page flow (user journey)
 */
function getPageFlow($session_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT page_name, page_url, viewed_at 
            FROM analytics_pageviews 
            WHERE session_id = ? 
            ORDER BY viewed_at ASC");
        $stmt->execute([$session_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
