<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        // Get settings from POST
        $settings_to_save = [
            'site_name' => sanitizeInput($_POST['site_name'] ?? '', 100),
            'site_tagline' => sanitizeInput($_POST['site_tagline'] ?? '', 200),
            'primary_color' => sanitizeInput($_POST['primary_color'] ?? '#8b5cf6', 20),
            'secondary_color' => sanitizeInput($_POST['secondary_color'] ?? '#ec4899', 20),
            'contact_email' => sanitizeInput($_POST['contact_email'] ?? '', 100),
            'contact_phone' => sanitizeInput($_POST['contact_phone'] ?? '', 50),
            'footer_text' => sanitizeInput($_POST['footer_text'] ?? '', 200),
            'social_twitter' => sanitizeInput($_POST['social_twitter'] ?? '', 255),
            'social_facebook' => sanitizeInput($_POST['social_facebook'] ?? '', 255),
            'social_instagram' => sanitizeInput($_POST['social_instagram'] ?? '', 255),
            'social_linkedin' => sanitizeInput($_POST['social_linkedin'] ?? '', 255),
            'privacy_policy_content' => sanitizeHTML($_POST['privacy_policy_content'] ?? ''),
            'terms_of_service_content' => sanitizeHTML($_POST['terms_of_service_content'] ?? ''),
            'branding_mode' => sanitizeInput($_POST['branding_mode'] ?? 'text_logo', 20),
        ];

        // Handle File Uploads
        $upload_dir = '../assets/images/branding/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Full Logo (350x70)
        if (isset($_FILES['full_logo']) && $_FILES['full_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = validateFileUpload($_FILES['full_logo']);
            if ($uploadResult['success']) {
                $file_extension = pathinfo($_FILES['full_logo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'full_logo_' . time() . '.' . $file_extension;
                if (move_uploaded_file($_FILES['full_logo']['tmp_name'], $upload_dir . $new_filename)) {
                    $settings_to_save['full_logo'] = 'assets/images/branding/' . $new_filename;
                }
            } else {
                $error .= "Full logo upload failed: " . implode(', ', $uploadResult['errors']) . "<br>";
            }
        }

        // Site Logo
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = validateFileUpload($_FILES['site_logo']);
            if ($uploadResult['success']) {
                $file_extension = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'logo_' . time() . '.' . $file_extension;
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $new_filename)) {
                    $settings_to_save['site_logo'] = 'assets/images/branding/' . $new_filename;
                }
            } else {
                $error .= "Logo upload failed: " . implode(', ', $uploadResult['errors']) . "<br>";
            }
        }

        // Site Icon (Favicon)
        if (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = validateFileUpload($_FILES['site_icon']);
            if ($uploadResult['success']) {
                $file_extension = pathinfo($_FILES['site_icon']['name'], PATHINFO_EXTENSION);
                $new_filename = 'favicon_' . time() . '.' . $file_extension;
                if (move_uploaded_file($_FILES['site_icon']['tmp_name'], $upload_dir . $new_filename)) {
                    $settings_to_save['site_icon'] = 'assets/images/branding/' . $new_filename;
                }
            } else {
                $error .= "Icon upload failed: " . implode(', ', $uploadResult['errors']) . "<br>";
            }
        }
        
        foreach ($settings_to_save as $key => $value) {
            $stmt = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        }
        
        if (empty($error)) {
            $success = "White label settings saved successfully!";
        }
    } catch (PDOException $e) {
        logError("White label settings error: " . $e->getMessage());
        $error = "Failed to save settings.";
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $settings = [];
}

