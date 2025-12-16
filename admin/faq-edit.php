<?php
require_once 'config.php';
checkLogin();

$error = '';
$success = '';
$faq = null;
$is_edit = false;

// Check if editing existing FAQ
if (isset($_GET['id'])) {
    $id = validateId($_GET['id']);
    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM faq WHERE id = ?");
            $stmt->execute([$id]);
            $faq = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($faq) {
                $is_edit = true;
            }
        } catch (PDOException $e) {
            logError("FAQ fetch error: " . $e->getMessage());
            $error = "Failed to load FAQ.";
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $question = sanitizeInput($_POST['question'] ?? '', 500);
    $answer = sanitizeInput($_POST['answer'] ?? '', 2000);
    $category = sanitizeInput($_POST['category'] ?? 'general', 100);
    $display_order = validateId($_POST['display_order'] ?? 0) ?: 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($question)) {
        $error = "Question is required.";
    } elseif (empty($answer)) {
        $error = "Answer is required.";
    } else {
        try {
            if ($is_edit && isset($_POST['id'])) {
                // Update existing FAQ
                $id = validateId($_POST['id']);
                $stmt = $pdo->prepare("UPDATE faq SET question = ?, answer = ?, category = ?, display_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$question, $answer, $category, $display_order, $is_active, $id]);
                $success = "FAQ updated successfully!";
            } else {
                // Insert new FAQ
                $stmt = $pdo->prepare("INSERT INTO faq (question, answer, category, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$question, $answer, $category, $display_order, $is_active]);
                $success = "FAQ added successfully!";
            }
            
            header("Location: faq.php");
            exit;
        } catch (PDOException $e) {
            logError("FAQ save error: " . $e->getMessage());
            $error = "Failed to save FAQ.";
        }
    }
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex items-center gap-4">
        <a href="faq.php" class="text-slate-400 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-white mb-2"><?php echo $is_edit ? 'Edit' : 'Add'; ?> FAQ</h1>
            <p class="text-slate-400">Manage frequently asked questions</p>
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
            <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
        <?php endif; ?>
        
        <div>
            <label class="block text-white font-medium mb-2">Question *</label>
            <input type="text" name="question" required maxlength="500"
                   value="<?php echo $faq ? htmlspecialchars($faq['question']) : ''; ?>"
                   class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none">
        </div>
        
        <div>
            <label class="block text-white font-medium mb-2">Answer *</label>
            <textarea name="answer" required rows="6" maxlength="2000"
                      class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none resize-none"><?php echo $faq ? htmlspecialchars($faq['answer']) : ''; ?></textarea>
        </div>
        
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-white font-medium mb-2">Category</label>
                <input type="text" name="category" maxlength="100"
                       value="<?php echo $faq ? htmlspecialchars($faq['category']) : 'general'; ?>"
                       class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none">
            </div>
            
            <div>
                <label class="block text-white font-medium mb-2">Display Order</label>
                <input type="number" name="display_order" min="0"
                       value="<?php echo $faq ? $faq['display_order'] : 0; ?>"
                       class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none">
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <input type="checkbox" name="is_active" id="is_active" 
                   <?php echo (!$faq || $faq['is_active']) ? 'checked' : ''; ?>
                   class="w-5 h-5 bg-slate-900 border border-white/10 rounded text-primary focus:ring-primary">
            <label for="is_active" class="text-white font-medium">Active (visible on website)</label>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors font-medium">
                <?php echo $is_edit ? 'Update' : 'Add'; ?> FAQ
            </button>
            <a href="faq.php" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
