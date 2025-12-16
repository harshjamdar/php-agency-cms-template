<?php
require_once 'config.php';
require_once '../includes/helpers/advanced-tracking.php';
checkLogin();

// Fetch Stats
try {
    // Real-time active sessions
    $active_sessions = getActiveSessions();
    
    // Total Views - Get from page_views table
    try {
        $stmt = $pdo->query("SELECT SUM(view_count) as total FROM page_views");
        $result = $stmt->fetch();
        $total_views = $result['total'] ? number_format($result['total']) : '0';
    } catch (PDOException $e) {
        // If table doesn't exist yet, show 0
        $total_views = '0';
    }
    
    // Total unique sessions (last 7 days)
    try {
        $total_sessions = getTotalSessions(7);
    } catch (PDOException $e) {
        $total_sessions = 0;
    }

    // Active Projects
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $active_projects = $stmt->fetchColumn();

    // Blog Posts
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
    $blog_posts = $stmt->fetchColumn();

    // Pending Inquiries
    $stmt = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'");
    $pending_inquiries = $stmt->fetchColumn();

    // Recent Inquiries
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
    $recent_inquiries = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Header -->
<div class="flex justify-between items-end mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Dashboard Overview</h1>
        <p class="text-slate-400">Welcome back, Admin. Here's what's happening today.</p>
    </div>
    <div class="hidden md:block">
        <span class="text-sm text-slate-500">Last login: <?php echo date('M d, Y H:i'); ?></span>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Active Sessions -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-green-500/10 rounded-lg text-green-400">
                <i data-lucide="activity" class="w-6 h-6"></i>
            </div>
            <?php if ($active_sessions > 0): ?>
            <span class="flex items-center gap-1 px-2 py-1 bg-green-500/10 border border-green-500/20 rounded-full text-xs text-green-400">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                Live
            </span>
            <?php endif; ?>
        </div>
        <h3 class="text-slate-400 text-sm font-medium mb-1">Active Now</h3>
        <p class="text-2xl font-bold text-white"><?php echo number_format($active_sessions); ?></p>
        <a href="analytics-advanced.php" class="text-xs text-slate-500 hover:text-primary transition-colors mt-2 inline-block">View Sessions →</a>
    </div>
    
    <!-- Total Sessions -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>
        <h3 class="text-slate-400 text-sm font-medium mb-1">Sessions (7 days)</h3>
        <p class="text-2xl font-bold text-white"><?php echo number_format($total_sessions); ?></p>
        <a href="analytics-advanced.php" class="text-xs text-slate-500 hover:text-primary transition-colors mt-2 inline-block">View Details →</a>
    </div>
    
    <!-- Total Views -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-purple-500/10 rounded-lg text-purple-400">
                <i data-lucide="eye" class="w-6 h-6"></i>
            </div>
        </div>
        <h3 class="text-slate-400 text-sm font-medium mb-1">Total Page Views</h3>
        <p class="text-2xl font-bold text-white"><?php echo $total_views; ?></p>
        <a href="analytics-advanced.php" class="text-xs text-slate-500 hover:text-primary transition-colors mt-2 inline-block">View Analytics →</a>
    </div>

    <!-- Pending Inquiries -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-orange-500/10 rounded-lg text-orange-400">
                <i data-lucide="mail" class="w-6 h-6"></i>
            </div>
        </div>
        <h3 class="text-slate-400 text-sm font-medium mb-1">Pending Inquiries</h3>
        <p class="text-2xl font-bold text-white"><?php echo $pending_inquiries; ?></p>
        <a href="inquiries.php" class="text-xs text-slate-500 hover:text-primary transition-colors mt-2 inline-block">View All →</a>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-gradient-to-r from-primary/10 to-secondary/10 border border-white/10 rounded-xl p-6 mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-bold text-white mb-1">Quick Actions</h2>
            <p class="text-slate-400 text-sm">Manage your content efficiently</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="project-edit.php" class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add Project
            </a>
            <a href="blog-edit.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Blog Post
            </a>
            <a href="analytics-advanced.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                Analytics
            </a>
        </div>
    </div>
</div>

<!-- Content removed - Stat Cards 3 & 4 were here but removed in consolidation -->
<div style="display:none;">
    <!-- This section intentionally hidden to maintain proper HTML structure -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-orange-500/10 rounded-lg text-orange-400">
                <i data-lucide="message-square" class="w-6 h-6"></i>
            </div>
            <?php if($pending_inquiries > 0): ?>
            <span class="text-xs font-medium text-red-400 bg-red-500/10 px-2 py-1 rounded">New</span>
            <?php endif; ?>
        </div>
        <h3 class="text-slate-400 text-sm font-medium mb-1">Pending Inquiries</h3>
        <p class="text-2xl font-bold text-white"><?php echo $pending_inquiries; ?></p>
    </div>
</div>

<!-- Recent Activity / Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Recent Inquiries -->
    <div class="lg:col-span-2 bg-slate-900/50 border border-white/10 rounded-xl overflow-hidden">
        <div class="p-6 border-b border-white/10 flex justify-between items-center">
            <h3 class="font-bold text-white">Recent Inquiries</h3>
            <a href="inquiries.php" class="text-sm text-primary hover:text-primary/80">View All</a>
        </div>
        <div class="divide-y divide-white/5">
            <?php if (count($recent_inquiries) > 0): ?>
                <?php foreach ($recent_inquiries as $inquiry): ?>
                <a href="inquiry-view.php?id=<?php echo $inquiry['id']; ?>" class="block p-4 hover:bg-white/5 transition-colors flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 font-bold">
                        <?php echo strtoupper(substr($inquiry['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-white font-medium truncate"><?php echo htmlspecialchars($inquiry['name']); ?></h4>
                        <p class="text-sm text-slate-400 truncate"><?php echo htmlspecialchars($inquiry['subject'] ?? 'No Subject'); ?></p>
                    </div>
                    <span class="text-xs text-slate-500 whitespace-nowrap"><?php echo date('M d', strtotime($inquiry['created_at'])); ?></span>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 text-center text-slate-500">No recent inquiries found.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <h3 class="font-bold text-white mb-6">Quick Actions</h3>
        <div class="space-y-3">
            <a href="project-edit.php" class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add New Project
            </a>
            <a href="blog-edit.php" class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                <i data-lucide="pen-tool" class="w-4 h-4"></i>
                Write Blog Post
            </a>
            <a href="team-edit.php" class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Add Team Member
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-white/10">
            <h4 class="text-sm font-medium text-slate-400 mb-4">System Status</h4>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-300">Server Load</span>
                        <span class="text-green-400">Normal</span>
                    </div>
                    <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 w-[35%]"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-300">Storage</span>
                        <span class="text-yellow-400">65%</span>
                    </div>
                    <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-yellow-500 w-[65%]"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
