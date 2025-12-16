<?php
require_once 'includes/helpers/whitelabel-helper.php';
$siteName = getSiteName();

// Fetch dynamic content
$blogPosts = [];
$projects = [];

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT id, title, slug FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 10");
        $blogPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->query("SELECT id, title, slug FROM projects ORDER BY created_at DESC LIMIT 10");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silent fail
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap | <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: "#020617",
                        foreground: "#f8fafc",
                        primary: "#8b5cf6",
                        secondary: "#06b6d4",
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="bg-background text-foreground antialiased">
    <?php include 'includes/tracking-body.php'; ?>

    <?php include 'includes/header.php'; ?>

    <main class="pt-32 pb-20 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <a href="index.php" class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors mb-8">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Home
            </a>

            <div class="mb-12">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Sitemap</h1>
                <p class="text-gray-400">Navigate our website easily.</p>
            </div>

            <!-- Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6 text-white">Main Pages</h2>
                    <ul class="space-y-4">
                        <li><a href="index.php" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Home</a></li>
                        <li><a href="index.php#services" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Services</a></li>
                        <li><a href="projects.php" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Portfolio</a></li>
                        <li><a href="blog.php" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Blog</a></li>
                        <li><a href="index.php#estimator" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Cost Estimator</a></li>
                        <li><a href="index.php#contact" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Contact</a></li>
                    </ul>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6 text-white">Latest Projects</h2>
                    <ul class="space-y-4">
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $project): ?>
                                <li><a href="project-view.php?slug=<?php echo htmlspecialchars($project['slug']); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span><?php echo htmlspecialchars($project['title']); ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="text-gray-500 italic">No projects yet.</li>
                        <?php endif; ?>
                        <li><a href="projects.php" class="text-primary hover:text-white transition-colors text-sm mt-2 inline-block">View All Projects &rarr;</a></li>
                    </ul>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6 text-white">Recent Blog Posts</h2>
                    <ul class="space-y-4">
                        <?php if (!empty($blogPosts)): ?>
                            <?php foreach ($blogPosts as $post): ?>
                                <li><a href="blog-post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span><?php echo htmlspecialchars($post['title']); ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="text-gray-500 italic">No posts yet.</li>
                        <?php endif; ?>
                        <li><a href="blog.php" class="text-primary hover:text-white transition-colors text-sm mt-2 inline-block">View All Posts &rarr;</a></li>
                    </ul>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6 text-white">Legal</h2>
                    <ul class="space-y-4">
                        <li><a href="privacy-policy.php" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Privacy Policy</a></li>
                        <li><a href="terms-of-service.php" class="text-gray-300 hover:text-primary transition-colors flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>Terms of Service</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
