<?php
$dsn = "mysql:host=localhost;dbname=lian_wsx_tax;charset=utf8mb4";
$user = "lian_wsx_tax";
$pass = "jrFzZxjMysCW6TkM";
$file = __DIR__ . '/bin.csv';

$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("TRUNCATE TABLE bank_bin");
$handle = fopen($file, 'r') or die('无法打开文件');
$stmt = $pdo->prepare("INSERT INTO bank_bin (bank_name, bank_code, bank_abbr, card_name, card_type, card_length, bin, bin_length) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$count = 0;
while (($row = fgetcsv($handle)) !== false) {
  if (count($row) < 8) continue;
  foreach ($row as &$col) {
    if (!mb_check_encoding($col, 'UTF-8')) $col = mb_convert_encoding($col, 'UTF-8', 'GBK,GB2312,BIG5,ASCII');
    $col = trim($col);
  }
  $stmt->execute($row);
  $count++;
}
fclose($handle);
echo "导入完成，共$count 条记录。\n";