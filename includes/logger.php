<?php
// 确保文件存在
$db_config = __DIR__ . '/../config/db.php';
if (!file_exists($db_config)) {
    die('数据库配置文件不存在');
}
require_once $db_config;

function addSystemLog($type, $action, $details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (type, action, details, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $type,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error adding system log: " . $e->getMessage());
        return false;
    }
}

// 日志类型常量
define('LOG_TYPE_AUTH', 'auth');         // 授权相关
define('LOG_TYPE_ADMIN', 'admin');       // 管理操作
define('LOG_TYPE_SYSTEM', 'system');     // 系统相关
define('LOG_TYPE_ERROR', 'error');       // 错误日志
define('LOG_TYPE_LOGIN', 'login');       // 登录相关 