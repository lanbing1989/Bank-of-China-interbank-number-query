<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * PAGE_SIZE;

// 查询构造
$where = '';
$params = [];
// 只有有搜索内容才查询
if ($q !== '') {
    $keywords = preg_split('/\s+/', $q);
    $like_fields = ['bank_name', 'bank_code', 'bank_abbr', 'card_name', 'card_type', 'bin'];
    $and_conditions = [];
    foreach ($keywords as $idx => $kw) {
        $or = [];
        foreach ($like_fields as $field) {
            $or[] = "$field LIKE :kw{$idx}_{$field}";
            $params[":kw{$idx}_{$field}"] = "%$kw%";
        }
        $and_conditions[] = '(' . implode(' OR ', $or) . ')';
    }
    $where = 'WHERE ' . implode(' AND ', $and_conditions);
}

// 获取总数和数据
$total = 0; $totalPages = 1; $data = [];
if ($q !== '') {
    $sql_count = "SELECT COUNT(*) FROM bank_bin $where";
    $stmt = $pdo->prepare($sql_count);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $totalPages = max(1, ceil($total / PAGE_SIZE));

    $sql = "SELECT bank_name, bank_code, bank_abbr, card_name, card_type, card_length, bin, bin_length
            FROM bank_bin
            $where
            ORDER BY id DESC
            LIMIT :offset, :pagesize";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':pagesize', PAGE_SIZE, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>中国银行卡归属行查询</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7fafc; }
        .main-title { letter-spacing: 2px; }
        .bank-table { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .search-bar input { border-radius: 24px; }
        .logo { width: 48px; height: 48px; }
        footer { color: #888; margin-top: 60px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="https://img.icons8.com/ios-filled/50/4a90e2/bank-building.png" class="logo me-2" alt="logo">
                <span class="main-title fw-bold fs-4">中国银行卡归属行查询</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="切换导航">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">联行号查询</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="bank_bin_search.php">银行卡归属行查询</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="bank_bin_batch.php">银行卡批量归属行查询</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <form class="search-bar mb-4" method="get" action="">
            <div class="input-group">
                <input class="form-control form-control-lg" name="q" value="<?=e($q)?>" placeholder="请输入银行卡号前10位）">
                <button class="btn btn-primary px-4" type="submit">搜索</button>
            </div>
        </form>
        <div class="bank-table p-4 mb-3">
            <?php if ($q === ''): ?>
                <div class="alert alert-info mb-0 text-center">请先输入银行卡号后再查询数据。（只能查个人银行卡，不支持公户查询。）</div>
            <?php elseif (count($data)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>银行</th>
                            <th>银行代码</th>
                            <th>简称</th>
                            <th>卡名</th>
                            <th>卡类型</th>
                            <th>卡号长度</th>
                            <th>BIN号</th>
                            <th>BIN长度</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?=e($row['bank_name'])?></td>
                            <td><?=e($row['bank_code'])?></td>
                            <td><?=e($row['bank_abbr'])?></td>
                            <td><?=e($row['card_name'])?></td>
                            <td><?=e($row['card_type'])?></td>
                            <td><?=e($row['card_length'])?></td>
                            <td><?=e($row['bin'])?></td>
                            <td><?=e($row['bin_length'])?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mb-0 text-center">暂无符合条件的银行卡BIN数据。</div>
            <?php endif; ?>
        </div>
        <?php if ($q !== '' && $totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?=$i == $page ? 'active' : ''?>">
                    <a class="page-link" href="?<?=http_build_query(['q'=>$q,'page'=>$i])?>"><?=$i?></a>
                </li>
            <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <footer class="text-center pt-5 pb-3 small">
            本网站是非营利性公益网站，全程免费查询，无需关注公众号或者付费，如果有人让您付费查询，请谨防诈骗。<br/>
            &copy; <?=date('Y')?>银行联行号查询|银行卡归属行查询 助理Pro Zhuli.Pro 鲁ICP备2025169043号
        </footer>
    </div>
</body>
</html>