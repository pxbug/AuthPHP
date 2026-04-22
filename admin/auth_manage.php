<?php
// 开启会话
session_start();

// 检查登录状态
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    die('拒绝访问：请先登录');
}

// 配置路径
define('CONFIG_FILE', __DIR__.'/../config/authorized_domains.php');

// 获取当前授权域名列表
function getAuthorizedDomains() {
    if (file_exists(CONFIG_FILE)) {
        return include CONFIG_FILE;
    }
    return [];
}

// 保存域名列表
function saveAuthorizedDomains($domains) {
    $content = "<?php\nreturn [\n";
    foreach ($domains as $domain => $info) {
        $content .= "    '".addslashes($domain)."' => ['expires' => '".addslashes($info['expires'])."'],\n";
    }
    $content .= "];\n";
    
    return file_put_contents(CONFIG_FILE, $content) !== false;
}

// 处理表单提交
$message = null;
$messageType = 'success';
$currentDomains = getAuthorizedDomains();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理AJAX请求
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        try {
            switch ($_POST['action']) {
                case 'add-domain':
                    $newDomain = strtolower(trim(str_replace('www.', '', $_POST['domain'])));
                    if (empty($newDomain)) {
                        throw new Exception('域名不能为空');
                    }
                    
                    $expires = isset($_POST['expires']) ? trim($_POST['expires']) : 'permanent';
                    if ($expires !== 'permanent' && !strtotime($expires)) {
                        throw new Exception('无效的过期日期格式');
                    }
                    
                    if (array_key_exists($newDomain, $currentDomains)) {
                        throw new Exception('域名已存在');
                    }
                    
                    $currentDomains[$newDomain] = ['expires' => $expires];
                    if (!saveAuthorizedDomains($currentDomains)) {
                        throw new Exception('保存失败');
                    }
                    
                    echo json_encode([
                        'status' => 'success', 
                        'domain' => $newDomain,
                        'expires' => $expires,
                        'count' => count($currentDomains)
                    ]);
                    exit;
                    
                case 'delete-domain':
                    $domainToDelete = strtolower(trim(str_replace('www.', '', $_POST['domain'])));
                    if (!array_key_exists($domainToDelete, $currentDomains)) {
                        throw new Exception('域名不存在');
                    }
                    
                    unset($currentDomains[$domainToDelete]);
                    if (!saveAuthorizedDomains($currentDomains)) {
                        throw new Exception('删除失败');
                    }
                    
                    echo json_encode([
                        'status' => 'success',
                        'count' => count($currentDomains)
                    ]);
                    exit;
                    
                case 'update-expiry':
                    $domain = strtolower(trim(str_replace('www.', '', $_POST['domain'])));
                    if (!array_key_exists($domain, $currentDomains)) {
                        throw new Exception('域名不存在');
                    }
                    
                    $expires = isset($_POST['expires']) ? trim($_POST['expires']) : 'permanent';
                    if ($expires !== 'permanent' && !strtotime($expires)) {
                        throw new Exception('无效的过期日期格式');
                    }
                    
                    $currentDomains[$domain]['expires'] = $expires;
                    if (!saveAuthorizedDomains($currentDomains)) {
                        throw new Exception('更新失败');
                    }
                    
                    echo json_encode([
                        'status' => 'success',
                        'domain' => $domain,
                        'expires' => $expires
                    ]);
                    exit;
                    
                default:
                    throw new Exception('无效的操作');
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权域名管理</title>
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2C3E50;
            --primary-dark: #1a2936;
            --success-color: #4ECDC4;
            --danger-color: #FF6B6B;
            --warning-color: #F4D03F;
            --light-bg: #FFF5EA;
            --border-color: #2C3E50;
            --text-color: #2C3E50;
            --text-muted: #6c757d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            background-color: var(--light-bg);
            color: var(--text-color);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 4px 4px 0 var(--border-color);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        h1, h2 {
            color: var(--text-color);
            font-weight: 900;
        }
        
        h1 {
            font-size: 1.8rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        h2 {
            font-size: 1.4rem;
            margin: 15px 0 10px;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border: 2px solid;
        }
        
        .message.success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .message.error {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }
        
        .message i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid var(--border-color);
            font-size: 0.9rem;
            white-space: nowrap;
            background: var(--light-bg);
            color: var(--text-color);
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .btn:active {
            transform: translate(4px, 4px);
            box-shadow: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--text-color);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .btn i {
            margin-right: 5px;
            font-size: 1rem;
        }
        
        .domain-list {
            margin-top: 20px;
        }
        
        .domain-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .domain-info {
            flex: 1;
            margin-bottom: 10px;
        }
        
        .domain-name {
            font-weight: 600;
            margin-bottom: 5px;
            word-break: break-all;
            color: var(--text-color);
        }
        
        .domain-expiry {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .expiry-permanent {
            color: var(--success-color);
        }
        
        .expiry-active {
            color: var(--primary-color);
        }
        
        .expiry-expired {
            color: var(--danger-color);
        }
        
        .domain-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .add-domain-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            background: white;
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .add-domain-form input, 
        .add-domain-form select {
            padding: 10px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            width: 100%;
            background: white;
            color: var(--text-color);
            box-shadow: 4px 4px 0 var(--border-color);
            transition: all 0.3s;
        }
        
        .add-domain-form input:focus,
        .add-domain-form select:focus {
            outline: none;
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .expiry-controls {
            display: flex;
            gap: 12px;
        }
        
        .expiry-type {
            flex: 1;
        }
        
        .expiry-date {
            flex: 2;
        }
        
        .domain-count {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-left: 10px;
        }

        /* SweetAlert2 自定义样式 */
        .swal2-popup {
            border: 2px solid var(--border-color) !important;
            border-radius: 12px !important;
            box-shadow: 4px 4px 0 var(--border-color) !important;
        }

        .swal2-input, 
        .swal2-select {
            border: 2px solid var(--border-color) !important;
            border-radius: 8px !important;
            box-shadow: 4px 4px 0 var(--border-color) !important;
        }

        .swal2-confirm {
            background-color: var(--primary-color) !important;
            border: 2px solid var(--border-color) !important;
            box-shadow: 4px 4px 0 var(--border-color) !important;
        }

        .swal2-cancel {
            background-color: var(--danger-color) !important;
            border: 2px solid var(--border-color) !important;
            box-shadow: 4px 4px 0 var(--border-color) !important;
        }
        
        @media (min-width: 768px) {
            .domain-item {
                flex-direction: row;
                align-items: center;
                padding: 15px 20px;
            }
            
            .domain-info {
                margin-bottom: 0;
            }
            
            .add-domain-form {
                flex-direction: row;
                align-items: flex-end;
            }
            
            .expiry-controls {
                flex: 2;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 767px) {
            body {
                padding: 10px;
            }
            
            .card {
                padding: 15px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            h2 {
                font-size: 1.2rem;
            }
            
            .action-text {
                display: none;
            }
            
            .btn i {
                margin-right: 0;
            }
            
            .domain-actions {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1><i class="mdi mdi-shield-account"></i> 授权域名管理</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="mdi <?php echo $messageType === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2><i class="mdi mdi-plus-circle"></i> 添加新域名</h2>
            <div class="add-domain-form">
                <input type="text" id="new-domain" placeholder="输入新域名，例如：pxxox.com">
                <div class="expiry-controls">
                    <select id="expiry-type" class="expiry-type">
                        <option value="permanent">永久有效</option>
                        <option value="custom">自定义日期</option>
                    </select>
                    <input type="date" id="expiry-date" class="expiry-date">
                </div>
                <button id="add-domain-btn" class="btn btn-primary"><i class="mdi mdi-plus"></i> 添加</button>
            </div>
            
            <div class="domain-list">
                <h2>
                    <i class="mdi mdi-domain"></i> 当前授权域名 
                    <span class="domain-count">(共 <?php echo count($currentDomains); ?> 个)</span>
                </h2>
                <div id="domains-container">
                    <?php foreach ($currentDomains as $domain => $info): 
                        $expiry = $info['expires'];
                        $isExpired = $expiry !== 'permanent' && strtotime($expiry) < time();
                        $expiryClass = $expiry === 'permanent' ? 'expiry-permanent' : 
                                      ($isExpired ? 'expiry-expired' : 'expiry-active');
                    ?>
                        <div class="domain-item" data-domain="<?php echo htmlspecialchars($domain); ?>">
                            <div class="domain-info">
                                <div class="domain-name"><?php echo htmlspecialchars($domain); ?></div>
                                <div class="domain-expiry <?php echo $expiryClass; ?>">
                                    <i class="mdi mdi-calendar"></i>
                                    <?php echo $expiry === 'permanent' ? '永久有效' : 
                                          ($isExpired ? '已过期 (' . $expiry . ')' : '有效期至 ' . $expiry); ?>
                                </div>
                            </div>
                            <div class="domain-actions">
                                <button class="btn btn-warning btn-sm edit-expiry-btn" data-domain="<?php echo htmlspecialchars($domain); ?>">
                                    <i class="mdi mdi-calendar-edit"></i> <span class="action-text">修改</span>
                                </button>
                                <button class="btn btn-danger btn-sm delete-btn" data-domain="<?php echo htmlspecialchars($domain); ?>">
                                    <i class="mdi mdi-delete"></i> <span class="action-text">删除</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 切换有效期类型
            const expiryType = document.getElementById('expiry-type');
            const expiryDate = document.getElementById('expiry-date');
            
            expiryType.addEventListener('change', function() {
                expiryDate.style.display = this.value === 'custom' ? 'block' : 'none';
            });
            
            // 添加域名
            const addDomain = () => {
                const domainInput = document.getElementById('new-domain');
                const domain = domainInput.value.trim().toLowerCase().replace('www.', '');
                
                if (!domain) {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: '请输入有效的域名'
                    });
                    return;
                }
                
                let expires = 'permanent';
                if (expiryType.value === 'custom' && expiryDate.value) {
                    expires = expiryDate.value;
                }
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add-domain&domain=${encodeURIComponent(domain)}&expires=${encodeURIComponent(expires)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // 添加到DOM
                        const domainItem = document.createElement('div');
                        domainItem.className = 'domain-item';
                        domainItem.dataset.domain = domain;
                        
                        const isExpired = data.expires !== 'permanent' && new Date(data.expires) < new Date();
                        const expiryClass = data.expires === 'permanent' ? 'expiry-permanent' : 
                                          (isExpired ? 'expiry-expired' : 'expiry-active');
                        const expiryText = data.expires === 'permanent' ? '永久有效' : 
                                          (isExpired ? '已过期 (' + data.expires + ')' : '有效期至 ' + data.expires);
                        
                        domainItem.innerHTML = `
                            <div class="domain-info">
                                <div class="domain-name">${domain}</div>
                                <div class="domain-expiry ${expiryClass}">
                                    <i class="mdi mdi-calendar"></i> ${expiryText}
                                </div>
                            </div>
                            <div class="domain-actions">
                                <button class="btn btn-warning btn-sm edit-expiry-btn" data-domain="${domain}">
                                    <i class="mdi mdi-calendar-edit"></i> <span class="action-text">修改</span>
                                </button>
                                <button class="btn btn-danger btn-sm delete-btn" data-domain="${domain}">
                                    <i class="mdi mdi-delete"></i> <span class="action-text">删除</span>
                                </button>
                            </div>
                        `;
                        
                        document.getElementById('domains-container').appendChild(domainItem);
                        
                        // 清空输入框
                        domainInput.value = '';
                        expiryType.value = 'permanent';
                        expiryDate.style.display = 'none';
                        expiryDate.value = '';
                        
                        // 更新计数
                        document.querySelector('.domain-count').textContent = `(共 ${data.count} 个)`;
                        
                        // 绑定事件
                        domainItem.querySelector('.delete-btn').addEventListener('click', deleteDomain);
                        domainItem.querySelector('.edit-expiry-btn').addEventListener('click', editExpiry);
                        
                        Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: '域名已添加',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: data.message || '添加失败'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: '请求失败'
                    });
                });
            };
            
            // 删除域名
            const deleteDomain = function() {
                const domain = this.dataset.domain;
                const domainItem = this.closest('.domain-item');
                
                Swal.fire({
                    title: '确认删除',
                    text: `确定要删除域名 ${domain} 吗？`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f72585',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '删除',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=delete-domain&domain=${encodeURIComponent(domain)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // 从DOM移除
                                domainItem.remove();
                                
                                // 更新计数
                                document.querySelector('.domain-count').textContent = `(共 ${data.count} 个)`;
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: '成功',
                                    text: '域名已删除',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '错误',
                                    text: data.message || '删除失败'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: '请求失败'
                            });
                        });
                    }
                });
            };
            
            // 修改有效期
            const editExpiry = function() {
                const domain = this.dataset.domain;
                const domainItem = this.closest('.domain-item');
                const currentExpiry = domainItem.querySelector('.domain-expiry').textContent.trim();
                
                let currentExpiryType = 'permanent';
                let currentExpiryDate = '';
                
                if (!currentExpiry.includes('永久')) {
                    currentExpiryType = 'custom';
                    // 提取日期部分 (可能包含"已过期"或"有效期至"前缀)
                    const dateMatch = currentExpiry.match(/(\d{4}-\d{2}-\d{2})/);
                    if (dateMatch) {
                        currentExpiryDate = dateMatch[1];
                    }
                }
                
                Swal.fire({
                    title: `修改域名 ${domain} 的有效期`,
                    html: `
                        <div style="text-align: left; margin-bottom: 15px;">
                            <label for="swal-expiry-type" style="display: block; margin-bottom: 5px;">有效期类型:</label>
                            <select id="swal-expiry-type" class="swal2-select" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="permanent" ${currentExpiryType === 'permanent' ? 'selected' : ''}>永久有效</option>
                                <option value="custom" ${currentExpiryType === 'custom' ? 'selected' : ''}>自定义日期</option>
                            </select>
                        </div>
                        <div id="swal-expiry-date-container" style="text-align: left; ${currentExpiryType === 'permanent' ? 'display: none;' : ''}">
                            <label for="swal-expiry-date" style="display: block; margin-bottom: 5px;">有效期至:</label>
                            <input id="swal-expiry-date" type="date" class="swal2-input" value="${currentExpiryDate}" style="width: 100%;">
                        </div>
                    `,
                    focusConfirm: false,
                    preConfirm: () => {
                        const type = document.getElementById('swal-expiry-type').value;
                        let expires = 'permanent';
                        
                        if (type === 'custom') {
                            const date = document.getElementById('swal-expiry-date').value;
                            if (!date) {
                                Swal.showValidationMessage('请选择有效日期');
                                return false;
                            }
                            expires = date;
                        }
                        
                        return { expires };
                    },
                    showCancelButton: true,
                    confirmButtonText: '保存',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { expires } = result.value;
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=update-expiry&domain=${encodeURIComponent(domain)}&expires=${encodeURIComponent(expires)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // 更新DOM
                                const isExpired = data.expires !== 'permanent' && new Date(data.expires) < new Date();
                                const expiryClass = data.expires === 'permanent' ? 'expiry-permanent' : 
                                                  (isExpired ? 'expiry-expired' : 'expiry-active');
                                const expiryText = data.expires === 'permanent' ? '永久有效' : 
                                                  (isExpired ? '已过期 (' + data.expires + ')' : '有效期至 ' + data.expires);
                                
                                const expiryElement = domainItem.querySelector('.domain-expiry');
                                expiryElement.className = `domain-expiry ${expiryClass}`;
                                expiryElement.innerHTML = `<i class="mdi mdi-calendar"></i> ${expiryText}`;
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: '成功',
                                    text: '有效期已更新',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '错误',
                                    text: data.message || '更新失败'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: '请求失败'
                            });
                        });
                    }
                });
                
                // 切换有效期类型时的显示/隐藏
                document.getElementById('swal-expiry-type').addEventListener('change', function() {
                    const dateContainer = document.getElementById('swal-expiry-date-container');
                    dateContainer.style.display = this.value === 'custom' ? 'block' : 'none';
                });
            };
            
            // 事件绑定
            document.getElementById('add-domain-btn').addEventListener('click', addDomain);
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', deleteDomain);
            });
            document.querySelectorAll('.edit-expiry-btn').forEach(btn => {
                btn.addEventListener('click', editExpiry);
            });
            
            // 回车键添加域名
            document.getElementById('new-domain').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addDomain();
                }
            });
            
            // 移动设备优化 - 隐藏按钮文字
            function checkScreenSize() {
                const actionTexts = document.querySelectorAll('.action-text');
                if (window.innerWidth < 768) {
                    actionTexts.forEach(text => {
                        text.style.display = 'none';
                    });
                } else {
                    actionTexts.forEach(text => {
                        text.style.display = 'inline';
                    });
                }
            }
            
            // 初始检查和窗口大小变化时检查
            checkScreenSize();
            window.addEventListener('resize', checkScreenSize);
        });
    </script>
</body>
</html>