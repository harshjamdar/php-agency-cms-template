<?php
require_once 'config.php';
checkLogin();

$testimonials = [];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Testimonial deleted successfully!";
        } catch (PDOException $e) {
            logError("Testimonial delete error: " . $e->getMessage());
            $error = "Failed to delete testimonial.";
        }
    } elseif ($action === 'toggle_active') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE testimonials SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Testimonial status toggled!";
        } catch (PDOException $e) {
            logError("Testimonial toggle error: " . $e->getMessage());
            $error = "Failed to toggle testimonial status.";
        }
    }
}

// Fetch all testimonials
try {
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC, id DESC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Testimonials fetch error: " . $e->getMessage());
    $testimonials = [];
    $error = "Error loading testimonials. Please ensure the database table is created.";
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Testimonials Management</h1>
            <p class="text-slate-400">Manage client testimonials</p>
        </div>
        <a href="testimonial-edit.php" class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Testimonial
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

<!-- Testimonials List -->
<div class="grid gap-6">
    <?php if (empty($testimonials)): ?>
        <div class="bg-slate-800/50 border border-white/10 rounded-xl p-12 text-center">
            <p class="text-slate-400">No testimonials yet. Add your first one!</p>
        </div>
    <?php else: ?>
        <?php foreach ($testimonials as $testimonial): ?>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
                <div class="flex gap-6">
                    <div class="flex-shrink-0">
                        <?php if (!empty($testimonial['client_image'])): ?>
                            <img src="<?php echo htmlspecialchars($testimonial['client_image']); ?>" alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" class="w-16 h-16 rounded-full object-cover border-2 border-primary/20">
                        <?php else: ?>
                            <div class="w-16 h-16 bg-slate-700 rounded-full flex items-center justify-center border-2 border-white/10">
                                <i data-lucide="user" class="w-8 h-8 text-slate-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-white font-semibold text-xl mb-1"><?php echo htmlspecialchars($testimonial['client_name']); ?></h3>
                                <p class="text-primary text-sm mb-2">
                                    <?php echo htmlspecialchars($testimonial['client_position']); ?>
                                    <?php if (!empty($testimonial['client_company'])): ?>
                                        at <?php echo htmlspecialchars($testimonial['client_company']); ?>
                                    <?php endif; ?>
                                </p>
                                <div class="flex items-center gap-1 mb-3">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i data-lucide="star" class="w-4 h-4 <?php echo $i <= $testimonial['rating'] ? 'text-yellow-400 fill-yellow-400' : 'text-slate-600'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-slate-300 text-sm leading-relaxed mb-3 italic">"<?php echo htmlspecialchars($testimonial['content']); ?>"</p>
                                <div class="flex items-center gap-4 text-xs text-slate-500">
                                    <span>Order: <?php echo $testimonial['display_order']; ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <?php if ($testimonial['is_active']): ?>
                                    <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs font-medium">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-gray-500/20 text-gray-400 rounded text-xs font-medium">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-3 border-t border-white/10">
                            <a href="testimonial-edit.php?id=<?php echo $testimonial['id']; ?>" 
                               class="px-3 py-1.5 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                <i data-lucide="edit" class="w-3 h-3"></i>
                                Edit
                            </a>
                            
                            <form method="POST" class="inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                <button type="submit" class="px-3 py-1.5 bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                    <?php echo $testimonial['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" class="inline ml-auto" onsubmit="return confirm('Delete this testimonial?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
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
