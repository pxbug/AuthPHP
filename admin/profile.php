<?php
// change_password.php

// 开启会话
session_start();

// 检查登录状态
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    die('拒绝访问：请先登录');
}

// 引入数据库连接文件
require_once '../config/db.php';

// 初始化消息
$error = '';
$success = '';

// 获取当前登录用户的用户名和用户ID
$current_username = $_SESSION['username'];
$current_userid = $_SESSION['userid'];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取表单数据
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // 验证用户名（如果提供了新用户名）
        if (!empty($new_username)) {
            if (strlen($new_username) < 4) {
                throw new Exception('用户名至少需要4个字符');
            }
            
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->execute(['username' => $new_username, 'id' => $current_userid]);
            if ($stmt->fetch()) {
                throw new Exception('该用户名已被使用');
            }
        }

        // 验证密码（如果提供了新密码）
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                throw new Exception('密码至少需要6个字符');
            }

            if ($new_password !== $confirm_password) {
                throw new Exception('两次输入的密码不一致');
            }
        }

        // 如果没有提供新用户名，则使用当前用户名
        if (empty($new_username)) {
            $new_username = $current_username;
        }

        // 准备更新语句
        $sql = "UPDATE users SET username = :username";
        $params = ['username' => $new_username, 'id' => $current_userid];

        // 如果有新密码，更新密码
        if (!empty($new_password)) {
            // 使用md5进行加密
            $new_password_hash = md5($new_password);
            $sql .= ", password_hash = :password_hash";
            $params['password_hash'] = $new_password_hash;
        }

        $sql .= " WHERE id = :id";

        // 更新数据库中的用户名和密码
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // 更新会话中的用户名
        $_SESSION['username'] = $new_username;

        // 提示修改成功
        $success = '账号信息更新成功！';
        $current_username = $new_username; // 更新显示的当前用户名
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账号设置 - 云智授权</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #34495E;
            --text-color: #2C3E50;
            --bg-color: #FFF5EA;
            --input-bg: #FFFFFF;
            --border-color: #2C3E50;
            --shadow: 0 4px 6px rgba(44, 62, 80, 0.1);
            --radius: 12px;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .card {
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 4px 4px 0 var(--border-color);
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .card-header h2 {
            color: var(--text-color);
            font-size: 1.8rem;
            font-weight: 900;
            margin: 0;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border: 2px solid;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #4ECDC4;
            border-color: #4ECDC4;
            color: white;
        }

        .alert-error {
            background: #FF6B6B;
            border-color: #FF6B6B;
            color: white;
        }

        .current-user {
            background: var(--bg-color);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            background: var(--input-bg);
            color: var(--text-color);
            box-shadow: 4px 4px 0 var(--border-color);
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 4px 4px 0 var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }

        .btn:active {
            transform: translate(4px, 4px);
            box-shadow: none;
        }

        .btn i {
            font-size: 1.1rem;
        }

        @media (max-width: 576px) {
            .card {
                padding: 1.5rem;
            }

            .card-header h2 {
                font-size: 1.5rem;
            }

            .form-control {
                padding: 0.8rem 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>账号设置</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="current-user">
                <i class="fas fa-user"></i>
                当前用户名：<?= htmlspecialchars($current_username) ?>
            </div>

            <form method="post">
                <div class="form-group">
                    <label for="new_username">
                        <i class="fas fa-user-edit"></i>
                        新用户名 (留空则不修改)
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="new_username" 
                           name="new_username" 
                           minlength="4" 
                           placeholder="请输入新用户名（至少4个字符）"
                           value="<?= isset($_POST['new_username']) ? htmlspecialchars($_POST['new_username']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-key"></i>
                        新密码 (留空则不修改)
                    </label>
                    <input type="password" 
                           class="form-control" 
                           id="new_password" 
                           name="new_password" 
                           minlength="6" 
                           placeholder="请输入新密码（至少6个字符）">
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        确认新密码
                    </label>
                    <input type="password" 
                           class="form-control" 
                           id="confirm_password" 
                           name="confirm_password" 
                           minlength="6" 
                           placeholder="请再次输入新密码">
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i>
                    保存修改
                </button>
            </form>
        </div>
    </div>

    <script>
        // 自动关闭警告消息
        document.addEventListener('DOMContentLoaded', function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 3000);
            });
        });
    </script>
</body>
</html>