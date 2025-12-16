<?php
require_once 'config.php';
checkLogin();

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = validateId($_GET['delete']);
    if ($id === null) {
        $error = "Invalid member ID";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: team.php?msg=deleted");
            exit;
        } catch (PDOException $e) {
            logError("Error deleting member: " . $e->getMessage());
            $error = "Error deleting member. Please try again.";
        }
    }
}

// Fetch Team Members
try {
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY created_at DESC");
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching team members: " . $e->getMessage());
    $error = "Database error. Please try again.";
    $members = [];
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Team Members</h1>
        <p class="text-slate-400">Manage your team.</p>
    </div>
    <a href="team-edit.php" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Member
    </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
<div class="bg-green-500/10 text-green-400 p-4 rounded-lg mb-6 border border-green-500/20">
    Member deleted successfully.
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($members) > 0): ?>
        <?php foreach ($members as $member): ?>
        <div class="bg-slate-900/50 border border-white/10 rounded-xl p-6 flex flex-col items-center text-center">
            <div class="w-24 h-24 rounded-full bg-slate-800 mb-4 overflow-hidden border-2 border-primary/20">
                <?php if ($member['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-slate-500 text-2xl font-bold">
                        <?php echo strtoupper(substr($member['name'], 0, 2)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($member['name']); ?></h3>
            <p class="text-primary text-sm mb-2"><?php echo htmlspecialchars($member['role']); ?></p>
            <p class="text-slate-400 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars($member['bio']); ?></p>
            
            <div class="flex gap-3 mt-auto">
                <a href="team-edit.php?id=<?php echo $member['id']; ?>" class="p-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors">
                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                </a>
                <a href="team.php?delete=<?php echo $member['id']; ?>" onclick="return confirm('Are you sure?');" class="p-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full p-8 text-center text-slate-500 bg-slate-900/50 border border-white/10 rounded-xl">
            No team members found. <a href="team-edit.php" class="text-primary hover:underline">Add one now</a>.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>