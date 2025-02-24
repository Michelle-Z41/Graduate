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
if (!$taskId) {
    jsonResponse(['success' => false, 'message' => 'Missing task ID']);
}

// Verify task belongs to current user
$task = getTaskById($taskId);
if (!$task || $task['user_id'] !== getCurrentUserId()) {
    jsonResponse(['success' => false, 'message' => 'Task not found'], 404);
}

$results = [];

// Handle file upload
if (isset($_FILES['textFile'])) {
    $file = $_FILES['textFile'];
    $fileName = $file['name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => '文件上传失败']);
    }

    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        jsonResponse(['success' => false, 'message' => '无法读取文件']);
    }

    if ($fileType === 'csv') {
        $delimiter = $_POST['delimiter'] ?? ',';
        $textColumn = intval($_POST['textColumn'] ?? 0);
        
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (isset($row[$textColumn])) {
                    $results[] = processText($taskId, $row[$textColumn]);
                }
            }
            fclose($handle);
        }
    } else {
        $texts = explode("\n", $content);
        foreach ($texts as $text) {
            if (trim($text) !== '') {
                $results[] = processText($taskId, $text);
            }
        }
    }
} 
// Handle direct text input
else if (isset($_POST['texts'])) {
    $texts = json_decode($_POST['texts'], true);
    if (is_array($texts)) {
        foreach ($texts as $text) {
            if (trim($text) !== '') {
                $results[] = processText($taskId, $text);
            }
        }
    }
}

if (empty($results)) {
    jsonResponse(['success' => false, 'message' => '没有可导入的文本']);
}

jsonResponse(['success' => true, 'results' => $results]);

function processText($taskId, $text) {
    $text = trim($text);
    if (empty($text)) {
        return [
            'text' => $text,
            'status' => 'error',
            'message' => '空文本'
        ];
    }

    try {
        // Check for similar existing texts
        $similarText = findSimilarApprovedText($text);
        if ($similarText) {
            // Use the approved text if found
            $sql = "INSERT INTO texts (task_id, text_key, original_text, optimized_text, status) 
                    VALUES (?, ?, ?, ?, 'approved')";
            $stmt = $GLOBALS['pdo']->prepare($sql);
            $stmt->execute([
                $taskId,
                generateUniqueKey($text),
                $text,
                $similarText['optimized_text']
            ]);
            
            return [
                'text' => $text,
                'status' => 'success',
                'message' => '已导入（使用已有优化）'
            ];
        } else {
            // Add new text for optimization
            $sql = "INSERT INTO texts (task_id, text_key, original_text) 
                    VALUES (?, ?, ?)";
            $stmt = $GLOBALS['pdo']->prepare($sql);
            $stmt->execute([
                $taskId,
                generateUniqueKey($text),
                $text
            ]);
            
            return [
                'text' => $text,
                'status' => 'success',
                'message' => '已导入（待优化）'
            ];
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate key error
            return [
                'text' => $text,
                'status' => 'warning',
                'message' => '重复文本'
            ];
        }
        return [
            'text' => $text,
            'status' => 'error',
            'message' => '导入失败'
        ];
    }
} 