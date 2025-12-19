<aside id="admin-sidebar" class="w-64 bg-slate-900 border-r border-white/10 hidden md:flex flex-col fixed h-full z-20 transition-transform">
    <div class="p-6 border-b border-white/10">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <span class="text-primary"><?php echo htmlspecialchars($siteName ?? ''); ?></span>
            <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded ml-auto">Admin</span>
        </h2>
    </div>
    
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="projects.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' || basename($_SERVER['PHP_SELF']) == 'project-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="folder-kanban" class="w-5 h-5"></i>
            <span>Projects</span>
        </a>
        <a href="blog.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' || basename($_SERVER['PHP_SELF']) == 'blog-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="file-text" class="w-5 h-5"></i>
            <span>Blog Posts</span>
        </a>
        <a href="team.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' || basename($_SERVER['PHP_SELF']) == 'team-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span>Team</span>
        </a>
        <a href="inquiries.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="message-square" class="w-5 h-5"></i>
            <span>Inquiries</span>
        </a>
        <a href="services.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' || basename($_SERVER['PHP_SELF']) == 'services-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="briefcase" class="w-5 h-5"></i>
            <span>Services</span>
        </a>
        <a href="testimonials.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' || basename($_SERVER['PHP_SELF']) == 'testimonial-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="message-circle" class="w-5 h-5"></i>
            <span>Testimonials</span>
        </a>
        <a href="faq.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'faq.php' || basename($_SERVER['PHP_SELF']) == 'faq-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="help-circle" class="w-5 h-5"></i>
            <span>FAQ</span>
        </a>
        <a href="newsletter.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="mail" class="w-5 h-5"></i>
            <span>Newsletter</span>
        </a>
        <a href="analytics-advanced.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'analytics-advanced.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
            <span>Analytics</span>
        </a>
        
        <div class="border-t border-white/5 my-2"></div>
        
        <a href="seo-manager.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'seo-manager.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="search" class="w-5 h-5"></i>
            <span>SEO Manager</span>
        </a>
        <a href="api-settings.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'api-settings.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="plug" class="w-5 h-5"></i>
            <span>API & Email</span>
        </a>
        <a href="whitelabel.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'whitelabel.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="palette" class="w-5 h-5"></i>
            <span>White Label</span>
        </a>
        <a href="themes.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'themes.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="monitor" class="w-5 h-5"></i>
            <span>Themes</span>
        </a>
        <a href="popups.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'popups.php' || basename($_SERVER['PHP_SELF']) == 'popup-edit.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="message-square-plus" class="w-5 h-5"></i>
            <span>Popups</span>
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-primary/10 text-primary border border-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?> rounded-lg transition-colors">
            <i data-lucide="shield" class="w-5 h-5"></i>
            <span>Users & Roles</span>
        </a>
    </nav>

    <div class="p-4 border-t border-white/10">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
            <i data-lucide="log-out" class="w-5 h-5"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>