<?php
if (is_post() && post('action') === 'login') {
    $u = post('username'); $pw = (string)($_POST['password'] ?? ''); $key = mb_strtolower(mb_substr($u, 0, 100)); $max = 5;
    if ($key !== '' && (int)q('SELECT COUNT(*) FROM login_fails WHERE username=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)', [$key])->fetchColumn() >= $max) {
        flash('err', 'ใส่รหัสผ่านผิดครบ ' . $max . ' ครั้ง ระงับการเข้าสู่ระบบของบัญชีนี้ชั่วคราว 15 นาที กรุณาลองใหม่ภายหลัง'); go('?');
    }
    $row = $u !== '' ? q('SELECT * FROM users WHERE username=?', [$u])->fetch() : false;
    if ($row && password_verify($pw, $row['password_hash'])) {
        q('DELETE FROM login_fails WHERE username=?', [$key]);
        sign_in($row);
        go((int)$row['must_change'] === 1 ? '?p=password' : '?p=devices');
    }
    usleep(600000);
    $left = $max;
    if ($key !== '') {
        q('DELETE FROM login_fails WHERE created_at<DATE_SUB(NOW(),INTERVAL 1 DAY)');
        q('INSERT INTO login_fails (username,ip) VALUES (?,?)', [$key, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]);
        $left = $max - (int)q('SELECT COUNT(*) FROM login_fails WHERE username=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)', [$key])->fetchColumn();
    }
    flash('err', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' . ($left > 0 ? ' (เหลืออีก ' . $left . ' ครั้งก่อนถูกระงับชั่วคราว)' : ' ระงับการเข้าสู่ระบบชั่วคราว 15 นาที'));
    go('?');
}
?>
<div class="login card"><h1>💻 NB Check</h1><p class="muted">ระบบตรวจเช็คโน้ตบุ๊ก</p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="login">
    <label>ชื่อผู้ใช้<input name="username" autocomplete="username" required autofocus></label>
    <label>รหัสผ่าน<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn primary big">เข้าสู่ระบบ</button></form>
<?php if (REGISTER_ENABLED): ?><a class="btn big" href="?p=register">ลงทะเบียนผู้ใช้ใหม่</a><?php endif; ?></div>
