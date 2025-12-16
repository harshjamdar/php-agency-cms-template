<?php
require_once 'config.php';
checkLogin();

$id = isset($_GET['id']) ? validateId($_GET['id']) : null;
$member = null;
$error = null;
$success = null;

// Fetch member if editing
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch();
    if (!$member) {
        header("Location: team.php");
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        // Validate inputs
        $name = sanitizeInput($_POST['name'] ?? '', 100);
        $role = sanitizeInput($_POST['role'] ?? '', 100);
        $bio = sanitizeInput($_POST['bio'] ?? '', 500);
        $linkedin_url = filter_var($_POST['linkedin_url'] ?? '', FILTER_SANITIZE_URL);
        $github_url = filter_var($_POST['github_url'] ?? '', FILTER_SANITIZE_URL);
        $twitter_url = filter_var($_POST['twitter_url'] ?? '', FILTER_SANITIZE_URL);
        
        // Validate required fields
        if (empty($name)) {
            $error = "Name is required.";
        } elseif (empty($role)) {
            $error = "Role is required.";
        } else {
            $image_url = $member ? $member['image_url'] : '';

            // Handle Image Upload
            if (isset($_FILES['image'])) {
                $uploadResult = validateFileUpload($_FILES['image']);
                
                if (isset($uploadResult['errors'])) {
                    $error = implode('<br>', $uploadResult['errors']);
                } elseif ($uploadResult['success'] && isset($uploadResult['mime_type'])) {
                    $upload_dir = '../assets/images/team/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $new_filename = sanitizeFilename($_FILES['image']['name']);
                    $dest_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest_path)) {
                        $image_url = 'assets/images/team/' . $new_filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }

            if (!$error) {
                try {
                    if ($id) {
                        // Update
                        $stmt = $pdo->prepare("UPDATE team_members SET name = ?, role = ?, bio = ?, linkedin_url = ?, github_url = ?, twitter_url = ?, image_url = ? WHERE id = ?");
                        $stmt->execute([$name, $role, $bio, $linkedin_url, $github_url, $twitter_url, $image_url, $id]);
                        $success = "Member updated successfully.";
                        // Refresh data
                        $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
                        $stmt->execute([$id]);
                        $member = $stmt->fetch();
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("INSERT INTO team_members (name, role, bio, linkedin_url, github_url, twitter_url, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $role, $bio, $linkedin_url, $github_url, $twitter_url, $image_url]);
                        header("Location: team.php?msg=created");
                        exit;
                    }
                } catch (PDOException $e) {
                    logError("Team edit error: " . $e->getMessage());
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2"><?php echo $id ? 'Edit Member' : 'Add Team Member'; ?></h1>
        <p class="text-slate-400">Manage your team details.</p>
    </div>
    <a href="team.php" class="text-slate-400 hover:text-white flex items-center gap-2 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Team
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

<div class="bg-slate-900/50 border border-white/10 rounded-xl p-6 md:p-8">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrfField(); ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Name</label>
                <input type="text" name="name" value="<?php echo $member ? htmlspecialchars($member['name']) : ''; ?>" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Role</label>
                <input type="text" name="role" value="<?php echo $member ? htmlspecialchars($member['role']) : ''; ?>" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
        </div>

        <!-- Image -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Profile Image</label>
            <div class="flex items-start gap-6">
                <?php if ($member && $member['image_url']): ?>
                    <div class="w-24 h-24 rounded-full bg-slate-800 overflow-hidden border border-white/10 shrink-0">
                        <img src="../<?php echo htmlspecialchars($member['image_url']); ?>" alt="Current Image" class="w-full h-full object-cover">
                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <input type="file" name="image" accept="image/*"
                        class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                    <p class="text-xs text-slate-500 mt-2">Recommended size: 400x400px (Square). Max size: 2MB.</p>
                </div>
            </div>
        </div>

        <!-- Social Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">LinkedIn URL</label>
                <input type="url" name="linkedin_url" value="<?php echo $member ? htmlspecialchars($member['linkedin_url']) : ''; ?>" placeholder="https://linkedin.com/in/..."
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">GitHub URL</label>
                <input type="url" name="github_url" value="<?php echo $member ? htmlspecialchars($member['github_url']) : ''; ?>" placeholder="https://github.com/..."
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Twitter/X URL</label>
                <input type="url" name="twitter_url" value="<?php echo $member ? htmlspecialchars($member['twitter_url']) : ''; ?>" placeholder="https://twitter.com/..."
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>
        </div>

        <!-- Bio -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Bio</label>
            <textarea name="bio" rows="4" required
                class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"><?php echo $member ? htmlspecialchars($member['bio']) : ''; ?></textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-white/10 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <?php echo $id ? 'Update Member' : 'Add Member'; ?>
            </button>
        </div>

    </form>
</div>

<?php include 'includes/footer.php'; ?>