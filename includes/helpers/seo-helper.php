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
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM seo_meta WHERE page_slug = ? LIMIT 1");
        $stmt->execute([$page_slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SEO Meta fetch error: " . $e->getMessage());
        return null;
    }
}global $pdo;
    
    $seo = null;
    if ($pdo) {
        $seo = getSEOMeta($page_slug);
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
/**
 * Calculate SEO score for a page
 * Returns an array with score (0-100) and recommendations
 */
function calculateSEOScore($data) {
    $score = 0;
    $maxScore = 100;
    $recommendations = [];
    
    // Title optimization (20 points)
    $title = $data['page_title'] ?? '';
    $titleLength = mb_strlen($title);
    
    if ($titleLength === 0) {
        $recommendations[] = ['type' => 'error', 'message' => 'Page title is missing (critical)'];
    } elseif ($titleLength < 30) {
        $score += 10;
        $recommendations[] = ['type' => 'warning', 'message' => 'Page title is too short (optimal: 50-60 characters)'];
    } elseif ($titleLength >= 30 && $titleLength <= 60) {
        $score += 20;
        $recommendations[] = ['type' => 'success', 'message' => 'Page title length is optimal'];
    } elseif ($titleLength > 60 && $titleLength <= 70) {
        $score += 15;
        $recommendations[] = ['type' => 'warning', 'message' => 'Page title is slightly too long (may be truncated in search results)'];
    } else {
        $score += 10;
        $recommendations[] = ['type' => 'error', 'message' => 'Page title is too long (will be truncated in search results)'];
    }
    
    // Meta description (20 points)
    $description = $data['meta_description'] ?? '';
    $descLength = mb_strlen($description);
    
    if ($descLength === 0) {
        $recommendations[] = ['type' => 'error', 'message' => 'Meta description is missing (critical)'];
    } elseif ($descLength < 70) {
        $score += 10;
        $recommendations[] = ['type' => 'warning', 'message' => 'Meta description is too short (optimal: 150-160 characters)'];
    } elseif ($descLength >= 120 && $descLength <= 160) {
        $score += 20;
        $recommendations[] = ['type' => 'success', 'message' => 'Meta description length is optimal'];
    } elseif ($descLength > 160 && $descLength <= 180) {
        $score += 15;
        $recommendations[] = ['type' => 'warning', 'message' => 'Meta description is slightly too long (may be truncated)'];
    } elseif ($descLength >= 70 && $descLength < 120) {
        $score += 15;
        $recommendations[] = ['type' => 'warning', 'message' => 'Meta description could be longer for better optimization'];
    } else {
        $score += 10;
        $recommendations[] = ['type' => 'error', 'message' => 'Meta description is too long (will be truncated)'];
    }
    
    // Keywords (10 points)
    $keywords = $data['meta_keywords'] ?? '';
    if (!empty($keywords)) {
        $keywordCount = count(array_filter(explode(',', $keywords)));
        if ($keywordCount >= 3 && $keywordCount <= 10) {
            $score += 10;
            $recommendations[] = ['type' => 'success', 'message' => 'Good number of meta keywords'];
        } elseif ($keywordCount < 3) {
            $score += 5;
            $recommendations[] = ['type' => 'warning', 'message' => 'Consider adding more keywords (3-10 recommended)'];
        } else {
            $score += 5;
            $recommendations[] = ['type' => 'warning', 'message' => 'Too many keywords (3-10 recommended)'];
        }
    } else {
        $recommendations[] = ['type' => 'info', 'message' => 'Meta keywords are optional but can help with SEO'];
    }
    
    // Open Graph tags (20 points)
    $ogScore = 0;
    if (!empty($data['og_title'] ?? '')) {
        $ogScore += 7;
    } else {
        $recommendations[] = ['type' => 'warning', 'message' => 'OG title missing (important for social media)'];
    }
    
    if (!empty($data['og_description'] ?? '')) {
        $ogScore += 7;
    } else {
        $recommendations[] = ['type' => 'warning', 'message' => 'OG description missing (important for social media)'];
    }
    
    if (!empty($data['og_image'] ?? '')) {
        $ogScore += 6;
    } else {
        $recommendations[] = ['type' => 'warning', 'message' => 'OG image missing (critical for social media sharing)'];
    }
    
    if ($ogScore === 20) {
        $recommendations[] = ['type' => 'success', 'message' => 'All Open Graph tags are present'];
    }
    $score += $ogScore;
    
    // Canonical URL (15 points)
    $canonical = $data['canonical_url'] ?? '';
    if (!empty($canonical)) {
        if (filter_var($canonical, FILTER_VALIDATE_URL)) {
            $score += 15;
            $recommendations[] = ['type' => 'success', 'message' => 'Canonical URL is properly set'];
        } else {
            $score += 5;
            $recommendations[] = ['type' => 'error', 'message' => 'Canonical URL format is invalid'];
        }
    } else {
        $recommendations[] = ['type' => 'warning', 'message' => 'Canonical URL not set (helps prevent duplicate content issues)'];
    }
    
    // Robots directive (10 points)
    $robots = $data['robots'] ?? 'index, follow';
    if ($robots === 'index, follow') {
        $score += 10;
        $recommendations[] = ['type' => 'success', 'message' => 'Robots directive allows indexing and following'];
    } elseif ($robots === 'noindex, follow' || $robots === 'index, nofollow') {
        $score += 5;
        $recommendations[] = ['type' => 'warning', 'message' => 'Robots directive is restrictive'];
    } else {
        $recommendations[] = ['type' => 'info', 'message' => 'Page is not indexed by search engines'];
    }
    
    // Page slug quality (5 points)
    $slug = $data['page_slug'] ?? '';
    if (!empty($slug)) {
        if (preg_match('/^[a-z0-9-]+$/', $slug)) {
            $score += 5;
            $recommendations[] = ['type' => 'success', 'message' => 'Page slug is SEO-friendly'];
        } else {
            $score += 2;
            $recommendations[] = ['type' => 'warning', 'message' => 'Page slug should use lowercase letters, numbers, and hyphens only'];
        }
    }
    
    // Calculate grade
    $grade = 'F';
    if ($score >= 90) {
        $grade = 'A+';
    } elseif ($score >= 80) {
        $grade = 'A';
    } elseif ($score >= 70) {
        $grade = 'B';
    } elseif ($score >= 60) {
        $grade = 'C';
    } elseif ($score >= 50) {
        $grade = 'D';
    }
    
    return [
        'score' => $score,
        'grade' => $grade,
        'recommendations' => $recommendations
    ];
}