<?php
require_once __DIR__ . '/helpers/whitelabel-helper.php';
$siteName = getSiteName();
$siteTagline = getSiteTagline();
$siteLogo = getSetting('site_logo');
$fullLogo = getSetting('full_logo');
$brandingMode = getSetting('branding_mode', 'text_logo');
outputColorVariables();
?>

<nav class="fixed top-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-md border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-2">
                <a href="index.php" class="flex items-center gap-2 hover:opacity-90 transition-opacity">
                    <?php if ($brandingMode === 'full_logo' && $fullLogo && file_exists(__DIR__ . '/../' . $fullLogo)): ?>
                        <img src="<?php echo htmlspecialchars($fullLogo); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-12 w-auto object-contain">
                    <?php else: ?>
                        <?php if ($siteLogo && file_exists(__DIR__ . '/../' . $siteLogo)): ?>
                            <img src="<?php echo htmlspecialchars($siteLogo); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-8 w-auto">
                        <?php else: ?>
                            <!-- Code2 Icon -->
                            <svg class="h-8 w-8 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                        <?php endif; ?>
                        <span class="font-bold text-xl tracking-tight text-white"><?php echo htmlspecialchars($siteName); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-8">
                    <a href="index.php#services" class="text-gray-300 hover:text-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Services</a>
                    <a href="index.php#portfolio" class="text-gray-300 hover:text-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Portfolio</a>
                    <a href="blog.php" class="text-gray-300 hover:text-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Blog</a>
                    <a href="index.php#estimator" class="text-gray-300 hover:text-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Cost Estimator</a>
                    <a href="index.php#contact" class="text-gray-300 hover:text-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Contact</a>
                </div>
            </div>
            
            <div class="hidden md:block">
                <a href="index.php#contact" class="bg-primary hover:bg-primary/90 text-white px-5 py-2 rounded-full font-semibold transition-all shadow-[0_0_15px_rgba(139,92,246,0.5)] text-sm">
                    Get Free Consultation
                </a>
            </div>

            <div class="-mr-2 flex md:hidden">
                <button id="mobile-menu-btn" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10 focus:outline-none">
                    <!-- Menu Icon -->
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-slate-900 border-b border-white/10">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="index.php#services" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Services</a>
            <a href="blog.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Blog</a>
            <a href="index.php#portfolio" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Portfolio</a>
            <a href="index.php#estimator" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Cost Estimator</a>
            <a href="index.php#contact" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Contact</a>
        </div>
    </div>
</nav>
