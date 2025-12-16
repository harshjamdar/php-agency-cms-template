<?php
// A/B Testing Logic
$headline = 'Build <span class="text-transparent bg-clip-text bg-gradient-to-r from-secondary via-blue-400 to-primary animate-gradient-x">Scalable Software</span> & SEO-Driven Websites';
$activeVariantId = null;

try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    // Check for active experiment
    $stmt = $pdo->prepare("SELECT id FROM ab_tests WHERE test_key = 'hero_headline' AND is_active = 1");
    $stmt->execute();
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($test) {
        // Check if user already has a variant assigned
        $sessionKey = 'ab_test_' . $test['id'];
        
        if (isset($_COOKIE[$sessionKey])) {
            $variantName = $_COOKIE[$sessionKey];
        } else {
            // Randomly assign A or B
            $variantName = (rand(0, 1) === 0) ? 'A' : 'B';
            setcookie($sessionKey, $variantName, time() + (86400 * 30), "/"); // 30 days
        }

        // Fetch content for assigned variant
        $stmt = $pdo->prepare("SELECT id, content FROM ab_variants WHERE test_id = ? AND variant_name = ?");
        $stmt->execute([$test['id'], $variantName]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($variant) {
            $headline = $variant['content'];
            $activeVariantId = $variant['id'];
        }
    }
} catch (Exception $e) {
    // Fallback to default
}
?>
<!-- Hero Section -->
<section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-[500px] h-[500px] bg-secondary/20 rounded-full blur-[100px] pointer-events-none animate-pulse-slow"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-[500px] h-[500px] bg-primary/20 rounded-full blur-[100px] pointer-events-none animate-pulse-slow" style="animation-delay: 2s;"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            
            <!-- Text Content -->
            <div class="fade-in-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 mb-6 backdrop-blur-sm hover:bg-white/10 transition-colors cursor-default">
                    <span class="flex h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-sm font-medium text-gray-300">Custom software development company for startups</span>
                </div>
                
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight mb-6">
                    <?php echo $headline; ?>
                </h1>
                
                <p class="text-lg md:text-xl text-gray-400 mb-8 leading-relaxed max-w-lg">
                    We turn your ideas into digital reality. From <span class="text-gray-200 font-semibold">custom Android apps</span> to <span class="text-gray-200 font-semibold">high-ROI digital marketing campaigns</span>, we fuel business growth.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#estimator" id="hero-cta-btn" class="group inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-semibold rounded-lg text-white bg-primary hover:bg-primary/90 transition-all shadow-[0_0_20px_rgba(139,92,246,0.3)] hover:shadow-[0_0_30px_rgba(139,92,246,0.5)] hover:-translate-y-1">
                        Get an Estimate 
                        <svg class="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                    <a href="#services" class="inline-flex items-center justify-center px-8 py-3 border border-white/20 text-base font-semibold rounded-lg text-white hover:bg-white/5 transition-all hover:-translate-y-1">
                        Explore Services
                    </a>
                </div>
            </div>

            <?php if ($activeVariantId): ?>
            <script>
                // Track View
                fetch('api/track-ab.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'view', variant_id: <?php echo $activeVariantId; ?> })
                });

                // Track Click
                document.getElementById('hero-cta-btn').addEventListener('click', function() {
                    fetch('api/track-ab.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ type: 'click', variant_id: <?php echo $activeVariantId; ?> })
                    });
                });
            </script>
            <?php endif; ?>

            <!-- Visual Content -->
            <div class="relative fade-in-up" style="animation-delay: 0.2s;">
                <div class="relative rounded-2xl bg-gradient-to-br from-gray-900/90 to-black/90 border border-white/10 p-6 shadow-2xl backdrop-blur-xl">
                    <!-- Floating Cards -->
                    <div class="absolute -top-6 -right-6 bg-gray-800/90 backdrop-blur-md p-4 rounded-xl border border-white/10 shadow-xl z-20 animate-float">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500/20 rounded-lg">
                                <svg class="h-6 w-6 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Monthly Growth</p>
                                <p class="text-lg font-bold text-white">+124% ROI</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-6 -left-6 bg-gray-800/90 backdrop-blur-md p-4 rounded-xl border border-white/10 shadow-xl z-20 animate-float-delayed">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500/20 rounded-lg">
                                <svg class="h-6 w-6 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Launch Speed</p>
                                <p class="text-lg font-bold text-white">2 Weeks</p>
                            </div>
                        </div>
                    </div>

                    <!-- Code Snippet Visual -->
                    <div class="rounded-lg bg-gray-950 p-4 font-mono text-sm border border-white/5 overflow-hidden">
                        <div class="flex gap-1.5 mb-4">
                            <div class="w-3 h-3 rounded-full bg-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/50"></div>
                        </div>
                        <div class="space-y-2 text-gray-400">
                            <div class="flex">
                                <span class="text-purple-400 mr-2">const</span>
                                <span class="text-blue-400">boostBusiness</span>
                                <span class="text-gray-400 mx-2">=</span>
                                <span class="text-yellow-300">async</span>
                                <span class="text-gray-400">()</span>
                                <span class="text-gray-400 mx-2">=></span>
                                <span class="text-gray-400">{</span>
                            </div>
                            <div class="pl-4 flex">
                                <span class="text-purple-400 mr-2">await</span>
                                <span class="text-blue-400">CodeFiesta</span>
                                <span class="text-gray-400">.</span>
                                <span class="text-yellow-300">launch</span>
                                <span class="text-gray-400">({</span>
                            </div>
                            <div class="pl-8">
                                <span class="text-blue-300">strategy:</span>
                                <span class="text-green-300">'ROI-First'</span><span class="text-gray-400">,</span>
                            </div>
                            <div class="pl-8">
                                <span class="text-blue-300">design:</span>
                                <span class="text-green-300">'World-Class'</span><span class="text-gray-400">,</span>
                            </div>
                            <div class="pl-8">
                                <span class="text-blue-300">tech:</span>
                                <span class="text-green-300">'Scalable'</span>
                            </div>
                            <div class="pl-4 text-gray-400">});</div>
                            <div class="pl-4 flex">
                                <span class="text-purple-400 mr-2">return</span>
                                <span class="text-green-300">"Success"</span><span class="text-gray-400">;</span>
                            </div>
                            <div class="text-gray-400">}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>