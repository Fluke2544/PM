<?php
declare(strict_types=1);
/* NB Check - ระบบตรวจเช็คโน้ตบุ๊ก (PHP 8.0+)  ไฟล์ควบคุมหลัก: ตรวจสิทธิ์และเรียกหน้าต่าง ๆ จากโฟลเดอร์ pages/ */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/devices.php';
require __DIR__ . '/includes/inspections.php';
require __DIR__ . '/includes/layout.php';

try {
    if (is_post()) csrf_check();
    $p = (string)($_GET['p'] ?? 'devices');

    if (is_post() && post('action') === 'logout') {
        $_SESSION = []; session_destroy(); session_start();
        flash('ok', 'ออกจากระบบเรียบร้อยแล้ว'); go('?');
    }

    if (!logged_in()) {
        $p = ($p === 'register' && REGISTER_ENABLED) ? 'register' : 'login';
        ob_start(); require __DIR__ . '/pages/' . $p . '.php';
        render_layout($p === 'register' ? 'ลงทะเบียน' : 'เข้าสู่ระบบ', ob_get_clean(), false);
        exit;
    }

    // อ่านสิทธิ์ล่าสุดจากฐานข้อมูลทุกครั้ง (กรณีผู้ดูแลเปลี่ยนสิทธิ์หรือลบบัญชี)
    $me = q('SELECT id,username,display_name,role,must_change FROM users WHERE id=?', [$_SESSION['uid']])->fetch();
    if (!$me) { $_SESSION = []; session_destroy(); session_start(); go('?'); }
    $_SESSION['role'] = $me['role']; $_SESSION['display'] = $me['display_name']; $_SESSION['username'] = $me['username'];

    $titles = ['devices' => 'ทะเบียนเครื่อง', 'device_view' => 'รายละเอียดเครื่อง', 'device_form' => 'ข้อมูลเครื่อง',
        'inspect' => 'ตรวจเช็คเครื่อง', 'history' => 'ประวัติการตรวจ', 'io' => 'ส่งออกข้อมูล', 'users' => 'ผู้ใช้งาน',
        'password' => 'เปลี่ยนรหัสผ่าน', 'export' => 'ส่งออก'];
    if (!isset($titles[$p])) $p = 'devices';
    if ((int)$me['must_change'] === 1 && $p !== 'password') { flash('err', 'กรุณาตั้งรหัสผ่านใหม่ก่อนใช้งานระบบ'); go('?p=password'); }

    ob_start();
    require __DIR__ . '/pages/' . $p . '.php';
    render_layout($titles[$p], ob_get_clean());
} catch (Throwable $e) {
    error_log('NBCheck: ' . $e->getMessage());
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    fail_page($e instanceof PDOException ? 'ไม่สามารถเชื่อมต่อหรือใช้งานฐานข้อมูลได้ กรุณาตรวจสอบการตั้งค่าในไฟล์ config.php หรือติดต่อผู้ดูแลระบบ' : 'ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง');
}
