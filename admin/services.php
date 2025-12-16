<?php
require_once 'config.php';
checkLogin();

$services = [];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Service deleted successfully!";
        } catch (PDOException $e) {
            logError("Service delete error: " . $e->getMessage());
            $error = "Failed to delete service.";
        }
    } elseif ($action === 'toggle_active') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Service status toggled!";
        } catch (PDOException $e) {
            logError("Service toggle error: " . $e->getMessage());
            $error = "Failed to toggle service status.";
        }
    } elseif ($action === 'toggle_featured') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE services SET is_featured = NOT is_featured WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Featured status toggled!";
        } catch (PDOException $e) {
            logError("Service featured toggle error: " . $e->getMessage());
            $error = "Failed to toggle featured status.";
        }
    }
}

// Fetch all services
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY display_order ASC, id ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Services fetch error: " . $e->getMessage());
    $services = [];
    $error = "Error loading services. Please ensure the database table is created. <a href='create-faq-services-tables.php' class='underline'>Click here to create it</a>.";
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Services Management</h1>
            <p class="text-slate-400">Manage your service offerings</p>
        </div>
        <a href="services-edit.php" class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Service
        </a>
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

<!-- Services List -->
<div class="grid gap-6">
    <?php if (empty($services)): ?>
        <div class="bg-slate-800/50 border border-white/10 rounded-xl p-12 text-center">
            <p class="text-slate-400">No services yet. Add your first one!</p>
        </div>
    <?php else: ?>
        <?php foreach ($services as $service): ?>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
                <div class="flex gap-6">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-<?php echo $service['color']; ?>-500/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="<?php echo htmlspecialchars($service['icon']); ?>" class="w-8 h-8 text-<?php echo $service['color']; ?>-400"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-white font-semibold text-xl mb-2"><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                                <div class="flex items-center gap-4 text-xs text-slate-500">
                                    <span>Order: <?php echo $service['display_order']; ?></span>
                                    <span>Icon: <?php echo htmlspecialchars($service['icon']); ?></span>
                                    <span>Color: <?php echo htmlspecialchars($service['color']); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <?php if ($service['is_featured']): ?>
                                    <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 rounded text-xs font-medium">Featured</span>
                                <?php endif; ?>
                                
                                <?php if ($service['is_active']): ?>
                                    <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs font-medium">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-gray-500/20 text-gray-400 rounded text-xs font-medium">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-3 border-t border-white/10">
                            <a href="services-edit.php?id=<?php echo $service['id']; ?>" 
                               class="px-3 py-1.5 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                <i data-lucide="edit" class="w-3 h-3"></i>
                                Edit
                            </a>
                            
                            <form method="POST" class="inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_featured">
                                <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                <button type="submit" class="px-3 py-1.5 bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                    <i data-lucide="star" class="w-3 h-3"></i>
                                    <?php echo $service['is_featured'] ? 'Unfeature' : 'Feature'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" class="inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                <button type="submit" class="px-3 py-1.5 bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                    <?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" class="inline ml-auto" onsubmit="return confirm('Delete this service?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                <button type="submit" class="px-3 py-1.5 bg-red-500/10 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
