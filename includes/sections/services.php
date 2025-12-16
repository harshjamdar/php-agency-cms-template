<?php
// Fetch Services from Database
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    $services = safeQuery("SELECT * FROM services WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
} catch (PDOException $e) {
    // Fallback to default services if table doesn't exist
    $services = [
        [
            'title' => 'SEO-Friendly Website Development',
            'description' => 'We build lightning-fast, responsive websites designed to rank high on Google. Perfect for small businesses looking for affordable web design without compromising quality.',
            'icon' => 'globe',
            'color' => 'blue',
            'is_featured' => 1
        ],
        [
            'title' => 'Native Android & iOS Solutions',
            'description' => 'Top-tier Android app development services in India. We create seamless mobile experiences using React Native and Flutter for maximum reach and performance.',
            'icon' => 'smartphone',
            'color' => 'purple',
            'is_featured' => 1
        ],
        [
            'title' => 'ROI-Focused Digital Marketing',
            'description' => 'Our digital marketing agency focuses on one metric: Profit. SEO, PPC, and Social Media campaigns that bring leads, not just likes.',
            'icon' => 'trending-up',
            'color' => 'green',
            'is_featured' => 1
        ],
        [
            'title' => 'AI Integration & Cloud',
            'description' => 'Future-proof your business with custom AI chatbots and scalable cloud architecture.',
            'icon' => 'cpu',
            'color' => 'orange',
            'is_featured' => 0
        ]
    ];
}

// Icon mapping for different service types
$iconMap = [
    'globe' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
    'smartphone' => '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line>',
    'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
    'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line>',
    'code' => '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>',
    'shopping-cart' => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>'
];

// Color mapping
$colorMap = [
    'blue' => 'text-blue-400',
    'purple' => 'text-purple-400',
    'green' => 'text-green-400',
    'orange' => 'text-orange-400',
    'cyan' => 'text-cyan-400',
    'indigo' => 'text-indigo-400',
    'red' => 'text-red-400',
    'pink' => 'text-pink-400'
];
?>

<!-- Services Bento -->
<section id="services" class="py-20 bg-background relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 fade-in-up">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Services That Drive <span class="text-secondary">Real Growth</span>
            </h2>
            <p class="text-gray-400 max-w-2xl mx-auto">
                From affordable web design for small businesses to enterprise-grade cloud solutions.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php 
            $featuredServices = array_filter($services, function($s) { return !empty($s['is_featured']); });
            $regularServices = array_filter($services, function($s) { return empty($s['is_featured']); });
            $allServices = array_merge($featuredServices, $regularServices);
            
            foreach ($allServices as $index => $service): 
                $icon = $service['icon'] ?? 'code';
                $color = $service['color'] ?? 'blue';
                $iconSvg = $iconMap[$icon] ?? $iconMap['code'];
                $colorClass = $colorMap[$color] ?? 'text-blue-400';
                
                // Determine grid size - first service gets larger space
                $gridClass = ($index === 0 && !empty($service['is_featured'])) ? 'md:col-span-2' : 'md:col-span-1';
            ?>
            <!-- Service <?php echo $index + 1; ?> -->
            <div class="<?php echo $gridClass; ?> p-6 rounded-2xl bg-accent/30 border border-white/5 hover:border-secondary/50 transition-all duration-300 group fade-in-up hover:-translate-y-1 hover:shadow-lg" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                <div class="mb-4 p-3 bg-background/50 rounded-xl w-fit group-hover:bg-secondary/20 transition-colors">
                    <svg class="h-8 w-8 <?php echo $colorClass; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?php echo $iconSvg; ?>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($service['title']); ?></h3>
                <p class="text-gray-400 text-sm leading-relaxed"><?php echo htmlspecialchars($service['description']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>