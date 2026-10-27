<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

const RESULTS = ['ผ่าน', 'ไม่ผ่าน', 'ไม่เกี่ยวข้อง'];
const OVERALLS = ['ผ่าน', 'ผ่านแต่ต้องติดตาม', 'ไม่ผ่าน'];
/* รายการเช็คลิสต์แบบเดิม (ใช้แสดงประวัติเก่าที่บันทึกไว้ก่อนเปลี่ยนแบบฟอร์ม) */
const LEGACY_CHECKS = [
    'body_ok' => 'สภาพตัวเครื่อง (รอยร้าว/บุบ/บานพับ)',
    'screen_ok' => 'หน้าจอ (จุดเสีย/สี/ความสว่าง)',
    'keyboard_ok' => 'คีย์บอร์ดทุกปุ่ม',
    'touchpad_ok' => 'ทัชแพดและปุ่มคลิก',
    'ports_ok' => 'พอร์ตเชื่อมต่อ (USB/HDMI/LAN)',
    'audio_cam_ok' => 'ลำโพง ไมค์ และกล้อง',
    'wifi_bt_ok' => 'Wi-Fi และบลูทูธ',
    'battery_ok' => 'แบตเตอรี่ใช้งานได้',
    'adapter_ok' => 'อะแดปเตอร์/สายชาร์จ',
    'thermal_ok' => 'ความร้อนและพัดลม',
    'os_update_ok' => 'อัปเดตระบบปฏิบัติการล่าสุด',
    'antivirus_ok' => 'โปรแกรมป้องกันไวรัสทำงาน',
    'disk_encrypt_ok' => 'เข้ารหัสดิสก์',
];
const DEVICE_TYPES = ['computer' => ['Notebook', 'PC', 'MAC', 'ALL IN ONE'], 'cctv' => ['DVR', 'NVR', 'UPS', 'CCTV']];
const CATEGORY_LABEL = ['computer' => 'คอมพิวเตอร์', 'cctv' => 'กล้องวงจรปิด / อุปกรณ์ที่เกี่ยวข้อง'];
/* รายการ Drop-down: ฝ่าย (เก็บในช่อง department) และ สาขา (เก็บในช่อง location) */
const DEPARTMENTS = ['ฝ่ายการตลาดกลาง', 'ฝ่ายผลิตภัณฑ์ Landy home', 'ฝ่ายผลิตภัณฑ์ Landy Grand', 'ฝ่ายผลิตภัณฑ์เทรนดี้โฮม', 'ฝ่ายลูกค้าสัมพันธ์',
    'ฝ่ายขาย (ส่วนพัฒนาฝ่ายขาย)', 'Call Center', 'ฝ่ายเขียนแบบและประมาณราคา', 'ฝ่ายปฏิบัติการ', 'ฝ่ายจัดหา', 'ฝ่ายก่อสร้าง', 'ฝ่ายบริการลูกค้า',
    'ฝ่ายวิศวกรรม', 'ฝ่ายวิจัยและพัฒนาธุรกิจ', 'ฝ่ายทรัพยากรมนุษย์และบริหารสำนักงาน', 'ฝ่ายเทคโนโลยีสารสนเทศ (IT)', 'บัญชีและการลงทุน',
    'ฝ่ายพัฒนาระบบคุณภาพ ISO', 'ฝ่ายขาย (ส่วนปรับแบบ)', 'ฝ่ายขาย', 'ฝ่ายขาย ส่วนบริการหลังการขาย'];
const BRANCHES = ['ลาดพร้าว', 'สุขุมวิท', 'บางนา', 'พงษ์เพชร', 'รามอินทรา', 'ราชพฤกษ์', 'สระบุรี', 'โคราช', 'นครปฐม'];
const DEV_FIELDS = ['asset_tag', 'device_name', 'device_type', 'serial_number', 'brand', 'model', 'cpu', 'ram_gb', 'storage', 'os', 'software', 'department', 'location', 'note'];

session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

