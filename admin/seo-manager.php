<?php
require_once 'config.php';
require_once 'security.php';
require_once __DIR__ . '/../includes/helpers/seo-helper.php';
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
        
        // Handle OG Image Upload
        $og_image = sanitizeInput($_POST['existing_og_image'] ?? '', 255);
        if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file = $_FILES['og_image'];
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= 5000000) { // 5MB max
                $upload_dir = dirname(__DIR__) . '/assets/images/og/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'og_' . time() . '_' . uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $og_image = '/assets/images/og/' . $filename;
                    
                    // Delete old image if exists and different
                    if (!empty($_POST['existing_og_image']) && $_POST['existing_og_image'] !== $og_image) {
                        $old_image = dirname(__DIR__) . $_POST['existing_og_image'];
                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                }
            }
        }
        
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

<div class="mb-8 bg-gradient-to-br from-primary/10 via-purple-500/10 to-pink-500/10 border border-white/10 rounded-2xl p-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="p-3 bg-gradient-to-br from-primary to-purple-600 rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">SEO Management</h1>
                    <p class="text-slate-300">Optimize your site for search engines and social media</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-medium flex items-center gap-1">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    Real-time Preview
                </span>
                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-medium flex items-center gap-1">
                    <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                    SEO Score
                </span>
                <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-xs font-medium flex items-center gap-1">
                    <span class="w-2 h-2 bg-purple-400 rounded-full animate-pulse"></span>
                    Image Upload
                </span>
            </div>
        </div>
        <form method="POST" class="inline">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="generate_sitemap">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl transition-all transform hover:scale-105 flex items-center gap-2 shadow-lg shadow-green-500/20 font-medium">
                <i data-lucide="file-code" class="w-5 h-5"></i>
                Generate Sitemap
            </button>
        </form>
    </div>
</div>

<?php if ($success): ?>
    <div class="mb-6 p-5 bg-gradient-to-r from-green-500/10 to-emerald-500/10 border border-green-500/30 rounded-xl text-green-400 flex items-start gap-3 shadow-lg">
        <svg class="w-6 h-6 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="flex-1"><?php echo $success; ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6 p-5 bg-gradient-to-r from-red-500/10 to-pink-500/10 border border-red-500/30 rounded-xl text-red-400 flex items-start gap-3 shadow-lg">
        <svg class="w-6 h-6 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="flex-1"><?php echo $error; ?></div>
    </div>
<?php endif; ?>

