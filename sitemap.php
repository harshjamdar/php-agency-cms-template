<?php
require_once 'config.php';
require_once 'includes/helpers/whitelabel-helper.php';

header("Content-Type: application/xml; charset=utf-8");

// Calculate Base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['PHP_SELF']);
$baseUrl = rtrim($protocol . "://" . $host . $scriptDir, '/');
if ($scriptDir == '/' || $scriptDir == '\\') {
    $baseUrl = $protocol . "://" . $host;
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo $baseUrl; ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/projects.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/blog.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/privacy-policy.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/terms-of-service.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    
    <?php
    // Projects
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SELECT slug, created_at FROM projects ORDER BY created_at DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $date = date('Y-m-d', strtotime($row['created_at']));
                echo "<url>\n";
                echo "    <loc>{$baseUrl}/project-view.php?slug=" . htmlspecialchars($row['slug']) . "</loc>\n";
                echo "    <lastmod>{$date}</lastmod>\n";
                echo "    <changefreq>monthly</changefreq>\n";
                echo "    <priority>0.7</priority>\n";
                echo "</url>\n";
            }
        } catch (PDOException $e) { }

        // Blog Posts
        try {
            $stmt = $pdo->query("SELECT slug, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $date = date('Y-m-d', strtotime($row['created_at']));
                echo "<url>\n";
                echo "    <loc>{$baseUrl}/blog-post.php?slug=" . htmlspecialchars($row['slug']) . "</loc>\n";
                echo "    <lastmod>{$date}</lastmod>\n";
                echo "    <changefreq>weekly</changefreq>\n";
                echo "    <priority>0.6</priority>\n";
                echo "</url>\n";
            }
        } catch (PDOException $e) { }
    }
    ?>
</urlset>