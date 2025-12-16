<?php
require_once dirname(__DIR__, 2) . '/includes/helpers/whitelabel-helper.php';
$siteName = getSiteName();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: "#020617",
                        primary: "#8b5cf6",
                        secondary: "#06b6d4",
                    }
                }
            }
        }
    </script>
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- TinyMCE for Rich Text Editing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: '.tinymce-editor',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        skin: 'oxide-dark',
        content_css: 'dark'
      });
    </script>
</head>
<body class="bg-background text-slate-50 flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative md:ml-64">
        <!-- Mobile Header -->
        <header class="md:hidden bg-slate-900 border-b border-white/10 p-4 flex justify-between items-center">
            <span class="font-bold text-white">CodeFiesta Admin</span>
            <button id="mobile-menu-btn" class="text-slate-400 hover:text-white">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </header>
        
        <!-- Content Scroll Area -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-7xl mx-auto">