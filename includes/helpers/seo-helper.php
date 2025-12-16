<?php
/**
 * SEO Helper Functions
 * Load and display SEO meta tags from database
 */

/**
 * Get SEO meta tags for a specific page
 */
function getSEOMeta($page_slug) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM seo_meta WHERE page_slug = ? LIMIT 1");
        $stmt->execute([$page_slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Output SEO meta tags HTML
 */
function outputSEOTags($page_slug, $fallback_title = '', $fallback_description = '', $fallback_image = '') {
    $seo = getSEOMeta($page_slug);
    
    // Use database values or fallbacks
    $title = $seo['page_title'] ?? $fallback_title;
    $description = $seo['meta_description'] ?? $fallback_description;
    $keywords = $seo['meta_keywords'] ?? '';
    $og_title = $seo['og_title'] ?? $title;
    $og_description = $seo['og_description'] ?? $description;
    $og_image = $seo['og_image'] ?? $fallback_image;
    $canonical = $seo['canonical_url'] ?? '';
    $robots = $seo['robots'] ?? 'index, follow';
    
    // Output meta tags
    if ($title) {
        echo '<title>' . htmlspecialchars($title) . '</title>' . "\n";
    }
    
    if ($description) {
        echo '    <meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    }
    
    if ($keywords) {
        echo '    <meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    }
    
    echo '    <meta name="robots" content="' . htmlspecialchars($robots) . '">' . "\n";
    
    if ($canonical) {
        echo '    <link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
    }
    
    // Open Graph tags
    if ($og_title) {
        echo '    <meta property="og:title" content="' . htmlspecialchars($og_title) . '">' . "\n";
    }
    
    if ($og_description) {
        echo '    <meta property="og:description" content="' . htmlspecialchars($og_description) . '">' . "\n";
    }
    
    if ($og_image) {
        echo '    <meta property="og:image" content="' . htmlspecialchars($og_image) . '">' . "\n";
    }
    
    echo '    <meta property="og:type" content="website">' . "\n";
    
    // Twitter Card tags
    echo '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    
    if ($og_title) {
        echo '    <meta name="twitter:title" content="' . htmlspecialchars($og_title) . '">' . "\n";
    }
    
    if ($og_description) {
        echo '    <meta name="twitter:description" content="' . htmlspecialchars($og_description) . '">' . "\n";
    }
    
    if ($og_image) {
        echo '    <meta name="twitter:image" content="' . htmlspecialchars($og_image) . '">' . "\n";
    }
}
