<?php
// 必须在文件最开头启动会话
session_start();

// 引入数据库配置文件
require_once __DIR__ . '/config/db.php';

$message = '';
$success = false;
$agent = null;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $agentId = trim($_POST['agent_id']);
    
    if (empty($agentId)) {
        $_SESSION['message'] = '请输入代理ID';
        $_SESSION['success'] = false;
    } else {
        try {
            // 查询代理信息
            $stmt = $pdo->prepare("SELECT * FROM proxy_users WHERE id = ? OR username = ?");
            $stmt->execute([$agentId, $agentId]);
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$agent) {
                $_SESSION['message'] = '代理ID或用户名不存在';
                $_SESSION['success'] = false;
            } else {
                // 查询代理的卡密数量
                $stmt = $pdo->prepare("SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as unused,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as used
                    FROM card_keys 
                    WHERE proxy_id = ?");
                $stmt->execute([$agent['id']]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $agent['stats'] = $stats;
                $_SESSION['message'] = '查询成功';
                $_SESSION['success'] = true;
            }
        } catch (Exception $e) {
            $_SESSION['message'] = '查询失败: ' . $e->getMessage();
            $_SESSION['success'] = false;
        }
    }
    
    $_SESSION['agent'] = $agent;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$message = $_SESSION['message'] ?? '';
$success = $_SESSION['success'] ?? false;
$agent = $_SESSION['agent'] ?? null;
unset($_SESSION['message'], $_SESSION['success'], $_SESSION['agent']);
?>

<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>代理查询 - 域名授权系统</title>
    <meta name="description" content="域名授权系统代理查询">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/loading.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 初始加载动画 -->
    <div class="loading-container active">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="nav-header page-transition">
        <div class="logo">
            <a href="/" title="返回首页">
                <img src="logo.png" alt="域名授权系统 - 返回首页">
            </a>
        </div>
        <div style="display: flex; align-items: center;">
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="切换主题">
                <svg class="theme-icon-dark" viewBox="0 0 24 24">
                    <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>
                </svg>
                <svg class="theme-icon-light" viewBox="0 0 24 24">
                    <path d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-2a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM11 1h2v3h-2V1zm0 19h2v3h-2v-3zM3.515 4.929l1.414-1.414L7.05 5.636 5.636 7.05 3.515 4.93zM16.95 18.364l1.414-1.414 2.121 2.121-1.414 1.414-2.121-2.121zm2.121-14.85l1.414 1.415-2.121 2.121-1.414-1.414 2.121-2.121zM5.636 16.95l1.414 1.414-2.121 2.121-1.414-1.414 2.121-2.121zM23 11v2h-3v-2h3zM4 11v2H1v-2h3z"/>
                </svg>
            </button>
            <button class="nav-toggle" onclick="toggleNav()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </button>
        </div>
    </div>

    <nav class="nav-menu">
        <button class="nav-close" onclick="toggleNav()">×</button>
        <a href="/" class="nav-item">
            <i class="fas fa-home"></i>
            <span>首页</span>授权查询
        </a>
        <a href="redeem.php" class="nav-item">
            <i class="fas fa-key"></i>
            <span>兑换</span>授权兑换
        </a>
        <a href="dlcx.php" class="nav-item active">
            <i class="fas fa-user-shield"></i>
            <span>代理</span>代理查询
        </a>
        <a href="zzgh.php" class="nav-item">
            <i class="fas fa-exchange-alt"></i>
            <span>更换</span>更换授权
        </a>
        <a href="download.php" class="nav-item">
            <i class="fas fa-download"></i>
            <span>下载</span>源码下载
        </a>
    </nav>

    <div class="container">
        <div class="header fade-in-section delay-100">
            <h1>代理查询</h1>
            <div class="clock">
                <i class="fas fa-clock"></i>
                <span id="beijing-time">正在获取北京时间...</span>
            </div>
        </div>
        
        <div class="form-container fade-in-section delay-200">
            <?php if ($message): ?>
                <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="auth-form">
                <div class="form-group">
                    <label for="agent_id">代理ID或用户名</label>
                    <input type="text" id="agent_id" name="agent_id" placeholder="输入代理ID或用户名" required autofocus>
                </div>
                
                <button type="submit" name="query" class="submit-btn">
                    <i class="fas fa-search"></i>
                    查询代理
                </button>
            </form>
            
            <?php if ($agent): ?>
            <div class="agent-info fade-in-section delay-300">
                <h3><i class="fas fa-user-shield"></i> 代理信息</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">代理ID</span>
                        <span class="value"><?= htmlspecialchars($agent['id']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">用户名</span>
                        <span class="value"><?= htmlspecialchars($agent['username']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">邮箱</span>
                        <span class="value"><?= htmlspecialchars($agent['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">注册时间</span>
                        <span class="value"><?= htmlspecialchars($agent['created_at']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">账户状态</span>
                        <span class="value"><?= $agent['status'] ? '正常' : '已禁用' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">账户余额</span>
                        <span class="value">￥<?= number_format($agent['balance'], 2) ?></span>
                    </div>
                </div>
                
                <h3><i class="fas fa-chart-pie"></i> 卡密统计</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $agent['stats']['total'] ?? 0 ?></span>
                            <span class="stat-label">总卡密数</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $agent['stats']['unused'] ?? 0 ?></span>
                            <span class="stat-label">未使用</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $agent['stats']['used'] ?? 0 ?></span>
                            <span class="stat-label">已使用</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="instructions fade-in-section delay-300">
                <h3><i class="fas fa-info-circle"></i> 查询说明</h3>
                <ul>
                    <li>请输入代理ID或用户名进行查询</li>
                    <li>可查看代理基本信息和卡密统计</li>
                    <li>如果您还不是代理，请联系管理员</li>
                    <li>代理相关问题请联系客服处理</li>
                </ul>
            </div>
        </div>
    </div>
    
    <footer class="fade-in-section delay-500">
        <p>2025 Powered by <a href="https://itroll.vip">@域名授权系统</a></p>
    </footer>

    <!-- 主题切换提示 -->
    <div class="theme-notification">
        <i class="fas fa-sun theme-icon-light"></i>
        <i class="fas fa-moon theme-icon-dark"></i>
        <span class="notification-text">已切换到亮色主题</span>
    </div>

    <script src="js/loading.js"></script>
    <script>
        // 更新北京时间
        function updateBeijingTime() {
            const options = {
                timeZone: 'Asia/Shanghai',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            
            const beijingTime = new Date().toLocaleString('zh-CN', options);
            document.getElementById('beijing-time').textContent = beijingTime;
        }

        setInterval(updateBeijingTime, 1000);
        updateBeijingTime();

        // 主题切换功能
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const notification = document.querySelector('.theme-notification');
            const notificationText = notification.querySelector('.notification-text');
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            notificationText.textContent = `已切换到${newTheme === 'dark' ? '暗色' : '亮色'}主题`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        initTheme();

        // 导航菜单切换
        function toggleNav() {
            document.querySelector('.nav-menu').classList.toggle('active');
        }

        // 页面加载动画
        window.addEventListener('load', function() {
            Loading.show().then(() => {
                document.querySelectorAll('.page-transition').forEach(el => {
                    el.classList.add('loaded');
                });
                checkFadeElements();
            });
        });

        function checkFadeElements() {
            const fadeElements = document.querySelectorAll('.fade-in-section');
            fadeElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementBottom = element.getBoundingClientRect().bottom;
                
                if (elementTop < window.innerHeight && elementBottom >= 0) {
                    element.classList.add('is-visible');
                }
            });
        }

        window.addEventListener('scroll', checkFadeElements);
    </script>
</body>
</html>