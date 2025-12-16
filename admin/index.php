<?php
/**
 * CodeFiesta Admin Panel
 * 
 * @package  CodeFiesta Admin
 * @author   Harsh Jamdar
 * @version  1.0.0
 */
require_once 'config.php';

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = login($username, $password);
    if ($result['success']) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = $result['error'] ?? "Invalid username or password";
    }
}

// Check for session expired message
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error = "Your session has expired. Please login again.";
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | CodeFiesta</title>
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-background text-slate-50 h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Background Effects -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-primary/10 blur-[100px]"></div>
        <div class="absolute -bottom-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-secondary/10 blur-[100px]"></div>
    </div>

    <div class="w-full max-w-md p-8 relative z-10">
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Welcome Back</h1>
                <p class="text-slate-400">Sign in to access your dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-lg mb-6 text-sm text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                    <input type="text" id="username" name="username" required 
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="Enter your username">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                    <input type="password" id="password" name="password" required 
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="••••••••">
                </div>

                <button type="submit" 
                    class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-lg transition-all shadow-[0_0_20px_rgba(139,92,246,0.3)] hover:shadow-[0_0_30px_rgba(139,92,246,0.5)] hover:-translate-y-0.5">
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="../index.php" class="text-sm text-slate-500 hover:text-white transition-colors">
                    ← Back to Website
                </a>
            </div>
        </div>
    </div>

</body>
</html>
