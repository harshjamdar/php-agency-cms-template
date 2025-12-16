<?php
require_once 'config.php';
checkLogin();

$error = '';
$success = '';
$testimonial = null;
$is_edit = false;

// Check if editing existing testimonial
if (isset($_GET['id'])) {
    $id = validateId($_GET['id']);
    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($testimonial) {
                $is_edit = true;
            }
        } catch (PDOException $e) {
            logError("Testimonial fetch error: " . $e->getMessage());
            $error = "Failed to load testimonial.";
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $client_name = sanitizeInput($_POST['client_name'] ?? '', 200);
    $client_position = sanitizeInput($_POST['client_position'] ?? '', 200);
    $client_company = sanitizeInput($_POST['client_company'] ?? '', 200);
    $content = sanitizeInput($_POST['content'] ?? '', 2000);
    $rating = validateId($_POST['rating'] ?? 5);
    $display_order = validateId($_POST['display_order'] ?? 0) ?: 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate rating
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    if (empty($client_name)) {
        $error = "Client Name is required.";
    } elseif (empty($content)) {
        $error = "Content is required.";
    } else {
        // Handle Image Upload
        $client_image = $testimonial ? $testimonial['client_image'] : '';
        
        if (isset($_FILES['client_image']) && $_FILES['client_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = validateFileUpload($_FILES['client_image']);
            
            if (isset($uploadResult['errors'])) {
                $error = implode('<br>', $uploadResult['errors']);
            } elseif ($uploadResult['success'] && isset($uploadResult['mime_type'])) {
                $upload_dir = '../assets/images/testimonials/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['client_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('testimonial_') . '.' . $file_extension;
                $dest_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['client_image']['tmp_name'], $dest_path)) {
                    $client_image = 'assets/images/testimonials/' . $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if (!$error) {
            try {
                if ($is_edit && isset($_POST['id'])) {
                    // Update existing testimonial
                    $id = validateId($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE testimonials SET client_name = ?, client_position = ?, client_company = ?, client_image = ?, content = ?, rating = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$client_name, $client_position, $client_company, $client_image, $content, $rating, $display_order, $is_active, $id]);
                    $success = "Testimonial updated successfully!";
                    
                    // Refresh data
                    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
                    $stmt->execute([$id]);
                    $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    // Create new testimonial
                    $stmt = $pdo->prepare("INSERT INTO testimonials (client_name, client_position, client_company, client_image, content, rating, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$client_name, $client_position, $client_company, $client_image, $content, $rating, $display_order, $is_active]);
                    $success = "Testimonial created successfully!";
                    
                    // Redirect to edit page of new testimonial or clear form
                    if (!$is_edit) {
                        header("Location: testimonials.php");
                        exit;
                    }
                }
            } catch (PDOException $e) {
                logError("Testimonial save error: " . $e->getMessage());
                $error = "Failed to save testimonial.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <a href="testimonials.php" class="text-slate-400 hover:text-white flex items-center gap-2 mb-4 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Testimonials
        </a>
        <h1 class="text-3xl font-bold text-white mb-2"><?php echo $is_edit ? 'Edit Testimonial' : 'Add Testimonial'; ?></h1>
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

    <form method="POST" enctype="multipart/form-data" class="bg-slate-800/50 border border-white/10 rounded-xl p-6 space-y-6">
        <?php echo csrfField(); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Client Name *</label>
                <input type="text" name="client_name" value="<?php echo htmlspecialchars($testimonial['client_name'] ?? ''); ?>" required
                       class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Client Position</label>
                <input type="text" name="client_position" value="<?php echo htmlspecialchars($testimonial['client_position'] ?? ''); ?>"
                       class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Client Company</label>
                <input type="text" name="client_company" value="<?php echo htmlspecialchars($testimonial['client_company'] ?? ''); ?>"
                       class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Rating (1-5)</label>
                <input type="number" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($testimonial['rating'] ?? '5'); ?>"
                       class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Client Image</label>
            <?php if (!empty($testimonial['client_image'])): ?>
                <div class="mb-2">
                    <img src="<?php echo htmlspecialchars($testimonial['client_image']); ?>" alt="Current Image" class="w-20 h-20 rounded-full object-cover border border-white/10">
                </div>
            <?php endif; ?>
            <input type="file" name="client_image" accept="image/*"
                   class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
            <p class="text-xs text-slate-500 mt-1">Recommended size: 200x200px. Max size: 2MB.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Testimonial Content *</label>
            <textarea name="content" rows="4" required
                      class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors"><?php echo htmlspecialchars($testimonial['content'] ?? ''); ?></textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Display Order</label>
                <input type="number" name="display_order" value="<?php echo htmlspecialchars($testimonial['display_order'] ?? '0'); ?>"
                       class="w-full bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors">
            </div>
            
            <div class="flex items-center pt-8">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" <?php echo (!isset($testimonial) || $testimonial['is_active']) ? 'checked' : ''; ?> 
                           class="w-5 h-5 rounded border-white/10 bg-slate-900 text-primary focus:ring-primary focus:ring-offset-slate-900">
                    <span class="text-white font-medium">Active</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-white/10 flex justify-end gap-4">
            <a href="testimonials.php" class="px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <?php echo $is_edit ? 'Update Testimonial' : 'Create Testimonial'; ?>
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