<!-- Add New Page Meta -->
<div class="bg-gradient-to-br from-slate-800/80 to-slate-900/80 border border-white/10 rounded-2xl p-8 mb-8 shadow-2xl">
    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-white/10">
        <div class="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white">Add/Edit Page Meta Tags</h2>
            <p class="text-slate-400 text-sm">Configure SEO settings with real-time preview and scoring</p>
        </div>
    </div>
    
    <!-- SEO Score Display -->
    <div id="seo-score-panel" class="mb-6 p-6 bg-gradient-to-br from-indigo-900/30 via-purple-900/30 to-pink-900/30 border border-purple-500/20 rounded-2xl backdrop-blur-sm shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">SEO Score</h3>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <div class="text-3xl font-bold" id="seo-score-value" style="color: #64748b;">--</div>
                    <div class="text-xs text-slate-400">out of 100</div>
                </div>
                <div class="w-20 h-20">
                    <svg class="transform -rotate-90" width="80" height="80">
                        <circle cx="40" cy="40" r="32" fill="none" stroke="#1e293b" stroke-width="8"></circle>
                        <circle id="seo-score-circle" cx="40" cy="40" r="32" fill="none" stroke="#64748b" stroke-width="8"
                            stroke-dasharray="201" stroke-dashoffset="201" stroke-linecap="round"></circle>
                    </svg>
                    <div class="text-center -mt-16">
                        <span id="seo-grade" class="text-2xl font-bold text-slate-400">--</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="seo-recommendations" class="space-y-2">
            <p class="text-slate-400 text-sm">Fill out the form to see your SEO score and recommendations...</p>
        </div>
    </div>

    <!-- Preview Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Google Search Preview -->
        <div class="bg-gradient-to-br from-blue-900/20 via-indigo-900/20 to-slate-900/20 border border-blue-500/20 rounded-2xl p-6 backdrop-blur-sm hover:border-blue-500/40 transition-all shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-white">Google Search Preview</h3>
            </div>
            <div class="bg-white rounded-lg p-4">
                <div class="flex items-start gap-3 mb-2">
                    <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs text-slate-600">codefiesta.in</span>
                            <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/>
                            </svg>
                        </div>
                        <div id="preview-title" class="text-xl text-blue-600 hover:underline cursor-pointer mb-1 break-words line-clamp-1">
                            Your Page Title Will Appear Here
                        </div>
                        <div id="preview-url" class="text-sm text-green-700 mb-2 break-all">
                            https://codefiesta.in/page-slug
                        </div>
                        <div id="preview-description" class="text-sm text-slate-700 leading-relaxed break-words line-clamp-2">
                            Your meta description will appear here. This is what users will see in search engine results. Make it compelling!
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-3">This is how your page will appear in Google search results</p>
        </div>

        <!-- Social Media (Open Graph) Preview -->
        <div class="bg-gradient-to-br from-pink-900/20 via-purple-900/20 to-slate-900/20 border border-pink-500/20 rounded-2xl p-6 backdrop-blur-sm hover:border-pink-500/40 transition-all shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <h3 class="text-lg font-semibold text-white">Social Media Preview</h3>
            </div>
            <div class="bg-white rounded-lg overflow-hidden border border-slate-200">
                <div id="preview-og-image-container" class="w-full h-48 bg-slate-200 flex items-center justify-center">
                    <div class="text-center text-slate-400">
                        <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">OG Image Preview</p>
                    </div>
                    <img id="preview-og-image" src="" alt="" class="w-full h-full object-cover hidden">
                </div>
                <div class="p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">codefiesta.in</div>
                    <div id="preview-og-title" class="text-base font-semibold text-slate-900 mb-1 break-words line-clamp-2">
                        Open Graph Title Will Appear Here
                    </div>
                    <div id="preview-og-description" class="text-sm text-slate-600 break-words line-clamp-2">
                        Your Open Graph description for social media sharing will appear here.
                    </div>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-3">This is how your page will appear when shared on Facebook, Twitter, LinkedIn, etc.</p>
        </div>
    </div>
    
    <form method="POST" id="seo-form" class="space-y-4" enctype="multipart/form-data">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="save_meta">
        <input type="hidden" name="existing_og_image" id="existing_og_image" value="">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Page Slug *</label>
                <input type="text" name="page_slug" id="page_slug" required
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="e.g., home, about, contact, blog-post-slug">
                <p class="text-xs text-slate-500 mt-1">Unique identifier for the page</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Page Title * 
                    <span id="title-counter" class="text-slate-500 text-xs">(0/60)</span>
                </label>
                <input type="text" name="page_title" id="page_title" required maxlength="60"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="Optimal: 50-60 characters">
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">
                Meta Description * 
                <span id="description-counter" class="text-slate-500 text-xs">(0/160)</span>
            </label>
            <textarea name="meta_description" id="meta_description" required maxlength="160" rows="3"
                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Optimal: 150-160 characters"></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Meta Keywords</label>
            <input type="text" name="meta_keywords" id="meta_keywords"
                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="keyword1, keyword2, keyword3">
        </div>
        
        <div class="border-t border-white/10 pt-6 mt-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-gradient-to-br from-pink-500 to-purple-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white">Open Graph Tags</h3>
                    <p class="text-slate-400 text-sm">Optimize for social media sharing</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">OG Title</label>
                    <input type="text" name="og_title" id="og_title" maxlength="60"
                        class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Title for social sharing">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">OG Image</label>
                    <div class="space-y-3">
                        <input type="file" name="og_image" id="og_image" accept="image/*"
                            class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/80 file:cursor-pointer">
                        <div id="og-image-preview" class="hidden">
                            <img id="og-image-preview-img" src="" alt="Preview" class="max-h-40 rounded-lg border border-white/10">
                            <button type="button" id="remove-og-image" class="mt-2 text-sm text-red-400 hover:text-red-300">
                                Remove Image
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Upload an image (Max 5MB, JPG/PNG/GIF/WebP)</p>
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-300 mb-2">OG Description</label>
                <textarea name="og_description" id="og_description" maxlength="200" rows="2"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="Description for social sharing"></textarea>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Canonical URL</label>
                <input type="text" name="canonical_url" id="canonical_url"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://codefiesta.in/page">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Robots</label>
                <select name="robots" id="robots"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="index, follow">Index, Follow</option>
                    <option value="noindex, follow">No Index, Follow</option>
                    <option value="index, nofollow">Index, No Follow</option>
                    <option value="noindex, nofollow">No Index, No Follow</option>
                </select>
            </div>
        </div>
        
        <div class="flex items-center gap-4 pt-4">
            <button type="submit" class="px-8 py-4 bg-gradient-to-r from-primary via-purple-600 to-pink-600 hover:from-primary/90 hover:via-purple-600/90 hover:to-pink-600/90 text-white rounded-xl transition-all transform hover:scale-105 font-semibold shadow-lg shadow-primary/30 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Meta Tags
            </button>
            <button type="button" onclick="document.getElementById('seo-form').reset(); calculateSEOScoreClient(); updatePreviews();" class="px-6 py-4 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-all font-medium">
                Reset Form
            </button>
        </div>
    </form>
