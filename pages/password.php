<?php
if (is_post() && post('action') === 'change_pw') {
    $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? ''); $new2 = (string)($_POST['new2'] ?? '');
    $row = q('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch();
    $err = [];
    if (!$row || !password_verify($cur, $row['password_hash'])) $err[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    if (strlen($new) < 8) $err[] = 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัวอักษร';
    if ($new !== $new2) $err[] = 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
    if (!$err && $new === $cur) $err[] = 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม';
    if ($err) { foreach ($err as $m) flash('err', $m); go('?p=password'); }
    q('UPDATE users SET password_hash=?, must_change=0 WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $_SESSION['uid']]);
    flash('ok', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว'); go('?p=devices');
}
?>
<h1>เปลี่ยนรหัสผ่าน</h1>
<form method="post" class="card form narrow"><?= csrf_field() ?><input type="hidden" name="action" value="change_pw">
    <label>รหัสผ่านปัจจุบัน (หรือรหัสผ่านชั่วคราวที่ได้รับ)<input type="password" name="current" autocomplete="current-password" required></label>
    <label>รหัสผ่านใหม่ (อย่างน้อย 8 ตัว)<input type="password" name="new" autocomplete="new-password" required minlength="8"></label>
    <label>ยืนยันรหัสผ่านใหม่<input type="password" name="new2" autocomplete="new-password" required minlength="8"></label>
    <div class="actions"><button class="btn primary big">บันทึกรหัสผ่านใหม่</button></div></form>
