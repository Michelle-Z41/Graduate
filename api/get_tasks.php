<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$userId = getCurrentUserId();

try {
    $sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll();
    
    jsonResponse($tasks);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error'], 500);
} 