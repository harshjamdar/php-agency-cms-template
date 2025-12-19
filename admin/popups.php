<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

// Handle Delete
if (isset($_POST['delete_id']) && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("DELETE FROM popups WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success = "Popup deleted successfully.";
    } catch (PDOException $e) {
        logError("Error deleting popup: " . $e->getMessage());
        $error = "Failed to delete popup.";
    }
}

// Handle Toggle Status
if (isset($_POST['toggle_id']) && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("UPDATE popups SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_POST['toggle_id']]);
        $success = "Popup status updated.";
    } catch (PDOException $e) {
        logError("Error toggling popup: " . $e->getMessage());
        $error = "Failed to update status.";
    }
}

// Fetch Popups
try {
    $stmt = $pdo->query("SELECT * FROM popups ORDER BY created_at DESC");
    $popups = $stmt->fetchAll();
} catch (PDOException $e) {
    $popups = [];
    logError("Error fetching popups: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Popup Manager</h1>
        <p class="text-slate-400">Create and manage exit-intent and timed popups.</p>
    </div>
    <a href="popup-edit.php" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Create Popup
    </a>
</div>

<?php if (isset($success)): ?>
    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="bg-slate-900 border border-white/10 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-950/50 border-b border-white/10">
                    <th class="p-4 text-slate-400 font-medium">Title</th>
                    <th class="p-4 text-slate-400 font-medium">Trigger</th>
                    <th class="p-4 text-slate-400 font-medium">Status</th>
                    <th class="p-4 text-slate-400 font-medium">Created</th>
                    <th class="p-4 text-slate-400 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (empty($popups)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            No popups found. Create one to get started.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($popups as $popup): ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="p-4 text-white font-medium">
                                <?php echo htmlspecialchars($popup['title']); ?>
                            </td>
                            <td class="p-4 text-slate-300">
                                <span class="px-2 py-1 rounded bg-slate-800 text-xs border border-white/10">
                                    <?php 
                                    echo ucfirst($popup['trigger_type']); 
                                    if ($popup['trigger_type'] === 'timer') echo " ({$popup['trigger_value']}s)";
                                    if ($popup['trigger_type'] === 'scroll') echo " ({$popup['trigger_value']}%)";
                                    ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <form method="POST" class="inline">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="toggle_id" value="<?php echo $popup['id']; ?>">
                                    <button type="submit" class="px-2 py-1 rounded text-xs font-medium <?php echo $popup['is_active'] ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-slate-800 text-slate-400 border border-white/10'; ?>">
                                        <?php echo $popup['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="p-4 text-slate-400 text-sm">
                                <?php echo date('M j, Y', strtotime($popup['created_at'])); ?>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="popup-edit.php?id=<?php echo $popup['id']; ?>" class="p-2 hover:bg-white/10 rounded-lg text-slate-400 hover:text-white transition-colors">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this popup?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="delete_id" value="<?php echo $popup['id']; ?>">
                                        <button type="submit" class="p-2 hover:bg-red-500/10 rounded-lg text-slate-400 hover:text-red-400 transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
