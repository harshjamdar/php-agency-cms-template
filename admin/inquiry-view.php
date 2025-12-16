<?php
require_once 'config.php';
checkLogin();

$id = isset($_GET['id']) ? validateId($_GET['id']) : null;
$inquiry = null;
$error = null;
$success = null;

if (!$id) {
    header("Location: inquiries.php");
    exit;
}

// Handle Status/Notes Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $status = in_array($_POST['status'] ?? '', ['new', 'read', 'replied']) ? $_POST['status'] : 'read';
        $notes = sanitizeInput($_POST['notes'] ?? '', 1000);
        
        try {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);
            $success = "Inquiry updated successfully.";
        } catch (PDOException $e) {
            logError("Inquiry update error: " . $e->getMessage());
            $error = "Database error. Please try again.";
        }
    }
}

// Fetch Inquiry
try {
    $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    $inquiry = $stmt->fetch();
    
    if (!$inquiry) {
        header("Location: inquiries.php");
        exit;
    }
    
    // Mark as read if it's new and we are viewing it
    if ($inquiry['status'] == 'new' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
        $inquiry['status'] = 'read'; // Update local variable
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">View Inquiry</h1>
        <p class="text-slate-400">Details from <?php echo htmlspecialchars($inquiry['name']); ?></p>
    </div>
    <a href="inquiries.php" class="text-slate-400 hover:text-white flex items-center gap-2 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Inquiries
    </a>
</div>

<?php if ($error): ?>
<div class="bg-red-500/10 text-red-400 p-4 rounded-lg mb-6 border border-red-500/20">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-500/10 text-green-400 p-4 rounded-lg mb-6 border border-green-500/20">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Message Card -->
        <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6 md:p-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($inquiry['subject'] ?? 'No Subject'); ?></h2>
                    <div class="text-slate-400 text-sm">
                        Received on <?php echo date('F j, Y \a\t g:i A', strtotime($inquiry['created_at'])); ?>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold 
                    <?php 
                    if($inquiry['status'] == 'new') echo 'bg-blue-500/10 text-blue-400';
                    elseif($inquiry['status'] == 'read') echo 'bg-slate-500/10 text-slate-400';
                    elseif($inquiry['status'] == 'replied') echo 'bg-green-500/10 text-green-400';
                    ?>">
                    <?php echo ucfirst($inquiry['status']); ?>
                </span>
            </div>
            
            <div class="prose prose-invert max-w-none text-slate-300 whitespace-pre-wrap">
                <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
            </div>
        </div>

        <!-- Internal Notes -->
        <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6 md:p-8">
            <h3 class="text-lg font-bold text-white mb-4">Internal Notes</h3>
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Status</label>
                    <select name="status" class="bg-slate-950 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary">
                        <option value="new" <?php echo $inquiry['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $inquiry['status'] == 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $inquiry['status'] == 'replied' ? 'selected' : ''; ?>>Replied</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Notes (Private)</label>
                    <textarea name="notes" rows="4" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary" placeholder="Add internal notes about this lead..."><?php echo htmlspecialchars($inquiry['notes'] ?? ''); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Save Notes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-6">
        <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6">
            <h3 class="text-lg font-bold text-white mb-4">Contact Details</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-xs text-slate-500 uppercase font-bold">Name</label>
                    <div class="text-white"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                </div>
                <div>
                    <label class="text-xs text-slate-500 uppercase font-bold">Email</label>
                    <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" class="block text-primary hover:underline truncate">
                        <?php echo htmlspecialchars($inquiry['email']); ?>
                    </a>
                </div>
                <?php if (!empty($inquiry['phone'])): ?>
                <div>
                    <label class="text-xs text-slate-500 uppercase font-bold">Phone</label>
                    <a href="tel:<?php echo htmlspecialchars($inquiry['phone']); ?>" class="block text-white hover:text-primary transition-colors">
                        <?php echo htmlspecialchars($inquiry['phone']); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>?subject=Re: <?php echo urlencode($inquiry['subject'] ?? 'Your Inquiry'); ?>" class="w-full bg-white/10 hover:bg-white/20 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition-colors mb-3">
                    <i data-lucide="reply" class="w-4 h-4"></i>
                    Reply via Email
                </a>
                <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" onclick="return confirm('Are you sure you want to delete this inquiry?');" class="w-full text-red-400 hover:bg-red-500/10 py-2 rounded-lg flex items-center justify-center gap-2 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete Inquiry
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>