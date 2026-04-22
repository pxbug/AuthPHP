<?php
// login.php

// 开启会话
session_start();

// 引入数据库连接文件
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logger.php';

// 如果已经登录，直接跳转到管理面板
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: protected.php');
    exit;
}

// 初始化错误消息
$error = '';

// 检查表单是否提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取用户输入的用户名和密码
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        try {
            // 获取数据库连接
            $pdo = require __DIR__ . '/../config/db.php';

            // 查询数据库中的用户
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 验证用户名和密码
            if ($user && md5($password) === $user['password_hash']) {
                // 登录成功，设置会话变量
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['created'] = time();
                $_SESSION['last_activity'] = time();

                // 记录登录日志
                addSystemLog(
                    LOG_TYPE_LOGIN,
                    '管理员登录',
                    json_encode([
                        'username' => $username,
                        'success' => true
                    ])
                );

                // 重定向到受保护的页面
                header('Location: protected.php');
                exit;
            } else {
                // 登录失败，设置错误消息
                $error = '用户名或密码错误';
                // 记录失败登录
                addSystemLog(
                    LOG_TYPE_LOGIN,
                    '登录失败',
                    json_encode([
                        'username' => $username,
                        'success' => false
                    ])
                );
            }
        } catch (PDOException $e) {
            // 数据库连接或查询错误
            $error = '系统错误，请稍后重试';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 域名授权系统</title>
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

        .login-container {
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 4px 4px 0 var(--border-color);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            padding: 0.5rem;
            background: var(--bg-color);
        }

        .login-header h2 {
            color: var(--text-color);
            font-size: 1.8rem;
            margin: 0;
            font-weight: 900;
        }

        .form-group {
            margin-bottom: 1.5rem;
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

        .error-box {
            background: rgba(255, 99, 99, 0.1);
            border: 2px solid #FF6363;
            color: #FF6363;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }

            .login-header img {
                width: 60px;
                height: 60px;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../logo.png" alt="云智授权">
            <h2>管理员登录</h2>
        </div>

        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                登录
            </button>
        </form>
    </div>
</body>
</html>