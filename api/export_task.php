<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$taskId = intval($_GET['id'] ?? 0);
if (!$taskId) {
    jsonResponse(['error' => 'Missing task ID']);
}

// Verify task belongs to current user
$task = getTaskById($taskId);
if (!$task || $task['user_id'] !== getCurrentUserId()) {
    jsonResponse(['error' => 'Task not found'], 404);
}

$format = strtolower($_GET['format'] ?? 'json');
if (!in_array($format, ['json', 'csv'])) {
    $format = 'json';
}

try {
    $content = exportTaskTexts($taskId, $format);
    
    // Set appropriate headers
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="task_' . $taskId . '_texts.csv"');
    } else {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="task_' . $taskId . '_texts.json"');
    }
    
    echo $content;
    exit;
} catch (Exception $e) {
    jsonResponse(['error' => 'Export failed: ' . $e->getMessage()], 500);
} 