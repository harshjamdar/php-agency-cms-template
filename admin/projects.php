<?php
require_once 'config.php';
checkLogin();

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = validateId($_GET['delete']);
    if ($id === null) {
        $error = "Invalid project ID";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: projects.php?msg=deleted");
            exit;
        } catch (PDOException $e) {
            logError("Error deleting project: " . $e->getMessage());
            $error = "Error deleting project. Please try again.";
        }
    }
}

// Fetch Projects
try {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Projects</h1>
        <p class="text-slate-400">Manage your portfolio projects.</p>
    </div>
    <a href="project-edit.php" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add New Project
    </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
<div class="bg-green-500/10 text-green-400 p-4 rounded-lg mb-6 border border-green-500/20">
    Project deleted successfully.
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-500/10 text-red-400 p-4 rounded-lg mb-6 border border-red-500/20">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="bg-slate-900/50 border border-white/10 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/10 bg-white/5">
                    <th class="p-4 text-slate-300 font-medium">Image</th>
                    <th class="p-4 text-slate-300 font-medium">Title</th>
                    <th class="p-4 text-slate-300 font-medium">Category</th>
                    <th class="p-4 text-slate-300 font-medium">Date</th>
                    <th class="p-4 text-slate-300 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-4">
                            <?php if ($project['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="Project" class="w-16 h-10 object-cover rounded bg-slate-800">
                            <?php else: ?>
                                <div class="w-16 h-10 bg-slate-800 rounded flex items-center justify-center text-slate-500 text-xs">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <div class="font-medium text-white">
                                <?php echo htmlspecialchars($project['title']); ?>
                                <?php if (!empty($project['is_featured'])): ?>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-500/10 text-yellow-500 border border-yellow-500/20">
                                        Featured
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-500 truncate max-w-[200px]"><?php echo htmlspecialchars(strip_tags($project['description'])); ?></div>
                        </td>
                        <td class="p-4">
                            <span class="bg-slate-800 text-slate-300 px-2 py-1 rounded text-xs border border-white/10">
                                <?php echo htmlspecialchars($project['category']); ?>
                            </span>
                        </td>
                        <td class="p-4 text-slate-400 text-sm">
                            <?php echo date('M d, Y', strtotime($project['created_at'])); ?>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="p-2 hover:bg-blue-500/10 text-blue-400 rounded transition-colors" title="Edit">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <a href="projects.php?delete=<?php echo $project['id']; ?>" onclick="return confirm('Are you sure you want to delete this project?');" class="p-2 hover:bg-red-500/10 text-red-400 rounded transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            No projects found. <a href="project-edit.php" class="text-primary hover:underline">Create one now</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>