<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $testId = $_POST['test_id'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $headlineA = sanitizeInput($_POST['headline_a']);
        $headlineB = sanitizeInput($_POST['headline_b']);

        // Update Test Status
        $stmt = $pdo->prepare("UPDATE ab_tests SET is_active = ? WHERE id = ?");
        $stmt->execute([$isActive, $testId]);

        // Update Variant A
        $stmt = $pdo->prepare("UPDATE ab_variants SET content = ? WHERE test_id = ? AND variant_name = 'A'");
        $stmt->execute([$headlineA, $testId]);

        // Update Variant B
        $stmt = $pdo->prepare("UPDATE ab_variants SET content = ? WHERE test_id = ? AND variant_name = 'B'");
        $stmt->execute([$headlineB, $testId]);

        $success = "A/B Test settings updated successfully!";
    } catch (PDOException $e) {
        logError("Error saving A/B test: " . $e->getMessage());
        $error = "Failed to save settings.";
    }
}

// Fetch Hero Experiment Data
try {
    $stmt = $pdo->prepare("SELECT * FROM ab_tests WHERE test_key = 'hero_headline'");
    $stmt->execute();
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($test) {
        $stmt = $pdo->prepare("SELECT * FROM ab_variants WHERE test_id = ? ORDER BY variant_name ASC");
        $stmt->execute([$test['id']]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Map variants for easier access
        $variantMap = [];
        foreach ($variants as $v) {
            $variantMap[$v['variant_name']] = $v;
        }
    }
} catch (PDOException $e) {
    logError("Error fetching A/B test: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-white mb-2">A/B Testing</h1>
    <p class="text-slate-400">Optimize your Hero section headline to maximize conversions.</p>
</div>

<?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($test && isset($variantMap['A']) && isset($variantMap['B'])): ?>
<form method="POST" class="space-y-8">
    <?php echo csrfField(); ?>
    <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">

    <!-- Status Card -->
    <div class="bg-slate-900 border border-white/10 rounded-xl p-6 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-white">Experiment Status</h3>
            <p class="text-sm text-slate-400">Enable to start serving random variants to visitors.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" <?php echo $test['is_active'] ? 'checked' : ''; ?>>
            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Variant A -->
        <div class="bg-slate-900 border border-white/10 rounded-xl overflow-hidden">
            <div class="p-4 bg-slate-950 border-b border-white/10 flex justify-between items-center">
                <span class="font-bold text-white">Variant A (Control)</span>
                <span class="text-xs bg-slate-800 text-slate-400 px-2 py-1 rounded">Original</span>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Headline Text</label>
                    <input type="text" name="headline_a" value="<?php echo htmlspecialchars($variantMap['A']['content']); ?>" 
                           class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary outline-none">
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-2 pt-4 border-t border-white/5">
                    <div class="text-center">
                        <div class="text-xs text-slate-500">Views</div>
                        <div class="text-lg font-bold text-white"><?php echo number_format($variantMap['A']['views']); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs text-slate-500">Clicks</div>
                        <div class="text-lg font-bold text-white"><?php echo number_format($variantMap['A']['conversions']); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs text-slate-500">CTR</div>
                        <div class="text-lg font-bold text-primary">
                            <?php 
                            $views = $variantMap['A']['views'];
                            $clicks = $variantMap['A']['conversions'];
                            echo $views > 0 ? round(($clicks / $views) * 100, 2) . '%' : '0%'; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variant B -->
        <div class="bg-slate-900 border border-white/10 rounded-xl overflow-hidden">
            <div class="p-4 bg-slate-950 border-b border-white/10 flex justify-between items-center">
                <span class="font-bold text-white">Variant B (Challenger)</span>
                <span class="text-xs bg-primary/20 text-primary px-2 py-1 rounded">Experimental</span>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Headline Text</label>
                    <input type="text" name="headline_b" value="<?php echo htmlspecialchars($variantMap['B']['content']); ?>" 
                           class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary outline-none">
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-2 pt-4 border-t border-white/5">
                    <div class="text-center">
                        <div class="text-xs text-slate-500">Views</div>
                        <div class="text-lg font-bold text-white"><?php echo number_format($variantMap['B']['views']); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs text-slate-500">Clicks</div>
                        <div class="text-lg font-bold text-white"><?php echo number_format($variantMap['B']['conversions']); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs text-slate-500">CTR</div>
                        <div class="text-lg font-bold text-primary">
                            <?php 
                            $views = $variantMap['B']['views'];
                            $clicks = $variantMap['B']['conversions'];
                            echo $views > 0 ? round(($clicks / $views) * 100, 2) . '%' : '0%'; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-bold transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i>
            Save & Update Experiment
        </button>
    </div>
</form>
<?php else: ?>
    <div class="p-8 text-center bg-slate-900 rounded-xl border border-white/10">
        <p class="text-slate-400">A/B Testing is not initialized. Please run the setup script.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
