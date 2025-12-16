<?php
require_once 'config.php';
checkLogin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        try {
            // Get form data
            $google_analytics_id = sanitizeInput($_POST['google_analytics_id'] ?? '', 50);
            $google_tag_manager_id = sanitizeInput($_POST['google_tag_manager_id'] ?? '', 50);
            $facebook_pixel_id = sanitizeInput($_POST['facebook_pixel_id'] ?? '', 50);
            $recaptcha_site_key = sanitizeInput($_POST['recaptcha_site_key'] ?? '', 100);
            $recaptcha_secret_key = sanitizeInput($_POST['recaptcha_secret_key'] ?? '', 100);
            
            // Email/SMTP Settings
            $smtp_host = sanitizeInput($_POST['smtp_host'] ?? '', 100);
            $smtp_port = sanitizeInput($_POST['smtp_port'] ?? '587', 10);
            $smtp_username = sanitizeInput($_POST['smtp_username'] ?? '', 100);
            $smtp_password = $_POST['smtp_password'] ?? ''; // Don't sanitize passwords
            $smtp_from_email = sanitizeInput($_POST['smtp_from_email'] ?? '', 100);
            $smtp_from_name = sanitizeInput($_POST['smtp_from_name'] ?? '', 100);
            $smtp_encryption = sanitizeInput($_POST['smtp_encryption'] ?? 'tls', 10);
            
            // Save or update settings
            $settings = [
                'google_analytics_id' => $google_analytics_id,
                'google_tag_manager_id' => $google_tag_manager_id,
                'facebook_pixel_id' => $facebook_pixel_id,
                'recaptcha_site_key' => $recaptcha_site_key,
                'recaptcha_secret_key' => $recaptcha_secret_key,
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_from_email' => $smtp_from_email,
                'smtp_from_name' => $smtp_from_name,
                'smtp_encryption' => $smtp_encryption
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("SELECT id FROM api_settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                
                if ($stmt->fetch()) {
                    // Update existing
                    $stmt = $pdo->prepare("UPDATE api_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare("INSERT INTO api_settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $value]);
                }
            }
            
            $success = "API settings saved successfully!";
        } catch (PDOException $e) {
            logError("API settings error: " . $e->getMessage());
            $error = "Failed to save settings. Please try again.";
        }
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM api_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist yet
    $settings = [];
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">API & Integrations</h1>
        <p class="text-slate-400">Configure third-party services and tracking codes</p>
    </div>
</div>

