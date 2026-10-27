<?php
function csv_safe(?string $v): string { $v = (string)$v; return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v; }
$type = $_GET['type'] ?? 'devices';
$br = (string)($_GET['branch'] ?? ''); $dp = (string)($_GET['dept'] ?? ''); $dt = (string)($_GET['dtype'] ?? ''); $yr = (int)($_GET['year'] ?? 0);
$w = []; $a = [];
if ($br !== '') { $w[] = 'd.location=?'; $a[] = $br; }
if ($dp !== '') { $w[] = 'd.department=?'; $a[] = $dp; }
if ($dt !== '') { $w[] = 'd.device_type=?'; $a[] = $dt; }
if ($type === 'inspections' && $yr >= 1900 && $yr <= 2999) { $w[] = 'YEAR(i.inspected_at)=?'; $a[] = $yr; }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
if ($type === 'inspections') {
    q('SET SESSION group_concat_max_len=65535');
    $rows = q("SELECT d.asset_tag,d.device_type,d.serial_number,i.inspector_name,i.inspected_at,i.user_name,i.user_position,i.overall_result,
        (SELECT GROUP_CONCAT(t.item_name ORDER BY t.id SEPARATOR '; ') FROM inspection_items t WHERE t.inspection_id=i.id AND t.result='ok') items_ok,
        (SELECT GROUP_CONCAT(t.item_name ORDER BY t.id SEPARATOR '; ') FROM inspection_items t WHERE t.inspection_id=i.id AND t.result='no') items_problem,
        i.problem_note FROM inspections i JOIN devices d ON d.id=i.device_id $where ORDER BY i.inspected_at DESC,i.id DESC", $a)->fetchAll();
    $head = ['asset_tag', 'device_type', 'serial_number', 'inspector_name', 'inspected_at', 'user_name', 'user_position', 'overall_result', 'items_ok(✓)', 'items_problem(✕)', 'problem_note'];
    $fn = 'inspections';
} else {
    $rows = q('SELECT ' . implode(',', array_map(fn($f) => "d.$f", DEV_FIELDS)) . " FROM devices d $where ORDER BY d.asset_tag", $a)->fetchAll();
    $head = DEV_FIELDS; $fn = 'devices';
}
while (ob_get_level()) ob_end_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="nbcheck_' . $fn . '_' . date('Ymd_His') . '.csv"');
$out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, $head);
foreach ($rows as $r) fputcsv($out, array_map(fn($v) => csv_safe($v === null ? '' : (string)$v), array_values($r)));
fclose($out); exit;
