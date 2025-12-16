<?php

/**
 * Ensure the popups table exists with required columns.
 */
function ensurePopupsTable(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS popups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            trigger_type VARCHAR(50) DEFAULT 'exit',
            trigger_value INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $columns = $pdo->query("SHOW COLUMNS FROM popups")->fetchAll(PDO::FETCH_COLUMN);
        $columns = array_map('strtolower', $columns);

        if (!in_array('trigger_type', $columns, true)) {
            $pdo->exec("ALTER TABLE popups ADD COLUMN trigger_type VARCHAR(50) DEFAULT 'exit' AFTER content");
        }

        if (!in_array('trigger_value', $columns, true)) {
            $pdo->exec("ALTER TABLE popups ADD COLUMN trigger_value INT DEFAULT 0 AFTER trigger_type");
        }

        if (!in_array('is_active', $columns, true)) {
            $pdo->exec("ALTER TABLE popups ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER trigger_value");
        }

        if (!in_array('created_at', $columns, true)) {
            $pdo->exec("ALTER TABLE popups ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
    } catch (PDOException $e) {
        if (function_exists('logError')) {
            logError("Popup table ensure failed: " . $e->getMessage());
        }
    }
}
