<?php
// Fetch Projects from Database
try {
    // Assuming $pdo is available from index.php which includes this file
    // If not, we might need to require config.php, but index.php usually handles it.
    // Let's check if $pdo is set, if not, require config.
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    // Fetch featured projects first, then recent ones
    $projects = safeQuery("SELECT * FROM projects ORDER BY is_featured DESC, created_at DESC LIMIT 3");
    
    // Fallback if is_featured column doesn't exist or query failed
    if (empty($projects)) {
        $projects = safeQuery("SELECT * FROM projects ORDER BY created_at DESC LIMIT 3");
    }
} catch (PDOException $e) {
    // Fallback to empty array if DB fails
    $projects = [];
}
?>

<!-- Portfolio Section -->
<section id="portfolio" class="py-20 bg-background relative border-t border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6 fade-in-up">
            <div class="text-left">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Our Recent <span class="text-secondary">Success Stories</span>
                </h2>
                <p class="text-gray-400 max-w-xl">
                    We don't just write code; we build digital assets that drive revenue. Check out some of our featured projects.
                </p>
            </div>
            <a href="projects.php" class="hidden md:flex items-center gap-2 text-white hover:text-secondary transition-colors font-medium group">
                View All Projects 
                <svg class="h-4 w-4 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $index => $project): ?>
                <!-- Project <?php echo $index + 1; ?> -->
                <a href="project-view.php?slug=<?php echo htmlspecialchars($project['slug']); ?>" class="group rounded-2xl bg-slate-900/50 border border-white/10 overflow-hidden hover:border-secondary/50 transition-all hover:shadow-[0_0_20px_rgba(6,182,212,0.15)] fade-in-up block" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <div class="h-48 w-full relative overflow-hidden group-hover:scale-105 transition-transform duration-500 bg-slate-800">
                        <?php if (!empty($project['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-600">No Image</div>
                        <?php endif; ?>
                        
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                        <div class="absolute bottom-4 left-4 bg-black/60 backdrop-blur-md px-3 py-1 rounded-full border border-white/10 project-category-badge">
                            <span class="text-xs font-semibold text-white tracking-wide uppercase project-category-label"><?php echo htmlspecialchars($project['category']); ?></span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-secondary transition-colors"><?php echo htmlspecialchars($project['title']); ?></h3>
                        <div class="text-gray-400 text-sm leading-relaxed mb-6 line-clamp-3">
                            <?php echo strip_tags($project['description']); ?>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-6">
                            <?php 
                            if (!empty($project['tags'])) {
                                $tags = explode(',', $project['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (empty($tag)) continue;
                            ?>
                            <span class="text-xs font-medium text-slate-300 bg-slate-800 px-2 py-1 rounded border border-slate-700"><?php echo htmlspecialchars($tag); ?></span>
                            <?php 
                                endforeach; 
                            } else {
                            ?>
                            <span class="text-xs font-medium text-slate-300 bg-slate-800 px-2 py-1 rounded border border-slate-700">View Details</span>
                            <?php } ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-500 py-10">
                    <p>No projects to display yet. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>