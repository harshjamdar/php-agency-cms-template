<?php
require_once 'config.php';
require_once 'security.php';
require_once '../includes/helpers/advanced-tracking.php';
checkLogin();

// Get analytics data
try {
    // Real-time active sessions
    $active_sessions = getActiveSessions();
    
    // Total sessions (last 30 days)
    $total_sessions = getTotalSessions(30);
    
    // Total page views
    $stmt = $pdo->query("SELECT SUM(view_count) as total FROM page_views");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_views = $result['total'] ?? 0;
    
    // Average session duration
    $avg_duration = getAvgSessionDuration(30);
    $avg_duration_formatted = gmdate("i:s", $avg_duration);
    
    // Traffic by country
    $countries = getTrafficByCountry(10);
    
    // Traffic by device
    $devices = getTrafficByDevice();
    
    // Traffic by browser
    $browsers = getTrafficByBrowser();
    
    // Top referrers
    $referrers = getTopReferrers(10);
    
    // Top pages
    $stmt = $pdo->query("SELECT page_name, view_count FROM page_views ORDER BY view_count DESC LIMIT 10");
    $top_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent sessions
    $recent_sessions = getRecentSessions(15);
    
    // Daily views for last 7 days
    $stmt = $pdo->query("SELECT DATE(viewed_at) as date, COUNT(*) as views 
        FROM analytics_pageviews 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(viewed_at) 
        ORDER BY date ASC");
    $daily_views = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Analytics fetch error: " . $e->getMessage());
    $active_sessions = 0;
    $total_sessions = 0;
    $total_views = 0;
    $avg_duration_formatted = '0:00';
    $countries = [];
    $devices = [];
    $browsers = [];
    $referrers = [];
    $top_pages = [];
    $recent_sessions = [];
    $daily_views = [];
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}

function formatDuration($seconds) {
    if ($seconds < 60) return $seconds . 's';
    if ($seconds < 3600) return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Advanced Analytics</h1>
            <p class="text-slate-400">Complete visitor behavior tracking and insights (Last 30 days)</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-green-500/10 border border-green-500/20 rounded-lg">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-green-400 font-medium"><?php echo $active_sessions; ?> Active Now</span>
        </div>
    </div>
</div>

<!-- Key Metrics Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Sessions -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-primary"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($total_sessions); ?></div>
        <div class="text-slate-400 text-sm">Total Sessions</div>
    </div>
    
    <!-- Total Views -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="eye" class="w-6 h-6 text-blue-400"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($total_views); ?></div>
        <div class="text-slate-400 text-sm">Page Views</div>
    </div>
    
    <!-- Avg Session Duration -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-purple-400"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo $avg_duration_formatted; ?></div>
        <div class="text-slate-400 text-sm">Avg Session Duration</div>
    </div>
    
    <!-- Pages per Session -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="file-text" class="w-6 h-6 text-green-400"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo $total_sessions > 0 ? number_format($total_views / $total_sessions, 1) : '0'; ?></div>
        <div class="text-slate-400 text-sm">Pages / Session</div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Traffic by Location -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
                <i data-lucide="map-pin" class="w-5 h-5 text-primary"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Traffic by Location</h2>
                <p class="text-sm text-slate-400">Visitor geographic distribution</p>
            </div>
        </div>
        <div class="space-y-3">
            <?php if (empty($countries)): ?>
                <p class="text-slate-400 text-center py-8">No location data available</p>
            <?php else: ?>
                <?php foreach ($countries as $country): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl"><?php 
                                $flag_code = strtolower($country['country_code']);
                                echo "🌍";
                            ?></span>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($country['country']); ?></span>
                        </div>
                        <span class="text-primary font-bold"><?php echo number_format($country['sessions']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Traffic by Device -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="monitor" class="w-5 h-5 text-blue-400"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Devices & Browsers</h2>
                <p class="text-sm text-slate-400">Platform breakdown</p>
            </div>
        </div>
        
        <!-- Devices -->
        <h3 class="text-white font-semibold mb-3">Device Type</h3>
        <div class="space-y-3 mb-6">
            <?php if (empty($devices)): ?>
                <p class="text-slate-400 text-center py-4">No device data</p>
            <?php else: ?>
                <?php 
                $total_device_sessions = array_sum(array_column($devices, 'sessions'));
                foreach ($devices as $device): 
                    $percentage = $total_device_sessions > 0 ? ($device['sessions'] / $total_device_sessions) * 100 : 0;
                ?>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-300"><?php echo htmlspecialchars($device['device_type']); ?></span>
                            <span class="text-white font-semibold"><?php echo number_format($device['sessions']); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Browsers -->
        <h3 class="text-white font-semibold mb-3">Browser</h3>
        <div class="space-y-3">
            <?php if (empty($browsers)): ?>
                <p class="text-slate-400 text-center py-4">No browser data</p>
            <?php else: ?>
                <?php 
                $total_browser_sessions = array_sum(array_column($browsers, 'sessions'));
                foreach ($browsers as $browser): 
                    $percentage = $total_browser_sessions > 0 ? ($browser['sessions'] / $total_browser_sessions) * 100 : 0;
                ?>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-300"><?php echo htmlspecialchars($browser['browser']); ?></span>
                            <span class="text-white font-semibold"><?php echo number_format($browser['sessions']); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Pages -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="trending-up" class="w-5 h-5 text-green-400"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Top Pages</h2>
                <p class="text-sm text-slate-400">Most viewed pages</p>
            </div>
        </div>
        <div class="space-y-2">
            <?php if (empty($top_pages)): ?>
                <p class="text-slate-400 text-center py-8">No page data available</p>
            <?php else: ?>
                <?php foreach ($top_pages as $page): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg hover:bg-slate-900 transition-colors">
                        <span class="text-slate-300 font-medium"><?php echo htmlspecialchars($page['page_name']); ?></span>
                        <span class="text-primary font-bold"><?php echo number_format($page['view_count']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Referrers -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="link" class="w-5 h-5 text-orange-400"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Top Referrers</h2>
                <p class="text-sm text-slate-400">Traffic sources</p>
            </div>
        </div>
        <div class="space-y-2">
            <?php if (empty($referrers)): ?>
                <p class="text-slate-400 text-center py-8">No referrer data available</p>
            <?php else: ?>
                <?php foreach ($referrers as $referrer): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg hover:bg-slate-900 transition-colors">
                        <span class="text-slate-300 font-medium text-sm truncate max-w-xs" title="<?php echo htmlspecialchars($referrer['referrer']); ?>">
                            <?php 
                            $url = htmlspecialchars($referrer['referrer']);
                            echo strlen($url) > 40 ? substr($url, 0, 40) . '...' : $url;
                            ?>
                        </span>
                        <span class="text-primary font-bold"><?php echo number_format($referrer['sessions']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Sessions -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
            <i data-lucide="activity" class="w-5 h-5 text-primary"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">Recent Sessions</h2>
            <p class="text-sm text-slate-400">Latest visitor activity</p>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Location</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Device</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Browser</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Pages</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Duration</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_sessions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400">No session data available</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_sessions as $session): ?>
                        <tr class="border-b border-white/5 hover:bg-slate-900/50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="text-white font-medium"><?php echo htmlspecialchars($session['city']); ?></div>
                                <div class="text-xs text-slate-400"><?php echo htmlspecialchars($session['country']); ?></div>
                            </td>
                            <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($session['device_type']); ?></td>
                            <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($session['browser']); ?></td>
                            <td class="py-3 px-4 text-primary font-bold"><?php echo $session['page_count']; ?></td>
                            <td class="py-3 px-4 text-slate-300"><?php echo formatDuration($session['duration']); ?></td>
                            <td class="py-3 px-4 text-slate-400 text-sm"><?php echo timeAgo($session['started_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
