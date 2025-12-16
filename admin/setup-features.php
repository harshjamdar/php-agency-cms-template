<?php
require_once 'config.php';
require_once 'security.php';
checkLogin();

try {
    // Popups Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS popups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        trigger_type VARCHAR(50) DEFAULT 'exit', -- exit, timer, scroll
        trigger_value INT DEFAULT 0, -- seconds or percentage
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // A/B Tests Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ab_tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_key VARCHAR(50) UNIQUE NOT NULL, -- e.g., 'hero_headline'
        name VARCHAR(255) NOT NULL,
        is_active TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // A/B Variants Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ab_variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_id INT NOT NULL,
        variant_name VARCHAR(10) NOT NULL, -- A, B
        content TEXT NOT NULL,
        views INT DEFAULT 0,
        conversions INT DEFAULT 0,
        FOREIGN KEY (test_id) REFERENCES ab_tests(id) ON DELETE CASCADE
    )");

    // Insert default Hero Headline Test if not exists
    $stmt = $pdo->prepare("SELECT id FROM ab_tests WHERE test_key = 'hero_headline'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO ab_tests (test_key, name, is_active) VALUES ('hero_headline', 'Hero Headline Experiment', 0)");
        $testId = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO ab_variants (test_id, variant_name, content) VALUES ($testId, 'A', 'Transform Your Digital Presence')");
        $pdo->exec("INSERT INTO ab_variants (test_id, variant_name, content) VALUES ($testId, 'B', 'Build Software That Scales')");
    }

    echo "Database tables created successfully.";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>