</div>

<!-- Existing Meta Tags -->
<div class="bg-gradient-to-br from-slate-800/80 to-slate-900/80 border border-white/10 rounded-2xl p-8 shadow-2xl">
    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-white/10">
        <div class="p-2 bg-gradient-to-br from-green-500 to-teal-600 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white">Existing Page Meta Tags</h2>
            <p class="text-slate-400 text-sm">Manage and review all configured SEO pages</p>
        </div>
    </div>
    
    <?php if (empty($seo_pages)): ?>
        <p class="text-slate-400 text-center py-8">No SEO meta tags configured yet.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($seo_pages as $page): ?>
                <?php 
                    $pageScore = calculateSEOScore($page); 
                    $scoreColor = 'text-red-400';
                    if ($pageScore['score'] >= 80) $scoreColor = 'text-green-400';
                    elseif ($pageScore['score'] >= 60) $scoreColor = 'text-yellow-400';
                    elseif ($pageScore['score'] >= 40) $scoreColor = 'text-orange-400';
                ?>
                <div class="bg-gradient-to-br from-slate-900/60 to-slate-800/60 border border-white/10 hover:border-primary/40 rounded-xl p-5 transition-all hover:shadow-xl hover:shadow-primary/10 hover:-translate-y-1">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 bg-primary/20 text-primary rounded-full text-sm font-medium">
                                    <?php echo htmlspecialchars($page['page_slug']); ?>
                                </span>
                                <span class="px-3 py-1 bg-slate-800 rounded-full text-xs font-semibold <?php echo $scoreColor; ?>">
                                    SEO Score: <?php echo $pageScore['score']; ?>/100 (<?php echo $pageScore['grade']; ?>)
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
                            <?php if ($page['og_image']): ?>
                                <div class="mt-2">
                                    <img src="<?php echo htmlspecialchars($page['og_image']); ?>" alt="OG Image" class="h-16 rounded border border-white/10">
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="ml-4" onsubmit="return confirm('Delete this SEO meta tag?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete_meta">
                            <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                            <button type="submit" class="p-2.5 text-red-400 hover:bg-red-500/20 hover:text-red-300 rounded-xl transition-all border border-red-500/20 hover:border-red-500/40 hover:scale-110">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// SEO Score Calculator (Client-side for real-time feedback)
