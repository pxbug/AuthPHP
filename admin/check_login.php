<?php
session_start();

// 检查是否已登录
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // 如果是AJAX请求，返回JSON响应
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => '请先登录']);
        exit;
    }
    
    // 普通请求则重定向到登录页面
    header('Location: login.php');
    exit;
}

// 检查用户权限级别（如果需要）
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // 如果是AJAX请求
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => '无权限访问']);
        exit;
    }
    
    // 普通请求
    header('HTTP/1.1 403 Forbidden');
    die('无权限访问此页面');
}

// 设置会话超时时间（2小时）
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['last_activity'] = time();

// 防止会话固定攻击
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // 如果会话开始超过30分钟则重新生成会话ID
    session_regenerate_id(true);
    $_SESSION['created'] = time();
} 