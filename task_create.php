<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskName = sanitizeInput($_POST['task_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if ($taskName) {
        if (createTask(getCurrentUserId(), $taskName, $description)) {
            $success = '任务创建成功！';
        } else {
            $error = '任务创建失败，请重试';
        }
    } else {
        $error = '请输入任务名称';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>创建任务 - 本地化内容优化工具</title>
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
                        <a class="nav-link active" href="task_create.php">创建任务</a>
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
        <h2>创建新任务</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="task_create.php">
                    <div class="mb-3">
                        <label for="task_name" class="form-label">任务名称</label>
                        <input type="text" class="form-control" id="task_name" name="task_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">任务描述</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">创建任务</button>
                    <a href="index.php" class="btn btn-secondary">返回</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 