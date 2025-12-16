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
    header("Location: projects.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ?");
    $stmt->execute([$slug]);
    $project = $stmt->fetch();

    if (!$project) {
        // Handle 404
        header("HTTP/1.0 404 Not Found");
        include '404.php'; // Assuming you have one, or just die("Project not found");
        exit;
    }
    
    // Track page view
    trackAdvancedPageView('project_' . $slug, '/project-view.php?slug=' . $slug);
} catch (PDOException $e) {
    die("Database error");
}
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
        $project['slug'], // Use project slug for lookup
        $project['title'] . ' | ' . $siteName . ' Projects',
        $project['description'],
        $project['image_url'] ?? ''
    ); 
    ?>
    
    <?php include 'includes/tracking-scripts.php'; ?>
    
    <!-- Favicon -->
    <?php 
    $siteIcon = getSetting('site_icon');
    $faviconPath = ($siteIcon && file_exists($siteIcon)) ? $siteIcon : '/favicon.ico';
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconPath); ?>">

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
        <!-- Hero Section -->
        <section class="relative py-20 overflow-hidden">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl bg-primary/5 blur-3xl rounded-full pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-4xl mx-auto fade-in-up">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary mb-6 border border-primary/20">
                        <span class="text-sm font-semibold uppercase tracking-wide"><?php echo htmlspecialchars($project['category']); ?></span>
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                        <?php echo htmlspecialchars($project['title']); ?>
                    </h1>
                    <p class="text-xl text-gray-400 leading-relaxed">
                        <?php echo htmlspecialchars($project['description']); ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Featured Image -->
                <?php if (!empty($project['image_url'])): ?>
                <div class="rounded-2xl overflow-hidden border border-white/10 shadow-2xl mb-12 fade-in-up">
                    <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-auto">
                </div>
                <?php endif; ?>

                <!-- Content Body -->
                <div class="prose prose-invert prose-lg max-w-none fade-in-up">
                    <?php 
                    echo sanitizeHTML($project['content']); 
                    ?>
                </div>

                <!-- Tags -->
                <?php if (!empty($project['tags'])): ?>
                <div class="mt-12 pt-8 border-t border-white/10">
                    <h3 class="text-lg font-semibold text-white mb-4">Technologies Used</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                        $tags = explode(',', $project['tags']);
                        foreach ($tags as $tag): 
                            $tag = trim($tag);
                            if (empty($tag)) continue;
                        ?>
                        <span class="px-3 py-1 bg-slate-900 text-slate-300 rounded-full border border-slate-800 text-sm">
                            <?php echo htmlspecialchars($tag); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- CTA -->
        <section class="py-20 border-t border-white/5">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold text-white mb-6">Ready to build something similar?</h2>
                <a href="index.php#contact" class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-primary hover:bg-primary/90 rounded-lg transition-all shadow-lg shadow-primary/25">
                    Start Your Project
                </a>
            </div>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>