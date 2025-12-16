<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $active_theme = sanitizeInput($_POST['active_theme'] ?? 'default', 20);
        
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = 'active_theme'");
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'active_theme'");
            $stmt->execute([$active_theme]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('active_theme', ?)");
            $stmt->execute([$active_theme]);
        }
        
        $success = "Theme settings saved successfully!";
    } catch (PDOException $e) {
        logError("Theme settings error: " . $e->getMessage());
        $error = "Failed to save theme settings.";
    }
}

// Fetch current theme
$active_theme = 'default';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'active_theme'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $active_theme = $result['setting_value'];
    }
} catch (PDOException $e) {
    // Default to default theme
}

include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-white mb-2">Theme Settings</h1>
    <p class="text-slate-400">Choose the visual style for your landing page</p>
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

<form method="POST" class="space-y-6">
    <?php echo csrfField(); ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Default Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="default" class="peer sr-only" <?php echo $active_theme === 'default' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-primary peer-checked:bg-primary/5 hover:border-white/20 h-full">
                <div class="aspect-video bg-slate-900 rounded-lg mb-4 overflow-hidden relative border border-white/5">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col">
                        <div class="h-2 bg-slate-800 border-b border-white/5"></div>
                        <div class="flex-1 p-4 flex flex-col gap-2">
                            <div class="h-8 w-3/4 bg-slate-800 rounded"></div>
                            <div class="h-4 w-1/2 bg-slate-800/50 rounded"></div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="h-20 bg-slate-800/30 rounded border border-white/5"></div>
                                <div class="h-20 bg-slate-800/30 rounded border border-white/5"></div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded border border-primary/20">Modern Dark</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Default Theme</h3>
                        <p class="text-sm text-slate-400">Clean, modern dark mode interface with glassmorphism effects.</p>
                    </div>
                    <div class="w-6 h-6 rounded-full border-2 border-white/20 peer-checked:border-primary peer-checked:bg-primary flex items-center justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Retro Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="retro" class="peer sr-only" <?php echo $active_theme === 'retro' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-[#ff00ff] peer-checked:bg-[#ff00ff]/5 hover:border-white/20 h-full">
                <div class="aspect-video bg-black rounded-lg mb-4 overflow-hidden relative border border-[#00ff00]">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col font-mono">
                        <div class="h-2 bg-[#00ff00] border-b border-black"></div>
                        <div class="flex-1 p-4 flex flex-col gap-2">
                            <div class="h-8 w-3/4 border-2 border-[#00ff00] text-[#00ff00] flex items-center px-2 text-xs">RETRO.EXE</div>
                            <div class="h-4 w-1/2 bg-[#ff00ff] rounded-none"></div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="h-20 border-2 border-[#00ffff] bg-transparent"></div>
                                <div class="h-20 border-2 border-[#ffff00] bg-transparent"></div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] bg-[length:100%_2px,3px_100%] pointer-events-none"></div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-[#ff00ff] text-white text-xs border-2 border-white font-mono">RETRO STYLE</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white font-mono">Retro Theme</h3>
                        <p class="text-sm text-slate-400">Pixel art style with neon colors, CRT effects, and terminal vibes.</p>
                    </div>
                    <div class="w-6 h-6 border-2 border-white/20 peer-checked:border-[#ff00ff] peer-checked:bg-[#ff00ff] flex items-center justify-center">
                        <div class="w-2.5 h-2.5 bg-white opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Minimalist Light Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="light" class="peer sr-only" <?php echo $active_theme === 'light' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-500/5 hover:border-white/20 h-full">
                <div class="aspect-video bg-white rounded-lg mb-4 overflow-hidden relative border border-slate-200">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col">
                        <div class="h-2 bg-white border-b border-slate-100"></div>
                        <div class="flex-1 p-4 flex flex-col gap-2">
                            <div class="h-8 w-3/4 bg-slate-100 rounded"></div>
                            <div class="h-4 w-1/2 bg-slate-50 rounded"></div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="h-20 bg-white rounded border border-slate-100 shadow-sm"></div>
                                <div class="h-20 bg-white rounded border border-slate-100 shadow-sm"></div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs rounded font-medium">Minimalist Light</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Minimalist Light</h3>
                        <p class="text-sm text-slate-400">Clean, professional SaaS style with white background and soft shadows.</p>
                    </div>
                    <div class="w-6 h-6 rounded-full border-2 border-white/20 peer-checked:border-indigo-500 peer-checked:bg-indigo-500 flex items-center justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Cyberpunk Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="cyberpunk" class="peer sr-only" <?php echo $active_theme === 'cyberpunk' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-[#00f3ff] peer-checked:bg-[#00f3ff]/5 hover:border-white/20 h-full">
                <div class="aspect-video bg-[#050505] rounded-lg mb-4 overflow-hidden relative border border-[#00f3ff]">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col">
                        <div class="h-2 bg-[#0a0a0a] border-b border-[#00f3ff]"></div>
                        <div class="flex-1 p-4 flex flex-col gap-2">
                            <div class="h-8 w-3/4 bg-transparent border border-[#00f3ff] rounded-none"></div>
                            <div class="h-4 w-1/2 bg-[#ff003c] rounded-none"></div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="h-20 bg-[#0a0a0a] border border-[#00f3ff] shadow-[0_0_10px_rgba(0,243,255,0.2)]"></div>
                                <div class="h-20 bg-[#0a0a0a] border border-[#00f3ff] shadow-[0_0_10px_rgba(0,243,255,0.2)]"></div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-[#00f3ff]/20 text-[#00f3ff] text-xs border border-[#00f3ff]">CYBERPUNK</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Cyberpunk</h3>
                        <p class="text-sm text-slate-400">High-tech, futuristic look with neon colors and glowing effects.</p>
                    </div>
                    <div class="w-6 h-6 rounded-none border border-white/20 peer-checked:border-[#00f3ff] peer-checked:bg-[#00f3ff] flex items-center justify-center">
                        <div class="w-2.5 h-2.5 bg-black opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Neo-Brutalism Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="brutalism" class="peer sr-only" <?php echo $active_theme === 'brutalism' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-[#a3e635] peer-checked:bg-[#a3e635]/5 hover:border-white/20 h-full">
                <div class="aspect-video bg-[#fffdf5] rounded-lg mb-4 overflow-hidden relative border-4 border-black">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col p-4">
                        <div class="h-8 w-3/4 bg-[#a3e635] border-2 border-black shadow-[4px_4px_0px_#000]"></div>
                        <div class="h-4 w-1/2 bg-[#f472b6] border-2 border-black mt-2"></div>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="h-20 bg-white border-2 border-black shadow-[4px_4px_0px_#000]"></div>
                            <div class="h-20 bg-white border-2 border-black shadow-[4px_4px_0px_#000]"></div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-[#a3e635] text-black text-xs border-2 border-black font-bold">NEO-BRUTALISM</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Neo-Brutalism</h3>
                        <p class="text-sm text-slate-400">Bold, trendy, high-contrast design with hard shadows and thick borders.</p>
                    </div>
                    <div class="w-6 h-6 border-2 border-white/20 peer-checked:border-[#a3e635] peer-checked:bg-[#a3e635] flex items-center justify-center">
                        <div class="w-2.5 h-2.5 bg-black opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Corporate Blue Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="corporate" class="peer sr-only" <?php echo $active_theme === 'corporate' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-[#0f3460] peer-checked:bg-[#0f3460]/20 hover:border-white/20 h-full">
                <div class="aspect-video bg-[#f0f2f5] rounded-lg mb-4 overflow-hidden relative border border-slate-300">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col">
                        <div class="h-8 bg-[#0f3460] w-full"></div>
                        <div class="flex-1 p-4 flex flex-col gap-2">
                            <div class="h-6 w-3/4 bg-[#0f3460] rounded-sm opacity-80"></div>
                            <div class="h-4 w-1/2 bg-slate-300 rounded-sm"></div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="h-20 bg-white rounded border border-slate-200"></div>
                                <div class="h-20 bg-white rounded border border-slate-200"></div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-[#0f3460] text-white text-xs rounded-sm">Corporate Blue</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Corporate Blue</h3>
                        <p class="text-sm text-slate-400">Stable, secure, enterprise look with navy blue and clean typography.</p>
                    </div>
                    <div class="w-6 h-6 rounded border-2 border-white/20 peer-checked:border-[#0f3460] peer-checked:bg-[#0f3460] flex items-center justify-center">
                        <div class="w-2.5 h-2.5 bg-white opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Glassmorphism Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="glass" class="peer sr-only" <?php echo $active_theme === 'glass' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-purple-400 peer-checked:bg-purple-400/10 hover:border-white/20 h-full">
                <div class="aspect-video bg-[#0f172a] rounded-lg mb-4 overflow-hidden relative border border-white/10">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 to-pink-500/20"></div>
                    <div class="absolute inset-0 flex flex-col p-4 z-10">
                        <div class="h-8 w-3/4 bg-white/10 backdrop-blur-md border border-white/10 rounded-xl mb-2"></div>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div class="h-24 bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-lg"></div>
                            <div class="h-24 bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-lg"></div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3 z-20">
                        <span class="px-2 py-1 bg-white/10 backdrop-blur text-white text-xs rounded-lg border border-white/10">Glassmorphism</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Glassmorphism</h3>
                        <p class="text-sm text-slate-400">Trendy frosted glass effect with soft gradients and blurs.</p>
                    </div>
                    <div class="w-6 h-6 rounded-xl border-2 border-white/20 peer-checked:border-purple-400 peer-checked:bg-purple-400 flex items-center justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>

        <!-- Monochrome Theme Card -->
        <label class="relative group cursor-pointer">
            <input type="radio" name="active_theme" value="monochrome" class="peer sr-only" <?php echo $active_theme === 'monochrome' ? 'checked' : ''; ?>>
            <div class="bg-slate-800/50 border-2 border-white/10 rounded-xl p-6 transition-all peer-checked:border-white peer-checked:bg-white/5 hover:border-white/20 h-full">
                <div class="aspect-video bg-black rounded-lg mb-4 overflow-hidden relative border border-white">
                    <!-- Preview Mockup -->
                    <div class="absolute inset-0 flex flex-col p-4">
                        <div class="h-8 w-3/4 bg-white mb-2"></div>
                        <div class="h-1 w-full bg-white/20 mb-4"></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="h-20 border border-white"></div>
                            <div class="h-20 border border-white"></div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3">
                        <span class="px-2 py-1 bg-white text-black text-xs font-serif">MONOCHROME</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Monochrome</h3>
                        <p class="text-sm text-slate-400">High contrast black and white design. Artistic and serious.</p>
                    </div>
                    <div class="w-6 h-6 border-2 border-white/20 peer-checked:border-white peer-checked:bg-white flex items-center justify-center">
                        <div class="w-2.5 h-2.5 bg-black opacity-0 peer-checked:opacity-100"></div>
                    </div>
                </div>
            </div>
        </label>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors flex items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i>
            Save Changes
        </button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
