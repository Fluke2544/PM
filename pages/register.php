<?php
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
if (is_post() && post('action') === 'register') {
    $u = post('username'); $n = post('display_name'); $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');
    $err = [];
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $u)) $err[] = 'ชื่อผู้ใช้ต้องเป็นตัวอักษรอังกฤษ ตัวเลข _ . - ยาว 3-30 ตัว';
    elseif (q('SELECT id FROM users WHERE username=?', [$u])->fetch()) $err[] = 'ชื่อผู้ใช้นี้ถูกใช้แล้ว';
    if ($n === '' || mb_strlen($n) > 100) $err[] = 'กรุณากรอกชื่อ-นามสกุล (ไม่เกิน 100 ตัวอักษร)';
    if (strlen($pw) < 8) $err[] = 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร';
    if ($pw !== $pw2) $err[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    if ($err) { $_SESSION['old'] = ['username' => $u, 'display_name' => $n]; foreach ($err as $m) flash('err', $m); go('?p=register'); }
    try { q('INSERT INTO users (username,display_name,password_hash,role) VALUES (?,?,?,?)', [$u, $n, password_hash($pw, PASSWORD_DEFAULT), 'user']); }
    catch (PDOException $e) { if ($e->getCode() === '23000') { flash('err', 'ชื่อผู้ใช้นี้ถูกใช้แล้ว'); go('?p=register'); } throw $e; }
    sign_in(q('SELECT * FROM users WHERE username=?', [$u])->fetch());
    flash('ok', 'ลงทะเบียนสำเร็จ ยินดีต้อนรับ บัญชีผู้ใช้ทั่วไปมีสิทธิ์อ่านข้อมูลอย่างเดียว');
    go('?p=devices');
}
?>
<div class="login card"><h1>💻 NB Check</h1><h2>ลงทะเบียนผู้ใช้ใหม่</h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="register">
    <label>ชื่อผู้ใช้ (อังกฤษ/ตัวเลข 3-30 ตัว)<input name="username" value="<?= h($old['username'] ?? '') ?>" autocomplete="username" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}" autofocus></label>
    <label>ชื่อ-นามสกุล<input name="display_name" value="<?= h($old['display_name'] ?? '') ?>" required maxlength="100"></label>
    <label>รหัสผ่าน (อย่างน้อย 8 ตัว)<input type="password" name="password" autocomplete="new-password" required minlength="8"></label>
    <label>ยืนยันรหัสผ่าน<input type="password" name="password2" autocomplete="new-password" required minlength="8"></label>
    <button class="btn primary big">ลงทะเบียนและเข้าใช้งาน</button></form>
<a class="btn big" href="?">← กลับไปเข้าสู่ระบบ</a></div>
