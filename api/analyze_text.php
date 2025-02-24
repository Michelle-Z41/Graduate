<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$textId = intval($_POST['text_id'] ?? 0);
$text = trim($_POST['text'] ?? '');

if (!$textId && !$text) {
    jsonResponse(['success' => false, 'message' => '请提供文本ID或文本内容']);
}

try {
    if ($textId) {
        // 分析已存在的文本
        $analysis = getTextAnalysis($textId);
        if (!$analysis) {
            jsonResponse(['success' => false, 'message' => '文本不存在']);
        }
    } else {
        // 分析新文本
        $analysis = analyzeText($text);
    }
    
    // 获取优化建议
    $optimization = optimizeText($textId ? getTextById($textId)['original_text'] : $text);
    
    jsonResponse([
        'success' => true,
        'analysis' => $analysis,
        'optimization' => $optimization
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => '分析失败：' . $e->getMessage()], 500);
} 