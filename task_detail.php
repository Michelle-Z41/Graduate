<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$taskId = intval($_GET['id'] ?? 0);
if (!$taskId) {
    header('Location: index.php');
    exit();
}

$task = getTaskById($taskId);
if (!$task || $task['user_id'] !== getCurrentUserId()) {
    header('Location: index.php');
    exit();
}

$texts = getTextsByTaskId($taskId);
$stats = getTaskStatistics($taskId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任务详情 - 本地化内容优化工具</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">本地化优化工具</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="task_create.php">创建任务</a>
                    </li>
                </ul>
                <span class="navbar-text">
                    欢迎, <?php echo htmlspecialchars($_SESSION['username']); ?> |
                    <a href="logout.php" class="text-white">退出</a>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo htmlspecialchars($task['task_name']); ?></h2>
            <div>
                <a href="task_import.php?id=<?php echo $taskId; ?>" class="btn btn-success me-2">
                    批量导入
                </a>
                <div class="btn-group">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        导出
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="api/export_task.php?id=<?php echo $taskId; ?>&format=json">JSON格式</a></li>
                        <li><a class="dropdown-item" href="api/export_task.php?id=<?php echo $taskId; ?>&format=csv">CSV格式</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">任务信息</h5>
                        <p class="card-text"><?php echo htmlspecialchars($task['description'] ?: '无描述'); ?></p>
                        <p class="card-text">
                            <small class="text-muted">
                                状态: <?php echo htmlspecialchars($task['status']); ?> |
                                创建时间: <?php echo htmlspecialchars($task['created_at']); ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">任务统计</h5>
                        <div class="row text-center">
                            <div class="col">
                                <h3><?php echo $stats['total_texts']; ?></h3>
                                <p>总文本数</p>
                            </div>
                            <div class="col">
                                <h3><?php echo $stats['approved_texts']; ?></h3>
                                <p>已审核</p>
                            </div>
                            <div class="col">
                                <h3><?php echo $stats['pending_texts']; ?></h3>
                                <p>待处理</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">文本列表</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 35%">原文</th>
                                <th style="width: 35%">优化后文本</th>
                                <th style="width: 15%">状态</th>
                                <th style="width: 15%">操作</th>
                            </tr>
                        </thead>
                        <tbody id="textList">
                            <?php foreach ($texts as $text): ?>
                            <tr>
                                <td>
                                    <div class="text-break"><?php echo htmlspecialchars($text['original_text']); ?></div>
                                    <button class="btn btn-sm btn-link analyze-btn" data-text-id="<?php echo $text['id']; ?>">
                                        分析
                                    </button>
                                </td>
                                <td>
                                    <div class="text-break"><?php echo htmlspecialchars($text['optimized_text'] ?: '未优化'); ?></div>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo $text['status']; ?>">
                                        <?php echo htmlspecialchars($text['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($text['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-warning optimize-btn" data-text-id="<?php echo $text['id']; ?>">
                                        优化
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Modal -->
    <div class="modal fade" id="analysisModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">文本分析</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>基本信息</h6>
                        <ul class="list-unstyled">
                            <li>文本长度：<span id="textLength"></span> 字符</li>
                            <li>词数：<span id="wordCount"></span></li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <h6>发现的问题</h6>
                        <ul id="issuesList" class="list-group">
                        </ul>
                    </div>
                    <div class="mb-3">
                        <h6>优化建议</h6>
                        <ul id="suggestionsList" class="list-group">
                        </ul>
                    </div>
                    <div>
                        <h6>优化预览</h6>
                        <div id="optimizedPreview" class="border rounded p-3 bg-light">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" id="applyOptimization">应用优化</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const analysisModal = new bootstrap.Modal(document.getElementById('analysisModal'));
            let currentTextId = null;
            let currentOptimization = null;

            // 分析按钮点击事件
            $('.analyze-btn').click(function() {
                const textId = $(this).data('text-id');
                currentTextId = textId;
                
                $.ajax({
                    url: 'api/analyze_text.php',
                    method: 'POST',
                    data: { text_id: textId },
                    success: function(response) {
                        if (response.success) {
                            showAnalysis(response.analysis, response.optimization);
                            currentOptimization = response.optimization;
                            analysisModal.show();
                        } else {
                            alert('分析失败：' + response.message);
                        }
                    },
                    error: function() {
                        alert('分析请求失败');
                    }
                });
            });

            // 优化按钮点击事件
            $('.optimize-btn').click(function() {
                const textId = $(this).data('text-id');
                const btn = $(this);
                
                btn.prop('disabled', true).text('优化中...');
                
                $.ajax({
                    url: 'api/optimize_text.php',
                    method: 'POST',
                    data: { text_id: textId },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('优化失败：' + response.message);
                            btn.prop('disabled', false).text('优化');
                        }
                    },
                    error: function() {
                        alert('优化失败');
                        btn.prop('disabled', false).text('优化');
                    }
                });
            });

            // 应用优化按钮点击事件
            $('#applyOptimization').click(function() {
                if (!currentTextId || !currentOptimization) return;
                
                $.ajax({
                    url: 'api/optimize_text.php',
                    method: 'POST',
                    data: {
                        text_id: currentTextId,
                        optimized_text: currentOptimization.optimized_text
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('应用优化失败：' + response.message);
                        }
                    },
                    error: function() {
                        alert('应用优化失败');
                    }
                });
            });

            function showAnalysis(analysis, optimization) {
                // 显示基本信息
                $('#textLength').text(analysis.length);
                $('#wordCount').text(analysis.word_count);
                
                // 显示问题列表
                const issuesHtml = analysis.issues.map(issue => 
                    `<li class="list-group-item list-group-item-warning">${issue}</li>`
                ).join('');
                $('#issuesList').html(issuesHtml || '<li class="list-group-item">未发现问题</li>');
                
                // 显示建议列表
                const suggestionsHtml = analysis.suggestions.map(suggestion => 
                    `<li class="list-group-item list-group-item-info">${suggestion}</li>`
                ).join('');
                $('#suggestionsList').html(suggestionsHtml || '<li class="list-group-item">无优化建议</li>');
                
                // 显示优化预览
                $('#optimizedPreview').text(optimization.optimized_text);
            }
        });
    </script>
</body>
</html> 