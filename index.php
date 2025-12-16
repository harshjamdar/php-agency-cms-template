<?php 
/**
 * CodeFiesta - Custom Software Development Landing Page
 * 
 * @package  CodeFiesta
 * @author   Harsh Jamdar
 * @version  1.0.0
 * @link     https://github.com/harshjamdar
 */
require_once 'includes/helpers/security-headers.php'; 
require_once 'config.php';
require_once 'includes/helpers/whitelabel-helper.php';
require_once 'includes/helpers/advanced-tracking.php';
require_once 'includes/helpers/seo-helper.php';

$siteName = getSiteName();
$activeTheme = getSetting('active_theme', 'default');
trackAdvancedPageView('home', '/');
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <?php 
    outputSEOTags(
        'home', 
        $siteName . ' | Custom Software Development Company for Startups',
        $siteName . ' is an ROI-focused digital marketing agency and custom software development company for startups. We offer affordable web design for small business and expert Android app development services in India.'
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

    <main class="relative z-10">
        <?php include 'includes/sections/hero.php'; ?>
        <?php include 'includes/sections/services.php'; ?>
        <?php include 'includes/sections/estimator.php'; ?>
        <?php include 'includes/popup-renderer.php'; ?>
        <?php include 'includes/sections/trust.php'; ?>
        <?php include 'includes/sections/portfolio.php'; ?>
        <?php include 'includes/sections/testimonials.php'; ?>
        <?php include 'includes/sections/team.php'; ?>
        <?php include 'includes/sections/blog.php'; ?>
        <?php include 'includes/sections/faq.php'; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scroll To Top -->
    <button id="scroll-to-top" class="fixed bottom-8 right-8 bg-primary text-white p-3 rounded-full shadow-lg opacity-0 pointer-events-none transition-all duration-300 hover:bg-primary/90 z-50">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
    </button>

    <!-- Scripts -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
