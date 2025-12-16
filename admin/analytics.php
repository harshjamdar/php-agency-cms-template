<?php
require_once 'config.php';
checkLogin();

// Get analytics data
try {
    // Total views
    $stmt = $pdo->query("SELECT SUM(view_count) as total FROM page_views");
    $result = $stmt->fetch();
    $total_views = $result['total'] ?? 0;
    
    // Top pages
    $stmt = $pdo->query("SELECT * FROM page_views ORDER BY view_count DESC LIMIT 10");
    $top_pages = $stmt->fetchAll();
    
    // Recent activity
    $stmt = $pdo->query("SELECT * FROM page_views ORDER BY last_viewed DESC LIMIT 10");
    $recent_pages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError("Analytics error: " . $e->getMessage());
    $total_views = 0;
    $top_pages = [];
    $recent_pages = [];
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Analytics</h1>
        <p class="text-slate-400">Track page views and visitor statistics</p>
    </div>
</div>

<!-- Total Views Card -->
<div class="bg-gradient-to-r from-primary/20 to-secondary/20 border border-white/10 rounded-xl p-8 mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-slate-300 text-sm font-medium mb-2">Total Page Views</h2>
            <p class="text-5xl font-bold text-white"><?php echo number_format($total_views); ?></p>
        </div>
        <div class="p-4 bg-white/10 rounded-2xl">
            <i data-lucide="eye" class="w-12 h-12 text-white"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Top Pages -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl">
        <div class="p-6 border-b border-white/10">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i data-lucide="trending-up" class="w-5 h-5 text-primary"></i>
                Top Pages
            </h3>
        </div>
        <div class="p-6">
            <?php if (count($top_pages) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($top_pages as $page): ?>
                <div class="flex items-center justify-between p-4 bg-slate-950 rounded-lg border border-white/5 hover:border-primary/30 transition-colors">
                    <div class="flex-1">
                        <p class="text-white font-medium"><?php echo htmlspecialchars($page['page_name']); ?></p>
                        <p class="text-slate-500 text-xs mt-1">Last viewed: <?php echo date('M d, Y H:i', strtotime($page['last_viewed'])); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-primary"><?php echo number_format($page['view_count']); ?></p>
                        <p class="text-slate-500 text-xs">views</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12 text-slate-500">
                <i data-lucide="bar-chart-3" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                <p>No page views tracked yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl">
        <div class="p-6 border-b border-white/10">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i data-lucide="activity" class="w-5 h-5 text-secondary"></i>
                Recent Activity
            </h3>
        </div>
        <div class="p-6">
            <?php if (count($recent_pages) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recent_pages as $page): ?>
                <div class="flex items-center justify-between p-3 bg-slate-950 rounded-lg border border-white/5">
                    <div class="flex-1">
                        <p class="text-white text-sm"><?php echo htmlspecialchars($page['page_name']); ?></p>
                        <p class="text-slate-500 text-xs mt-1">
                            <?php 
                            $time_ago = time() - strtotime($page['last_viewed']);
                            if ($time_ago < 60) {
                                echo 'Just now';
                            } elseif ($time_ago < 3600) {
                                echo floor($time_ago / 60) . ' min ago';
                            } elseif ($time_ago < 86400) {
                                echo floor($time_ago / 3600) . ' hours ago';
                            } else {
                                echo floor($time_ago / 86400) . ' days ago';
                            }
                            ?>
                        </p>
                    </div>
                    <span class="text-slate-400 text-sm"><?php echo number_format($page['view_count']); ?> views</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12 text-slate-500">
                <i data-lucide="clock" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                <p>No recent activity</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Page Breakdown -->
<div class="mt-8 bg-slate-900/50 border border-white/10 rounded-xl p-6">
    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
        <i data-lucide="pie-chart" class="w-5 h-5 text-pink-400"></i>
        Page View Breakdown
    </h3>
    
    <?php if (count($top_pages) > 0): ?>
    <div class="space-y-3">
        <?php 
        $max_views = $top_pages[0]['view_count'];
        foreach ($top_pages as $page): 
            $percentage = $max_views > 0 ? ($page['view_count'] / $max_views) * 100 : 0;
        ?>
        <div>
            <div class="flex justify-between mb-2">
                <span class="text-slate-300 text-sm"><?php echo htmlspecialchars($page['page_name']); ?></span>
                <span class="text-slate-400 text-sm"><?php echo number_format($page['view_count']); ?> views</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-2">
                <div class="bg-gradient-to-r from-primary to-secondary h-2 rounded-full transition-all" style="width: <?php echo $percentage; ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-12 text-slate-500">
        <p>No data available</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
