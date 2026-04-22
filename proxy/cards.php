<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// 检查代理登录状态
if (!isset($_SESSION['proxy_logged_in'])) {
    header('Location: login.php');
    exit;
}

// 处理回收授权码请求
if (isset($_GET['recycle']) && isset($_GET['card_key'])) {
    $cardKey = $_GET['card_key'];
    try {
        $stmt = $pdo->prepare("UPDATE card_keys SET status = 0 WHERE card_key = ? AND proxy_id = ?");
        $stmt->execute([$cardKey, $_SESSION['proxy_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        die("回收授权码失败: " . $e->getMessage());
    }
}

// 处理导出未使用授权码请求
if (isset($_GET['export'])) {
    try {
        $stmt = $pdo->prepare("SELECT card_key FROM card_keys WHERE proxy_id = ? AND status = 0");
        $stmt->execute([$_SESSION['proxy_id']]);
        $unusedCards = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $filename = 'unused_card_keys_' . date('YmdHis') . '.txt';
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        foreach ($unusedCards as $cardKey) {
            echo $cardKey . "\n";
        }
        exit;
    } catch (PDOException $e) {
        die("导出未使用授权码失败: " . $e->getMessage());
    }
}

// 获取当前页码，默认为第1页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// 每页显示的记录数
$limit = 20;
// 计算偏移量
$offset = ($page - 1) * $limit;

// 获取当前代理生成的授权码总数
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM card_keys WHERE proxy_id = ?");
    $countStmt->execute([$_SESSION['proxy_id']]);
    $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $totalResult['total'];
} catch (PDOException $e) {
    die("获取授权码总数失败: " . $e->getMessage());
}

// 计算总页数
$totalPages = ceil($total / $limit);

// 获取当前页的授权码
try {
    $stmt = $pdo->prepare("SELECT * FROM card_keys 
                          WHERE proxy_id = ? 
                          ORDER BY create_time DESC
                          LIMIT $limit OFFSET $offset");
    $stmt->execute([$_SESSION['proxy_id']]);
    $cards = $stmt->fetchAll();
} catch (PDOException $e) {
    die("获取授权码列表失败: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权码管理</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- 顶部导航栏 -->
        <nav class="navbar">
            <div class="container">
                <a class="navbar-brand" href="#">代理中心</a>
                <div class="nav-menu">
                    <a class="nav-link" href="index.php">首页</a>
                    <a class="nav-link active" href="cards.php">授权码管理</a>
                    <a class="nav-link" href="add_card.php">生成授权码</a>
                    <a class="nav-link" href="logout.php">退出登录</a>
                </div>
            </div>
        </nav>

        <!-- 页面标题和操作按钮 -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">授权码管理</h3>
                    <div>
                        <a href="add_card.php" class="btn btn-primary">生成新授权码</a>
                        <a href="?export=1" class="btn">导出未使用授权码</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 授权码列表 -->
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>授权码</th>
                            <th>状态</th>
                            <th>有效期</th>
                            <th>生成时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cards)): ?>
                            <tr>
                                <td colspan="5" class="text-center">暂无授权码数据</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cards as $card): ?>
                                <tr>
                                    <td><?= htmlspecialchars($card['card_key']) ?></td>
                                    <td>
                                        <?php if ($card['status'] == 0): ?>
                                            <span class="badge badge-success">未使用</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">已使用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $card['duration'] ?> 天</td>
                                    <td><?= date('Y-m-d H:i:s', strtotime($card['create_time'])) ?></td>
                                    <td>
                                        <?php if ($card['status'] == 1): ?>
                                            <a href="?recycle=1&card_key=<?= urlencode($card['card_key']) ?>&page=<?= $page ?>" class="btn btn-sm">回收</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1" class="page-link">首页</a>
                    <a href="?page=<?= $page - 1 ?>" class="page-link">上一页</a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="page-link">下一页</a>
                    <a href="?page=<?= $totalPages ?>" class="page-link">末页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.25rem;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: var(--success-color);
            color: #fff;
        }
        
        .badge-secondary {
            background: var(--secondary-color);
            color: #fff;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .d-flex {
            display: flex;
        }
        
        .justify-content-between {
            justify-content: space-between;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</body>
</html>    