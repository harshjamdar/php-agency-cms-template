<?php
require_once __DIR__ . '/helpers/whitelabel-helper.php';
$siteName = getSiteName();
$siteTagline = getSiteTagline();
$contactEmail = getContactEmail();
$contactPhone = getContactPhone();
$footerText = getFooterText();

$socialTwitter = getSetting('social_twitter');
$socialFacebook = getSetting('social_facebook');
$socialInstagram = getSetting('social_instagram');
$socialLinkedin = getSetting('social_linkedin');
?>

<footer id="contact" class="bg-slate-950 pt-20 pb-10 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
            <!-- Contact Info -->
            <div>
                <div class="flex items-center gap-2 mb-6">
                    <svg class="h-8 w-8 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                    <span class="font-bold text-2xl text-white"><?php echo htmlspecialchars($siteName); ?></span>
                </div>
                <p class="text-gray-400 mb-8 max-w-md leading-relaxed">
                    <?php echo htmlspecialchars($siteTagline); ?>
                </p>
                
                <div class="space-y-4 mb-8">
                    <div class="flex items-center gap-3 text-gray-300">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </div>
                        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="hover:text-white transition-colors"><?php echo htmlspecialchars($contactEmail); ?></a>
                    </div>
                    <div class="flex items-center gap-3 text-gray-300">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </div>
                        <a href="tel:<?php echo htmlspecialchars($contactPhone); ?>" class="hover:text-white transition-colors"><?php echo htmlspecialchars($contactPhone); ?></a>
                    </div>
                    <div class="flex items-center gap-3 text-gray-300">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                        <span>Bangalore, India</span>
                    </div>
                </div>

                <div class="flex gap-4">
                    <!-- Social Icons -->
                    <?php if ($socialTwitter): ?>
                    <a href="<?php echo htmlspecialchars($socialTwitter); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-gray-400 hover:text-white hover:border-primary/50 hover:bg-primary/10 transition-all">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($socialFacebook): ?>
                    <a href="<?php echo htmlspecialchars($socialFacebook); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-gray-400 hover:text-white hover:border-primary/50 hover:bg-primary/10 transition-all">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                    </a>
                    <?php endif; ?>

                    <?php if ($socialInstagram): ?>
                    <a href="<?php echo htmlspecialchars($socialInstagram); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-gray-400 hover:text-white hover:border-primary/50 hover:bg-primary/10 transition-all">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                    </a>
                    <?php endif; ?>

                    <?php if ($socialLinkedin): ?>
                    <a href="<?php echo htmlspecialchars($socialLinkedin); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-gray-400 hover:text-white hover:border-primary/50 hover:bg-primary/10 transition-all">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 md:p-8">
                <h3 class="text-xl font-bold text-white mb-6">Send us a message</h3>
                <form id="contact-form" action="submit-inquiry.php" method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-1">Name</label>
                        <input type="text" id="name" name="name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors" placeholder="Your name">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors" placeholder="john@example.com">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-400 mb-1">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors" placeholder="Your phone number">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-400 mb-1">Message</label>
                        <textarea id="message" name="message" rows="4" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors" placeholder="Tell us about your project..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white font-bold py-3 px-6 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                        Send Message
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex flex-col md:flex-row items-center gap-2 text-center md:text-left">
                <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($footerText); ?></p>
                <span class="hidden md:inline text-gray-700">|</span>
                <p class="text-gray-600 text-xs">Developed by Harsh Jamdar</p>
            </div>
            <div class="flex gap-6 text-sm text-gray-500">
                <a href="privacy-policy.php" class="hover:text-white transition-colors">Privacy Policy</a>
                <a href="terms-of-service.php" class="hover:text-white transition-colors">Terms of Service</a>
                <a href="sitemap-page.php" class="hover:text-white transition-colors">Sitemap</a>
            </div>
        </div>
    </div>
    <?php include 'includes/cookie-banner.php'; ?>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 z-50 flex items-center justify-center px-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 max-w-md w-full relative z-10 transform scale-95 transition-transform duration-300 shadow-2xl">
            <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-bold text-white text-center mb-2">Thank You!</h3>
            <p class="text-gray-400 text-center mb-8">Your message has been sent successfully. We will get back to you soon.</p>
            <button onclick="closeModal()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                Close
            </button>
        </div>
    </div>

    <!-- Notification Script -->
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');
        const modal = document.getElementById('success-modal');

        function showModal() {
            if(modal) {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                const content = modal.querySelector('.relative');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }
        }

        function closeModal() {
            if(modal) {
                modal.classList.add('opacity-0', 'pointer-events-none');
                const content = modal.querySelector('.relative');
                content.classList.add('scale-95');
                content.classList.remove('scale-100');
            }
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        if (status === 'success') {
            showModal();
        } else if (status === 'error') {
            alert(msg || 'Something went wrong. Please try again.');
        }
    </script>
</footer>
