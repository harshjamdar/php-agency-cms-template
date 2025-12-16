<?php
// Fetch Blog Posts from Database
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    // Only fetch published posts, prioritize featured ones, limit to 3 for the homepage
    $blog_posts = safeQuery("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY is_featured DESC, created_at DESC LIMIT 3");
    
    // Fallback if is_featured column doesn't exist or query failed
    if (empty($blog_posts)) {
        $blog_posts = safeQuery("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    }
} catch (PDOException $e) {
    // Fallback if table doesn't exist or other error
    $blog_posts = [];
}
?>

<!-- Blog Section -->
<section id="blog" class="py-20 bg-slate-950 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16 fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary mb-4 border border-primary/20">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <span class="text-sm font-semibold">Latest Insights</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Thoughts & <span class="text-secondary">Trends</span>
            </h2>
            <p class="text-gray-400 max-w-2xl mx-auto">
                Stay updated with the latest trends in software development, digital marketing, and startup growth.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (count($blog_posts) > 0): ?>
                <?php foreach ($blog_posts as $index => $post): ?>
                <!-- Blog Post <?php echo $index + 1; ?> -->
                <article class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden hover:border-primary/30 transition-all group fade-in-up hover:-translate-y-2 hover:shadow-xl flex flex-col h-full" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <div class="relative h-48 overflow-hidden">
                        <?php if (!empty($post['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full bg-slate-800 flex items-center justify-center text-slate-600">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        <?php endif; ?>
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 bg-slate-950/80 backdrop-blur-sm text-white text-xs font-medium rounded-full border border-white/10">
                                <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 text-sm text-primary mb-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span><?php echo htmlspecialchars($post['author']); ?></span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-primary transition-colors line-clamp-2">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h3>
                        <p class="text-gray-400 text-sm mb-4 leading-relaxed line-clamp-3 flex-1">
                            <?php echo htmlspecialchars($post['excerpt']); ?>
                        </p>
                        <a href="blog-post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="inline-flex items-center text-primary hover:text-primary/80 font-medium transition-colors mt-auto">
                            Read Article 
                            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12 bg-slate-900/50 rounded-2xl border border-slate-800 border-dashed">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800 mb-4 text-slate-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-1">No articles yet</h3>
                    <p class="text-slate-400">Check back soon for our latest updates and insights.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($blog_posts) > 0): ?>
        <div class="text-center mt-12">
            <a href="blog.php" class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-slate-800 border border-slate-700 rounded-full hover:bg-slate-700 transition-all hover:scale-105">
                View All Articles
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>