<?php if ($error): ?>
<div class="bg-red-500/10 text-red-400 p-4 rounded-lg mb-6 border border-red-500/20">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-500/10 text-green-400 p-4 rounded-lg mb-6 border border-green-500/20">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <?php echo csrfField(); ?>
    
    <!-- Google Analytics Section -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-blue-500/10 rounded-lg">
                <svg class="w-6 h-6 text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.84 2.998v17.999l-3.998 3.002H2.848L0 20.997V2.998L2.848 0h16.994l3.001 2.998h-.003zM12 4.498c-4.142 0-7.5 3.358-7.5 7.5s3.358 7.5 7.5 7.5 7.5-3.358 7.5-7.5-3.358-7.5-7.5-7.5zm0 12.75c-2.9 0-5.25-2.35-5.25-5.25S9.1 6.748 12 6.748s5.25 2.35 5.25 5.25-2.35 5.25-5.25 5.25z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Google Analytics</h3>
                <p class="text-slate-400 text-sm">Track website traffic and user behavior</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Google Analytics Measurement ID
                    <span class="text-slate-500 font-normal">(GA4)</span>
                </label>
                <input 
                    type="text" 
                    name="google_analytics_id"
                    value="<?php echo htmlspecialchars($settings['google_analytics_id'] ?? ''); ?>"
                    placeholder="G-XXXXXXXXXX"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                <p class="text-slate-500 text-xs mt-1">Example: G-XXXXXXXXXX (Find this in your Google Analytics property)</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Google Tag Manager ID
                    <span class="text-slate-500 font-normal">(Optional)</span>
                </label>
                <input 
                    type="text" 
                    name="google_tag_manager_id"
                    value="<?php echo htmlspecialchars($settings['google_tag_manager_id'] ?? ''); ?>"
                    placeholder="GTM-XXXXXXX"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                <p class="text-slate-500 text-xs mt-1">Example: GTM-XXXXXXX</p>
            </div>
        </div>
    </div>

    <!-- Facebook Pixel Section -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-indigo-500/10 rounded-lg">
                <svg class="w-6 h-6 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Facebook Pixel</h3>
                <p class="text-slate-400 text-sm">Track conversions and create audiences</p>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">
                Facebook Pixel ID
            </label>
            <input 
                type="text" 
                name="facebook_pixel_id"
                value="<?php echo htmlspecialchars($settings['facebook_pixel_id'] ?? ''); ?>"
                placeholder="XXXXXXXXXXXXXXX"
                class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            <p class="text-slate-500 text-xs mt-1">Find this in Facebook Events Manager</p>
        </div>
    </div>

    <!-- Google reCAPTCHA Section -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-green-500/10 rounded-lg">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Google reCAPTCHA</h3>
                <p class="text-slate-400 text-sm">Protect forms from spam and abuse</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Site Key (Public)
                </label>
                <input 
                    type="text" 
                    name="recaptcha_site_key"
                    value="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>"
                    placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Secret Key (Private)
                </label>
                <input 
                    type="password" 
                    name="recaptcha_secret_key"
                    value="<?php echo htmlspecialchars($settings['recaptcha_secret_key'] ?? ''); ?>"
                    placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                <p class="text-slate-500 text-xs mt-1">⚠️ Keep this secret - never share publicly</p>
            </div>
        </div>
    </div>

    <!-- Email/SMTP Settings Section -->
    <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-purple-500/10 rounded-lg">
                <i data-lucide="mail" class="w-6 h-6 text-purple-400"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Email/SMTP Settings (Hostinger)</h3>
                <p class="text-sm text-slate-400">Configure email sending via SMTP</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    SMTP Host
                </label>
                <input 
                    type="text" 
                    name="smtp_host"
                    value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                    placeholder="smtp.hostinger.com"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                <p class="text-slate-500 text-xs mt-1">Hostinger: smtp.hostinger.com</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    SMTP Port
                </label>
                <input 
                    type="text" 
                    name="smtp_port"
                    value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>"
                    placeholder="587"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                <p class="text-slate-500 text-xs mt-1">Typical: 587 (TLS) or 465 (SSL)</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    SMTP Username/Email
                </label>
                <input 
                    type="text" 
                    name="smtp_username"
                    value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"
                    placeholder="noreply@yourdomain.com"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    SMTP Password
                </label>
                <input 
                    type="password" 
                    name="smtp_password"
                    value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>"
                    placeholder="••••••••••••"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                <p class="text-slate-500 text-xs mt-1">⚠️ Keep this secure</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    From Email Address
                </label>
                <input 
                    type="email" 
                    name="smtp_from_email"
                    value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>"
                    placeholder="noreply@yourdomain.com"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    From Name
                </label>
                <input 
                    type="text" 
                    name="smtp_from_name"
                    value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>"
                    placeholder="CodeFiesta"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Encryption Method
                </label>
                <select 
                    name="smtp_encryption"
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                    <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="" <?php echo ($settings['smtp_encryption'] ?? '') === '' ? 'selected' : ''; ?>>None</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-6">
        <h4 class="text-blue-400 font-semibold mb-3 flex items-center gap-2">
            <i data-lucide="info" class="w-5 h-5"></i>
            Setup Instructions
        </h4>
        <ul class="space-y-2 text-sm text-slate-300">
            <li class="flex gap-2">
                <span class="text-blue-400">1.</span>
                <span><strong>Google Analytics:</strong> Visit <a href="https://analytics.google.com" target="_blank" class="text-blue-400 hover:underline">analytics.google.com</a>, create a property, and copy the Measurement ID</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-400">2.</span>
                <span><strong>Facebook Pixel:</strong> Go to <a href="https://business.facebook.com/events_manager" target="_blank" class="text-blue-400 hover:underline">Events Manager</a>, create a pixel, and copy the Pixel ID</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-400">3.</span>
                <span><strong>reCAPTCHA:</strong> Visit <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-blue-400 hover:underline">reCAPTCHA Admin</a>, register your site, and get both keys</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-400">4.</span>
                <span><strong>Hostinger Email:</strong> Login to Hostinger panel, go to Emails, and use the SMTP credentials provided there</span>
            </li>
        </ul>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end gap-4">
        <button 
            type="submit"
            class="px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-lg font-semibold hover:opacity-90 transition-opacity shadow-lg">
            <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
            Save Settings
        </button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
