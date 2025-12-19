<?php
require_once 'config.php';
require_once 'security.php';
require_once '../includes/helpers/email-helper.php';
require_once '../includes/helpers/whitelabel-helper.php';
checkLogin();

$siteName = getSiteName();
$siteUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$success = '';
$error = '';
$sending = false;

// Handle newsletter send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $subject = sanitizeInput($_POST['subject'] ?? '', 200);
    $html_content = $_POST['html_content'] ?? '';
    $send_to = $_POST['send_to'] ?? 'active';
    
    if (empty($subject)) {
        $error = "Subject is required.";
    } elseif (empty($html_content)) {
        $error = "Newsletter content is required.";
    } else {
        try {
            // Get recipients with unsubscribe tokens
            $sql = "SELECT email, name, unsubscribe_token FROM newsletter_subscribers WHERE status = 'active'";
            if ($send_to === 'all') {
                $sql = "SELECT email, name, unsubscribe_token FROM newsletter_subscribers";
            }
            
            $stmt = $pdo->query($sql);
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($recipients)) {
                $error = "No recipients found.";
            } else {
                $sent_count = 0;
                $failed_count = 0;
                
                // Send to each recipient
                foreach ($recipients as $recipient) {
                    // Generate unsubscribe link
                    $unsubscribeUrl = $siteUrl . '/newsletter-unsubscribe.php?token=' . urlencode($recipient['unsubscribe_token']);
                    
                    // Add unsubscribe footer if not already present
                    $contentWithFooter = $html_content;
                    if (stripos($html_content, 'unsubscribe') === false) {
                        $contentWithFooter .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; text-align: center;">';
                        $contentWithFooter .= '<p><a href="' . htmlspecialchars($unsubscribeUrl) . '" style="color: #999;">Unsubscribe from this list</a></p>';
                        $contentWithFooter .= '</div>';
                    }
                    
                    // Personalize content
                    $personalized_content = str_replace(
                        ['{{name}}', '{{email}}', '{{unsubscribe_url}}'],
                        [$recipient['name'] ?? 'Subscriber', $recipient['email'], $unsubscribeUrl],
                        $contentWithFooter
                    );
                    
                    if (sendEmail($recipient['email'], $subject, $personalized_content, $recipient['name'] ?? '')) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                    }
                    
                    // Small delay to avoid spam filters
                    usleep(100000); // 0.1 second
                }
                
                $success = "Newsletter sent successfully! Sent: $sent_count, Failed: $failed_count";
                
                // Log the campaign
                try {
                    $stmt = $pdo->prepare("INSERT INTO newsletter_campaigns (subject, content, recipients_count, sent_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$subject, $html_content, $sent_count]);
                } catch (PDOException $e) {
                    // Table might not exist, ignore
                }
            }
        } catch (Exception $e) {
            logError("Newsletter send error: " . $e->getMessage());
            $error = "Failed to send newsletter: " . $e->getMessage();
        }
    }
}

// Get subscriber count
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = 'active'");
    $active_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $active_count = $total_count = 0;
}

include 'includes/header.php';
?>

<div class="mb-8">
    <div class="flex items-center gap-4">
        <a href="newsletter.php" class="text-slate-400 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Send Newsletter</h1>
            <p class="text-slate-400">Create and send HTML newsletters to your subscribers</p>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
        <div class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-6 h-6 flex-shrink-0"></i>
            <div><?php echo $success; ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-6 h-6 flex-shrink-0"></i>
            <div><?php echo $error; ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Recipient Info -->
<div class="grid md:grid-cols-2 gap-6 mb-8">
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-green-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-white"><?php echo number_format($active_count); ?></div>
                <div class="text-slate-400 text-sm">Active Subscribers</div>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="mail" class="w-6 h-6 text-blue-400"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-white"><?php echo number_format($total_count); ?></div>
                <div class="text-slate-400 text-sm">Total Subscribers</div>
            </div>
        </div>
    </div>
