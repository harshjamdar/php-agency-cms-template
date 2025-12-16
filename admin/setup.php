<?php

/**
 * Setup Script
 *
 * Initializes the CodeFiesta admin panel database and creates the initial admin user.
 * This file will self-destruct after successful setup for security purposes.
 *
 * @package    CodeFiesta
 * @subpackage Admin
 * @author     CodeFiesta Development Team
 * @version    1.0.0
 */

// Load dependencies
define('IS_SETUP', true);
require_once 'config.php';
require_once __DIR__ . '/includes/ValidationHelper.php';

// Initialize variables
$setupComplete = false;
$error = '';
$success = '';
$step = 1;

// Helper function to write .env file
function writeEnvFile($host, $name, $user, $pass) {
    $envContent = "APP_ENV=production\n";
    $envContent .= "DB_HOST=" . $host . "\n";
    $envContent .= "DB_NAME=" . $name . "\n";
    $envContent .= "DB_USER=" . $user . "\n";
    $envContent .= "DB_PASS=" . $pass . "\n";
    $envContent .= "SESSION_LIFETIME=7200\n";
    
    return file_put_contents(__DIR__ . '/.env', $envContent);
}

// Check if we have a valid database connection from config.php
if ($pdo) {
    $step = 2;
    require_once __DIR__ . '/includes/SetupService.php';
    
    // Initialize setup service
    $setupService = new SetupService($pdo);

    // Check if setup has already been completed
    if ($setupService->isSetupComplete()) {
        die("Setup has already been completed. Please delete this file manually if you need to reset the database.");
    }
} else {
    // If connection failed in config.php, we might have an error message
    if (isset($dbConnectionError)) {
        $error = "Current configuration failed: " . $dbConnectionError;
    }
}

// Handle Database Setup Form Submission (Step 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_setup_submit'])) {
    $dbHost = trim($_POST['db_host']);
    $dbName = trim($_POST['db_name']);
    $dbUser = trim($_POST['db_user']);
    $dbPass = trim($_POST['db_pass']);

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = "Host, Database Name, and Username are required.";
    } else {
        try {
            // Test connection
            $dsn = "mysql:host=$dbHost;dbname=$dbName";
            $testPdo = new PDO($dsn, $dbUser, $dbPass);
            $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // If successful, write to .env
            if (writeEnvFile($dbHost, $dbName, $dbUser, $dbPass)) {
                // Redirect to self to reload config and proceed to step 2
                header("Location: setup.php");
                exit;
            } else {
                $error = "Connection successful, but failed to write .env file. Please check directory permissions.";
            }
        } catch (PDOException $e) {
            $error = "Database Connection Failed: " . $e->getMessage();
        }
    }
}

