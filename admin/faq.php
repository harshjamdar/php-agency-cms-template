<?php
require_once 'config.php';
checkLogin();

$faq_items = [];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM faq WHERE id = ?");
            $stmt->execute([$id]);
            $success = "FAQ deleted successfully!";
        } catch (PDOException $e) {
            logError("FAQ delete error: " . $e->getMessage());
            $error = "Failed to delete FAQ.";
        }
    } elseif ($action === 'toggle_active') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE faq SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $success = "FAQ status toggled!";
        } catch (PDOException $e) {
            logError("FAQ toggle error: " . $e->getMessage());
            $error = "Failed to toggle FAQ status.";
        }
    }
}

// Fetch all FAQs
try {
    $stmt = $pdo->query("SELECT * FROM faq ORDER BY display_order ASC, id ASC");
    $faq_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("FAQ fetch error: " . $e->getMessage());
    $faq_items = [];
    $error = "Error loading FAQs. Please ensure the database table is created. <a href='create-faq-services-tables.php' class='underline'>Click here to create it</a>.";
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">FAQ Management</h1>
            <p class="text-slate-400">Manage frequently asked questions</p>
        </div>
        <a href="faq-edit.php" class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add FAQ
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

<!-- FAQ List -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
    <h2 class="text-xl font-bold text-white mb-4">All FAQs</h2>
    
    <?php if (empty($faq_items)): ?>
        <p class="text-slate-400 text-center py-12">No FAQs yet. Add your first one!</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($faq_items as $faq): ?>
                <div class="bg-slate-900/50 border border-white/10 rounded-lg p-6">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-white font-semibold text-lg mb-2"><?php echo htmlspecialchars($faq['question']); ?></h3>
                            <p class="text-slate-300 text-sm leading-relaxed mb-3"><?php echo htmlspecialchars($faq['answer']); ?></p>
                            <div class="flex items-center gap-4 text-xs text-slate-500">
                                <span>Order: <?php echo $faq['display_order']; ?></span>
                                <span>Category: <?php echo htmlspecialchars($faq['category']); ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 ml-4">
                            <?php if ($faq['is_active']): ?>
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs font-medium">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-gray-500/20 text-gray-400 rounded text-xs font-medium">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t border-white/10">
                        <a href="faq-edit.php?id=<?php echo $faq['id']; ?>" 
                           class="px-3 py-1.5 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                            <i data-lucide="edit" class="w-3 h-3"></i>
                            Edit
                        </a>
                        
                        <form method="POST" class="inline">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                            <button type="submit" class="px-3 py-1.5 bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <?php echo $faq['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline ml-auto" onsubmit="return confirm('Delete this FAQ?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                            <button type="submit" class="px-3 py-1.5 bg-red-500/10 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors text-sm flex items-center gap-1">
                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
