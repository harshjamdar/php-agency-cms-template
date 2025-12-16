<?php
require_once 'config.php';
checkLogin();

$id = isset($_GET['id']) ? validateId($_GET['id']) : null;
$post = null;
$error = null;
$success = null;

// Fetch post if editing
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) {
        header("Location: blog.php");
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
        $title = sanitizeInput($_POST['title'] ?? '', 255);
        $author = sanitizeInput($_POST['author'] ?? '', 100);
        $excerpt = sanitizeInput($_POST['excerpt'] ?? '', 500);
        $content = $_POST['content'] ?? '';
        $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Validate required fields
        if (empty($title)) {
            $error = "Title is required.";
        } elseif (empty($author)) {
            $error = "Author is required.";
        } elseif (empty($content)) {
            $error = "Content is required.";
        } else {
            // Generate Slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $image_url = $post ? $post['image_url'] : '';

            // Handle Image Upload
            if (isset($_FILES['image'])) {
                $uploadResult = validateFileUpload($_FILES['image']);
                
                if (isset($uploadResult['errors'])) {
                    $error = implode('<br>', $uploadResult['errors']);
                } elseif ($uploadResult['success'] && isset($uploadResult['mime_type'])) {
                    $upload_dir = '../assets/images/blog/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $new_filename = sanitizeFilename($_FILES['image']['name']);
                    $dest_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest_path)) {
                        $image_url = 'assets/images/blog/' . $new_filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }

            if (!$error) {
                try {
                    if ($id) {
                        // Update
                        $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, slug = ?, author = ?, excerpt = ?, content = ?, status = ?, is_featured = ?, image_url = ? WHERE id = ?");
                        $stmt->execute([$title, $slug, $author, $excerpt, $content, $status, $is_featured, $image_url, $id]);
                        $success = "Post updated successfully.";
                        // Refresh post data
                        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
                        $stmt->execute([$id]);
                        $post = $stmt->fetch();
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, author, excerpt, content, status, is_featured, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $slug, $author, $excerpt, $content, $status, $is_featured, $image_url]);
                        header("Location: blog.php?msg=created");
                        exit;
                    }
                } catch (PDOException $e) {
                    logError("Blog edit error: " . $e->getMessage());
                    if (strpos($e->getMessage(), 'Unknown column') !== false) {
                        $error = "Database Error: Missing columns. Please run update_schema.php first.";
                    } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = "A post with this title already exists.";
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
        <h1 class="text-3xl font-bold text-white mb-2"><?php echo $id ? 'Edit Post' : 'Write New Post'; ?></h1>
        <p class="text-slate-400">Share your thoughts with the world.</p>
    </div>
    <a href="blog.php" class="text-slate-400 hover:text-white flex items-center gap-2 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Blog
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
                <label class="block text-sm font-medium text-slate-300 mb-2">Post Title</label>
                <input type="text" name="title" value="<?php echo $post ? htmlspecialchars($post['title']) : ''; ?>" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>

            <!-- Author -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Author</label>
                <input type="text" name="author" value="<?php echo $post ? htmlspecialchars($post['author']) : 'Admin'; ?>" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                <select name="status" required
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    <option value="draft" <?php echo ($post && $post['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo ($post && $post['status'] == 'published') ? 'selected' : 'selected'; ?>>Published</option>
                </select>
            </div>

            <!-- Featured -->
            <div class="flex items-center h-full pt-6">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="sr-only peer" <?php echo ($post && $post['is_featured']) ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    <span class="ml-3 text-sm font-medium text-slate-300">Feature on Homepage</span>
                </label>
            </div>
        </div>

        <!-- Image -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Featured Image</label>
            <div class="flex items-start gap-6">
                <?php if ($post && $post['image_url']): ?>
                    <div class="w-32 h-20 bg-slate-800 rounded-lg overflow-hidden border border-white/10 shrink-0">
                        <img src="../<?php echo htmlspecialchars($post['image_url']); ?>" alt="Current Image" class="w-full h-full object-cover">
                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <input type="file" name="image" accept="image/*"
                        class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                    <p class="text-xs text-slate-500 mt-2">Recommended size: 1200x630px. Max size: 2MB.</p>
                </div>
            </div>
        </div>

        <!-- Excerpt -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Excerpt (Short Summary)</label>
            <textarea name="excerpt" rows="3" required
                class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"><?php echo $post ? htmlspecialchars($post['excerpt']) : ''; ?></textarea>
        </div>

        <!-- Content -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Content</label>
            <textarea name="content" class="tinymce-editor"><?php echo $post ? htmlspecialchars($post['content']) : ''; ?></textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-white/10 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <?php echo $id ? 'Update Post' : 'Publish Post'; ?>
            </button>
        </div>

    </form>
</div>

<?php include 'includes/footer.php'; ?>