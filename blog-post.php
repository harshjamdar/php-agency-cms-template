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
    
    // Track page view and increment view counter
    trackAdvancedPageView('blog_' . $slug, '/blog-post.php?slug=' . $slug);
    
    // Increment view count for this post
    if ($pdo) {
        try {
            // Check if view_count column exists, if not add it
            $pdo->exec("ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0");
        } catch (PDOException $e) {
            // Column might already exist
        }
        
        // Only increment if not viewed in this session
        $session_key = 'viewed_post_' . $post['id'];
        if (!isset($_SESSION[$session_key])) {
            try {
                $stmt = $pdo->prepare("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?");
                $stmt->execute([$post['id']]);
                $_SESSION[$session_key] = true;
                
                // Refresh post data to get updated view count
                $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
                $stmt->execute([$slug]);
                $post = $stmt->fetch();
            } catch (PDOException $e) {
                error_log("View count update error: " . $e->getMessage());
            }
        }
    }
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
                <div class="flex items-center justify-center gap-4 text-sm text-gray-400 mb-6 flex-wrap">
                    <?php if ($post['is_featured']): ?>
                    <span class="px-3 py-1 rounded-full bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        Featured
                    </span>
                    <?php endif; ?>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <?php echo $reading_time; ?> min read
                    </span>
                    <span class="flex items-center gap-1 px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <?php echo number_format($post['view_count'] ?? 0); ?> views
                    </span>
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
            <div class="border-t border-white/10 mt-12 pt-8">
                <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/80 border border-white/10 rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 text-gray-300">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <span class="text-lg font-semibold"><?php echo number_format($post['view_count'] ?? 0); ?></span>
                                <span class="text-sm text-gray-400">people read this</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <span class="text-gray-300 text-sm font-medium">Share this article:</span>
                            <div class="flex gap-2">
                                <button onclick="shareOnFacebook()" class="group relative w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center text-white hover:scale-110 transition-all shadow-lg hover:shadow-blue-500/50" title="Share on Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </button>
                                <button onclick="shareOnTwitter()" class="group relative w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center text-white hover:scale-110 transition-all shadow-lg hover:shadow-sky-500/50" title="Share on Twitter">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                                </button>
                                <button onclick="shareOnLinkedIn()" class="group relative w-10 h-10 rounded-xl bg-gradient-to-br from-blue-700 to-blue-800 flex items-center justify-center text-white hover:scale-110 transition-all shadow-lg hover:shadow-blue-700/50" title="Share on LinkedIn">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </button>
                                <button onclick="shareOnWhatsApp()" class="group relative w-10 h-10 rounded-xl bg-gradient-to-br from-green-600 to-green-700 flex items-center justify-center text-white hover:scale-110 transition-all shadow-lg hover:shadow-green-600/50" title="Share on WhatsApp">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                </button>
                                <button onclick="copyLink(event)" class="group relative w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center text-white hover:scale-110 transition-all shadow-lg hover:shadow-slate-700/50" title="Copy link">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </div>
                        </div>
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

        // Social Share Functions
        const postUrl = encodeURIComponent(window.location.href);
        const postTitle = encodeURIComponent("<?php echo addslashes($post['title']); ?>");
        const postDescription = encodeURIComponent("<?php echo addslashes($post['excerpt'] ?? substr(strip_tags($post['content']), 0, 200)); ?>");

        function shareOnFacebook() {
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${postUrl}`, '_blank', 'width=600,height=400');
        }

        function shareOnTwitter() {
            window.open(`https://twitter.com/intent/tweet?url=${postUrl}&text=${postTitle}`, '_blank', 'width=600,height=400');
        }

        function shareOnLinkedIn() {
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${postUrl}`, '_blank', 'width=600,height=400');
        }

        function shareOnWhatsApp() {
            window.open(`https://wa.me/?text=${postTitle}%20${postUrl}`, '_blank');
        }

        function copyLink(event) {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                // Show success notification
                const button = event.currentTarget;
                const originalHTML = button.innerHTML;
                button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                button.classList.add('bg-green-600');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('bg-green-600');
                }, 2000);
            }).catch(err => {
                console.error('Copy failed:', err);
                alert('Failed to copy link');
            });
        }
    </script>
</body>
</html>
