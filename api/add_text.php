<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$taskId = intval($_POST['task_id'] ?? 0);
$text = trim($_POST['text'] ?? '');

if (!$taskId || !$text) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields']);
}

// Verify task belongs to current user
$task = getTaskById($taskId);
if (!$task || $task['user_id'] !== getCurrentUserId()) {
    jsonResponse(['success' => false, 'message' => 'Task not found'], 404);
}

try {
    // Check for similar existing texts
    $similarText = findSimilarApprovedText($text);
    if ($similarText) {
        // Use the approved text if found
        $sql = "INSERT INTO texts (task_id, text_key, original_text, optimized_text, status) 
                VALUES (?, ?, ?, ?, 'approved')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $taskId,
            generateUniqueKey($text),
            $text,
            $similarText['optimized_text']
        ]);
    } else {
        // Add new text for optimization
        $sql = "INSERT INTO texts (task_id, text_key, original_text) 
                VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $taskId,
            generateUniqueKey($text),
            $text
        ]);
    }
    
    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
} 