<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/check_login.php';

// 获取筛选参数
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// 构建查询条件
$where = [];
$params = [];

if ($type) {
    $where[] = "type = ?";
    $params[] = $type;
}

if ($start_date) {
    $where[] = "created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
}

if ($end_date) {
    $where[] = "created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
}

if ($search) {
    $where[] = "(action LIKE ? OR details LIKE ? OR ip LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总记录数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs $where_clause");
$stmt->execute($params);
$total = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

// 获取日志记录
$stmt = $pdo->prepare("SELECT * FROM system_logs $where_clause ORDER BY created_at DESC LIMIT ?, ?");
$params[] = $offset;
$params[] = $per_page;
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取所有日志类型
$stmt = $pdo->query("SELECT DISTINCT type FROM system_logs");
$log_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统日志 - 管理后台</title>
    <style>
        :root {
            --primary-color: #2C3E50;
            --primary-dark: #1a2936;
            --success-color: #4ECDC4;
            --danger-color: #FF6B6B;
            --danger-dark: #ff3838;
            --secondary-color: #34495E;
            --light-color: #FFF5EA;
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
            line-height: 1.5;
            color: var(--text-color);
            background-color: var(--light-color);
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        h1 {
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: var(--light-color);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-control {
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .form-control:focus {
            outline: none;
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            background: var(--light-color);
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
            color: #fff;
        }
        
        .table-container {
            margin: 1.5rem 0;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .log-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        
        .log-table th,
        .log-table td {
            padding: 12px 16px;
            border-bottom: 2px solid var(--border-color);
            font-size: 14px;
        }
        
        .log-table th {
            background: var(--light-color);
            font-weight: 600;
            text-align: left;
            white-space: nowrap;
        }
        
        .log-table tr:last-child td {
            border-bottom: none;
        }
        
        .log-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .log-type-auth { background: #e3f2fd; color: #1976d2; }
        .log-type-admin { background: #fce4ec; color: #c2185b; }
        .log-type-system { background: #f3e5f5; color: #7b1fa2; }
        .log-type-error { background: #ffebee; color: #d32f2f; }
        .log-type-login { background: #e8f5e9; color: #388e3c; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .page-link {
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 4px 4px 0 var(--border-color);
        }
        
        .page-link:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }
        
        .page-link.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 15px;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .log-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>系统日志</h1>
        
        <form method="get" class="filters">
            <div class="filter-group">
                <label>日志类型</label>
                <select name="type" class="form-control">
                    <option value="">全部类型</option>
                    <?php foreach ($log_types as $log_type): ?>
                        <option value="<?= htmlspecialchars($log_type) ?>" <?= $type === $log_type ? 'selected' : '' ?>>
                            <?= htmlspecialchars($log_type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>开始日期</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
            </div>
            
            <div class="filter-group">
                <label>结束日期</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
            </div>
            
            <div class="filter-group">
                <label>搜索</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索操作内容、详情或IP" class="form-control">
            </div>
            
            <div class="filter-group" style="justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">
                    搜索
                </button>
            </div>
        </form>
        
        <div class="table-container">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>类型</th>
                        <th>操作内容</th>
                        <th>详细信息</th>
                        <th>IP地址</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">暂无日志记录</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td>
                                    <span class="log-type log-type-<?= strtolower(htmlspecialchars($log['type'])) ?>">
                                        <?= htmlspecialchars($log['type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['details']) ?></td>
                                <td><?= htmlspecialchars($log['ip']) ?></td>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= $type ? "&type=$type" : '' ?><?= $start_date ? "&start_date=$start_date" : '' ?><?= $end_date ? "&end_date=$end_date" : '' ?><?= $search ? "&search=$search" : '' ?>" class="page-link">首页</a>
                    <a href="?page=<?= $page - 1 ?><?= $type ? "&type=$type" : '' ?><?= $start_date ? "&start_date=$start_date" : '' ?><?= $end_date ? "&end_date=$end_date" : '' ?><?= $search ? "&search=$search" : '' ?>" class="page-link">上一页</a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?= $i ?><?= $type ? "&type=$type" : '' ?><?= $start_date ? "&start_date=$start_date" : '' ?><?= $end_date ? "&end_date=$end_date" : '' ?><?= $search ? "&search=$search" : '' ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $type ? "&type=$type" : '' ?><?= $start_date ? "&start_date=$start_date" : '' ?><?= $end_date ? "&end_date=$end_date" : '' ?><?= $search ? "&search=$search" : '' ?>" class="page-link">下一页</a>
                    <a href="?page=<?= $total_pages ?><?= $type ? "&type=$type" : '' ?><?= $start_date ? "&start_date=$start_date" : '' ?><?= $end_date ? "&end_date=$end_date" : '' ?><?= $search ? "&search=$search" : '' ?>" class="page-link">末页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // 自动提交表单当日期或类型改变时
        document.querySelectorAll('input[type="date"], select[name="type"]').forEach(input => {
            input.addEventListener('change', () => {
                input.form.submit();
            });
        });
    </script>
</body>
</html> 