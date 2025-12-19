<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_subscriber') {
        $email = validateEmail($_POST['email'] ?? '');
        $name = sanitizeInput($_POST['name'] ?? '', 100);
        
        if (!$email) {
            $error = "Valid email is required.";
        } else {
            try {
                // Check if already exists
                $stmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if ($existing['status'] === 'active') {
                        $error = "Email already subscribed.";
                    } else {
                        // Reactivate
                        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active', name = ?, unsubscribed_at = NULL WHERE id = ?");
                        $stmt->execute([$name, $existing['id']]);
                        $success = "Subscriber reactivated successfully!";
                    }
                } else {
                    // Add new with unsubscribe token
                    $token = bin2hex(random_bytes(32));
                    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, source, ip_address, unsubscribe_token) VALUES (?, ?, 'manual', '127.0.0.1', ?)");
                    $stmt->execute([$email, $name, $token]);
                    $success = "Subscriber added successfully!";
                }
            } catch (PDOException $e) {
                logError("Add subscriber error: " . $e->getMessage());
                $error = "Failed to add subscriber.";
            }
        }
    } elseif ($action === 'bulk_import') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file']['tmp_name'];
            $added = 0;
            $skipped = 0;
            
            try {
                if (($handle = fopen($file, 'r')) !== FALSE) {
                    // Skip header
                    fgetcsv($handle);
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        if (empty($data[0])) continue;
                        
                        $email = validateEmail(trim($data[0]));
                        $name = sanitizeInput($data[1] ?? '', 100);
                        
                        if (!$email) {
                            $skipped++;
                            continue;
                        }
                        
                        // Check if exists
                        $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
                        $stmt->execute([$email]);
                        
                        if ($stmt->fetch()) {
                            $skipped++;
                            continue;
                        }
                        
                        // Add new with unsubscribe token
                        $token = bin2hex(random_bytes(32));
                        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, source, ip_address, unsubscribe_token) VALUES (?, ?, 'bulk_import', '127.0.0.1', ?)");
                        $stmt->execute([$email, $name, $token]);
                        $added++;
                    }
                    
                    fclose($handle);
                    $success = "Import complete! Added: $added, Skipped: $skipped";
                }
            } catch (Exception $e) {
                logError("Bulk import error: " . $e->getMessage());
                $error = "Failed to import subscribers.";
            }
        } else {
            $error = "Please upload a valid CSV file.";
        }
    } elseif ($action === 'delete_subscriber') {
        $id = validateId($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Subscriber deleted successfully!";
        } catch (PDOException $e) {
            logError("Subscriber delete error: " . $e->getMessage());
            $error = "Failed to delete subscriber.";
        }
    } elseif ($action === 'toggle_status') {
        $id = validateId($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'unsubscribed' : 'active';
        try {
            $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = ?, unsubscribed_at = ? WHERE id = ?");
            $stmt->execute([$status, $status === 'unsubscribed' ? date('Y-m-d H:i:s') : null, $id]);
            $success = "Subscriber status updated!";
        } catch (PDOException $e) {
            logError("Subscriber status update error: " . $e->getMessage());
            $error = "Failed to update subscriber status.";
        }
    } elseif ($action === 'export_csv') {
        try {
            $stmt = $pdo->query("SELECT email, name, status, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
            $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Email', 'Name', 'Status', 'Subscribed At']);
            
            foreach ($subscribers as $sub) {
                fputcsv($output, [
                    $sub['email'],
                    $sub['name'] ?? '',
                    $sub['status'],
                    $sub['subscribed_at']
                ]);
            }
            
            fclose($output);
            exit;
        } catch (PDOException $e) {
            logError("Newsletter export error: " . $e->getMessage());
            $error = "Failed to export subscribers.";
        }
    }
}

// Get statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = 'active'");
    $active_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = 'unsubscribed'");
    $unsubscribed_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE DATE(subscribed_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $new_this_week = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $active_count = $unsubscribed_count = $new_this_week = 0;
}

// Get all subscribers
$search = sanitizeInput($_GET['search'] ?? '', 100);
$filter = sanitizeInput($_GET['filter'] ?? 'all', 20);

try {
    $sql = "SELECT * FROM newsletter_subscribers WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (email LIKE ? OR name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($filter !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $filter;
    }
    
    $sql .= " ORDER BY subscribed_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Subscribers fetch error: " . $e->getMessage());
    $subscribers = [];
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Newsletter Subscribers</h1>
            <p class="text-slate-400">Manage email subscribers and send campaigns</p>
        </div>
        <div class="flex gap-3">
            <a href="newsletter-send.php" class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors flex items-center gap-2">
                <i data-lucide="send" class="w-4 h-4"></i>
                Send Newsletter
            </a>
            <form method="POST" class="inline">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="export_csv">
                <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Export CSV
                </button>
            </form>
        </div>
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

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-green-400"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($active_count); ?></div>
        <div class="text-slate-400 text-sm">Active Subscribers</div>
    </div>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="user-plus" class="w-6 h-6 text-blue-400"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($new_this_week); ?></div>
        <div class="text-slate-400 text-sm">New This Week</div>
    </div>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="user-x" class="w-6 h-6 text-orange-400"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($unsubscribed_count); ?></div>
        <div class="text-slate-400 text-sm">Unsubscribed</div>
    </div>
