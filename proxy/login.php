<?php
session_start();

if (isset($_SESSION['proxy_logged_in']) && $_SESSION['proxy_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = '请填写用户名和密码';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM proxy_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['proxy_logged_in'] = true;
                $_SESSION['proxy_id'] = $user['id'];
                $_SESSION['proxy_username'] = $user['username'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
            }
        } catch (PDOException $e) {
            $error = '登录失败，请稍后重试';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>代理登录</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background-color: var(--light-color);
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--text-muted);
            margin: 0;
        }
        
        .login-card {
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 4px 4px 0 var(--border-color);
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .form-group input:focus {
            outline: none;
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .error-message {
            background: var(--danger-color);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 2px solid var(--danger-dark);
            box-shadow: 4px 4px 0 var(--danger-dark);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-dark);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 4px 4px 0 var(--primary-dark);
        }
        
        .btn-login:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--primary-dark);
        }
        
        .btn-login:active {
            transform: translate(4px, 4px);
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>代理登录</h1>
            <p>请输入您的账号和密码</p>
        </div>
        
        <div class="login-card">
            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-login">登录</button>
            </form>
        </div>
    </div>
</body>
</html>