function device_category(?string $type): string { return in_array($type, DEVICE_TYPES['cctv'], true) ? 'cctv' : 'computer'; }
/* รายการตรวจเริ่มต้นตามแบบฟอร์มตรวจสอบ */
function default_items(string $cat): array {
    if ($cat === 'cctv') {
        $o = ['เครื่องบันทึก', 'เครื่องสำรองไฟ UPS'];
        for ($i = 1; $i <= 18; $i++) $o[] = sprintf('Camera %02d', $i);
        return $o;
    }
    return ['Boot Windows', 'Battery', 'Microsoft Office', 'Google Chrome', 'PDF', 'Line', 'FortiClientVPN', 'AnyDesk'];
}
/* Drop-down: ถ้าค่าเดิมในฐานข้อมูลไม่อยู่ในรายการ จะแสดงเป็นตัวเลือกเพิ่มให้ ไม่ให้ข้อมูลหาย */
function select_field(string $n, string $label, array $opts, string $cur, string $empty = '-- เลือก --'): string {
    $o = '<label>' . h($label) . '<select name="' . $n . '"><option value="">' . h($empty) . '</option>';
    if ($cur !== '' && !in_array($cur, $opts, true)) $o .= '<option selected>' . h($cur) . '</option>';
    foreach ($opts as $x) $o .= '<option' . ($x === $cur ? ' selected' : '') . '>' . h($x) . '</option>';
    return $o . '</select></label>';
}
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        fail_page('คำขอไม่ถูกต้องหรือหมดอายุ กรุณากลับไปโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function go(string $url): void { header('Location: ' . $url); exit; }
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function post(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function valid_date($d): bool {
    if (!is_string($d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$y, $m, $dd] = array_map('intval', explode('-', $d));
    return checkdate($m, $dd, $y);
}
function fdate(?string $d): string {
    if (!$d || !valid_date(substr($d, 0, 10))) return '-';
    $t = strtotime(substr($d, 0, 10));
    return date('d/m/', $t) . ((int)date('Y', $t) + 543);
}
function fail_page(string $msg): void {
    echo '<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ข้อผิดพลาด</title><body style="font-family:sans-serif;padding:24px"><h2>เกิดข้อผิดพลาด</h2><p>' . h($msg) . '</p><p><a href="?">กลับหน้าแรก</a></p></body></html>';
    exit;
}

/* ----- สิทธิ์ผู้ใช้ ----- */
function logged_in(): bool { return !empty($_SESSION['uid']); }
function is_admin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }
function require_admin(): void {
    if (!is_admin()) { flash('err', 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่ทำรายการนี้ได้ ผู้ใช้ทั่วไปมีสิทธิ์อ่านอย่างเดียว'); go('?p=devices'); }
}
function sign_in(array $u): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['username'] = $u['username'];
    $_SESSION['display'] = $u['display_name'];
    $_SESSION['role'] = $u['role'];
    unset($_SESSION['csrf']);
}

/* ----- ตัวช่วยแสดงผล ----- */
function badge(string $t): string {
    $c = match ($t) { 'ผ่าน' => 'b-ok', 'ผ่านแต่ต้องติดตาม' => 'b-warn', 'ไม่ผ่าน' => 'b-bad', default => 'b-na' };
    return '<span class="badge ' . $c . '">' . h($t) . '</span>';
}
function pager(int $total, int $per, int $page, array $params): string {
    $pages = max(1, (int)ceil($total / $per));
    if ($pages <= 1) return '';
    $o = '<nav class="pager">';
    for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++)
        $o .= '<a class="btn' . ($i === $page ? ' primary' : '') . '" href="?' . h(http_build_query($params + ['page' => $i])) . '">' . $i . '</a>';
    return $o . '<span class="muted">หน้า ' . $page . '/' . $pages . ' (ทั้งหมด ' . $total . ' รายการ)</span></nav>';
}
function software_list(?string $s): array {
    $out = [];
    foreach (preg_split('/\R/u', (string)$s) ?: [] as $l) { $l = trim($l); if ($l !== '') $out[] = $l; }
    return $out;
}
function fld(string $n, string $l, $v, string $type = 'text', string $x = ''): string {
    return '<label>' . h($l) . '<input type="' . $type . '" name="' . $n . '" value="' . h($v) . '" ' . $x . '></label>';
}
function rand_password(int $len = 10): string {
    $c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'; $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $c[random_int(0, strlen($c) - 1)];
    return $p;
}
