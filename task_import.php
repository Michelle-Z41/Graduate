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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量导入文本 - 本地化内容优化工具</title>
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
            <h2>批量导入文本 - <?php echo htmlspecialchars($task['task_name']); ?></h2>
            <a href="task_detail.php?id=<?php echo $taskId; ?>" class="btn btn-secondary">返回任务</a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">文本文件导入</h5>
                        <form id="fileUploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="textFile" class="form-label">选择文本文件 (.txt, .csv)</label>
                                <input type="file" class="form-control" id="textFile" name="textFile" accept=".txt,.csv">
                                <div class="form-text">支持TXT文件（每行一个文本）或CSV文件（包含文本列）</div>
                            </div>
                            <div class="mb-3">
                                <label for="delimiter" class="form-label">CSV分隔符（仅CSV文件）</label>
                                <input type="text" class="form-control" id="delimiter" name="delimiter" value="," maxlength="1">
                            </div>
                            <div class="mb-3">
                                <label for="textColumn" class="form-label">文本列索引（仅CSV文件）</label>
                                <input type="number" class="form-control" id="textColumn" name="textColumn" value="0" min="0">
                            </div>
                            <button type="submit" class="btn btn-primary">上传文件</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">批量文本输入</h5>
                        <form id="batchTextForm">
                            <div class="mb-3">
                                <label for="batchText" class="form-label">输入多行文本（每行一个）</label>
                                <textarea class="form-control" id="batchText" name="batchText" rows="10" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">导入文本</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">导入进度</h5>
                <div class="progress mb-3" style="height: 20px;">
                    <div id="importProgress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="importStatus" class="alert d-none"></div>
                <div id="importResults" class="mt-3">
                    <h6>导入结果：</h6>
                    <ul id="resultsList" class="list-group">
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const taskId = <?php echo $taskId; ?>;

            // 文件上传处理
            $('#fileUploadForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('task_id', taskId);
                
                uploadTexts(formData);
            });

            // 批量文本处理
            $('#batchTextForm').submit(function(e) {
                e.preventDefault();
                const texts = $('#batchText').val().split('\n').filter(text => text.trim());
                
                if (texts.length === 0) {
                    showStatus('请输入至少一行文本', 'danger');
                    return;
                }

                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('texts', JSON.stringify(texts));
                
                uploadTexts(formData);
            });

            function uploadTexts(formData) {
                $('#importProgress').css('width', '0%');
                $('#importStatus').removeClass('d-none alert-success alert-danger').addClass('alert-info');
                $('#resultsList').empty();
                
                $.ajax({
                    url: 'api/import_texts.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                $('#importProgress').css('width', percent + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        if (response.success) {
                            showStatus('导入完成！', 'success');
                            response.results.forEach(function(result) {
                                $('#resultsList').append(`
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="text-truncate me-3">${result.text}</div>
                                            <span class="badge ${result.status === 'success' ? 'bg-success' : 'bg-warning'}">
                                                ${result.message}
                                            </span>
                                        </div>
                                    </li>
                                `);
                            });
                        } else {
                            showStatus('导入失败：' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showStatus('导入失败，请重试', 'danger');
                    }
                });
            }

            function showStatus(message, type) {
                $('#importStatus')
                    .removeClass('d-none alert-info alert-success alert-danger')
                    .addClass('alert-' + type)
                    .text(message);
            }
        });
    </script>
</body>
</html> 