include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-white mb-2">White Label Settings</h1>
    <p class="text-slate-400">Customize your platform branding and appearance</p>
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

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <?php echo csrfField(); ?>
    
    <!-- Branding Section -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Branding</h2>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-2">Branding Mode</label>
            <div class="flex gap-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="branding_mode" value="text_logo" class="form-radio text-primary" <?php echo ($settings['branding_mode'] ?? 'text_logo') === 'text_logo' ? 'checked' : ''; ?>>
                    <span class="ml-2 text-white">Icon + Text</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="branding_mode" value="full_logo" class="form-radio text-primary" <?php echo ($settings['branding_mode'] ?? '') === 'full_logo' ? 'checked' : ''; ?>>
                    <span class="ml-2 text-white">Full Logo</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Site Name *</label>
                <input type="text" name="site_name" required
                    value="<?php echo htmlspecialchars($settings['site_name'] ?? 'CodeFiesta'); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Site Tagline</label>
                <input type="text" name="site_tagline"
                    value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Site Icon (Small Logo)</label>
                <?php if (!empty($settings['site_logo'])): ?>
                    <div class="mb-2 p-2 bg-slate-900 rounded border border-white/10 inline-block">
                        <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Current Logo" class="h-12 object-contain">
                    </div>
                <?php endif; ?>
                <input type="file" name="site_logo" accept="image/*"
                    class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                <p class="text-xs text-slate-500 mt-1">Used for "Icon + Text" mode. Recommended height: 40-60px.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Full Logo (Banner)</label>
                <?php if (!empty($settings['full_logo'])): ?>
                    <div class="mb-2 p-2 bg-slate-900 rounded border border-white/10 inline-block">
                        <img src="../<?php echo htmlspecialchars($settings['full_logo']); ?>" alt="Current Full Logo" class="h-12 object-contain">
                    </div>
                <?php endif; ?>
                <input type="file" name="full_logo" accept="image/*"
                    class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                <p class="text-xs text-slate-500 mt-1">Used for "Full Logo" mode. Recommended size: 350x70px.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Site Icon (Favicon)</label>
                <?php if (!empty($settings['site_icon'])): ?>
                    <div class="mb-2 p-2 bg-slate-900 rounded border border-white/10 inline-block">
                        <img src="../<?php echo htmlspecialchars($settings['site_icon']); ?>" alt="Current Icon" class="w-8 h-8 object-contain">
                    </div>
                <?php endif; ?>
                <input type="file" name="site_icon" accept="image/*"
                    class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                <p class="text-xs text-slate-500 mt-1">Recommended size: 32x32px or 64x64px. PNG or ICO.</p>
            </div>
        </div>
    </div>
    
    <!-- Color Scheme -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Color Scheme</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Primary Color</label>
                <div class="flex gap-2">
                    <input type="color" name="primary_color"
                        value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#8b5cf6'); ?>"
                        class="w-16 h-10 bg-slate-900 border border-white/10 rounded cursor-pointer">
                    <input type="text" name="primary_color_hex"
                        value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#8b5cf6'); ?>"
                        class="flex-1 px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                        readonly>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Secondary Color</label>
                <div class="flex gap-2">
                    <input type="color" name="secondary_color"
                        value="<?php echo htmlspecialchars($settings['secondary_color'] ?? '#ec4899'); ?>"
                        class="w-16 h-10 bg-slate-900 border border-white/10 rounded cursor-pointer">
                    <input type="text" name="secondary_color_hex"
                        value="<?php echo htmlspecialchars($settings['secondary_color'] ?? '#ec4899'); ?>"
                        class="flex-1 px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                        readonly>
                </div>
            </div>
        </div>
        
        <p class="text-slate-500 text-sm mt-4">Note: Color changes require page refresh to take effect</p>
    </div>
    
    <!-- Contact Information -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Contact Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Contact Email</label>
                <input type="email" name="contact_email"
                    value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Contact Phone</label>
                <input type="text" name="contact_phone"
                    value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
        </div>
    </div>

    <!-- Social Media Links -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Social Media Links</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Twitter / X URL</label>
                <input type="url" name="social_twitter"
                    value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://twitter.com/yourhandle">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Facebook URL</label>
                <input type="url" name="social_facebook"
                    value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://facebook.com/yourpage">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Instagram URL</label>
                <input type="url" name="social_instagram"
                    value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://instagram.com/yourhandle">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">LinkedIn URL</label>
                <input type="url" name="social_linkedin"
                    value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://linkedin.com/company/yourcompany">
            </div>
        </div>
    </div>

    <!-- Legal Pages -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Legal Pages</h2>
        
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Privacy Policy Content</label>
                <textarea name="privacy_policy_content" rows="10"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm"><?php echo htmlspecialchars($settings['privacy_policy_content'] ?? ''); ?></textarea>
                <p class="text-xs text-slate-500 mt-1">HTML allowed. Leave empty to use default template.</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Terms of Service Content</label>
                <textarea name="terms_of_service_content" rows="10"
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm"><?php echo htmlspecialchars($settings['terms_of_service_content'] ?? ''); ?></textarea>
                <p class="text-xs text-slate-500 mt-1">HTML allowed. Leave empty to use default template.</p>
            </div>
        </div>
    </div>
    
    <!-- Footer Settings -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Footer Settings</h2>
        
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Footer Copyright Text</label>
            <input type="text" name="footer_text"
                value="<?php echo htmlspecialchars($settings['footer_text'] ?? ''); ?>"
                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="© 2025 YourCompany. All rights reserved.">
        </div>
    </div>
    
    <div class="flex justify-end gap-4">
        <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors">
            Save Settings
        </button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
