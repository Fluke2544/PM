<?php
declare(strict_types=1);

function dev_input(): array { $d = []; foreach (DEV_FIELDS as $f) $d[$f] = post($f); return $d; }

function dev_validate(array $d, int $id = 0): array {
    $e = [];
    if ($d['asset_tag'] === '' || mb_strlen($d['asset_tag']) > 50) $e[] = 'กรุณากรอกรหัสทรัพย์สิน (ไม่เกิน 50 ตัวอักษร)';
    elseif (q('SELECT id FROM devices WHERE asset_tag=? AND id<>?', [$d['asset_tag'], $id])->fetch()) $e[] = 'รหัสทรัพย์สิน "' . $d['asset_tag'] . '" มีอยู่ในระบบแล้ว';
    if (!in_array($d['device_type'], [...DEVICE_TYPES['computer'], ...DEVICE_TYPES['cctv']], true)) $e[] = 'กรุณาเลือกประเภทอุปกรณ์';
    if ($d['ram_gb'] !== '' && (!ctype_digit($d['ram_gb']) || (int)$d['ram_gb'] > 4096)) $e[] = 'RAM ต้องเป็นจำนวนเต็ม (GB)';
    foreach (['device_name' => 100, 'serial_number' => 100, 'brand' => 60, 'model' => 100, 'cpu' => 100, 'storage' => 60, 'os' => 60, 'department' => 100, 'location' => 100, 'software' => 20000, 'note' => 5000] as $k => $n)
        if (mb_strlen($d[$k]) > $n) $e[] = "ช่อง $k ยาวเกิน $n ตัวอักษร";
    return $e;
}
function dev_params(array $d): array {
    $o = [];
    foreach (DEV_FIELDS as $f) $o[] = $d[$f] === '' ? null : ($f === 'ram_gb' ? (int)$d[$f] : $d[$f]);
    return $o;
}
