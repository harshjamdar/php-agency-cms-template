<?php
require_once '../config.php';

header('Content-Type: application/json');

// Simple tracking endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['type']) && isset($data['variant_id'])) {
        try {
            $variantId = (int)$data['variant_id'];
            
            if ($data['type'] === 'view') {
                $stmt = $pdo->prepare("UPDATE ab_variants SET views = views + 1 WHERE id = ?");
                $stmt->execute([$variantId]);
            } elseif ($data['type'] === 'click') {
                $stmt = $pdo->prepare("UPDATE ab_variants SET conversions = conversions + 1 WHERE id = ?");
                $stmt->execute([$variantId]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
}
?>