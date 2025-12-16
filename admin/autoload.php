<?php

/**
 * Autoloader for CodeFiesta Admin Helper Classes
 *
 * Automatically loads helper classes from the includes directory
 * without requiring manual require_once statements.
 *
 * Usage:
 *   require_once 'autoload.php';
 *   $validator = new ValidationHelper();
 *
 * @package    CodeFiesta
 * @subpackage Admin
 * @author     CodeFiesta Development Team
 * @version    1.0.0
 */

spl_autoload_register(function ($class) {
    // Define the base directory for helper classes
    $baseDir = __DIR__ . '/includes/';
    
    // List of helper classes and their file names
    $classes = [
        'ValidationHelper' => 'ValidationHelper.php',
        'SecurityHelper' => 'SecurityHelper.php',
        'DatabaseManager' => 'DatabaseManager.php',
        'SetupService' => 'SetupService.php'
    ];
    
    // Check if the requested class is one of our helper classes
    if (isset($classes[$class])) {
        $file = $baseDir . $classes[$class];
        
        // Check if file exists before requiring
        if (file_exists($file)) {
            require_once $file;
            return true;
        } else {
            error_log("Autoloader: File not found for class {$class}: {$file}");
            return false;
        }
    }
    
    return false;
});

// Optional: Log that autoloader was loaded (for debugging)
// error_log("CodeFiesta Autoloader loaded successfully");
