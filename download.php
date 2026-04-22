<?php
// 设置下载信息
$downloads = [
    [
        'id' => 'auth-system',
        'name' => '授权系统源码',
        'version' => 'v2.0.1',
        'description' => '完整的授权查询和管理系统，包含前后端源码',
        'size' => '2.8MB',
        'date' => '2024-03-20',
        'icon' => 'code'
    ],
    [
        'id' => 'auth-demo',
        'name' => '授权系统Demo',
        'version' => 'v1.5.0',
        'description' => '授权系统演示版本，帮助您快速了解系统功能',
        'size' => '1.2MB',
        'date' => '2024-03-18',
        'icon' => 'laptop-code'
    ],
    [
        'id' => 'auth-api',
        'name' => '授权API文档',
        'version' => 'v2.0.0',
        'description' => '详细的API接口文档，包含调用示例和说明',
        'size' => '0.5MB',
        'date' => '2024-03-15',
        'icon' => 'book'
    ]
];
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>源码下载 - 云智授权</title>
    <meta name="description" content="云智授权系统源码下载中心">
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
                <img src="logo.png" alt="云智授权 - 返回首页">
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
        <a href="dlcx.php" class="nav-item">
            <i class="fas fa-user-shield"></i>
            <span>代理</span>代理查询
        </a>
        <a href="zzgh.php" class="nav-item">
            <i class="fas fa-exchange-alt"></i>
            <span>更换</span>更换授权
        </a>
        <a href="download.php" class="nav-item active">
            <i class="fas fa-download"></i>
            <span>下载</span>源码下载
        </a>
    </nav>

    <div class="container">
        <div class="header fade-in-section delay-100">
            <h1>源码下载中心</h1>
            <div class="clock">
                <i class="fas fa-clock"></i>
                <span id="beijing-time">正在获取北京时间...</span>
            </div>
        </div>

        <div class="download-grid fade-in-section delay-200">
            <?php foreach ($downloads as $download): ?>
            <div class="download-card">
                <div class="download-icon">
                    <i class="fas fa-<?php echo $download['icon']; ?>"></i>
                </div>
                <div class="download-info">
                    <h3><?php echo $download['name']; ?></h3>
                    <p class="version">版本：<?php echo $download['version']; ?></p>
                    <p class="description"><?php echo $download['description']; ?></p>
                    <div class="download-meta">
                        <span><i class="fas fa-file-archive"></i> <?php echo $download['size']; ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo $download['date']; ?></span>
                    </div>
                </div>
                <button class="download-btn" onclick="startDownload('<?php echo $download['id']; ?>')">
                    <i class="fas fa-download"></i>
                    立即下载
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <footer class="fade-in-section delay-500">
        <p>2025 Powered by <a href="https://itroll.vip">@云智授权</a></p>
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

        // 下载功能
        function startDownload(id) {
            const btn = event.currentTarget;
            btn.classList.add('btn-loading');
            btn.disabled = true;

            // 模拟下载延迟
            setTimeout(() => {
                btn.classList.remove('btn-loading');
                btn.disabled = false;
                // 这里添加实际的下载逻辑
                alert('下载即将开始，请稍候...');
            }, 2000);
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