<?php
// Fetch FAQs from Database
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    $faqs = safeQuery("SELECT * FROM faq WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
} catch (PDOException $e) {
    // Fallback to default FAQs if table doesn't exist
    $faqs = [
        [
            'question' => 'How long does it take to build a website or app?',
            'answer' => 'Timelines vary by complexity. A standard business website typically takes 2-4 weeks, while a custom mobile app MVP can take 6-12 weeks. We provide a detailed timeline after our initial consultation.'
        ],
        [
            'question' => 'Do you provide post-launch support?',
            'answer' => 'Absolutely. We offer 30 days of free support after launch. Beyond that, we have flexible maintenance packages to ensure your software remains secure, updated, and performing optimally.'
        ],
        [
            'question' => 'What technology stack do you use?',
            'answer' => 'We specialize in modern, scalable stacks. For web, we use React, Next.js, and Node.js. For mobile, we use Flutter and React Native. Our cloud infrastructure is built on AWS and Google Cloud.'
        ]
    ];
}
?>

<!-- FAQ Section -->
<section class="py-20 bg-background relative z-10 border-t border-white/5">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent text-gray-300 mb-4 border border-white/10">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <span class="text-sm font-medium">Common Questions</span>
            </div>
            <h2 class="text-3xl font-bold text-white mb-4">Frequently Asked Questions</h2>
        </div>

        <div class="space-y-4 fade-in-up">
            <?php foreach ($faqs as $index => $faq): ?>
            <!-- FAQ <?php echo $index + 1; ?> -->
            <div class="border border-white/10 rounded-xl bg-slate-900/50 overflow-hidden hover:border-secondary/30 transition-colors">
                <button class="faq-btn w-full flex items-center justify-between p-6 text-left focus:outline-none">
                    <span class="text-lg font-medium text-white"><?php echo htmlspecialchars($faq['question']); ?></span>
                    <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="hidden px-6 pb-6 text-gray-400 leading-relaxed border-t border-white/5 pt-4">
                    <?php echo htmlspecialchars($faq['answer']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>