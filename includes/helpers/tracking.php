<?php
/**
 * Page View Tracking
 * Simple analytics to track page views
 */

function trackPageView($pageName) {
    global $pdo;
    
    if (!isset($pdo)) {
        return false;
    }
    
    try {
        // Check if page exists
        $stmt = $pdo->prepare("SELECT id, view_count FROM page_views WHERE page_name = ?");
        $stmt->execute([$pageName]);
        $page = $stmt->fetch();
        
        if ($page) {
            // Update view count
            $stmt = $pdo->prepare("UPDATE page_views SET view_count = view_count + 1, last_viewed = NOW() WHERE page_name = ?");
            $stmt->execute([$pageName]);
        } else {
            // Insert new page
            $stmt = $pdo->prepare("INSERT INTO page_views (page_name, view_count) VALUES (?, 1)");
            $stmt->execute([$pageName]);
        }
        
        return true;
    } catch (PDOException $e) {
        // Silently fail - don't break the page if tracking fails
        error_log("Page view tracking error: " . $e->getMessage());
        return false;
    }
}

function getPageViews($pageName = null) {
    global $pdo;
    
    if (!isset($pdo)) {
        return 0;
    }
    
    try {
        if ($pageName) {
            // Get views for specific page
            $stmt = $pdo->prepare("SELECT view_count FROM page_views WHERE page_name = ?");
            $stmt->execute([$pageName]);
            $result = $stmt->fetch();
            return $result ? $result['view_count'] : 0;
        } else {
            // Get total views
            $stmt = $pdo->query("SELECT SUM(view_count) as total FROM page_views");
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        }
    } catch (PDOException $e) {
        return 0;
    }
}

function getTopPages($limit = 10) {
    global $pdo;
    
    if (!isset($pdo)) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT page_name, view_count, last_viewed FROM page_views ORDER BY view_count DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
?>
