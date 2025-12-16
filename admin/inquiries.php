<?php
require_once 'config.php';
checkLogin();

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = validateId($_GET['delete']);
    if ($id === null) {
        $error = "Invalid inquiry ID";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: inquiries.php?msg=deleted");
            exit;
        } catch (PDOException $e) {
            logError("Error deleting inquiry: " . $e->getMessage());
            $error = "Error deleting inquiry. Please try again.";
        }
    }
}

// Handle Mark as Read
if (isset($_GET['read'])) {
    $id = validateId($_GET['read']);
    if ($id === null) {
        $error = "Invalid inquiry ID";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = 'read' WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: inquiries.php?msg=read");
            exit;
        } catch (PDOException $e) {
            logError("Error updating inquiry: " . $e->getMessage());
            $error = "Error updating inquiry. Please try again.";
        }
    }
}

// Fetch Inquiries
try {
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC");
    $inquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Inquiries</h1>
        <p class="text-slate-400">Messages from your contact form.</p>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="bg-green-500/10 text-green-400 p-4 rounded-lg mb-6 border border-green-500/20">
    <?php 
    if ($_GET['msg'] == 'deleted') echo "Inquiry deleted successfully.";
    if ($_GET['msg'] == 'read') echo "Inquiry marked as read.";
    ?>
</div>
<?php endif; ?>

<div class="bg-slate-900/50 border border-white/10 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/10 bg-white/5">
                    <th class="p-4 text-slate-300 font-medium">Status</th>
                    <th class="p-4 text-slate-300 font-medium">Name</th>
                    <th class="p-4 text-slate-300 font-medium">Subject</th>
                    <th class="p-4 text-slate-300 font-medium">Date</th>
                    <th class="p-4 text-slate-300 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (count($inquiries) > 0): ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                    <tr class="hover:bg-white/5 transition-colors <?php echo $inquiry['status'] == 'new' ? 'bg-blue-500/5' : ''; ?>">
                        <td class="p-4">
                            <?php if ($inquiry['status'] == 'new'): ?>
                                <span class="bg-blue-500 text-white text-xs font-bold px-2 py-1 rounded-full">New</span>
                            <?php else: ?>
                                <span class="text-slate-500 text-xs">Read</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <div class="font-medium text-white"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($inquiry['email']); ?></div>
                        </td>
                        <td class="p-4">
                            <div class="text-slate-300"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
                            <div class="text-xs text-slate-500 truncate max-w-[300px]"><?php echo htmlspecialchars($inquiry['message']); ?></div>
                        </td>
                        <td class="p-4 text-slate-400 text-sm">
                            <?php echo date('M d, Y H:i', strtotime($inquiry['created_at'])); ?>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="inquiry-view.php?id=<?php echo $inquiry['id']; ?>" class="p-2 hover:bg-purple-500/10 text-purple-400 rounded transition-colors" title="View Details">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <?php if ($inquiry['status'] == 'new'): ?>
                                <a href="inquiries.php?read=<?php echo $inquiry['id']; ?>" class="p-2 hover:bg-blue-500/10 text-blue-400 rounded transition-colors" title="Mark as Read">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" class="p-2 hover:bg-green-500/10 text-green-400 rounded transition-colors" title="Reply">
                                    <i data-lucide="reply" class="w-4 h-4"></i>
                                </a>
                                <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" onclick="return confirm('Are you sure you want to delete this inquiry?');" class="p-2 hover:bg-red-500/10 text-red-400 rounded transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            No inquiries found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>