// Handle Admin Setup Form Submission (Step 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_submit']) && $step === 2) {
    // Process setup
    $setupComplete = $setupService->processSetup($_POST);
    
    if ($setupComplete) {
        // Get success messages
        $success = $setupService->getSuccessMessagesHtml();
        
        // Schedule self-destruct
        SetupService::selfDestruct(__FILE__);
    } else {
        // Get error message
        $error = $setupService->getError();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - CodeFiesta Admin</title>
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
</head>
<body class="bg-background text-slate-50 min-h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Background Effects -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-primary/10 blur-[100px]"></div>
        <div class="absolute -bottom-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-secondary/10 blur-[100px]"></div>
    </div>

    <div class="w-full max-w-2xl p-8 relative z-10">
        <?php if (!$setupComplete): ?>
        <!-- Setup Form -->
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">🚀 CodeFiesta Setup</h1>
                <p class="text-slate-400">
                    <?php echo ($step === 1) ? 'Configure Database Connection' : 'Create Admin Account'; ?>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-lg mb-6 text-sm" role="alert">
                <?php echo ValidationHelper::sanitizeOutput($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- Step 1: Database Configuration -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="db_host" class="block text-sm font-medium text-slate-300 mb-2">Database Host</label>
                    <input type="text" id="db_host" name="db_host" required value="<?php echo isset($_POST['db_host']) ? ValidationHelper::sanitizeOutput($_POST['db_host']) : 'localhost'; ?>" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="e.g., localhost">
                </div>
                <div>
                    <label for="db_name" class="block text-sm font-medium text-slate-300 mb-2">Database Name</label>
                    <input type="text" id="db_name" name="db_name" required value="<?php echo isset($_POST['db_name']) ? ValidationHelper::sanitizeOutput($_POST['db_name']) : ''; ?>" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="e.g., codefiesta_db">
                </div>
                <div>
                    <label for="db_user" class="block text-sm font-medium text-slate-300 mb-2">Database User</label>
                    <input type="text" id="db_user" name="db_user" required value="<?php echo isset($_POST['db_user']) ? ValidationHelper::sanitizeOutput($_POST['db_user']) : ''; ?>" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="e.g., root">
                </div>
                <div>
                    <label for="db_pass" class="block text-sm font-medium text-slate-300 mb-2">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="Database password">
                </div>

                <button type="submit" name="db_setup_submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity shadow-lg focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background">
                    Test Connection & Save
                </button>
            </form>

            <?php else: ?>
            <!-- Step 2: Admin Creation -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-300 mb-2">
                        Admin Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        minlength="3"
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]+"
                        value="<?php echo ValidationHelper::sanitizeOutput($_POST['username'] ?? ''); ?>"
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="Choose your username"
                        autocomplete="off"
                        aria-describedby="username-help">
                    <p id="username-help" class="text-slate-500 text-xs mt-1">Minimum 3 characters (letters, numbers, and underscores only)</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-2">
                        Admin Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        minlength="6"
                        maxlength="255"
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="Create a strong password"
                        autocomplete="new-password"
                        aria-describedby="password-help">
                    <p id="password-help" class="text-slate-500 text-xs mt-1">Minimum 6 characters</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-300 mb-2">
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        minlength="6"
                        maxlength="255"
                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                        placeholder="Re-enter your password"
                        autocomplete="new-password">
                </div>

                <div class="bg-blue-500/10 border border-blue-500/20 text-blue-400 px-4 py-3 rounded-lg text-sm" role="alert">
                    <strong>⚠️ Important:</strong> Save these credentials securely. You'll need them to access the admin panel.
                </div>

                <button 
                    type="submit" 
                    name="setup_submit"
                    class="w-full bg-gradient-to-r from-primary to-secondary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity shadow-lg focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background">
                    Initialize Database &amp; Create Admin
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Success Message -->
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-2xl text-center">
            <div class="mb-6">
                <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Setup Complete! 🎉</h1>
                <p class="text-slate-400 mb-6">Your database has been initialized successfully</p>
            </div>

            <div class="bg-slate-950 border border-slate-800 rounded-lg p-6 mb-6 text-left">
                <h3 class="text-sm font-semibold text-slate-300 mb-3">Setup Summary:</h3>
                <div class="space-y-1 text-sm text-slate-400">
                    <?php 
                    // Success messages are already HTML-escaped in the service
                    echo $success; 
                    ?>
                </div>
            </div>

            <div class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 px-4 py-3 rounded-lg text-sm mb-6" role="status">
                <strong>🔥 Self-destructing in <span id="countdown">5</span> seconds...</strong>
            </div>

            <p class="text-slate-500 text-sm">Redirecting to admin login page...</p>
        </div>

        <script>
            (function() {
                'use strict';
                
                let countdown = 5;
                const countdownEl = document.getElementById('countdown');
                
                const interval = setInterval(function() {
                    countdown--;
                    if (countdownEl) {
                        countdownEl.textContent = countdown;
                    }
                    
                    if (countdown <= 0) {
                        clearInterval(interval);
                        window.location.href = 'index.php';
                    }
                }, 1000);
            })();
        </script>

        <?php
        // Give output time to render before shutdown
        sleep(1);
        ?>
        <?php endif; ?>
    </div>
</body>
</html>