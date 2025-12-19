<?php
require_once 'config.php';
require_once 'admin/security.php';

$success = '';
$error = '';
$token = sanitizeInput($_GET['token'] ?? '', 64);

// Handle unsubscribe
if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT id, email, name, status FROM newsletter_subscribers WHERE unsubscribe_token = ?");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscriber) {
            if ($subscriber['status'] === 'unsubscribed') {
                $error = "This email has already been unsubscribed.";
            } else {
                // Unsubscribe the user
                $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = NOW() WHERE unsubscribe_token = ?");
                $stmt->execute([$token]);
                
                $success = "You have been successfully unsubscribed from our newsletter.";
            }
        } else {
            $error = "Invalid unsubscribe link. Please contact support if you continue to receive emails.";
        }
    } catch (PDOException $e) {
        logError("Unsubscribe error: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
} else {
    $error = "Invalid unsubscribe link.";
}

include 'includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-20">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-slate-800/50 backdrop-blur-sm border border-white/10 rounded-2xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <?php if ($success): ?>
                    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-4">Unsubscribed Successfully</h1>
                    <p class="text-slate-300 text-lg"><?php echo htmlspecialchars($success); ?></p>
                    <p class="text-slate-400 mt-4">We're sorry to see you go! You will no longer receive newsletters from us.</p>
                <?php else: ?>
                    <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-4">Unsubscribe Failed</h1>
                    <p class="text-slate-300 text-lg"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="flex justify-center gap-4 mt-8">
                <a href="index.php" class="px-6 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors">
                    Return to Home
                </a>
                <?php if ($success): ?>
                    <a href="newsletter-subscribe.php" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                        Resubscribe
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($success): ?>
        <div class="mt-8 bg-slate-800/30 border border-white/5 rounded-xl p-6">
            <h2 class="text-xl font-bold text-white mb-4">Why did you unsubscribe?</h2>
            <p class="text-slate-400 mb-4">We'd love to hear your feedback to improve our newsletter:</p>
            <form method="POST" action="newsletter-feedback.php" class="space-y-3">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label class="flex items-center text-slate-300 hover:text-white transition-colors cursor-pointer">
                    <input type="radio" name="reason" value="too_frequent" class="mr-3">
                    Too many emails
                </label>
                <label class="flex items-center text-slate-300 hover:text-white transition-colors cursor-pointer">
                    <input type="radio" name="reason" value="not_relevant" class="mr-3">
                    Content not relevant
                </label>
                <label class="flex items-center text-slate-300 hover:text-white transition-colors cursor-pointer">
                    <input type="radio" name="reason" value="never_signed_up" class="mr-3">
                    Never signed up
                </label>
                <label class="flex items-center text-slate-300 hover:text-white transition-colors cursor-pointer">
                    <input type="radio" name="reason" value="other" class="mr-3">
                    Other reason
                </label>
                <button type="submit" class="mt-4 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors text-sm">
                    Submit Feedback
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
