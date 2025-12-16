<?php
require_once 'includes/helpers/whitelabel-helper.php';
$siteName = getSiteName();
$footerText = getFooterText();
outputColorVariables();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <?php 
    $siteIcon = getSetting('site_icon');
    $faviconPath = ($siteIcon && file_exists(__DIR__ . '/' . $siteIcon)) ? $siteIcon : 'favicon.ico';
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
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <?php
    // Calculate base path for assets based on URL depth
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Remove script name if it's part of the path (e.g. /index.php)
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptName !== '/' && strpos($requestPath, $scriptName) === 0) {
        $relativePath = substr($requestPath, strlen($scriptName));
    } else {
        $relativePath = $requestPath;
    }
    $depth = substr_count(trim($relativePath, '/'), '/');
    $assetBase = $depth > 0 ? str_repeat('../', $depth) : '';
    ?>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/css/style.css">
</head>
<body class="bg-background text-foreground antialiased h-screen flex flex-col relative overflow-hidden">
    <?php include 'includes/tracking-body.php'; ?>

    <!-- Particle Background Canvas -->
    <canvas id="particle-canvas" class="fixed inset-0 w-full h-full pointer-events-none z-0"></canvas>

    <main class="flex-1 flex items-center justify-center relative z-10 px-4">
        <div class="text-center max-w-2xl mx-auto fade-in-up">
            <div class="mb-8 relative inline-block">
                <h1 class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary opacity-20 select-none">404</h1>
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="h-32 w-32 text-white animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
            </div>
            
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Page Not Found</h2>
            <p class="text-gray-400 text-lg mb-8">
                Oops! The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?php echo $assetBase; ?>index.php" class="px-8 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg transition-all shadow-[0_0_20px_rgba(139,92,246,0.3)] hover:shadow-[0_0_30px_rgba(139,92,246,0.5)] hover:-translate-y-1 flex items-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Back to Home
                </a>
                <a href="<?php echo $assetBase; ?>index.php#contact" class="px-8 py-3 border border-white/20 text-white font-bold rounded-lg hover:bg-white/5 transition-all hover:-translate-y-1">
                    Contact Support
                </a>
            </div>
        </div>
    </main>

    <footer class="relative z-10 py-6 text-center border-t border-white/5">
        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($footerText); ?></p>
    </footer>

    <!-- Scripts -->
    <script src="<?php echo $assetBase; ?>assets/js/main.js"></script>
</body>
</html>
