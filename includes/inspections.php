<?php
declare(strict_types=1);

function latest_sql(): string {
    return 'SELECT i.* FROM inspections i JOIN (SELECT device_id, MAX(id) mid FROM inspections GROUP BY device_id) m ON i.id=m.mid';
}
/* $items = [[ชื่อรายการ, 'ok'|'no'], ...]  ไม่มีรายการใดมีปัญหา (✕) = ผ่าน, มีอย่างน้อย 1 รายการ = ไม่ผ่าน */
function calc_overall(array $items): string {
    foreach ($items as $it) if ($it[1] === 'no') return 'ไม่ผ่าน';
    return 'ผ่าน';
}
/* ตรวจว่าเป็นรูป PNG (data URL) จริงและขนาดไม่เกินกำหนด */
function sig_valid(string $s): bool {
    if (strlen($s) > 400000 || !preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $s, $m)) return false;
    $bin = base64_decode($m[1], true);
    return $bin !== false && str_starts_with($bin, "\x89PNG");
}