function calculateSEOScoreClient() {
    const data = {
        page_slug: document.getElementById('page_slug').value,
        page_title: document.getElementById('page_title').value,
        meta_description: document.getElementById('meta_description').value,
        meta_keywords: document.getElementById('meta_keywords').value,
        og_title: document.getElementById('og_title').value,
        og_description: document.getElementById('og_description').value,
        og_image: document.getElementById('og_image').value,
        canonical_url: document.getElementById('canonical_url').value,
        robots: document.getElementById('robots').value
    };
    
    let score = 0;
    const recommendations = [];
    
    // Title optimization (20 points)
    const titleLength = data.page_title.length;
    if (titleLength === 0) {
        recommendations.push({ type: 'error', message: 'Page title is missing (critical)' });
    } else if (titleLength < 30) {
        score += 10;
        recommendations.push({ type: 'warning', message: 'Page title is too short (optimal: 50-60 characters)' });
    } else if (titleLength >= 30 && titleLength <= 60) {
        score += 20;
        recommendations.push({ type: 'success', message: 'Page title length is optimal' });
    } else if (titleLength > 60 && titleLength <= 70) {
        score += 15;
        recommendations.push({ type: 'warning', message: 'Page title is slightly too long' });
    } else {
        score += 10;
        recommendations.push({ type: 'error', message: 'Page title is too long (will be truncated)' });
    }
    
    // Meta description (20 points)
    const descLength = data.meta_description.length;
    if (descLength === 0) {
        recommendations.push({ type: 'error', message: 'Meta description is missing (critical)' });
    } else if (descLength < 70) {
        score += 10;
        recommendations.push({ type: 'warning', message: 'Meta description is too short (optimal: 150-160 characters)' });
    } else if (descLength >= 120 && descLength <= 160) {
        score += 20;
        recommendations.push({ type: 'success', message: 'Meta description length is optimal' });
    } else if (descLength > 160 && descLength <= 180) {
        score += 15;
        recommendations.push({ type: 'warning', message: 'Meta description is slightly too long' });
    } else if (descLength >= 70 && descLength < 120) {
        score += 15;
        recommendations.push({ type: 'warning', message: 'Meta description could be longer' });
    } else {
        score += 10;
        recommendations.push({ type: 'error', message: 'Meta description is too long (will be truncated)' });
    }
    
    // Keywords (10 points)
    if (data.meta_keywords) {
        const keywordCount = data.meta_keywords.split(',').filter(k => k.trim()).length;
        if (keywordCount >= 3 && keywordCount <= 10) {
            score += 10;
            recommendations.push({ type: 'success', message: 'Good number of meta keywords' });
        } else if (keywordCount < 3) {
            score += 5;
            recommendations.push({ type: 'warning', message: 'Consider adding more keywords (3-10 recommended)' });
        } else {
            score += 5;
            recommendations.push({ type: 'warning', message: 'Too many keywords (3-10 recommended)' });
        }
    } else {
        recommendations.push({ type: 'info', message: 'Meta keywords are optional but can help with SEO' });
    }
    
    // Open Graph tags (20 points)
    let ogScore = 0;
    if (data.og_title) {
        ogScore += 7;
    } else {
        recommendations.push({ type: 'warning', message: 'OG title missing (important for social media)' });
    }
    
    if (data.og_description) {
        ogScore += 7;
    } else {
        recommendations.push({ type: 'warning', message: 'OG description missing (important for social media)' });
    }
    
    if (data.og_image) {
        ogScore += 6;
    } else {
        recommendations.push({ type: 'warning', message: 'OG image missing (critical for social sharing)' });
    }
    
    if (ogScore === 20) {
        recommendations.push({ type: 'success', message: 'All Open Graph tags are present' });
    }
    score += ogScore;
    
    // Canonical URL (15 points)
    if (data.canonical_url) {
        try {
            new URL(data.canonical_url);
            score += 15;
            recommendations.push({ type: 'success', message: 'Canonical URL is properly set' });
        } catch {
            score += 5;
            recommendations.push({ type: 'error', message: 'Canonical URL format is invalid' });
        }
    } else {
        recommendations.push({ type: 'warning', message: 'Canonical URL not set' });
    }
    
    // Robots directive (10 points)
    if (data.robots === 'index, follow') {
        score += 10;
        recommendations.push({ type: 'success', message: 'Robots directive allows indexing' });
    } else if (data.robots === 'noindex, follow' || data.robots === 'index, nofollow') {
        score += 5;
        recommendations.push({ type: 'warning', message: 'Robots directive is restrictive' });
    } else {
        recommendations.push({ type: 'info', message: 'Page is not indexed by search engines' });
    }
    
    // Page slug quality (5 points)
    if (data.page_slug) {
        if (/^[a-z0-9-]+$/.test(data.page_slug)) {
            score += 5;
            recommendations.push({ type: 'success', message: 'Page slug is SEO-friendly' });
        } else {
            score += 2;
            recommendations.push({ type: 'warning', message: 'Page slug should use lowercase, numbers, and hyphens only' });
        }
    }
    
    // Calculate grade
    let grade = 'F';
    if (score >= 90) grade = 'A+';
    else if (score >= 80) grade = 'A';
    else if (score >= 70) grade = 'B';
    else if (score >= 60) grade = 'C';
    else if (score >= 50) grade = 'D';
    
    updateSEODisplay(score, grade, recommendations);
}

function updateSEODisplay(score, grade, recommendations) {
    // Update score value
    document.getElementById('seo-score-value').textContent = score;
    document.getElementById('seo-grade').textContent = grade;
    
    // Update color based on score
    let color = '#ef4444'; // red
    if (score >= 80) color = '#22c55e'; // green
    else if (score >= 60) color = '#eab308'; // yellow
    else if (score >= 40) color = '#f97316'; // orange
    
    document.getElementById('seo-score-value').style.color = color;
    document.getElementById('seo-grade').style.color = color;
    document.getElementById('seo-score-circle').style.stroke = color;
    
    // Update circle progress
    const circumference = 2 * Math.PI * 32;
    const offset = circumference - (score / 100 * circumference);
    document.getElementById('seo-score-circle').style.strokeDashoffset = offset;
    
    // Update recommendations
    const recsContainer = document.getElementById('seo-recommendations');
    if (recommendations.length === 0) {
        recsContainer.innerHTML = '<p class="text-slate-400 text-sm">No recommendations at this time.</p>';
    } else {
        const icons = {
            success: '✓',
            warning: '⚠',
            error: '✗',
            info: 'ℹ'
        };
        
        const colors = {
            success: 'text-green-400 bg-green-500/10 border-green-500/20',
            warning: 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20',
            error: 'text-red-400 bg-red-500/10 border-red-500/20',
            info: 'text-blue-400 bg-blue-500/10 border-blue-500/20'
        };
        
        recsContainer.innerHTML = recommendations.map(rec => 
            `<div class="flex items-start gap-2 p-3 ${colors[rec.type]} border rounded-lg">
                <span class="font-bold">${icons[rec.type]}</span>
                <span class="text-sm flex-1">${rec.message}</span>
            </div>`
        ).join('');
    }
}

