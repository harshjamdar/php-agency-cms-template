<?php
require_once 'config.php';
require_once __DIR__ . '/../includes/helpers/seo-helper.php';
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
        
        // SEO Fields
        $meta_title = sanitizeInput($_POST['meta_title'] ?? '', 255);
        $meta_description = sanitizeInput($_POST['meta_description'] ?? '', 500);
        $meta_keywords = sanitizeInput($_POST['meta_keywords'] ?? '', 500);
        $og_title = sanitizeInput($_POST['og_title'] ?? '', 255);
        $og_description = sanitizeInput($_POST['og_description'] ?? '', 500);
        
        // Handle OG Image Upload
        $og_image = $project ? ($project['og_image'] ?? '') : '';
        if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file = $_FILES['og_image'];
            
            if (in_array($file['type'], $allowed_types) && $file['size'] <= 5000000) {
                $upload_dir = '../assets/images/og/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'project_og_' . time() . '_' . uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $og_image = 'assets/images/og/' . $filename;
                    
                    // Delete old OG image if exists
                    if ($project && !empty($project['og_image']) && $project['og_image'] !== $og_image) {
                        $old_image = '../' . $project['og_image'];
                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                }
            }
        }
        
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
                    // Check if SEO columns exist, if not create them
                    try {
                        $pdo->exec("ALTER TABLE projects 
                            ADD COLUMN IF NOT EXISTS meta_title VARCHAR(255) DEFAULT NULL,
                            ADD COLUMN IF NOT EXISTS meta_description TEXT DEFAULT NULL,
                            ADD COLUMN IF NOT EXISTS meta_keywords TEXT DEFAULT NULL,
                            ADD COLUMN IF NOT EXISTS og_title VARCHAR(255) DEFAULT NULL,
                            ADD COLUMN IF NOT EXISTS og_description TEXT DEFAULT NULL,
                            ADD COLUMN IF NOT EXISTS og_image VARCHAR(255) DEFAULT NULL");
                    } catch (PDOException $e) {
                        // Columns might already exist, continue
                    }
                    
                    if ($id) {
                        // Update
                        $stmt = $pdo->prepare("UPDATE projects SET title = ?, slug = ?, category = ?, description = ?, content = ?, tags = ?, is_featured = ?, image_url = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, og_title = ?, og_description = ?, og_image = ? WHERE id = ?");
                        $stmt->execute([$title, $slug, $category, $description, $content, $tags, $is_featured, $image_url, $meta_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image, $id]);
                        $success = "Project updated successfully.";
                        // Refresh project data
                        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
                        $stmt->execute([$id]);
                        $project = $stmt->fetch();
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("INSERT INTO projects (title, slug, category, description, content, tags, is_featured, image_url, meta_title, meta_description, meta_keywords, og_title, og_description, og_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $slug, $category, $description, $content, $tags, $is_featured, $image_url, $meta_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image]);
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

<div class="mb-8 bg-gradient-to-br from-primary/10 via-purple-500/10 to-pink-500/10 border border-white/10 rounded-2xl p-6">
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="p-3 bg-gradient-to-br from-primary to-purple-600 rounded-xl">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1"><?php echo $id ? 'Edit Project' : 'Add New Project'; ?></h1>
                <p class="text-slate-300">Fill in the details below to showcase your work.</p>
            </div>
        </div>
        <a href="projects.php" class="text-slate-300 hover:text-white flex items-center gap-2 transition-colors px-4 py-2 bg-slate-800/50 rounded-lg hover:bg-slate-700/50">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Projects
        </a>
    </div>
</div>

<?php if ($error): ?>
<div class="bg-gradient-to-r from-red-500/10 to-pink-500/10 text-red-400 p-5 rounded-xl mb-6 border border-red-500/30 flex items-start gap-3">
    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <div><?php echo $error; ?></div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-gradient-to-r from-green-500/10 to-emerald-500/10 text-green-400 p-5 rounded-xl mb-6 border border-green-500/30 flex items-start gap-3">
    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <div><?php echo $success; ?></div>
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

        <!-- SEO Section -->
        <div class="border-t border-white/10 pt-6 mt-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">SEO Optimization</h3>
                    <p class="text-slate-400 text-sm">Improve your project's visibility on search engines</p>
                </div>
            </div>

            <!-- SEO Score Panel -->
            <div id="seo-score-panel" class="mb-6 p-6 bg-gradient-to-br from-indigo-900/30 via-purple-900/30 to-pink-900/30 border border-purple-500/20 rounded-2xl backdrop-blur-sm">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-white">SEO Score</h4>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-3xl font-bold" id="seo-score-value" style="color: #64748b;">--</div>
                            <div class="text-xs text-slate-400">out of 100</div>
                        </div>
                        <div class="w-20 h-20">
                            <svg class="transform -rotate-90" width="80" height="80">
                                <circle cx="40" cy="40" r="32" fill="none" stroke="#1e293b" stroke-width="8"></circle>
                                <circle id="seo-score-circle" cx="40" cy="40" r="32" fill="none" stroke="#64748b" stroke-width="8"
                                    stroke-dasharray="201" stroke-dashoffset="201" stroke-linecap="round"></circle>
                            </svg>
                            <div class="text-center -mt-16">
                                <span id="seo-grade" class="text-2xl font-bold text-slate-400">--</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="seo-recommendations" class="space-y-2">
                    <p class="text-slate-400 text-sm">Fill out the SEO fields below to see your score...</p>
                </div>
            </div>

            <!-- SEO Preview -->
            <div class="mb-6 bg-gradient-to-br from-blue-900/20 to-slate-900/20 border border-blue-500/20 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h4 class="text-lg font-semibold text-white">Google Search Preview</h4>
                </div>
                <div class="bg-white rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-slate-600">codefiesta.in</span>
                            </div>
                            <div id="preview-title" class="text-xl text-blue-600 hover:underline cursor-pointer mb-1 break-words line-clamp-1">
                                Your Project Title Will Appear Here
                            </div>
                            <div id="preview-description" class="text-sm text-slate-700 leading-relaxed break-words line-clamp-2">
                                Your meta description will appear here. Make it engaging!
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">
                        Meta Title <span id="meta-title-counter" class="text-slate-500 text-xs">(0/60)</span>
                    </label>
                    <input type="text" name="meta_title" id="meta_title" maxlength="60"
                        value="<?php echo $project ? htmlspecialchars($project['meta_title'] ?? '') : ''; ?>"
                        class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="Leave empty to use project title">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Meta Keywords</label>
                    <input type="text" name="meta_keywords" id="meta_keywords"
                        value="<?php echo $project ? htmlspecialchars($project['meta_keywords'] ?? '') : ''; ?>"
                        class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="keyword1, keyword2, keyword3">
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    Meta Description <span id="meta-desc-counter" class="text-slate-500 text-xs">(0/160)</span>
                </label>
                <textarea name="meta_description" id="meta_description" maxlength="160" rows="3"
                    class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                    placeholder="Leave empty to use project description"><?php echo $project ? htmlspecialchars($project['meta_description'] ?? '') : ''; ?></textarea>
            </div>

            <!-- Open Graph Section -->
            <div class="border-t border-white/10 pt-6 mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-gradient-to-br from-pink-500 to-purple-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-white">Social Media (Open Graph)</h4>
                        <p class="text-slate-400 text-sm">How your project appears when shared</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">OG Title</label>
                        <input type="text" name="og_title" id="og_title" maxlength="60"
                            value="<?php echo $project ? htmlspecialchars($project['og_title'] ?? '') : ''; ?>"
                            class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                            placeholder="Leave empty to use meta title">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">OG Image</label>
                        <input type="file" name="og_image" id="og_image" accept="image/*"
                            class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/80 file:cursor-pointer">
                        <?php if ($project && !empty($project['og_image'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($project['og_image']); ?>" alt="Current OG Image" class="h-20 rounded border border-white/10">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">OG Description</label>
                    <textarea name="og_description" id="og_description" maxlength="200" rows="2"
                        class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="Leave empty to use meta description"><?php echo $project ? htmlspecialchars($project['og_description'] ?? '') : ''; ?></textarea>
                </div>
            </div>
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

<script>
// SEO Score Calculator for Projects
function calculateProjectSEO() {
    const title = document.querySelector('input[name="title"]').value;
    const description = document.querySelector('textarea[name="description"]').value;
    const metaTitle = document.getElementById('meta_title').value;
    const metaDescription = document.getElementById('meta_description').value;
    const metaKeywords = document.getElementById('meta_keywords').value;
    const ogTitle = document.getElementById('og_title').value;
    const ogDescription = document.getElementById('og_description').value;
    
    const data = {
        page_title: metaTitle || title,
        meta_description: metaDescription || description,
        meta_keywords: metaKeywords,
        og_title: ogTitle || metaTitle || title,
        og_description: ogDescription || metaDescription || description,
        og_image: '<?php echo $project ? ($project["og_image"] ?? "") : ""; ?>',
        canonical_url: '',
        robots: 'index, follow',
        page_slug: '<?php echo $project ? $project["slug"] : ""; ?>'
    };
    
    let score = 0;
    const recommendations = [];
    
    // Title (20 points)
    const titleLength = data.page_title.length;
    if (titleLength === 0) {
        recommendations.push({ type: 'error', message: 'Title is missing' });
    } else if (titleLength >= 30 && titleLength <= 60) {
        score += 20;
        recommendations.push({ type: 'success', message: 'Title length is optimal' });
    } else if (titleLength < 30) {
        score += 10;
        recommendations.push({ type: 'warning', message: 'Title is too short (optimal: 30-60 chars)' });
    } else {
        score += 15;
        recommendations.push({ type: 'warning', message: 'Title is too long' });
    }
    
    // Description (20 points)
    const descLength = data.meta_description.length;
    if (descLength === 0) {
        recommendations.push({ type: 'error', message: 'Meta description is missing' });
    } else if (descLength >= 120 && descLength <= 160) {
        score += 20;
        recommendations.push({ type: 'success', message: 'Meta description length is optimal' });
    } else if (descLength < 70) {
        score += 10;
        recommendations.push({ type: 'warning', message: 'Meta description is too short' });
    } else if (descLength >= 70 && descLength < 120) {
        score += 15;
        recommendations.push({ type: 'warning', message: 'Meta description could be longer' });
    } else {
        score += 15;
        recommendations.push({ type: 'warning', message: 'Meta description is too long' });
    }
    
    // Keywords (10 points)
    if (data.meta_keywords) {
        const keywordCount = data.meta_keywords.split(',').filter(k => k.trim()).length;
        if (keywordCount >= 3 && keywordCount <= 10) {
            score += 10;
            recommendations.push({ type: 'success', message: 'Good number of keywords' });
        } else if (keywordCount < 3) {
            score += 5;
            recommendations.push({ type: 'warning', message: 'Add more keywords (3-10 recommended)' });
        } else {
            score += 5;
            recommendations.push({ type: 'warning', message: 'Too many keywords' });
        }
    } else {
        recommendations.push({ type: 'info', message: 'Add keywords to improve SEO' });
    }
    
    // Open Graph (30 points)
    let ogScore = 0;
    if (data.og_title) ogScore += 10;
    else recommendations.push({ type: 'warning', message: 'OG title missing' });
    
    if (data.og_description) ogScore += 10;
    else recommendations.push({ type: 'warning', message: 'OG description missing' });
    
    if (data.og_image) ogScore += 10;
    else recommendations.push({ type: 'warning', message: 'OG image missing for social sharing' });
    
    if (ogScore === 30) recommendations.push({ type: 'success', message: 'All Open Graph tags present' });
    score += ogScore;
    
    // Content quality bonus (20 points)
    const content = document.querySelector('.tinymce-editor').value;
    const wordCount = content.split(/\s+/).filter(w => w.length > 0).length;
    if (wordCount >= 200) {
        score += 20;
        recommendations.push({ type: 'success', message: `Good content length (${wordCount} words)` });
    } else if (wordCount >= 100) {
        score += 15;
        recommendations.push({ type: 'warning', message: `Content could be longer (${wordCount}/200+ words)` });
    } else if (wordCount > 0) {
        score += 10;
        recommendations.push({ type: 'error', message: `Content is too short (${wordCount}/200+ words)` });
    }
    
    // Calculate grade
    let grade = 'F';
    if (score >= 90) grade = 'A+';
    else if (score >= 80) grade = 'A';
    else if (score >= 70) grade = 'B';
    else if (score >= 60) grade = 'C';
    else if (score >= 50) grade = 'D';
    
    updateSEODisplay(score, grade, recommendations);
    updatePreview();
}

function updateSEODisplay(score, grade, recommendations) {
    document.getElementById('seo-score-value').textContent = score;
    document.getElementById('seo-grade').textContent = grade;
    
    let color = '#ef4444';
    if (score >= 80) color = '#22c55e';
    else if (score >= 60) color = '#eab308';
    else if (score >= 40) color = '#f97316';
    
    document.getElementById('seo-score-value').style.color = color;
    document.getElementById('seo-grade').style.color = color;
    document.getElementById('seo-score-circle').style.stroke = color;
    
    const circumference = 2 * Math.PI * 32;
    const offset = circumference - (score / 100 * circumference);
    document.getElementById('seo-score-circle').style.strokeDashoffset = offset;
    
    const recsContainer = document.getElementById('seo-recommendations');
    const icons = { success: '✓', warning: '⚠', error: '✗', info: 'ℹ' };
    const colors = {
        success: 'text-green-400 bg-green-500/10 border-green-500/20',
        warning: 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20',
        error: 'text-red-400 bg-red-500/10 border-red-500/20',
        info: 'text-blue-400 bg-blue-500/10 border-blue-500/20'
    };
    
    recsContainer.innerHTML = recommendations.map(rec => 
        `<div class="flex items-start gap-2 p-3 ${colors[rec.type]} border rounded-lg">
            <span class="font-bold">${icons[rec.type]}</span>
            <span class="text-sm flex-1">${rec.message}</span>
        </div>`
    ).join('');
}

function updatePreview() {
    const title = document.querySelector('input[name="title"]').value;
    const description = document.querySelector('textarea[name="description"]').value;
    const metaTitle = document.getElementById('meta_title').value;
    const metaDescription = document.getElementById('meta_description').value;
    
    document.getElementById('preview-title').textContent = metaTitle || title || 'Your Project Title Will Appear Here';
    document.getElementById('preview-description').textContent = metaDescription || description || 'Your meta description will appear here.';
}

// Character counters
document.getElementById('meta_title').addEventListener('input', function() {
    const count = this.value.length;
    const counter = document.getElementById('meta-title-counter');
    counter.textContent = `(${count}/60)`;
    counter.className = count > 60 ? 'text-red-400 text-xs' : count >= 50 ? 'text-green-400 text-xs' : 'text-slate-500 text-xs';
    calculateProjectSEO();
});

document.getElementById('meta_description').addEventListener('input', function() {
    const count = this.value.length;
    const counter = document.getElementById('meta-desc-counter');
    counter.textContent = `(${count}/160)`;
    counter.className = count > 160 ? 'text-red-400 text-xs' : count >= 150 ? 'text-green-400 text-xs' : 'text-slate-500 text-xs';
    calculateProjectSEO();
});

// Add listeners
['meta_keywords', 'og_title', 'og_description'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', calculateProjectSEO);
});

document.querySelector('input[name="title"]').addEventListener('input', calculateProjectSEO);
document.querySelector('textarea[name="description"]').addEventListener('input', calculateProjectSEO);

// Initial calculation
calculateProjectSEO();
</script>

<?php include 'includes/footer.php'; ?>