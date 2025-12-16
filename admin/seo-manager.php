<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_meta') {
        $page_slug = sanitizeInput($_POST['page_slug'] ?? '', 255);
        $page_title = sanitizeInput($_POST['page_title'] ?? '', 200);
        $meta_description = sanitizeInput($_POST['meta_description'] ?? '', 500);
        $meta_keywords = sanitizeInput($_POST['meta_keywords'] ?? '', 500);
        $og_title = sanitizeInput($_POST['og_title'] ?? '', 200);
        $og_description = sanitizeInput($_POST['og_description'] ?? '', 500);
        $og_image = sanitizeInput($_POST['og_image'] ?? '', 255);
        $canonical_url = sanitizeInput($_POST['canonical_url'] ?? '', 255);
        $robots = sanitizeInput($_POST['robots'] ?? 'index, follow', 50);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO seo_meta 
                (page_slug, page_title, meta_description, meta_keywords, og_title, og_description, og_image, canonical_url, robots) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                page_title = ?, meta_description = ?, meta_keywords = ?, og_title = ?, og_description = ?, og_image = ?, canonical_url = ?, robots = ?");
            
            $stmt->execute([
                $page_slug, $page_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image, $canonical_url, $robots,
                $page_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image, $canonical_url, $robots
            ]);
            
            $success = "SEO meta tags saved successfully!";
        } catch (PDOException $e) {
            logError("SEO meta save error: " . $e->getMessage());
            $error = "Failed to save SEO meta tags.";
        }
    } elseif ($action === 'delete_meta') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM seo_meta WHERE id = ?");
            $stmt->execute([$id]);
            $success = "SEO meta tags deleted successfully!";
        } catch (PDOException $e) {
            logError("SEO meta delete error: " . $e->getMessage());
            $error = "Failed to delete SEO meta tags.";
        }
    } elseif ($action === 'generate_sitemap') {
        // Generate sitemap
        try {
            generateXMLSitemap($pdo);
            $success = "Sitemap generated successfully! View at: <a href='../sitemap.xml' target='_blank' class='underline'>sitemap.xml</a>";
        } catch (Exception $e) {
            logError("Sitemap generation error: " . $e->getMessage());
            $error = "Failed to generate sitemap: " . $e->getMessage();
        }
    }
}

// Fetch all SEO meta tags
try {
    $stmt = $pdo->query("SELECT * FROM seo_meta ORDER BY page_slug ASC");
    $seo_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("SEO meta fetch error: " . $e->getMessage());
    $seo_pages = [];
}

// Sitemap generation function
function generateXMLSitemap($pdo) {
    $base_url = 'https://codefiesta.in';
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Homepage
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$base_url}/</loc>\n";
    $xml .= "    <changefreq>daily</changefreq>\n";
    $xml .= "    <priority>1.0</priority>\n";
    $xml .= "  </url>\n";
    
    // Static pages
    $static_pages = [
        ['url' => '/projects.php', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['url' => '/blog.php', 'changefreq' => 'daily', 'priority' => '0.9'],
        ['url' => '/privacy-policy.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['url' => '/terms-of-service.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ];
    
    foreach ($static_pages as $page) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$base_url}{$page['url']}</loc>\n";
        $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$page['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }
    
    // Projects
    $stmt = $pdo->query("SELECT slug, created_at FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($projects as $project) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$base_url}/project-view.php?slug=" . urlencode($project['slug']) . "</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d', strtotime($project['created_at'])) . "</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        $xml .= "  </url>\n";
    }
    
    // Blog posts
    $stmt = $pdo->query("SELECT slug, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($posts as $post) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$base_url}/blog-post.php?slug=" . urlencode($post['slug']) . "</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d', strtotime($post['created_at'])) . "</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.7</priority>\n";
        $xml .= "  </url>\n";
    }
    
    $xml .= '</urlset>';
    
    // Save to file
    $sitemap_path = dirname(__DIR__) . '/sitemap.xml';
    if (file_put_contents($sitemap_path, $xml) === false) {
        throw new Exception("Failed to write sitemap file to $sitemap_path. Check permissions.");
    }
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">SEO Management</h1>
            <p class="text-slate-400">Manage meta tags, Open Graph data, and XML sitemap</p>
        </div>
        <form method="POST" class="inline">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="generate_sitemap">
            <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors flex items-center gap-2">
                <i data-lucide="file-code" class="w-4 h-4"></i>
                Generate Sitemap
            </button>
        </form>
    </div>
</div>

<?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Add New Page Meta -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <h2 class="text-xl font-bold text-white mb-4">Add/Edit Page Meta Tags</h2>
    <form method="POST" class="space-y-4">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="save_meta">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Page Slug *</label>
                <input type="text" name="page_slug" required
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="e.g., home, about, contact, blog-post-slug">
                <p class="text-xs text-slate-500 mt-1">Unique identifier for the page</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Page Title *</label>
                <input type="text" name="page_title" required maxlength="60"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="Optimal: 50-60 characters">
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Meta Description *</label>
            <textarea name="meta_description" required maxlength="160" rows="3"
                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Optimal: 150-160 characters"></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Meta Keywords</label>
            <input type="text" name="meta_keywords"
                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="keyword1, keyword2, keyword3">
        </div>
        
        <div class="border-t border-white/10 pt-4">
            <h3 class="text-lg font-semibold text-white mb-3">Open Graph Tags (Social Media)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">OG Title</label>
                    <input type="text" name="og_title" maxlength="60"
                        class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Title for social sharing">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">OG Image URL</label>
                    <input type="text" name="og_image"
                        class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="https://example.com/image.jpg">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-300 mb-2">OG Description</label>
                <textarea name="og_description" maxlength="200" rows="2"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="Description for social sharing"></textarea>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Canonical URL</label>
                <input type="text" name="canonical_url"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://codefiesta.in/page">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Robots</label>
                <select name="robots"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="index, follow">Index, Follow</option>
                    <option value="noindex, follow">No Index, Follow</option>
                    <option value="index, nofollow">Index, No Follow</option>
                    <option value="noindex, nofollow">No Index, No Follow</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors">
            Save Meta Tags
        </button>
    </form>
</div>

<!-- Existing Meta Tags -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
    <h2 class="text-xl font-bold text-white mb-4">Existing Page Meta Tags</h2>
    
    <?php if (empty($seo_pages)): ?>
        <p class="text-slate-400 text-center py-8">No SEO meta tags configured yet.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($seo_pages as $page): ?>
                <div class="bg-slate-900/50 border border-white/10 rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 bg-primary/20 text-primary rounded-full text-sm font-medium">
                                    <?php echo htmlspecialchars($page['page_slug']); ?>
                                </span>
                                <span class="text-xs text-slate-500">
                                    <?php echo $page['robots']; ?>
                                </span>
                            </div>
                            <h3 class="text-white font-semibold mb-1"><?php echo htmlspecialchars($page['page_title']); ?></h3>
                            <p class="text-slate-400 text-sm mb-2"><?php echo htmlspecialchars($page['meta_description']); ?></p>
                            <?php if ($page['meta_keywords']): ?>
                                <p class="text-slate-500 text-xs">Keywords: <?php echo htmlspecialchars($page['meta_keywords']); ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="ml-4" onsubmit="return confirm('Delete this SEO meta tag?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete_meta">
                            <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                            <button type="submit" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
