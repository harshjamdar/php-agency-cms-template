<?php
/**
 * Security Headers
 * Add this to the top of each public-facing PHP file or in a central location
 */

// Prevent clickjacking
header("X-Frame-Options: SAMEORIGIN");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Enable XSS protection
header("X-XSS-Protection: 1; mode=block");

// Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy (adjust as needed for your site)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';");

// Permissions Policy (formerly Feature Policy)
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Strict Transport Security (only enable if using HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}
?>
