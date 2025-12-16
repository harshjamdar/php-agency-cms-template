<?php
require_once 'includes/helpers/whitelabel-helper.php';
require_once 'includes/helpers/seo-helper.php';
$siteName = getSiteName();
$customContent = getSetting('privacy_policy_content');
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <?php 
    outputSEOTags(
        'privacy-policy', 
        'Privacy Policy | ' . $siteName,
        'Privacy Policy for ' . $siteName . '. Learn how we collect, use, and protect your personal information.'
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
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 text-primary mb-4">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span class="text-sm font-semibold">Privacy Policy</span>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Privacy Policy</h1>
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
                        Introduction
                    </h2>
                    <p class="text-gray-300 leading-relaxed">
                        <?php echo htmlspecialchars($siteName); ?> ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.
                    </p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-4 flex items-center gap-3">
                        <svg class="h-6 w-6 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                        Information We Collect
                    </h2>
                    
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-xl font-semibold mb-3 text-white">Personal Data</h3>
                            <p class="text-gray-300 leading-relaxed">
                                Personally identifiable information, such as your name, shipping address, email address, and telephone number, and demographic information, such as your age, gender, hometown, and interests, that you voluntarily give to us when you register with the Site or when you choose to participate in various activities related to the Site.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
