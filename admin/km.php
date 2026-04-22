<?php
session_start();

// Check login status
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    die('拒绝访问：请先登录');
}

// Use absolute path for database connection
require_once '../config/db.php';

// Generate card keys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $cardType = $_POST['card_type'];
    $duration = (int)$_POST['duration'];
    $quantity = (int)$_POST['quantity'];
    $prefix = $_POST['prefix'] ?? '';

    if ($quantity <= 0 || $quantity > 1000) {
        die('生成数量必须在1 - 1000之间');
    }

    $keys = [];
    $pdo->beginTransaction();

    try {
        for ($i = 0; $i < $quantity; $i++) {
            $key = $prefix . strtoupper(substr(md5(uniqid() . microtime() . rand(1000, 9999)), 0, 16));

            $stmt = $pdo->prepare("INSERT INTO card_keys (card_key, card_type, duration) VALUES (?, ?, ?)");
            $stmt->execute([$key, $cardType, $duration]);

            $keys[] = $key;
        }

        $pdo->commit();
        $success = "成功生成 {$quantity} 张卡密！";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "生成卡密失败: " . $e->getMessage();
    }
}

// Delete used card keys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_used'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM card_keys WHERE status = 1");
        $stmt->execute();
        $success = "已成功删除所有已使用卡密！";
    } catch (PDOException $e) {
        $error = "删除已使用卡密失败: " . $e->getMessage();
    }
}

// Delete all card keys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM card_keys");
        $stmt->execute();
        $success = "已成功删除所有卡密！";
    } catch (PDOException $e) {
        $error = "删除所有卡密失败: " . $e->getMessage();
    }
}

// Get card key list
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(card_key LIKE ? OR card_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM card_keys $whereClause");
$stmt->execute($params);
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM card_keys $whereClause ORDER BY id DESC LIMIT $offset, $perPage");
$stmt->execute($params);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卡密管理 - 云智授权</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #34495E;
            --text-color: #2C3E50;
            --bg-color: #FFF5EA;
            --input-bg: #FFFFFF;
            --border-color: #2C3E50;
            --shadow: 0 4px 6px rgba(44, 62, 80, 0.1);
            --radius: 12px;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: var(--text-color);
            font-size: 2rem;
            font-weight: 900;
            margin: 0;
        }

        .card {
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: 4px 4px 0 var(--border-color);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            background: var(--bg-color);
        }

        .card-header h2 {
            color: var(--text-color);
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            background: var(--input-bg);
            color: var(--text-color);
            box-shadow: 4px 4px 0 var(--border-color);
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }

        .btn {
            padding: 1rem 2rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            box-shadow: 4px 4px 0 var(--border-color);
            background: var(--bg-color);
            color: var(--text-color);
        }

        .btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }

        .btn:active {
            transform: translate(4px, 4px);
            box-shadow: none;
        }

        .btn-danger {
            background: #FF6B6B;
            color: white;
            border-color: #FF6B6B;
        }

        .btn-warning {
            background: #FFB900;
            color: white;
            border-color: #FFB900;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background: var(--bg-color);
            font-weight: 600;
            color: var(--text-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 4px 4px 0 var(--border-color);
        }

        .page-link:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--border-color);
        }

        .page-link.active {
            background: var(--border-color);
            color: white;
        }

        .status-used {
            color: #FF6B6B;
            font-weight: 500;
        }

        .status-unused {
            color: #4ECDC4;
            font-weight: 500;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border: 2px solid;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #4ECDC4;
            border-color: #4ECDC4;
            color: white;
        }

        .alert-error {
            background: #FF6B6B;
            border-color: #FF6B6B;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .table-container {
                border: 2px solid var(--border-color);
                border-radius: var(--radius);
                margin-bottom: 1.5rem;
            }

            .table th {
                display: none;
            }

            .table tr {
                display: block;
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
            }

            .table td {
                display: block;
                padding: 0.5rem 0;
                border: none;
            }

            .table td:before {
                content: attr(data-label);
                font-weight: 600;
                display: inline-block;
                width: 120px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>卡密管理</h1>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-plus-circle"></i>
                    生成卡密
                </h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="card_type">
                                <i class="fas fa-tag"></i>
                                会员类型
                            </label>
                            <input type="text" id="card_type" name="card_type" class="form-control" required placeholder="VIP会员">
                        </div>
                        <div class="form-group">
                            <label for="duration">
                                <i class="fas fa-calendar-alt"></i>
                                有效期(天)
                            </label>
                            <input type="number" id="duration" name="duration" class="form-control" min="1" value="30" required>
                        </div>
                        <div class="form-group">
                            <label for="quantity">
                                <i class="fas fa-copy"></i>
                                生成数量
                            </label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="1" max="1000" value="10" required>
                        </div>
                        <div class="form-group">
                            <label for="prefix">
                                <i class="fas fa-font"></i>
                                前缀(可选)
                            </label>
                            <input type="text" id="prefix" name="prefix" class="form-control" maxlength="4" placeholder="VIP-">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="generate" class="btn">
                            <i class="fas fa-magic"></i>
                            立即生成
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-list"></i>
                    卡密列表
                </h2>
            </div>
            <div class="card-body">
                <div class="form-actions" style="margin-bottom: 1.5rem;">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <form method="get" style="flex: 1;">
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" name="search" class="form-control" placeholder="搜索卡密或类型" value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn">
                                    <i class="fas fa-search"></i>
                                    搜索
                                </button>
                            </div>
                        </form>
                        <form method="post" style="display: flex; gap: 0.5rem;">
                            <button type="submit" name="delete_used" class="btn btn-warning">
                                <i class="fas fa-trash-alt"></i>
                                删除已使用
                            </button>
                            <button type="submit" name="delete_all" class="btn btn-danger">
                                <i class="fas fa-trash"></i>
                                删除全部
                            </button>
                        </form>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>卡密</th>
                                <th>类型</th>
                                <th>有效期</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>使用时间</th>
                                <th>兑换域名</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cards)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">
                                        <i class="fas fa-inbox"></i>
                                        暂无卡密数据
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td data-label="卡密"><?= htmlspecialchars($card['card_key']) ?></td>
                                        <td data-label="类型"><?= htmlspecialchars($card['card_type']) ?></td>
                                        <td data-label="有效期"><?= $card['duration'] ?>天</td>
                                        <td data-label="状态">
                                            <span class="<?= $card['status'] ? 'status-used' : 'status-unused' ?>">
                                                <?= $card['status'] ? '已使用' : '未使用' ?>
                                            </span>
                                        </td>
                                        <td data-label="创建时间"><?= $card['create_time'] ?></td>
                                        <td data-label="使用时间"><?= $card['use_time'] ?? '-' ?></td>
                                        <td data-label="兑换域名"><?= htmlspecialchars($card['domain'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1): ?>
                            <a href="?page=1&search=<?= urlencode($search) ?>" class="page-link">1</a>
                            <?php if ($start > 2): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                               class="page-link <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>" class="page-link">
                                <?= $totalPages ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>