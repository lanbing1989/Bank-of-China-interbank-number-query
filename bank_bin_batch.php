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

$card_numbers = isset($_POST['card_numbers']) ? trim($_POST['card_numbers']) : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$results = [];
$not_found = [];

function export_xls($results) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment;filename=bank_bin_batch_" . date('Ymd_His') . ".xls");
    header("Cache-Control: max-age=0");
    echo "<meta charset='UTF-8'>";
    echo "<table border='1'>";
    echo "<tr>
        <th>输入卡号</th>
        <th>银行</th>
        <th>银行代码</th>
        <th>简称</th>
        <th>卡名</th>
        <th>卡类型</th>
        <th>卡号长度</th>
        <th>BIN号</th>
        <th>BIN长度</th>
    </tr>";
    foreach ($results as $row) {
        echo "<tr>";
        // 注意这里
        echo "<td>=\"" . htmlspecialchars($row['input']) . "\"</td>";
        echo "<td>" . htmlspecialchars($row['bank_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bank_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bank_abbr']) . "</td>";
        echo "<td>" . htmlspecialchars($row['card_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['card_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['card_length']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bin']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bin_length']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

if ($card_numbers !== '') {
    $lines = preg_split('/[\r\n]+/', $card_numbers, -1, PREG_SPLIT_NO_EMPTY);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, function($line) { return preg_match('/^\d{6,20}$/', $line); });
    foreach ($lines as $num) {
        // 尝试匹配最长BIN，优先bin_length长的
        $sql = "SELECT bank_name, bank_code, bank_abbr, card_name, card_type, card_length, bin, bin_length
                FROM bank_bin
                WHERE bin = LEFT(:card, bin_length)
                ORDER BY bin_length DESC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':card', $num, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['input'] = $num;
            $results[] = $row;
        } else {
            $not_found[] = $num;
        }
    }
    if ($action === 'export') {
        export_xls($results);
    }
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>银行卡批量归属行查询</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #f7fafc; }
    .main-title { letter-spacing: 2px; }
    .bank-table { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
    .input-area-wrap {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 160px;
    }
    .search-bar-form {
        width: 100%;
    }
    .input-group-center {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .main-textarea {
        width: 100%;
        max-width: 900px;   /* 这里调大宽度 */
        min-height: 220px;  /* 这里调大高度 */
        font-size: 1.35rem;
        border-radius: 24px;
        padding: 22px 28px;
        margin-bottom: 16px;
        background: #fff;
        box-shadow: 0 2px 16px rgba(0,0,0,0.03);
    }
    .action-btns {
        display: flex;
        gap: 24px;
        justify-content: center;
    }
    @media (max-width: 991px) {
        .main-textarea { max-width: 100%; }
    }
    .logo { width: 48px; height: 48px; }
    footer { color: #888; margin-top: 60px; }
</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="https://img.icons8.com/ios-filled/50/4a90e2/bank-building.png" class="logo me-2" alt="logo">
                <span class="main-title fw-bold fs-4">银行联行号查询|银行卡归属行查询</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="切换导航">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">联行号查询</a></li>
                    <li class="nav-item"><a class="nav-link" href="bank_bin_search.php">银行卡归属行查询</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="bank_bin_batch.php">银行卡批量归属行查询</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="input-area-wrap mb-4">
    <form class="search-bar-form w-100" method="post" action="">
        <div class="input-group-center">
            <textarea class="main-textarea" name="card_numbers" placeholder="每行一个银行卡号，仅支持个人银行卡"><?=e($card_numbers)?></textarea>
            <div class="form-text mb-3">输入多行银行卡号，每行一个，回车换行分隔。仅支持个人银行卡，不支持对公账户。</div>
            <div class="action-btns">
                <button class="btn btn-primary px-4" name="action" value="search" type="submit">批量查询</button>
                <button class="btn btn-success px-4" name="action" value="export" type="submit">导出为Excel</button>
            </div>
        </div>
    </form>
</div>
        <div class="bank-table p-4 mb-3">
            <?php if ($card_numbers === ''): ?>
                <div class="alert alert-info mb-0 text-center">请输入银行卡号后再批量查询。</div>
            <?php elseif (count($results)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>输入卡号</th>
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
                        <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?=e($row['input'])?></td>
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
            <div class="alert alert-warning mb-0 text-center">未查询到任何银行卡信息，请确认输入。</div>
            <?php endif; ?>

            <?php if (count($not_found)): ?>
                <div class="alert alert-secondary mt-3">
                    以下卡号未能识别归属行：<br>
                    <?=e(implode(', ', $not_found))?>
                </div>
            <?php endif; ?>
        </div>
        <footer class="text-center pt-5 pb-3 small">
            &copy; <?=date('Y')?> 银行联行号查询|银行卡归属行查询 助理Pro Zhuli.Pro <a href="https://github.com/lanbing1989/" target="_blank">GitHub</a>
        </footer>
    </div>
</body>
</html>