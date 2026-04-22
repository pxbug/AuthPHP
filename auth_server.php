<?php
// auth_server.php - 授权服务器端
header('Content-Type: application/json');

// 配置授权密钥
define('AUTH_SECRET', '5LqR6L+9572R57uc');

// 从配置文件获取允许的域名
function get_allowed_domains() {
    $configFile = __DIR__ . '/config/authorized_domains.php';
    if (file_exists($configFile)) {
        return include $configFile;
    }
    return [];
}

// 处理不同操作
$action = isset($_GET['action']) ? $_GET['action'] : 'check_auth';

switch ($action) {
    case 'list_domains':
        // 列出所有授权域名(需要管理员权限)
        if (!check_admin_access()) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }
        
        $domains = [];
        foreach (get_allowed_domains() as $domain => $info) {
            $domains[$domain] = [
                'expires' => $info['expires'],
                'status' => ($info['expires'] == 'permanent' || strtotime($info['expires']) > time()) 
                            ? 'active' 
                            : 'expired'
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'domains' => $domains
        ]);
        break;
        
    case 'check_auth':
    default:
        // 检查域名授权
        $requested_domain = isset($_GET['domain']) ? strtolower(trim($_GET['domain'])) : '';
        $requested_domain = str_replace('www.', '', $requested_domain);
        
        // 验证逻辑
        $allowed_domains = get_allowed_domains();
        if (array_key_exists($requested_domain, $allowed_domains)) {
            // 检查有效期
            $expiry = $allowed_domains[$requested_domain]['expires'];
            $is_valid = ($expiry == 'permanent') || (strtotime($expiry) > time());
            
            if ($is_valid) {
                // 生成授权令牌
                $token_data = [
                    'domain' => $requested_domain,
                    'expires' => time() + (24 * 60 * 60), // 1天后过期
                    'timestamp' => time(),
                    'strict_check' => true,
                    'domain_expiry' => $expiry
                ];
                
                $token = base64_encode(json_encode($token_data));
                $signature = hash_hmac('sha256', $token, AUTH_SECRET);
                
                echo json_encode([
                    'status' => 'success',
                    'token' => $token,
                    'signature' => $signature,
                    'expires' => $token_data['expires'],
                    'domain_expiry' => $expiry
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Domain authorization expired',
                    'expiry_date' => $expiry
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Domain not authorized'
            ]);
        }
        break;
}

function check_admin_access() {
    $adminKey = isset($_GET['admin_key']) ? $_GET['admin_key'] : '';
    $validAdminKey = 'your_admin_secret_key'; // 应该存储在配置文件中
    
    // 验证IP地址(可选)
    $allowedIPs = ['127.0.0.1', '192.168.1.100']; // 允许的管理IP
    
    return ($adminKey === $validAdminKey) && in_array($_SERVER['REMOTE_ADDR'], $allowedIPs);
}