</div>

<!-- Newsletter Form -->
<form method="POST" class="space-y-6">
    <?php echo csrfField(); ?>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <h3 class="text-xl font-bold text-white mb-6">Newsletter Details</h3>
        
        <div class="space-y-6">
            <div>
                <label class="block text-white font-medium mb-2">Subject Line *</label>
                <input type="text" name="subject" required maxlength="200"
                    class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none"
                    placeholder="e.g., New Features & Updates - December 2025">
            </div>
            
            <div>
                <label class="block text-white font-medium mb-2">Send To</label>
                <select name="send_to" 
                    class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white focus:border-primary focus:outline-none">
                    <option value="active">Active Subscribers Only (<?php echo $active_count; ?>)</option>
                    <option value="all">All Subscribers (<?php echo $total_count; ?>)</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 border border-white/10 rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-white">Newsletter Content (HTML/CSS)</h3>
            <button type="button" onclick="togglePreview()" 
                class="px-4 py-2 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors text-sm flex items-center gap-2">
                <i data-lucide="eye" class="w-4 h-4"></i>
                Toggle Preview
            </button>
        </div>
        
        <div class="mb-4 p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg text-blue-400 text-sm">
            <strong>Pro Tip:</strong> Use <code>{{name}}</code> and <code>{{email}}</code> for personalization. Full HTML/CSS support included!
        </div>
        
        <div class="grid md:grid-cols-2 gap-6">
            <!-- HTML Editor -->
            <div>
                <label class="block text-white font-medium mb-2">HTML Code</label>
                <textarea name="html_content" id="html_content" required rows="20"
                    class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:outline-none font-mono text-sm resize-none"><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #8b5cf6;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #8b5cf6;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hello {{name}}! 👋</h1>
        <p>We hope this email finds you well. We're excited to share some updates with you.</p>
        
        <h2>What's New?</h2>
        <ul>
            <li>New feature announcement</li>
            <li>Latest blog posts</li>
            <li>Special offers and promotions</li>
        </ul>
        
        <p>Thank you for being a valued subscriber!</p>
        
        <a href="<?php echo htmlspecialchars($siteUrl); ?>" class="button">Visit Our Website</a>
        
        <div class="footer">
            <p>You're receiving this because you subscribed to our newsletter.</p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html></textarea>
            </div>
            
            <!-- Live Preview -->
            <div id="preview-panel" style="display: none;">
                <label class="block text-white font-medium mb-2">Live Preview</label>
                <div class="bg-white rounded-lg border border-white/10 overflow-auto" style="height: 520px;">
                    <iframe id="preview-frame" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex gap-4">
        <button type="submit" onclick="return confirm('Send newsletter to all selected recipients?');"
            class="px-8 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors font-medium flex items-center gap-2">
            <i data-lucide="send" class="w-5 h-5"></i>
            Send Newsletter
        </button>
        <a href="newsletter.php" class="px-8 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors font-medium">
            Cancel
        </a>
    </div>
</form>

<script>
// Live preview functionality
function togglePreview() {
    const previewPanel = document.getElementById('preview-panel');
    if (previewPanel.style.display === 'none') {
        previewPanel.style.display = 'block';
        updatePreview();
    } else {
        previewPanel.style.display = 'none';
    }
}

function updatePreview() {
    const htmlContent = document.getElementById('html_content').value;
    const previewFrame = document.getElementById('preview-frame');
    const previewDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
    
    // Replace personalization tags with sample data
    const sampleContent = htmlContent
        .replace(/\{\{name\}\}/g, 'John Doe')
        .replace(/\{\{email\}\}/g, 'john@example.com');
    
    previewDoc.open();
    previewDoc.write(sampleContent);
    previewDoc.close();
}

// Auto-update preview on input
document.getElementById('html_content').addEventListener('input', function() {
    if (document.getElementById('preview-panel').style.display !== 'none') {
        updatePreview();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
