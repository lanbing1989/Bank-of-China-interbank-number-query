<?php
/**
 * 用法：
 * 1. 修改下面的数据库连接信息和CSV文件路径
 * 2. 命令行执行：php import_net_bank_code.php
 */

$csvFile = __DIR__ . '/net_bank_code.csv'; // CSV文件路径
$dsn = "mysql:host=localhost;dbname=lian_wsx_tax;charset=utf8mb4";
$user = "lian_wsx_tax";
$pass = "jrFzZxjMysCW6TkM";

// 数据表名
$table = "bank_codes";

// 建表语句
$createTableSQL = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `net_bank_code` VARCHAR(32) NOT NULL,
  `bank_name` VARCHAR(128) NOT NULL,
  `province_code` VARCHAR(64),
  `area` VARCHAR(64),
  `bankcode` VARCHAR(32),
  INDEX idx_net_bank_code (net_bank_code),
  INDEX idx_bank_name (bank_name),
  INDEX idx_area (area),
  INDEX idx_bankcode (bankcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("数据库连接失败：" . $e->getMessage() . "\n");
}

// 建表
$pdo->exec($createTableSQL);
echo "数据表[{$table}]已准备好。\n";

// 读取并导入CSV
if (!file_exists($csvFile)) {
    die("CSV文件不存在：$csvFile\n");
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("无法打开CSV文件：$csvFile\n");
}

$header = fgetcsv($handle);
$expectedHeader = ['NET_BANK_CODE', 'BANK_NAME', 'PROVINCE_CODE', 'AREA', 'BANKCODE'];
if (array_map('strtoupper', $header) !== $expectedHeader) {
    die("CSV表头不匹配，期望：" . implode(',', $expectedHeader) . "\n实际：" . implode(',', $header) . "\n");
}

// 预处理语句
$insertSQL = "INSERT INTO `{$table}` (net_bank_code, bank_name, province_code, area, bankcode) VALUES (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($insertSQL);

// 批量插入
$rowCount = 0;
$batchSize = 1000;
$batch = [];
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) != 5) continue;
    $batch[] = $row;
    if (count($batch) >= $batchSize) {
        $pdo->beginTransaction();
        foreach ($batch as $item) {
            $stmt->execute($item);
        }
        $pdo->commit();
        $rowCount += count($batch);
        echo "已导入 {$rowCount} 行...\n";
        $batch = [];
    }
}
// 插入剩余数据
if ($batch) {
    $pdo->beginTransaction();
    foreach ($batch as $item) {
        $stmt->execute($item);
    }
    $pdo->commit();
    $rowCount += count($batch);
    echo "已导入 {$rowCount} 行.\n";
}

fclose($handle);
echo "导入完成，总共导入 {$rowCount} 行数据。\n";