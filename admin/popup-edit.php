<?php
require_once 'config.php';
require_once 'security.php';
require_once __DIR__ . '/includes/popup-schema.php';
checkLogin();

ensurePopupsTable($pdo);

$success = '';
$error = '';
$popup = [
    'id' => '',
    'title' => '',
    'content' => '',
    'trigger_type' => 'exit',
    'trigger_value' => 0,
    'is_active' => 1
];

// Fetch existing popup if editing
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM popups WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetched) {
            $popup = $fetched;
        }
    } catch (PDOException $e) {
        logError("Error fetching popup: " . $e->getMessage());
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $title = sanitizeInput($_POST['title']);
        $content = $_POST['content']; // Allow HTML for popup content
        $trigger_type = sanitizeInput($_POST['trigger_type']);
        $trigger_value = (int)$_POST['trigger_value'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($popup['id'])) {
            // Create
            $stmt = $pdo->prepare("INSERT INTO popups (title, content, trigger_type, trigger_value, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $trigger_type, $trigger_value, $is_active]);
            $success = "Popup created successfully!";
            $popup['id'] = $pdo->lastInsertId();
        } else {
            // Update
            $stmt = $pdo->prepare("UPDATE popups SET title = ?, content = ?, trigger_type = ?, trigger_value = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $content, $trigger_type, $trigger_value, $is_active, $popup['id']]);
            $success = "Popup updated successfully!";
        }
        
        // Update local state
        $popup['title'] = $title;
        $popup['content'] = $content;
        $popup['trigger_type'] = $trigger_type;
        $popup['trigger_value'] = $trigger_value;
        $popup['is_active'] = $is_active;

    } catch (PDOException $e) {
        logError("Error saving popup: " . $e->getMessage());
        $error = "Failed to save popup.";
    }
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2"><?php echo $popup['id'] ? 'Edit Popup' : 'Create Popup'; ?></h1>
        <p class="text-slate-400">Design your lead magnet or announcement.</p>
    </div>
    <a href="popups.php" class="text-slate-400 hover:text-white flex items-center gap-2 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to List
    </a>
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

<form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <?php echo csrfField(); ?>
    
    <!-- Main Editor -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-slate-900 border border-white/10 rounded-xl p-6">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-400 mb-2">Popup Title (Internal Name)</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($popup['title']); ?>" required
                       class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Content</label>
                <textarea name="content" class="tinymce-editor"><?php echo htmlspecialchars($popup['content']); ?></textarea>
                <p class="text-xs text-slate-500 mt-2">Tip: Use the editor to add images, buttons, and formatting.</p>
            </div>
        </div>
    </div>

    <!-- Sidebar Settings -->
    <div class="space-y-6">
        <div class="bg-slate-900 border border-white/10 rounded-xl p-6">
            <h3 class="text-lg font-bold text-white mb-4">Trigger Settings</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Trigger Type</label>
                    <select name="trigger_type" id="trigger_type" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary outline-none">
                        <option value="exit" <?php echo $popup['trigger_type'] === 'exit' ? 'selected' : ''; ?>>Exit Intent (Mouse Leave)</option>
                        <option value="timer" <?php echo $popup['trigger_type'] === 'timer' ? 'selected' : ''; ?>>Time Delay</option>
                        <option value="scroll" <?php echo $popup['trigger_type'] === 'scroll' ? 'selected' : ''; ?>>Scroll Percentage</option>
                    </select>
                </div>

                <div id="trigger_value_container" class="<?php echo $popup['trigger_type'] === 'exit' ? 'hidden' : ''; ?>">
                    <label class="block text-sm font-medium text-slate-400 mb-2" id="trigger_label">
                        <?php echo $popup['trigger_type'] === 'scroll' ? 'Scroll Percentage (%)' : 'Delay (Seconds)'; ?>
                    </label>
                    <input type="number" name="trigger_value" value="<?php echo $popup['trigger_value']; ?>"
                           class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary outline-none">
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-white/5">
                    <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo $popup['is_active'] ? 'checked' : ''; ?>
                           class="w-5 h-5 rounded border-white/10 bg-slate-950 text-primary focus:ring-primary">
                    <label for="is_active" class="text-white font-medium">Active</label>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-bold transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i>
            Save Popup
        </button>
    </div>
</form>

<script>
    const triggerType = document.getElementById('trigger_type');
    const valueContainer = document.getElementById('trigger_value_container');
    const valueLabel = document.getElementById('trigger_label');

    triggerType.addEventListener('change', (e) => {
        if (e.target.value === 'exit') {
            valueContainer.classList.add('hidden');
        } else {
            valueContainer.classList.remove('hidden');
            valueLabel.textContent = e.target.value === 'scroll' ? 'Scroll Percentage (%)' : 'Delay (Seconds)';
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