</div>

<!-- Add Subscriber Forms -->
<div class="grid md:grid-cols-2 gap-6 mb-8">
    <!-- Single Add -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-5 h-5 text-primary"></i>
            Add Single Subscriber
        </h3>
        <form method="POST" class="space-y-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="add_subscriber">
            <div>
                <input type="email" name="email" required placeholder="Email Address *" 
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <input type="text" name="name" placeholder="Name (optional)" 
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit" class="w-full px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors font-medium">
                Add Subscriber
            </button>
        </form>
    </div>
    
    <!-- Bulk Import -->
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="upload" class="w-5 h-5 text-primary"></i>
            Bulk Import (CSV)
        </h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="bulk_import">
            <div>
                <label class="block text-slate-400 text-sm mb-2">Upload CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required
                    class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white file:cursor-pointer hover:file:bg-primary/80">
                <p class="text-slate-500 text-xs mt-2">Format: email, name (one per line, first row as header)</p>
            </div>
            <button type="submit" class="w-full px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors font-medium">
                Import Subscribers
            </button>
        </form>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <form method="GET" class="flex gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                placeholder="Search by email or name..."
                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        <select name="filter" 
            class="px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Subscribers</option>
            <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
            <option value="unsubscribed" <?php echo $filter === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
        </select>
        <button type="submit" class="px-6 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors">
            Search
        </button>
    </form>
</div>

<!-- Subscribers List -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
    <h2 class="text-xl font-bold text-white mb-4">All Subscribers (<?php echo count($subscribers); ?>)</h2>
    
    <?php if (empty($subscribers)): ?>
        <p class="text-slate-400 text-center py-8">No subscribers found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left py-3 px-4 text-slate-400 font-medium">Email</th>
                        <th class="text-left py-3 px-4 text-slate-400 font-medium">Name</th>
                        <th class="text-left py-3 px-4 text-slate-400 font-medium">Status</th>
                        <th class="text-left py-3 px-4 text-slate-400 font-medium">Subscribed</th>
                        <th class="text-left py-3 px-4 text-slate-400 font-medium">Source</th>
                        <th class="text-right py-3 px-4 text-slate-400 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $sub): ?>
                        <tr class="border-b border-white/5 hover:bg-slate-900/50 transition-colors">
                            <td class="py-3 px-4 text-white"><?php echo htmlspecialchars($sub['email']); ?></td>
                            <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($sub['name'] ?? 'N/A'); ?></td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $sub['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400'; ?>">
                                    <?php echo ucfirst($sub['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-400 text-sm"><?php echo date('M j, Y', strtotime($sub['subscribed_at'])); ?></td>
                            <td class="py-3 px-4 text-slate-400 text-sm"><?php echo htmlspecialchars($sub['source']); ?></td>
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" class="inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $sub['status']; ?>">
                                        <button type="submit" class="p-2 text-blue-400 hover:bg-blue-500/10 rounded-lg transition-colors" title="Toggle Status">
                                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this subscriber?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_subscriber">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
