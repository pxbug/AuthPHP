<?php
session_start();

// 从配置文件读取授权域名列表
$config_file = __DIR__ . '/config/authorized_domains.php';

// 检查配置文件是否存在
if (!file_exists($config_file)) {
    die('<div class="error-box"><i class="fa fa-exclamation-triangle"></i> 错误：授权配置文件不存在</div>');
}

// 包含配置文件获取域名列表
$authorized_domains = include $config_file;

// 处理查询请求
$search_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) {
    $domain = trim($_POST['domain']);
    $domain = strtolower($domain);
    
    // 移除协议和www前缀
    $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
    
    // 提取主域名
    $domain = explode('/', $domain)[0];
    
    // 更严格的域名验证
    if (!preg_match('/^(?!\-)(?:(?:[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])\.)+(?:[a-z]{2,63})$/i', $domain)) {
        $search_result = [
            'error' => '域名格式不正确',
            'domain' => $domain,
            'icon' => 'exclamation-triangle',
            'color' => 'orange'
        ];
    } else {
        // 检查域名是否授权
        $is_authorized = array_key_exists($domain, $authorized_domains);
        $expiry_info = '';
        
        if ($is_authorized) {
            $expires = $authorized_domains[$domain]['expires'];
            if ($expires === 'permanent') {
                $expiry_info = '（永久有效）';
            } else {
                $expiry_date = strtotime($expires);
                $current_date = time();
                if ($expiry_date < $current_date) {
                    $expiry_info = '（已过期）';
                    $is_authorized = false; // 过期视为未授权
                } else {
                    $expiry_info = '（有效期至 '.date('Y-m-d', $expiry_date).'）';
                }
            }
        }
        
        $search_result = [
            'domain' => $domain,
            'status' => $is_authorized ? '已授权' : '未授权',
            'message' => $is_authorized 
                ? '该域名在授权列表中'.$expiry_info 
                : '该域名未在授权列表中',
            'icon' => $is_authorized ? 'check-circle' : 'times-circle',
            'color' => $is_authorized ? 'green' : 'red',
            'expires' => $is_authorized ? $expires : null
        ];
    }
    
    // 存储结果到SESSION
    $_SESSION['search_result'] = $search_result;
    
    // 重定向到当前页面，避免刷新时重新提交表单
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// 从SESSION获取结果（如果存在）
if (isset($_SESSION['search_result'])) {
    $search_result = $_SESSION['search_result'];
    unset($_SESSION['search_result']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云智授权查询 - 授权中心</title>
    <meta name="description" content="域名授权查询系统，验证域名是否在授权列表中">
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
        <a href="download.php" class="nav-item">
            <i class="fas fa-download"></i>
            <span>下载</span>源码下载
        </a>
    </nav>

    <div class="container">
        <div class="header fade-in-section delay-100">
            <h1>云智授权查询</h1>
            <div class="clock">
                <i class="fas fa-clock"></i>
                <span id="beijing-time">正在获取北京时间...</span>
            </div>
        </div>
        
        <div class="function-buttons fade-in-section delay-200">
            <a href="redeem.php" class="function-btn">
                <i class="fas fa-key"></i>
                <span>在线授权</span>
            </a>
            <a href="dlcx.php" class="function-btn">
                <i class="fas fa-user-shield"></i>
                <span>代理查询</span>
            </a>
            <a href="zzgh.php" class="function-btn">
                <i class="fas fa-exchange-alt"></i>
                <span>更换授权</span>
            </a>
            <a href="download.php" class="function-btn">
                <i class="fas fa-download"></i>
                <span>源码下载</span>
            </a>
        </div>
        
        <div class="search-form fade-in-section delay-300">
            <form method="post">
                <div class="form-group">
                    <input type="text" class="form-control" name="domain" placeholder="请输入域名进行查询" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> 立即查询
                </button>
            </form>
        </div>
            
        <?php if ($search_result): ?>
        <div class="result-container fade-in-section delay-400">
            <div class="result-box <?php 
                echo isset($search_result['error']) ? 'warning-box' : 
                    ($search_result['status'] === '已授权' ? 'success-box' : 'danger-box'); 
            ?>">
                <i class="fas fa-<?php echo $search_result['icon']; ?> result-icon" 
                   style="color: <?php echo $search_result['color']; ?>"></i>
                <div class="result-content">
                    <h3>
                        <?php if (isset($search_result['error'])): ?>
                            查询错误
                        <?php else: ?>
                            查询结果: <?php echo $search_result['status']; ?>
                        <?php endif; ?>
                    </h3>
                    <p><span class="domain-name"><?php echo htmlspecialchars($search_result['domain']); ?></span></p>
                    <?php if (isset($search_result['error'])): ?>
                        <p><?php echo htmlspecialchars($search_result['error']); ?></p>
                    <?php else: ?>
                        <p><?php echo $search_result['message']; ?></p>
                        <?php if (isset($search_result['expires']) && $search_result['expires'] !== 'permanent'): ?>
                            <p><small>剩余有效期: <?php echo ceil((strtotime($search_result['expires']) - time()) / 86400); ?>天</small></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

        // 每秒更新一次时间
        setInterval(updateBeijingTime, 1000);
        updateBeijingTime(); // 立即更新一次

        // 主题切换功能
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const notification = document.querySelector('.theme-notification');
            const notificationText = notification.querySelector('.notification-text');
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // 显示主题切换提示
            notificationText.textContent = `已切换到${newTheme === 'dark' ? '暗色' : '亮色'}主题`;
            notification.classList.add('show');

            // 3秒后隐藏提示
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // 初始化主题
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        // 页面加载时初始化主题
        initTheme();

        // 页面加载时显示加载动画
        window.addEventListener('load', function() {
            // 显示加载动画3秒
            Loading.show().then(() => {
                // 添加页面过渡效果
                document.querySelectorAll('.page-transition').forEach(el => {
                    el.classList.add('loaded');
                });
                
                // 检查并显示淡入元素
                checkFadeElements();
            });
        });

        // 检查淡入元素的可见性
        function checkFadeElements() {
            const fadeElements = document.querySelectorAll('.fade-in-section');
            fadeElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementBottom = element.getBoundingClientRect().bottom;
                
                // 当元素在视口中时添加可见类
                if (elementTop < window.innerHeight && elementBottom >= 0) {
                    element.classList.add('is-visible');
                }
            });
        }

        // 监听滚动事件
        window.addEventListener('scroll', checkFadeElements);

        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            Loading.showButton(btn);
            
            // 模拟表单提交
            setTimeout(() => {
                this.submit();
            }, 500);
        });
        
        function toggleNav() {
            document.querySelector('.nav-menu').classList.toggle('active');
        }
    </script>
</body>
</html>