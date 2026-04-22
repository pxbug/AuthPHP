<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// 检查代理登录状态
if (!isset($_SESSION['proxy_logged_in'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// 处理生成授权码请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $amount = intval($_POST['amount']);
    $duration = intval($_POST['duration']);
    
    if ($amount <= 0 || $amount > 100) {
        $error = "生成数量必须在1-100之间";
    } elseif ($duration <= 0) {
        $error = "有效期必须大于0天";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 生成授权码
            $cards = [];
            for ($i = 0; $i < $amount; $i++) {
                $cardKey = strtoupper(bin2hex(random_bytes(8))); // 16位随机授权码
                $cards[] = $cardKey;
                
                // 插入数据库
                $stmt = $pdo->prepare("INSERT INTO card_keys (card_key, duration, proxy_id, create_time) 
                                      VALUES (?, ?, ?, NOW())");
                $stmt->execute([$cardKey, $duration, $_SESSION['proxy_id']]);
            }
            
            $pdo->commit();
            $success = "成功生成 {$amount} 张授权码";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "生成授权码失败: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>生成授权码</title>
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
                    <a class="nav-link" href="cards.php">授权码管理</a>
                    <a class="nav-link active" href="add_card.php">生成授权码</a>
                    <a class="nav-link" href="logout.php">退出登录</a>
                </div>
            </div>
        </nav>

        <!-- 页面标题 -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">生成授权码</h3>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
            
            <?php if (isset($cards)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">生成的授权码列表</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="10" readonly style="margin-bottom: 1rem;"><?= implode("\n", $cards) ?></textarea>
                        <button class="btn" onclick="copyCards()">复制授权码</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">生成设置</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label>生成数量 (1-100)</label>
                        <input type="number" name="amount" class="form-control" min="1" max="100" value="10" required>
                    </div>
                    <div class="form-group">
                        <label>有效期(天)</label>
                        <input type="number" name="duration" class="form-control" min="1" value="30" required>
                    </div>
                    <button type="submit" name="generate" class="btn btn-primary">生成授权码</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: var(--danger-color);
            color: white;
            border: 2px solid var(--danger-dark);
            box-shadow: 4px 4px 0 var(--danger-dark);
        }
        
        .alert-success {
            background: var(--success-color);
            color: white;
            border: 2px solid var(--success-color);
            box-shadow: 4px 4px 0 var(--success-color);
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
        
        textarea.form-control {
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.5;
            resize: none;
        }
    </style>

    <script>
        function copyCards() {
            const textarea = document.querySelector('textarea');
            textarea.select();
            document.execCommand('copy');
            alert('授权码已复制到剪贴板');
        }
    </script>
</body>
</html>