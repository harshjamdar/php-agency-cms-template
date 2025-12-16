<?php
require_once 'config.php';
checkLogin();

$error = '';
$success = '';
$service = null;
$is_edit = false;

// Check if editing existing service
if (isset($_GET['id'])) {
    $id = validateId($_GET['id']);
    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($service) {
                $is_edit = true;
            }
        } catch (PDOException $e) {
            logError("Service fetch error: " . $e->getMessage());
            $error = "Failed to load service.";
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $title = sanitizeInput($_POST['title'] ?? '', 200);
    $description = sanitizeInput($_POST['description'] ?? '', 2000);
    $icon = sanitizeInput($_POST['icon'] ?? 'code', 50);
    $color = sanitizeInput($_POST['color'] ?? 'blue', 50);
    $display_order = validateId($_POST['display_order'] ?? 0) ?: 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) {
        $error = "Title is required.";
    } elseif (empty($description)) {
        $error = "Description is required.";
    } else {
        try {
            if ($is_edit && isset($_POST['id'])) {
                // Update existing service
                $id = validateId($_POST['id']);
                $stmt = $pdo->prepare("UPDATE services SET title = ?, description = ?, icon = ?, color = ?, display_order = ?, is_featured = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $description, $icon, $color, $display_order, $is_featured, $is_active, $id]);
                $success = "Service updated successfully!";
            } else {
                // Insert new service
                $stmt = $pdo->prepare("INSERT INTO services (title, description, icon, color, display_order, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $icon, $color, $display_order, $is_featured, $is_active]);
                $success = "Service added successfully!";
            }
            
            header("Location: services.php");
            exit;
        } catch (PDOException $e) {
            logError("Service save error: " . $e->getMessage());
            $error = "Failed to save service.";
        }
    }
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex items-center gap-4">
        <a href="services.php" class="text-slate-400 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-white mb-2"><?php echo $is_edit ? 'Edit' : 'Add'; ?> Service</h1>
            <p class="text-slate-400">Manage your service offerings</p>
        </div>
    </div>
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

<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
    <form method="POST" class="space-y-6">
        <?php echo csrfField(); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
        <?php endif; ?>
        
        <div>
            <label class="block text-white font-medium mb-2">Title *</label>
            <input type="text" name="title" required maxlength="200"
                   value="<?php echo $service ? htmlspecialchars($service['title']) : ''; ?>"
                   class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none">
        </div>
        
        <div>
            <label class="block text-white font-medium mb-2">Description *</label>
            <textarea name="description" required rows="4" maxlength="2000"
                      class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none resize-none"><?php echo $service ? htmlspecialchars($service['description']) : ''; ?></textarea>
        </div>
        
        <div class="grid md:grid-cols-3 gap-6">
            <div>
                <label class="block text-white font-medium mb-2">Icon</label>
                <select name="icon" class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white focus:border-primary focus:outline-none">
                    <?php 
                    $icons = ['code', 'globe', 'smartphone', 'trending-up', 'cpu', 'shopping-cart', 'database', 'cloud', 'zap', 'lock', 'mail', 'users'];
                    foreach ($icons as $icon_option): 
                    ?>
                        <option value="<?php echo $icon_option; ?>" <?php echo ($service && $service['icon'] === $icon_option) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($icon_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-white font-medium mb-2">Color</label>
                <select name="color" class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white focus:border-primary focus:outline-none">
                    <?php 
                    $colors = ['blue', 'purple', 'green', 'orange', 'cyan', 'indigo', 'red', 'pink'];
                    foreach ($colors as $color_option): 
                    ?>
                        <option value="<?php echo $color_option; ?>" <?php echo ($service && $service['color'] === $color_option) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($color_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-white font-medium mb-2">Display Order</label>
                <input type="number" name="display_order" min="0"
                       value="<?php echo $service ? $service['display_order'] : 0; ?>"
                       class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none">
            </div>
        </div>
        
        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_featured" id="is_featured" 
                       <?php echo ($service && $service['is_featured']) ? 'checked' : ''; ?>
                       class="w-5 h-5 bg-slate-900 border border-white/10 rounded text-primary focus:ring-primary">
                <label for="is_featured" class="text-white font-medium">Featured (larger display on homepage)</label>
            </div>
            
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" 
                       <?php echo (!$service || $service['is_active']) ? 'checked' : ''; ?>
                       class="w-5 h-5 bg-slate-900 border border-white/10 rounded text-primary focus:ring-primary">
                <label for="is_active" class="text-white font-medium">Active (visible on website)</label>
            </div>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors font-medium">
                <?php echo $is_edit ? 'Update' : 'Add'; ?> Service
            </button>
            <a href="services.php" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
