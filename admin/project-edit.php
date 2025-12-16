<?php
require_once 'config.php';
checkLogin();

$id = isset($_GET['id']) ? validateId($_GET['id']) : null;
$project = null;
$error = null;
$success = null;

// Fetch project if editing
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    if (!$project) {
        header("Location: projects.php");
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
        $title = sanitizeInput($_POST['title'] ?? '', 100);
        $category = sanitizeInput($_POST['category'] ?? '', 50);
        $description = sanitizeInput($_POST['description'] ?? '', 500);
        $content = $_POST['content'] ?? ''; // Keep HTML for content
        $tags = sanitizeInput($_POST['tags'] ?? '', 255);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Validate required fields
        if (empty($title)) {
            $error = "Title is required.";
        } elseif (empty($category)) {
            $error = "Category is required.";
        } elseif (empty($description)) {
            $error = "Description is required.";
        } else {
            // Generate Slug automatically from title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $image_url = $project ? $project['image_url'] : '';

            // Handle Image Upload
            if (isset($_FILES['image'])) {
                $uploadResult = validateFileUpload($_FILES['image']);
                
                if (isset($uploadResult['errors'])) {
                    $error = implode('<br>', $uploadResult['errors']);
                } elseif ($uploadResult['success'] && isset($uploadResult['mime_type'])) {
                    $upload_dir = '../assets/images/projects/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $new_filename = sanitizeFilename($_FILES['image']['name']);
                    $dest_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest_path)) {
                        $image_url = 'assets/images/projects/' . $new_filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }

            if (!$error) {
                try {
                    if ($id) {
                        // Update
                        $stmt = $pdo->prepare("UPDATE projects SET title = ?, slug = ?, category = ?, description = ?, content = ?, tags = ?, is_featured = ?, image_url = ? WHERE id = ?");
                        $stmt->execute([$title, $slug, $category, $description, $content, $tags, $is_featured, $image_url, $id]);
                        $success = "Project updated successfully.";
                        // Refresh project data
                        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
                        $stmt->execute([$id]);
                        $project = $stmt->fetch();
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("INSERT INTO projects (title, slug, category, description, content, tags, is_featured, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $slug, $category, $description, $content, $tags, $is_featured, $image_url]);
                        header("Location: projects.php?msg=created");
                        exit;
                    }
                } catch (PDOException $e) {
                    logError("Project edit error: " . $e->getMessage());
                    if (strpos($e->getMessage(), 'Unknown column') !== false) {
                        $error = "Database Error: Missing columns. Please run update_schema.php first.";
                    } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = "A project with this title already exists.";
                    } else {
                        $error = "Database error. Please try again.";
                    }
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2"><?php echo $id ? 'Edit Project' : 'Add New Project'; ?></h1>
        <p class="text-slate-400">Fill in the details below.</p>
    </div>
    <a href="projects.php" class="text-slate-400 hover:text-white flex items-center gap-2 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Projects
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
            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Project Title</label>
                <input type="text" name="title" value="<?php echo $project ? htmlspecialchars($project['title']) : ''; ?>" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>

            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                <select name="category" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    <option value="Web Development" <?php echo ($project && $project['category'] == 'Web Development') ? 'selected' : ''; ?>>Web Development</option>
                    <option value="Mobile App" <?php echo ($project && $project['category'] == 'Mobile App') ? 'selected' : ''; ?>>Mobile App</option>
                    <option value="UI/UX Design" <?php echo ($project && $project['category'] == 'UI/UX Design') ? 'selected' : ''; ?>>UI/UX Design</option>
                    <option value="Branding" <?php echo ($project && $project['category'] == 'Branding') ? 'selected' : ''; ?>>Branding</option>
                    <option value="Other" <?php echo ($project && $project['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <!-- Tags -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Tags (comma separated)</label>
            <input type="text" name="tags" value="<?php echo $project ? htmlspecialchars($project['tags']) : ''; ?>" placeholder="React, Node.js, AWS"
                class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
        </div>

        <!-- Image -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Project Image</label>
            <div class="flex items-start gap-6">
                <?php if ($project && $project['image_url']): ?>
                    <div class="w-32 h-20 bg-slate-800 rounded-lg overflow-hidden border border-white/10 shrink-0">
                        <img src="../<?php echo htmlspecialchars($project['image_url']); ?>" alt="Current Image" class="w-full h-full object-cover">
                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <input type="file" name="image" accept="image/*"
                        class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                    <p class="text-xs text-slate-500 mt-2">Recommended size: 800x600px. Max size: 2MB.</p>
                </div>
            </div>
        </div>

        <!-- Short Description (Excerpt) -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Short Description (for cards)</label>
            <textarea name="description" rows="3" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"><?php echo $project ? htmlspecialchars($project['description']) : ''; ?></textarea>
        </div>

        <!-- Full Content -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Full Project Details</label>
            <textarea name="content" class="tinymce-editor"><?php echo $project ? htmlspecialchars($project['content']) : ''; ?></textarea>
        </div>

        <!-- Featured Checkbox -->
        <div class="flex items-center gap-3">
            <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo ($project && $project['is_featured']) ? 'checked' : ''; ?> class="w-5 h-5 rounded border-slate-700 bg-slate-950 text-primary focus:ring-primary">
            <label for="is_featured" class="text-sm font-medium text-slate-300">Feature this project on Homepage</label>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-white/10 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <?php echo $id ? 'Update Project' : 'Save Project'; ?>
            </button>
        </div>

    </form>
</div>

<?php include 'includes/footer.php'; ?>