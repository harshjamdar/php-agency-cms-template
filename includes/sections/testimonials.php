<?php
// Fetch Testimonials
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    // Check if table exists first to avoid error logs on fresh install
    $tableExists = false;
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'testimonials'");
        $tableExists = $result->rowCount() > 0;
    } catch (Exception $e) {
        $tableExists = false;
    }

    if ($tableExists) {
        $testimonials = safeQuery("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC, id DESC");
    } else {
        throw new Exception("Table not found");
    }
} catch (Exception $e) {
    // Fallback data
    $testimonials = [
        [
            'client_name' => 'Sarah Johnson',
            'client_position' => 'Founder',
            'client_company' => 'EcoStyle',
            'content' => 'CodeFiesta delivered an exceptional e-commerce platform that perfectly captures our brand. Their attention to detail and technical expertise is unmatched.',
            'rating' => 5,
            'client_image' => ''
        ],
        [
            'client_name' => 'Michael Chen',
            'client_position' => 'CTO',
            'client_company' => 'DataFlow Systems',
            'content' => 'The team at CodeFiesta helped us modernize our legacy infrastructure. The transition was smooth, and the performance improvements are incredible.',
            'rating' => 5,
            'client_image' => ''
        ],
        [
            'client_name' => 'Emily Rodriguez',
            'client_position' => 'Marketing Director',
            'client_company' => 'GrowthWave',
            'content' => 'Our SEO rankings skyrocketed after CodeFiesta revamped our website. They truly understand digital marketing and technical SEO.',
            'rating' => 5,
            'client_image' => ''
        ]
    ];
}
?>

<section id="testimonials" class="py-20 bg-slate-900 relative overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-6 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                What Our Clients <span class="text-primary">Say</span>
            </h2>
            <p class="text-slate-400 text-lg">
                Don't just take our word for it. Here's what our partners have to say about working with us.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-8 hover:border-primary/30 transition-colors relative group">
                    <!-- Quote Icon -->
                    <div class="absolute top-6 right-6 text-slate-700 group-hover:text-primary/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                            <path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H15.017C14.4647 8 14.017 7.55228 14.017 7V3H19.017C20.6739 3 22.017 4.34315 22.017 6V15C22.017 16.6569 20.6739 18 19.017 18H16.017C15.4647 18 15.017 18.4477 15.017 19V21H14.017ZM5.0166 21L5.0166 18C5.0166 16.8954 5.91203 16 7.0166 16H10.0166C10.5689 16 11.0166 15.5523 11.0166 15V9C11.0166 8.44772 10.5689 8 10.0166 8H6.0166C5.46432 8 5.0166 7.55228 5.0166 7V3H10.0166C11.6735 3 13.0166 4.34315 13.0166 6V15C13.0166 16.6569 11.6735 18 10.0166 18H7.0166C6.46432 18 6.0166 18.4477 6.0166 19V21H5.0166Z"></path>
                        </svg>
                    </div>

                    <!-- Rating -->
                    <div class="flex gap-1 mb-6">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $i <= $testimonial['rating'] ? '#FACC15' : 'none'; ?>" stroke="<?php echo $i <= $testimonial['rating'] ? '#FACC15' : '#475569'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        <?php endfor; ?>
                    </div>

                    <!-- Content -->
                    <p class="text-slate-300 mb-8 leading-relaxed relative z-10">
                        "<?php echo htmlspecialchars($testimonial['content']); ?>"
                    </p>

                    <!-- Author -->
                    <div class="flex items-center gap-4 mt-auto">
                        <?php if (!empty($testimonial['client_image']) && file_exists(__DIR__ . '/../../' . $testimonial['client_image'])): ?>
                            <img src="<?php echo htmlspecialchars($testimonial['client_image']); ?>" alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-primary/20">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-slate-700 rounded-full flex items-center justify-center border-2 border-white/10 text-slate-400 font-bold text-lg">
                                <?php echo substr($testimonial['client_name'], 0, 1); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <h4 class="text-white font-semibold"><?php echo htmlspecialchars($testimonial['client_name']); ?></h4>
                            <p class="text-sm text-slate-500">
                                <?php echo htmlspecialchars($testimonial['client_position']); ?>
                                <?php if (!empty($testimonial['client_company'])): ?>
                                    <span class="text-primary/60">@ <?php echo htmlspecialchars($testimonial['client_company']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
