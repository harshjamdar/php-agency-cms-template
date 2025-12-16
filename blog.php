<?php
require_once 'includes/helpers/security-headers.php';
require_once 'config.php';
require_once 'includes/helpers/whitelabel-helper.php';
require_once 'includes/helpers/advanced-tracking.php';
require_once 'includes/helpers/seo-helper.php';

$siteName = getSiteName();
$activeTheme = getSetting('active_theme', 'default');

// Track page view
trackAdvancedPageView('blog', '/blog.php');

try {
    // Fetch all published posts
    $stmt = $pdo->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist or other DB error, handle gracefully
    $posts = [];
    // Uncomment for debugging:
    // echo "Database Error: " . $e->getMessage();
}

$featured_post = null;
$grid_posts = [];

if (!empty($posts)) {
    $featured_post = $posts[0];
    $grid_posts = array_slice($posts, 1);
}
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <?php 
    outputSEOTags(
        'blog', 
        'Blog | ' . $siteName . ' Insights',
        'Latest insights on software development, digital marketing trends, and startup growth strategies from the ' . $siteName . ' team.'
    ); 
    ?>
    
    <?php include 'includes/tracking-scripts.php'; ?>
    
    <!-- Favicon -->
    <?php 
    $siteIcon = getSetting('site_icon');
    $faviconPath = ($siteIcon && file_exists($siteIcon)) ? $siteIcon : '/favicon.ico';
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "#020617", // slate-950
                        foreground: "#f8fafc", // slate-50
                        primary: {
                            DEFAULT: "#8b5cf6", // violet-500
                            foreground: "#ffffff",
                        },
                        secondary: {
                            DEFAULT: "#06b6d4", // cyan-500
                            foreground: "#ffffff",
                        },
                        accent: {
                            DEFAULT: "#1e293b", // slate-800
                            foreground: "#f8fafc",
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <?php if ($activeTheme !== 'default' && file_exists('assets/css/' . $activeTheme . '.css')): ?>
    <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($activeTheme); ?>.css?v=<?php echo time(); ?>">
    <?php endif; ?>
</head>
<body class="bg-background text-foreground antialiased selection:bg-secondary/30 relative <?php echo $activeTheme !== 'default' ? 'theme-' . htmlspecialchars($activeTheme) : ''; ?>">
    <?php include 'includes/tracking-body.php'; ?>

    <!-- Particle Background Canvas -->
    <canvas id="particle-canvas" class="fixed inset-0 w-full h-full pointer-events-none z-0"></canvas>

    <?php include 'includes/header.php'; ?>

    <main class="relative z-10 pt-24 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-16 fade-in-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary mb-4 border border-primary/20">
                    <span class="text-sm font-semibold">Our Blog</span>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Insights & <span class="text-secondary">Resources</span>
                </h1>
                <p class="text-gray-400 max-w-2xl mx-auto text-lg">
                    Expert articles on technology, marketing, and business growth to help you stay ahead of the curve.
                </p>
            </div>

            <?php if ($featured_post): ?>
            <!-- Featured Post -->
            <div class="mb-16 fade-in-up">
                <a href="blog-post.php?slug=<?= htmlspecialchars($featured_post['slug']) ?>" class="group relative block rounded-3xl overflow-hidden bg-slate-900 border border-white/10 hover:border-secondary/50 transition-all">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="h-64 lg:h-auto bg-gradient-to-br from-purple-900 to-slate-900 relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                            <?php if ($featured_post['image_url']): ?>
                                <img src="<?= htmlspecialchars($featured_post['image_url']) ?>" alt="<?= htmlspecialchars($featured_post['title']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center text-white/10">
                                    <svg class="w-32 h-32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-8 lg:p-12 flex flex-col justify-center">
                            <div class="flex items-center gap-4 mb-4 text-sm">
                                <span class="text-secondary font-semibold">Latest</span>
                                <span class="text-gray-500">•</span>
                                <span class="text-gray-400"><?= date('M d, Y', strtotime($featured_post['created_at'])) ?></span>
                            </div>
                            <h2 class="text-3xl font-bold text-white mb-4 group-hover:text-secondary transition-colors">
                                <?= htmlspecialchars($featured_post['title']) ?>
                            </h2>
                            <p class="text-gray-400 mb-6 leading-relaxed">
                                <?= htmlspecialchars($featured_post['excerpt']) ?>
                            </p>
                            <span class="inline-flex items-center text-white font-medium group-hover:translate-x-2 transition-transform">
                                Read Article 
                                <svg class="ml-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Post Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($grid_posts as $post): ?>
                <article class="group bg-slate-900/50 border border-white/10 rounded-2xl overflow-hidden hover:border-secondary/50 transition-all fade-in-up">
                    <a href="blog-post.php?slug=<?= htmlspecialchars($post['slug']) ?>" class="block">
                        <div class="h-48 bg-gradient-to-br from-blue-900 to-slate-800 relative overflow-hidden">
                            <?php if ($post['image_url']): ?>
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center text-white/10">
                                    <svg class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-3 text-xs text-gray-500">
                                <span>By <?= htmlspecialchars($post['author']) ?></span>
                                <span>•</span>
                                <span><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3 group-hover:text-secondary transition-colors">
                                <?= htmlspecialchars($post['title']) ?>
                            </h3>
                            <p class="text-gray-400 text-sm leading-relaxed mb-4 line-clamp-3">
                                <?= htmlspecialchars($post['excerpt']) ?>
                            </p>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($posts)): ?>
                <div class="text-center py-20">
                    <p class="text-gray-400 text-lg">No blog posts found. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
