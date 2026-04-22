<?php
session_start();

// 确保文件存在
$logger_file = __DIR__ . '/includes/logger.php';
$db_config = __DIR__ . '/config/db.php';

if (!file_exists($logger_file)) {
    die('日志处理文件不存在');
}
if (!file_exists($db_config)) {
    die('数据库配置文件不存在');
}

require_once $db_config;
require_once $logger_file;

$pdo = require $db_config;

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    $originalCardKey = $_POST['card_key']; // 保存原始输入
    $cardKey = strtoupper(str_replace('-', '', trim($_POST['card_key'])));
    $domain = trim($_POST['domain']);
    
    // 调试信息
    error_log("Original card key: " . $originalCardKey);
    error_log("Processed card key: " . $cardKey);
    
    // 验证卡密格式
    if (!preg_match('/^[A-Z0-9]{16}$/', $cardKey)) {
        $_SESSION['message'] = '请输入16位有效卡密';
        $_SESSION['success'] = false;
    } else {
        // 验证域名格式
        $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
        $domain = explode('/', $domain)[0];
        
        if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $domain)) {
            $_SESSION['message'] = '请输入有效的域名（如：pxxox.com）';
            $_SESSION['success'] = false;
        } elseif (empty($cardKey)) {
            $_SESSION['message'] = '请输入卡密';
            $_SESSION['success'] = false;
        } else {
            try {
                $pdo->beginTransaction();
                
                // 检查卡密是否存在且未使用
                $stmt = $pdo->prepare("SELECT * FROM card_keys WHERE card_key = ? FOR UPDATE");
                error_log("Searching for card key in database: " . $cardKey);
                $stmt->execute([$cardKey]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$card) {
                    // 尝试模糊匹配
                    $stmt = $pdo->prepare("SELECT * FROM card_keys WHERE card_key LIKE ? FOR UPDATE");
                    $searchPattern = '%' . $cardKey . '%';
                    error_log("Trying fuzzy search with pattern: " . $searchPattern);
                    $stmt->execute([$searchPattern]);
                    $card = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($card) {
                        error_log("Found card with fuzzy search: " . $card['card_key']);
                        $cardKey = $card['card_key']; // 使用数据库中的实际卡密
                    } else {
                        $_SESSION['message'] = '卡密不存在';
                        $_SESSION['success'] = false;
                    }
                }
                
                if ($card) {
                    error_log("Found card: " . json_encode($card));
                    if ($card['status'] == 1) {
                        $_SESSION['message'] = '卡密已被使用';
                        $_SESSION['success'] = false;
                    } elseif ($card['domain']) {
                        $_SESSION['message'] = '卡密已绑定其他域名';
                        $_SESSION['success'] = false;
                    } else {
                        // 计算有效期
                        $expiresDate = date('Y-m-d', strtotime("+{$card['duration']} days"));
                        
                        // 更新卡密状态
                        $stmt = $pdo->prepare("UPDATE card_keys SET status = 1, use_time = NOW(), user_ip = ?, domain = ? WHERE id = ?");
                        $stmt->execute([$_SERVER['REMOTE_ADDR'], $domain, $card['id']]);
                        
                        // 更新授权文件
                        $authFile = __DIR__ . '/config/authorized_domains.php';
                        $domains = file_exists($authFile) ? include $authFile : [];
                        
                        $domains[$domain] = [
                            'expires' => $expiresDate,
                            'created_at' => date('Y-m-d')
                        ];
                        
                        $content = "<?php\nreturn [\n";
                        foreach ($domains as $d => $info) {
                            $content .= "    '".addslashes($d)."' => [\n";
                            $content .= "        'expires' => '".addslashes($info['expires'])."',\n";
                            $content .= "        'created_at' => '".addslashes($info['created_at'])."'\n";
                            $content .= "    ],\n";
                        }
                        $content .= "];\n";
                        
                        file_put_contents($authFile, $content);
                        
                        $pdo->commit();
                        $_SESSION['message'] = "兑换成功！域名 {$domain} 已获得授权，有效期至 {$expiresDate}";
                        $_SESSION['success'] = true;

                        // 记录日志
                        addSystemLog(
                            LOG_TYPE_AUTH,
                            '授权兑换',
                            json_encode([
                                'domain' => $domain,
                                'card_key' => $cardKey,
                                'expires' => $expiresDate,
                                'duration' => $card['duration']
                            ])
                        );
                    }
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = '兑换失败: ' . $e->getMessage();
                $_SESSION['success'] = false;
            }
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$message = $_SESSION['message'] ?? '';
$success = $_SESSION['success'] ?? false;
unset($_SESSION['message'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权兑换 - 云智授权</title>
    <meta name="description" content="云智授权系统卡密兑换">
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
        <a href="redeem.php" class="nav-item active">
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
            <h1>授权兑换</h1>
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
                    <label for="card_key">卡密</label>
                    <input type="text" id="card_key" name="card_key" placeholder="输入卡密" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="domain">需要授权的域名</label>
                    <div class="input-group">
                        <span class="input-group-text">https://</span>
                        <input type="text" id="domain" name="domain" placeholder="pxxox.com" required>
                    </div>
                </div>
                
                <button type="submit" name="redeem" class="submit-btn">
                    <i class="fas fa-key"></i>
                    在线授权
                </button>
            </form>
            
            <div class="instructions fade-in-section delay-300">
                <h3><i class="fas fa-info-circle"></i> 兑换说明</h3>
                <ul>
                    <li>请输入16位卡密和需要授权的域名</li>
                    <li>卡密区分大小写，请准确输入</li>
                    <li>每个卡密只能授权一个域名</li>
                    <li>授权成功后域名将立即生效</li>
                </ul>
            </div>
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

        // 卡密输入格式化
        document.getElementById('card_key').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            if (value.length > 16) value = value.substr(0, 16);
            
            // 添加破折号分隔
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += '-';
                formatted += value[i];
            }
            
            e.target.value = formatted;
        });
        
        // 域名输入处理
        document.getElementById('domain').addEventListener('input', function(e) {
            let value = e.target.value
                .replace(/^https?:\/\//, '')
                .replace(/^www\./, '')
                .replace(/\/.*$/, '')
                .toLowerCase();
            e.target.value = value;
        });

        // 表单提交前处理
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            // 移除卡密中的破折号后再提交
            let cardKeyInput = document.getElementById('card_key');
            cardKeyInput.value = cardKeyInput.value.replace(/-/g, '');
        });

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