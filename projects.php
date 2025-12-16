<?php
require_once 'includes/helpers/security-headers.php';
require_once 'config.php';
require_once 'includes/helpers/whitelabel-helper.php';
require_once 'includes/helpers/advanced-tracking.php';
require_once 'includes/helpers/seo-helper.php';

$siteName = getSiteName();
$activeTheme = getSetting('active_theme', 'default');

// Track page view
trackAdvancedPageView('projects', '/projects.php');

// Fetch Projects
try {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching projects: " . $e->getMessage());
    $projects = [];
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
        'projects', 
        'All Projects | ' . $siteName,
        'Explore our complete portfolio of custom software, web applications, and mobile apps developed for startups and businesses worldwide.'
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
                        destructive: {
                            DEFAULT: "hsl(var(--destructive))",
                            foreground: "hsl(var(--destructive-foreground))",
                        },
                        muted: {
                            DEFAULT: "hsl(var(--muted))",
                            foreground: "hsl(var(--muted-foreground))",
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 fade-in-up">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Our <span class="text-secondary">Projects</span>
                </h1>
                <p class="text-gray-400 max-w-2xl mx-auto text-lg">
                    Explore our diverse portfolio of successful digital products, from enterprise platforms to consumer mobile apps.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $index => $project): ?>
                    <!-- Project <?php echo $index + 1; ?> -->
                    <a href="project-view.php?slug=<?php echo htmlspecialchars($project['slug']); ?>" class="group rounded-2xl bg-slate-900/50 border border-white/10 overflow-hidden hover:border-secondary/50 transition-all hover:shadow-[0_0_20px_rgba(6,182,212,0.15)] fade-in-up block" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="h-48 w-full relative overflow-hidden group-hover:scale-105 transition-transform duration-500 bg-gradient-to-br from-blue-600 to-cyan-500">
                            <?php if (!empty($project['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="absolute inset-0 w-full h-full object-cover">
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                            <div class="absolute bottom-4 left-4 bg-black/60 backdrop-blur-md px-3 py-1 rounded-full border border-white/10">
                                <span class="text-xs font-semibold text-white tracking-wide uppercase"><?php echo htmlspecialchars($project['category']); ?></span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-white mb-3 group-hover:text-secondary transition-colors"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="text-gray-400 text-sm leading-relaxed mb-6 line-clamp-3"><?php echo htmlspecialchars($project['description']); ?></p>
                            <div class="flex flex-wrap gap-2 mb-6">
                                <?php 
                                $tags = explode(',', $project['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (empty($tag)) continue;
                                ?>
                                <span class="text-xs font-medium text-slate-300 bg-slate-800 px-2 py-1 rounded border border-slate-700"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-20">
                        <p class="text-gray-400 text-lg">No projects found. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scroll To Top -->
    <button id="scroll-to-top" class="fixed bottom-8 right-8 bg-primary text-white p-3 rounded-full shadow-lg opacity-0 pointer-events-none transition-all duration-300 hover:bg-primary/90 z-50">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
    </button>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
</body>
</html>
