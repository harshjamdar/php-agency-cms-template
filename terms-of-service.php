<?php
require_once 'includes/helpers/whitelabel-helper.php';
require_once 'includes/helpers/seo-helper.php';
$siteName = getSiteName();
$customContent = getSetting('terms_of_service_content');
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <?php 
    outputSEOTags(
        'terms-of-service', 
        'Terms of Service | ' . $siteName,
        'Terms of Service for ' . $siteName . '. Please read these terms carefully before using our services.'
    ); 
    ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: "#020617",
                        foreground: "#f8fafc",
                        primary: "#8b5cf6",
                        secondary: "#06b6d4",
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="bg-background text-foreground antialiased">
    <?php include 'includes/tracking-body.php'; ?>

    <?php include 'includes/header.php'; ?>

    <main class="pt-32 pb-20 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <a href="index.php" class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors mb-8">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Home
            </a>

            <div class="mb-12">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-secondary/10 text-secondary mb-4">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                    <span class="text-sm font-semibold">Terms of Service</span>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Terms of Service</h1>
                <p class="text-gray-400">Last updated: December 14, 2025</p>
            </div>

            <!-- Content -->
            <div class="space-y-8">
                <?php if ($customContent): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 prose prose-invert max-w-none">
                        <?php echo $customContent; ?>
                    </div>
                <?php else: ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                        <svg class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Agreement to Terms
                    </h2>
                    <p class="text-gray-300 leading-relaxed">
                        Welcome to <?php echo htmlspecialchars($siteName); ?>! These Terms of Service ("Terms") govern your use of our website and services. By accessing or using our services, you agree to be bound by these Terms. If you do not agree to these Terms, please do not use our services.
                    </p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                        <svg class="h-6 w-6 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Services Offered
                    </h2>
                    <p class="text-gray-300 mb-4">
                        <?php echo htmlspecialchars($siteName); ?> provides custom software development, web design, and digital marketing services. We reserve the right to modify, suspend, or discontinue any aspect of our services at any time.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