// Handle OG Image file upload preview
let currentOgImageUrl = '';

document.getElementById('og_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            currentOgImageUrl = event.target.result;
            document.getElementById('og-image-preview-img').src = currentOgImageUrl;
            document.getElementById('og-image-preview').classList.remove('hidden');
            updatePreviews();
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('remove-og-image').addEventListener('click', function() {
    document.getElementById('og_image').value = '';
    document.getElementById('og-image-preview').classList.add('hidden');
    document.getElementById('existing_og_image').value = '';
    currentOgImageUrl = '';
    updatePreviews();
});

// Update preview sections
function updatePreviews() {
    // Google Search Preview
    const title = document.getElementById('page_title').value || 'Your Page Title Will Appear Here';
    const description = document.getElementById('meta_description').value || 'Your meta description will appear here. This is what users will see in search engine results. Make it compelling!';
    const slug = document.getElementById('page_slug').value || 'page-slug';
    const canonicalUrl = document.getElementById('canonical_url').value;
    
    document.getElementById('preview-title').textContent = title;
    document.getElementById('preview-description').textContent = description;
    
    // Use canonical URL if provided, otherwise construct from slug
    if (canonicalUrl) {
        document.getElementById('preview-url').textContent = canonicalUrl;
    } else {
        document.getElementById('preview-url').textContent = `https://codefiesta.in/${slug}`;
    }
    
    // Social Media (Open Graph) Preview
    const ogTitle = document.getElementById('og_title').value || title || 'Open Graph Title Will Appear Here';
    const ogDescription = document.getElementById('og_description').value || description || 'Your Open Graph description for social media sharing will appear here.';
    
    document.getElementById('preview-og-title').textContent = ogTitle;
    document.getElementById('preview-og-description').textContent = ogDescription;
    
    // Handle OG Image
    const imageContainer = document.getElementById('preview-og-image-container');
    const imageElement = document.getElementById('preview-og-image');
    
    if (currentOgImageUrl) {
        imageElement.src = currentOgImageUrl;
        imageElement.classList.remove('hidden');
        imageContainer.querySelector('.text-center')?.classList.add('hidden');
        
        // Handle image load error
        imageElement.onerror = function() {
            this.classList.add('hidden');
            imageContainer.querySelector('.text-center')?.classList.remove('hidden');
        };
        
        imageElement.onload = function() {
            imageContainer.querySelector('.text-center')?.classList.add('hidden');
        };
    } else {
        imageElement.classList.add('hidden');
        imageContainer.querySelector('.text-center')?.classList.remove('hidden');
    }
}

// Character counters
document.getElementById('page_title').addEventListener('input', function() {
    const count = this.value.length;
    const counter = document.getElementById('title-counter');
    counter.textContent = `(${count}/60)`;
    counter.className = count > 60 ? 'text-red-400 text-xs' : count >= 50 ? 'text-green-400 text-xs' : 'text-slate-500 text-xs';
    calculateSEOScoreClient();
    updatePreviews();
});

document.getElementById('meta_description').addEventListener('input', function() {
    const count = this.value.length;
    const counter = document.getElementById('description-counter');
    counter.textContent = `(${count}/160)`;
    counter.className = count > 160 ? 'text-red-400 text-xs' : count >= 150 ? 'text-green-400 text-xs' : 'text-slate-500 text-xs';
    calculateSEOScoreClient();
    updatePreviews();
});

// Add event listeners to all form inputs
['page_slug', 'meta_keywords', 'og_title', 'og_description', 'canonical_url', 'robots'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener('input', function() {
            calculateSEOScoreClient();
            updatePreviews();
        });
        element.addEventListener('change', function() {
            calculateSEOScoreClient();
            updatePreviews();
        });
    }
});

// Initial calculation and preview
calculateSEOScoreClient();
updatePreviews();
</script>

<?php include 'includes/footer.php'; ?>
