<?php
require_once 'includes/helpers/security-headers.php';
require_once 'config.php';
require_once 'includes/helpers/whitelabel-helper.php';
require_once 'includes/helpers/advanced-tracking.php';
require_once 'includes/helpers/seo-helper.php';
require_once 'admin/security.php';

$activeTheme = getSetting('active_theme', 'default');
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : null;

if (!$slug) {
    header("Location: blog.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();

    if (!$post) {
        header("HTTP/1.0 404 Not Found");
        include '404.php';
        exit;
    }
    
    // Track page view
    trackAdvancedPageView('blog_' . $slug, '/blog-post.php?slug=' . $slug);
} catch (PDOException $e) {
    // logError("Blog post fetch error: " . $e->getMessage()); // logError might not be defined
    die("An error occurred. Please try again later.");
}

// Calculate reading time (approximate)
$word_count = str_word_count(strip_tags($post['content']));
$reading_time = max(1, ceil($word_count / 200)); // 200 words per minute
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <?php 
    $siteName = getSiteName();
    outputSEOTags(
        $post['slug'], // Use post slug for lookup
        $post['title'] . ' | ' . $siteName . ' Blog',
        $post['excerpt'] ?? substr(strip_tags($post['content']), 0, 160),
        $post['image_url'] ?? ''
    ); 
    ?>
    <meta name="author" content="<?php echo htmlspecialchars($post['author']); ?>">
    
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
    <link rel="stylesheet" href="assets/css/style.css">
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
        <!-- Progress Bar -->
        <div class="fixed top-0 left-0 h-1 bg-secondary z-50 transition-all duration-300" id="scroll-progress" style="width: 0%"></div>

        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Article Header -->
            <header class="text-center mb-12 fade-in-up">
                <div class="flex items-center justify-center gap-4 text-sm text-gray-400 mb-6">
                    <?php if ($post['is_featured']): ?>
                    <span class="px-3 py-1 rounded-full bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Featured</span>
                    <?php endif; ?>
                    <span><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                    <span><?php echo $reading_time; ?> min read</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-8 leading-tight">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h1>
                <div class="flex items-center justify-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                        <?php echo strtoupper(substr($post['author'], 0, 2)); ?>
                    </div>
                    <div class="text-left">
                        <div class="text-white font-medium"><?php echo htmlspecialchars($post['author']); ?></div>
                        <div class="text-gray-500 text-sm">Author</div>
                    </div>
                </div>
            </header>

            <!-- Featured Image -->
            <?php if (!empty($post['image_url'])): ?>
            <div class="rounded-2xl overflow-hidden mb-12 border border-white/10 shadow-2xl fade-in-up">
                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto">
            </div>
            <?php endif; ?>
            
            <!-- Excerpt -->
            <?php if (!empty($post['excerpt'])): ?>
            <div class="prose prose-invert prose-lg max-w-none text-gray-300 fade-in-up mb-8">
                <p class="lead text-xl text-gray-200">
                    <?php echo htmlspecialchars($post['excerpt']); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Article Content -->
            <div class="prose prose-invert prose-lg max-w-none text-gray-300 fade-in-up">
                <?php 
                echo sanitizeHTML($post['content']); 
                ?>
            </div>

            <!-- Share & Tags -->
            <div class="border-t border-white/10 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex gap-2">
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-gray-400 text-sm">Article</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-400 text-sm">Share this article:</span>
                    <div class="flex gap-2">
                        <button class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-secondary transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </button>
                        <button class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-secondary transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                        </button>
                        <button class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-secondary transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                        </button>
                    </div>
                </div>
            </div>
        </article>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script>
        // Scroll Progress Bar
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        });
    </script>
</body>
</html>
