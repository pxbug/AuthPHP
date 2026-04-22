<?php
// 开启会话
session_start();

// 检查登录状态
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    die('拒绝访问：请先登录');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logger.php';

// 添加代理用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_proxy'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    
    if (empty($username) || empty($password) || empty($email)) {
        $_SESSION['error'] = "请填写所有字段";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "请输入有效的邮箱地址";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM proxy_users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "用户名已存在";
                $pdo->rollBack();
            } else {
                // 检查邮箱是否已存在
                $stmt = $pdo->prepare("SELECT id FROM proxy_users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "邮箱已被使用";
                    $pdo->rollBack();
                } else {
                    // 插入新代理用户
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO proxy_users (username, password, email, status) VALUES (?, ?, ?, 1)");
                    if ($stmt->execute([$username, $hashedPassword, $email])) {
                        $proxyId = $pdo->lastInsertId();
                        
                        // 记录日志
                        addSystemLog(
                            LOG_TYPE_ADMIN,
                            '添加代理',
                            json_encode([
                                'proxy_id' => $proxyId,
                                'username' => $username,
                                'email' => $email
                            ])
                        );
                        
                        $pdo->commit();
                        $_SESSION['success'] = "代理用户添加成功";
                    } else {
                        $pdo->rollBack();
                        $_SESSION['error'] = "添加代理用户失败";
                    }
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "数据库错误: " . $e->getMessage();
            error_log("Add proxy error: " . $e->getMessage());
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// 删除代理用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = (int)$_POST['user_id'];
    try {
        $pdo->beginTransaction();
        
        // 先删除关联的卡密
        $stmt = $pdo->prepare("DELETE FROM card_keys WHERE proxy_id = ?");
        $stmt->execute([$userId]);
        
        // 再删除代理用户
        $stmt = $pdo->prepare("DELETE FROM proxy_users WHERE id = ?");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        $_SESSION['success'] = "代理用户删除成功";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "删除失败: " . $e->getMessage();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// 获取代理用户列表
$proxyUsers = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM proxy_users ORDER BY id DESC");
    $proxyUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "获取用户列表失败: " . $e->getMessage();
}

// 显示消息后清除
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>代理用户管理</title>
    <style>
        /* 基础样式 */
        :root {
            --primary-color: #2C3E50;
            --primary-dark: #1a2936;
            --success-color: #4ECDC4;
            --danger-color: #FF6B6B;
            --danger-dark: #ff3838;
            --secondary-color: #34495E;
            --light-color: #FFF5EA;
            --border-color: #2C3E50;
            --text-color: #2C3E50;
            --text-muted: #6c757d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            color: var(--text-color);
            background-color: var(--light-color);
            padding: 20px 0;
            min-height: 100vh;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* 布局样式 */
        .container {
            width: 100%;
            padding: 0 15px;
            margin: 0 auto;
        }
        
        /* 卡片样式 */
        .card {
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 4px 4px 0 var(--border-color);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px;
            background-color: #fff;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 0;
        }
        
        /* 表格样式 */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }
        
        .table th {
            font-weight: 600;
            color: var(--text-color);
            background-color: var(--light-color);
            white-space: nowrap;
        }
        
        .table tr:hover {
            background-color: rgba(44, 62, 80, 0.05);
        }
        
        /* 按钮样式 */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            background: var(--light-color);
            color: var(--text-color);
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .btn:active {
            transform: translate(4px, 4px);
            box-shadow: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: #fff;
        }
        
        .btn-danger:hover {
            background-color: var(--danger-dark);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 13px;
        }
        
        /* 表单样式 */
        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            line-height: 1.5;
            color: var(--text-color);
            background-color: #fff;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .form-control:focus {
            outline: none;
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* 工具类 */
        .d-flex {
            display: flex;
        }
        
        .justify-content-between {
            justify-content: space-between;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        .py-3 {
            padding-top: 16px;
            padding-bottom: 16px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--text-muted);
        }
        
        /* 搜索框 */
        .search-box {
            position: relative;
            width: 250px;
        }
        
        .search-box input {
            padding-left: 35px;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        /* 提示信息 */
        .alert {
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            position: relative;
            border: 2px solid;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: #fff;
        }
        
        .alert-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: #fff;
        }
        
        /* 模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal.show {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 30px;
        }
        
        .modal-dialog {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .modal-header {
            padding: 16px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .modal-body {
            padding: 16px;
        }
        
        .modal-footer {
            padding: 12px 16px;
            border-top: 2px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* 响应式设计 */
        @media (min-width: 576px) {
            .container {
                max-width: 540px;
            }
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 720px;
            }
        }
        
        @media (min-width: 992px) {
            .container {
                max-width: 960px;
            }
        }
        
        @media (min-width: 1200px) {
            .container {
                max-width: 1140px;
            }
        }
        
        @media (max-width: 767px) {
            body {
                padding: 10px;
            }
            
            .card-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .page-title {
                font-size: 20px;
            }
        }
        
        @media (max-width: 575px) {
            .container {
                padding: 0 10px;
            }
            
            .table-responsive {
                border: 1px solid var(--border-color);
                border-radius: 4px;
            }
            
            .table {
                min-width: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-title">代理管理</h1>
            <button class="btn btn-primary" id="addUserBtn">添加代理</button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
                <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="m-0">代理用户列表</h2>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" class="form-control" placeholder="搜索用户名或邮箱...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="proxyTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proxyUsers as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('确定要删除此代理吗？所有关联卡密也将被删除！');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">删除</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($proxyUsers)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">暂无代理用户数据</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 添加用户模态框 -->
    <div class="modal" id="addUserModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加代理用户</h5>
                    <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                        <button type="submit" name="add_proxy" class="btn btn-primary">确认添加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // 显示模态框
        document.getElementById('addUserBtn').addEventListener('click', function() {
            document.getElementById('addUserModal').classList.add('show');
        });
        
        // 关闭模态框
        function closeModal() {
            document.getElementById('addUserModal').classList.remove('show');
        }
        
        // 点击模态框外部关闭
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('addUserModal')) {
                closeModal();
            }
        });
        
        // 搜索功能
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#proxyTable tbody tr');
            
            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                
                if (username.includes(searchValue) || email.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // 如果有错误消息且是添加操作，显示模态框
        <?php if ($error && isset($_POST['add_proxy'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('addUserModal').classList.add('show');
            });
        <?php endif; ?>
    </script>
</body>
</html>