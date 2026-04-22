<?php
session_start();
require_once __DIR__ . '/config/db.php';
$pdo = require __DIR__ . '/config/db.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change'])) {
    $oldDomain = trim($_POST['old_domain']);
    $newDomain = trim($_POST['new_domain']);
    $originalCardKey = $_POST['card_key']; // 保存原始输入
    $cardKey = strtoupper(str_replace('-', '', trim($_POST['card_key'])));
    
    // 调试信息
    error_log("Original card key: " . $originalCardKey);
    error_log("Processed card key: " . $cardKey);
    
    // 验证卡密格式
    if (!preg_match('/^[A-Z0-9]{16}$/', $cardKey)) {
        $_SESSION['message'] = '请输入16位有效卡密';
        $_SESSION['success'] = false;
    } else {
        // 验证域名格式
        $oldDomain = str_replace(['http://', 'https://', 'www.'], '', $oldDomain);
        $oldDomain = explode('/', $oldDomain)[0];
        
        $newDomain = str_replace(['http://', 'https://', 'www.'], '', $newDomain);
        $newDomain = explode('/', $newDomain)[0];
        
        if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $oldDomain) || 
            !preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $newDomain)) {
            $_SESSION['message'] = '请输入有效的域名（如：pxxox.com）';
            $_SESSION['success'] = false;
        } else {
            try {
                $pdo->beginTransaction();
                
                // 验证授权码和域名是否匹配
                $stmt = $pdo->prepare("SELECT * FROM card_keys WHERE card_key = ? FOR UPDATE");
                error_log("Searching for card key in database: " . $cardKey);
                $stmt->execute([$cardKey]);
                $auth = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$auth) {
                    // 尝试模糊匹配
                    $stmt = $pdo->prepare("SELECT * FROM card_keys WHERE card_key LIKE ? FOR UPDATE");
                    $searchPattern = '%' . $cardKey . '%';
                    error_log("Trying fuzzy search with pattern: " . $searchPattern);
                    $stmt->execute([$searchPattern]);
                    $auth = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($auth) {
                        error_log("Found card with fuzzy search: " . $auth['card_key']);
                        $cardKey = $auth['card_key']; // 使用数据库中的实际卡密
                    } else {
                        $_SESSION['message'] = '授权码不存在';
                        $_SESSION['success'] = false;
                    }
                }
                
                if ($auth) {
                    error_log("Found auth: " . json_encode($auth));
                    if ($auth['status'] != 1) {
                        $_SESSION['message'] = '授权码未被使用，无法更换域名';
                        $_SESSION['success'] = false;
                    } elseif ($auth['domain'] !== $oldDomain) {
                        $_SESSION['message'] = '授权码与原域名不匹配';
                        $_SESSION['success'] = false;
                    } else {
                        // 检查新域名是否已被授权
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM card_keys WHERE domain = ? AND status = 1");
                        $stmt->execute([$newDomain]);
                        if ($stmt->fetchColumn() > 0) {
                            $_SESSION['message'] = '新域名已被其他授权码使用';
                            $_SESSION['success'] = false;
                        } else {
                            // 更新数据库中的域名
                            $stmt = $pdo->prepare("UPDATE card_keys SET domain = ? WHERE card_key = ? AND domain = ?");
                            $stmt->execute([$newDomain, $cardKey, $oldDomain]);
                            
                            // 更新授权文件
                            $authFile = __DIR__ . '/config/authorized_domains.php';
                            $domains = file_exists($authFile) ? include $authFile : [];
                            
                            if (isset($domains[$oldDomain])) {
                                $domains[$newDomain] = $domains[$oldDomain];
                                unset($domains[$oldDomain]);
                                
                                $content = "<?php\nreturn [\n";
                                foreach ($domains as $d => $info) {
                                    $content .= "    '".addslashes($d)."' => [\n";
                                    $content .= "        'expires' => '".addslashes($info['expires'])."',\n";
                                    $content .= "        'created_at' => '".addslashes($info['created_at'])."'\n";
                                    $content .= "    ],\n";
                                }
                                $content .= "];\n";
                                
                                file_put_contents($authFile, $content);
                            }
                            
                            $pdo->commit();
                            $_SESSION['message'] = "域名更换成功！已将授权从 {$oldDomain} 更换至 {$newDomain}";
                            $_SESSION['success'] = true;
                        }
                    }
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = '域名更换失败: ' . $e->getMessage();
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
    <title>更换授权 - 云智授权</title>
    <meta name="description" content="云智授权系统域名更换">
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
        <a href="zzgh.php" class="nav-item active">
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
            <h1>更换授权</h1>
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
                    <label for="old_domain">原授权域名</label>
                    <div class="input-group">
                        <span class="input-group-text">https://</span>
                        <input type="text" id="old_domain" name="old_domain" placeholder="pxxox.com" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="card_key">原授权码</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="text" id="card_key" name="card_key" placeholder="请输入原授权码" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_domain">新授权域名</label>
                    <div class="input-group">
                        <span class="input-group-text">https://</span>
                        <input type="text" id="new_domain" name="new_domain" placeholder="clozhi.com" required>
                    </div>
                </div>
                
                <button type="submit" name="change" class="submit-btn">
                    <i class="fas fa-exchange-alt"></i>
                    更换授权
                </button>
            </form>
            
            <div class="instructions fade-in-section delay-300">
                <h3><i class="fas fa-info-circle"></i> 更换说明</h3>
                <ul>
                    <li>请确保输入正确的原授权码和原域名</li>
                    <li>原授权码必须与原域名匹配才能更换</li>
                    <li>请确保原域名已获得授权且未过期</li>
                    <li>新域名不能是已授权的域名</li>
                    <li>更换后原域名的授权将失效</li>
                    <li>更换后新域名将继承原域名的到期时间</li>
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

        // 域名输入处理
        document.querySelectorAll('#old_domain, #new_domain').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value
                    .replace(/^https?:\/\//, '')
                    .replace(/^www\./, '')
                    .replace(/\/.*$/, '')
                    .toLowerCase();
                e.target.value = value;
            });
        });

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