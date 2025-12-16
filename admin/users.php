<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

// Check if current user is admin
$current_user_role = $_SESSION['user_role'] ?? 'editor';
if ($current_user_role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $username = sanitizeInput($_POST['username'] ?? '', 50);
        $password = $_POST['password'] ?? '';
        $email = sanitizeInput($_POST['email'] ?? '', 100);
        $full_name = sanitizeInput($_POST['full_name'] ?? '', 100);
        $role = sanitizeInput($_POST['role'] ?? 'editor', 20);
        
        if (empty($username) || empty($password)) {
            $error = "Username and password are required.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $full_name, $role]);
                $success = "User added successfully!";
            } catch (PDOException $e) {
                logError("User add error: " . $e->getMessage());
                $error = "Failed to add user. Username may already exist.";
            }
        }
    } elseif ($action === 'delete_user') {
        $id = validateId($_POST['id'] ?? 0);
        if ($id === $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = "User deleted successfully!";
            } catch (PDOException $e) {
                logError("User delete error: " . $e->getMessage());
                $error = "Failed to delete user.";
            }
        }
    } elseif ($action === 'update_role') {
        $id = validateId($_POST['id'] ?? 0);
        $role = sanitizeInput($_POST['role'] ?? 'editor', 20);
        
        if ($id === $_SESSION['user_id'] && $role !== 'admin') {
            $error = "You cannot change your own admin role.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$role, $id]);
                $success = "User role updated!";
            } catch (PDOException $e) {
                logError("User role update error: " . $e->getMessage());
                $error = "Failed to update role.";
            }
        }
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Users fetch error: " . $e->getMessage());
    $users = [];
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">User Management</h1>
            <p class="text-slate-400">Manage user accounts and roles</p>
        </div>
        <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" 
            class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Add User
        </button>
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

<!-- Roles Info -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
            <i data-lucide="shield" class="w-6 h-6 text-red-400"></i>
        </div>
        <h3 class="text-white font-semibold mb-2">Admin</h3>
        <p class="text-slate-400 text-sm">Full access to all features including user management</p>
    </div>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
            <i data-lucide="edit" class="w-6 h-6 text-blue-400"></i>
        </div>
        <h3 class="text-white font-semibold mb-2">Editor</h3>
        <p class="text-slate-400 text-sm">Can create and edit content, manage projects and blog</p>
    </div>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
            <i data-lucide="eye" class="w-6 h-6 text-green-400"></i>
        </div>
        <h3 class="text-white font-semibold mb-2">Viewer</h3>
        <p class="text-slate-400 text-sm">Read-only access to view analytics and reports</p>
    </div>
</div>

<!-- Users List -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
    <h2 class="text-xl font-bold text-white mb-4">All Users (<?php echo count($users); ?>)</h2>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">User</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Email</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Role</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Status</th>
                    <th class="text-left py-3 px-4 text-slate-400 font-medium">Joined</th>
                    <th class="text-right py-3 px-4 text-slate-400 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="border-b border-white/5 hover:bg-slate-900/50">
                        <td class="py-3 px-4">
                            <div class="font-medium text-white"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
                            <div class="text-sm text-slate-400">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </td>
                        <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                echo $user['role'] === 'admin' ? 'bg-red-500/20 text-red-400' : 
                                    ($user['role'] === 'editor' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400'); 
                            ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                echo ($user['status'] ?? 'active') === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'; 
                            ?>">
                                <?php echo ucfirst($user['status'] ?? 'active'); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-400 text-sm"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td class="py-3 px-4">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <select name="role" onchange="this.form.submit()" 
                                            class="px-2 py-1 bg-slate-900 border border-white/10 rounded text-white text-sm">
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                            <option value="viewer" <?php echo $user['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                        </select>
                                    </form>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this user?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-slate-500 text-sm">You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-white/10 rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-white">Add New User</h3>
            <button onclick="document.getElementById('addUserModal').classList.add('hidden')" 
                class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="add_user">
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Username *</label>
                <input type="text" name="username" required
                    class="w-full px-4 py-2 bg-slate-950 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                <input type="text" name="full_name"
                    class="w-full px-4 py-2 bg-slate-950 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                <input type="email" name="email"
                    class="w-full px-4 py-2 bg-slate-950 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Password *</label>
                <input type="password" name="password" required minlength="6"
                    class="w-full px-4 py-2 bg-slate-950 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Role *</label>
                <select name="role" required
                    class="w-full px-4 py-2 bg-slate-950 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="viewer">Viewer</option>
                    <option value="editor" selected>Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors">
                    Add User
                </button>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" 
                    class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
