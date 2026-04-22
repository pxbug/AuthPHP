<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['proxy_logged_in'])) {
    header('Location: login.php');
    exit;
}

try {
    // 获取代理信息
    $stmt = $pdo->prepare("SELECT * FROM proxy_users WHERE id = ?");
    $stmt->execute([$_SESSION['proxy_id']]);
    $proxyInfo = $stmt->fetch();
    
    // 获取代理的卡密统计
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_cards,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_cards,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as used_cards
        FROM card_keys WHERE proxy_id = ?");
    $stmt->execute([$_SESSION['proxy_id']]);
    $cardStats = $stmt->fetch();
    
} catch (PDOException $e) {
    die("获取信息失败: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>代理中心</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- 顶部导航栏 -->
        <nav class="navbar">
            <div class="container">
                <a class="navbar-brand" href="#">代理中心</a>
                <div class="nav-menu">
                    <a class="nav-link active" href="index.php">首页</a>
                    <a class="nav-link" href="cards.php">授权码管理</a>
                    <a class="nav-link" href="add_card.php">生成授权码</a>
                    <a class="nav-link" href="logout.php">退出登录</a>
                </div>
            </div>
        </nav>

        <!-- 欢迎信息 -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">欢迎回来，<?= htmlspecialchars($_SESSION['proxy_username']) ?></h3>
            </div>
            <div class="card-body">
                <p class="text-muted">最后登录时间：<?= date('Y-m-d H:i:s') ?></p>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>总授权码数量</h5>
                        <h2><?= number_format($cardStats['total_cards']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>未使用授权码</h5>
                        <h2><?= number_format($cardStats['active_cards']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>已使用授权码</h5>
                        <h2><?= number_format($cardStats['used_cards']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- 账户信息 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">账户信息</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span>用户名</span>
                            <span><?= htmlspecialchars($proxyInfo['username']) ?></span>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span>邮箱</span>
                            <span><?= htmlspecialchars($proxyInfo['email']) ?></span>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span>注册时间</span>
                            <span><?= date('Y-m-d H:i:s', strtotime($proxyInfo['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 快捷操作 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">快捷操作</h5>
            </div>
            <div class="card-body">
                <a href="add_card.php" class="btn btn-primary">生成新授权码</a>
                <a href="cards.php" class="btn">管理授权码</a>
            </div>
        </div>
    </div>
</body>
</html>