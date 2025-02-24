<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// requireLogin();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>本地化内容优化工具</title>
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
        <h2>我的任务</h2>
        <div class="row" id="taskList">
            <!-- Tasks will be loaded here via AJAX -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load tasks when page loads
            loadTasks();

            function loadTasks() {
                $.ajax({
                    url: 'api/get_tasks.php',
                    method: 'GET',
                    success: function(response) {
                        let tasksHtml = '';
                        response.forEach(function(task) {
                            tasksHtml += `
                                <div class="col-md-4 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">${task.task_name}</h5>
                                            <p class="card-text">${task.description || '无描述'}</p>
                                            <p class="card-text">
                                                <small class="text-muted">状态: ${task.status}</small>
                                            </p>
                                            <a href="task_detail.php?id=${task.id}" class="btn btn-primary">查看详情</a>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        $('#taskList').html(tasksHtml || '<div class="col">暂无任务</div>');
                    },
                    error: function() {
                        alert('加载任务失败');
                    }
                });
            }
        });
    </script>
</body>
</html> 