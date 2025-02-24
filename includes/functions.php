<?php
require_once __DIR__ . '/../config/database.php';

function generateUniqueKey($text) {
    // Generate a unique key based on text content and timestamp
    return md5($text . microtime());
}

function calculateSimilarity($text1, $text2) {
    // Simple similarity calculation using levenshtein distance
    // For production, you might want to use more sophisticated algorithms
    $maxLength = max(strlen($text1), strlen($text2));
    if ($maxLength === 0) return 100;
    return (1 - levenshtein($text1, $text2) / $maxLength) * 100;
}

function findSimilarApprovedText($text, $threshold = 90) {
    global $pdo;
    
    $sql = "SELECT t.* FROM texts t 
            WHERE t.status = 'approved'";
    $stmt = $pdo->query($sql);
    $approvedTexts = $stmt->fetchAll();
    
    foreach ($approvedTexts as $approvedText) {
        $similarity = calculateSimilarity($text, $approvedText['original_text']);
        if ($similarity >= $threshold) {
            return $approvedText;
        }
    }
    
    return null;
}

function analyzeText($text) {
    $analysis = [
        'length' => mb_strlen($text),
        'word_count' => str_word_count($text),
        'issues' => [],
        'suggestions' => []
    ];

    // 检查文本长度
    if ($analysis['length'] > 500) {
        $analysis['issues'][] = '文本过长，建议分段';
    } else if ($analysis['length'] < 10) {
        $analysis['issues'][] = '文本过短，可能缺少必要信息';
    }

    // 检查标点符号使用
    if (preg_match('/[。，、；：？！,.;:?!]{2,}/', $text)) {
        $analysis['issues'][] = '存在连续标点符号';
    }

    // 检查中英文混用
    if (preg_match('/[\x{4e00}-\x{9fa5}]+.*[a-zA-Z]+|[a-zA-Z]+.*[\x{4e00}-\x{9fa5}]+/u', $text)) {
        $analysis['issues'][] = '中英文混用，建议统一使用中文';
    }

    // 检查括号匹配
    $brackets = ['(' => ')', '（' => '）', '[' => ']', '【' => '】', '{' => '}'];
    $stack = [];
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($chars as $char) {
        if (in_array($char, array_keys($brackets))) {
            array_push($stack, $char);
        } else if (in_array($char, array_values($brackets))) {
            if (empty($stack) || $brackets[array_pop($stack)] !== $char) {
                $analysis['issues'][] = '括号不匹配';
                break;
            }
        }
    }
    if (!empty($stack)) {
        $analysis['issues'][] = '括号不匹配';
    }

    // 检查常见的不规范用语
    $irregularTerms = [
        '/不可以/' => '不能',
        '/不好的/' => '不良的',
        '/进行/' => '',
        '/当中/' => '中',
        '/有着/' => '有',
        '/看到/' => '看见',
    ];

    foreach ($irregularTerms as $pattern => $suggestion) {
        if (preg_match($pattern, $text)) {
            $analysis['issues'][] = "发现不规范用语：" . trim($pattern, '/');
            if ($suggestion) {
                $analysis['suggestions'][] = "建议使用：" . $suggestion;
            }
        }
    }

    // 检查重复词语
    preg_match_all('/(.{2,})\1+/u', $text, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $word) {
            $analysis['issues'][] = "存在重复词语：" . $word;
        }
    }

    // 检查拼接词条
    if (preg_match('/\{[^\}]+\}|\[[^\]]+\]|\$\{[^\}]+\}/', $text)) {
        $analysis['issues'][] = '存在拼接词条，建议改写为完整句子';
    }

    return $analysis;
}

function optimizeText($text) {
    $analysis = analyzeText($text);
    $optimizedText = $text;
    
    // 根据分析结果优化文本
    if (!empty($analysis['issues'])) {
        // 替换不规范用语
        $irregularTerms = [
            '/不可以/' => '不能',
            '/不好的/' => '不良的',
            '/进行/' => '',
            '/当中/' => '中',
            '/有着/' => '有',
            '/看到/' => '看见',
        ];
        
        foreach ($irregularTerms as $pattern => $replacement) {
            $optimizedText = preg_replace($pattern, $replacement, $optimizedText);
        }
        
        // 处理中英文混用
        if (preg_match('/[\x{4e00}-\x{9fa5}]+.*[a-zA-Z]+|[a-zA-Z]+.*[\x{4e00}-\x{9fa5}]+/u', $text)) {
            // TODO: 调用翻译API将英文转换为中文
        }
        
        // 处理拼接词条
        $optimizedText = preg_replace('/\{([^\}]+)\}/', '$1', $optimizedText);
        $optimizedText = preg_replace('/\[([^\]]+)\]/', '$1', $optimizedText);
        $optimizedText = preg_replace('/\$\{([^\}]+)\}/', '$1', $optimizedText);
        
        // 处理重复标点
        $optimizedText = preg_replace('/[。，、；：？！,.;:?!]{2,}/', '$0', $optimizedText);
    }
    
    return [
        'optimized_text' => $optimizedText,
        'analysis' => $analysis
    ];
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getTaskById($taskId) {
    global $pdo;
    
    $sql = "SELECT * FROM tasks WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    return $stmt->fetch();
}

function getTextsByTaskId($taskId) {
    global $pdo;
    
    $sql = "SELECT * FROM texts WHERE task_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

function createTask($userId, $taskName, $description) {
    global $pdo;
    
    $sql = "INSERT INTO tasks (user_id, task_name, description) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $taskName, $description]);
}

function addTextToTask($taskId, $text) {
    global $pdo;
    
    $key = generateUniqueKey($text);
    $sql = "INSERT INTO texts (task_id, text_key, original_text) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$taskId, $key, $text]);
}

function updateTextStatus($textId, $status) {
    global $pdo;
    
    $sql = "UPDATE texts SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$status, $textId]);
}

function getTextAnalysis($textId) {
    global $pdo;
    
    $sql = "SELECT * FROM texts WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$textId]);
    $text = $stmt->fetch();
    
    if (!$text) {
        return null;
    }
    
    return analyzeText($text['original_text']);
}

function getTaskStatistics($taskId) {
    global $pdo;
    
    $sql = "SELECT 
                COUNT(*) as total_texts,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_texts,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_texts,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_texts,
                MIN(created_at) as start_date,
                MAX(updated_at) as last_update
            FROM texts 
            WHERE task_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    return $stmt->fetch();
}

function exportTaskTexts($taskId, $format = 'json') {
    global $pdo;
    
    $sql = "SELECT * FROM texts WHERE task_id = ? ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    $texts = $stmt->fetchAll();
    
    switch ($format) {
        case 'csv':
            $output = fopen('php://temp', 'r+');
            fputcsv($output, ['ID', '原文', '优化后文本', '状态', '创建时间', '更新时间']);
            foreach ($texts as $text) {
                fputcsv($output, [
                    $text['id'],
                    $text['original_text'],
                    $text['optimized_text'],
                    $text['status'],
                    $text['created_at'],
                    $text['updated_at']
                ]);
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            return $csv;
            
        case 'json':
        default:
            return json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} 