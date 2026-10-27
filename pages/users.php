<?php
require_admin();
if (is_post()) {
    $act = post('action'); $id = (int)post('id');
    $t = q('SELECT * FROM users WHERE id=?', [$id])->fetch();
    if (!$t) { flash('err', 'ไม่พบบัญชีผู้ใช้'); go('?p=users'); }
    if ($id === (int)$_SESSION['uid']) { flash('err', 'ไม่สามารถทำรายการนี้กับบัญชีของตัวเองได้ (เปลี่ยนรหัสผ่านของตัวเองที่เมนู "รหัสผ่าน")'); go('?p=users'); }
    if ($act === 'reset_pw') {
        $pw = rand_password();
        q('UPDATE users SET password_hash=?, must_change=1 WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
        q('DELETE FROM login_fails WHERE username=?', [mb_strtolower($t['username'])]);
        $_SESSION['reset_pw'] = ['u' => $t['username'], 'n' => $t['display_name'], 'p' => $pw];
        flash('ok', 'รีเซ็ตรหัสผ่านของ ' . $t['username'] . ' เรียบร้อยแล้ว');
    } elseif ($act === 'set_role') {
        $role = post('role') === 'admin' ? 'admin' : 'user';
        q('UPDATE users SET role=? WHERE id=?', [$role, $id]);
        flash('ok', 'เปลี่ยนสิทธิ์ของ ' . $t['username'] . ' เป็น ' . ($role === 'admin' ? 'Admin' : 'ผู้ใช้ทั่วไป') . ' แล้ว');
    } elseif ($act === 'delete_user') {
        q('DELETE FROM users WHERE id=?', [$id]);
        flash('ok', 'ลบบัญชี ' . $t['username'] . ' แล้ว');
    }
    go('?p=users');
}
$reset = $_SESSION['reset_pw'] ?? null; unset($_SESSION['reset_pw']);
$rows = q('SELECT * FROM users ORDER BY role, created_at DESC')->fetchAll();
?>
<h1>ผู้ใช้งานระบบ</h1>
<p class="muted">ผู้ที่ลงทะเบียนเองจะเข้าใช้ได้ทันทีในสิทธิ์ "ผู้ใช้ทั่วไป" (อ่านอย่างเดียว) เฉพาะ Admin เท่านั้นที่เพิ่ม แก้ไข ลบ และบันทึกผลตรวจได้</p>
<div class="card tw"><table class="resp"><thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>สิทธิ์</th><th>สมัครเมื่อ</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $self = (int)$r["id"] === (int)$_SESSION["uid"]; $adm = $r['role'] === 'admin'; ?>
    <tr><td data-label="ชื่อผู้ใช้"><div class="cv"><b><?= h($r['username']) ?></b><?= $self ? ' <small class="muted">(คุณ)</small>' : '' ?></div></td>
    <td data-label="ชื่อ-นามสกุล"><div class="cv"><?= h($r['display_name']) ?></div></td>
    <td data-label="สิทธิ์"><div class="cv"><span class="badge <?= $adm ? 'b-ok' : 'b-na' ?>"><?= $adm ? 'Admin' : 'ผู้ใช้ทั่วไป' ?></span></div></td>
    <td data-label="สมัครเมื่อ"><div class="cv"><?= fdate($r['created_at']) ?></div></td>
    <td class="acts"><?php if (!$self): ?>
        <form method="post" class="inl" data-confirm="รีเซ็ตรหัสผ่านของ <?= h($r['username']) ?> ? ระบบจะสร้างรหัสผ่านชั่วคราวใหม่ให้"><?= csrf_field() ?><input type="hidden" name="action" value="reset_pw"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small primary">รีเซ็ตรหัสผ่าน</button></form>
        <form method="post" class="inl" data-confirm="เปลี่ยนสิทธิ์ของ <?= h($r['username']) ?> ?"><?= csrf_field() ?><input type="hidden" name="action" value="set_role"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="role" value="<?= $adm ? 'user' : 'admin' ?>"><button class="btn small"><?= $adm ? 'ลดเป็นผู้ใช้ทั่วไป' : 'ตั้งเป็น Admin' ?></button></form>
        <form method="post" class="inl" data-confirm="ยืนยันการลบบัญชี <?= h($r['username']) ?> ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small danger">ลบ</button></form>
    <?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php if ($reset): ?>
<dialog id="pwModal" data-autoopen><h2>รีเซ็ตรหัสผ่านแล้ว</h2>
    <p>ผู้ใช้: <b><?= h($reset['u']) ?></b> (<?= h($reset['n']) ?>)</p>
    <div class="lbl">รหัสผ่านชั่วคราว</div>
    <div class="pwbox" id="pwText"><?= h($reset['p']) ?></div>
    <p class="muted">แจ้งรหัสนี้ให้ผู้ใช้ ระบบจะบังคับให้ตั้งรหัสผ่านใหม่เมื่อเข้าสู่ระบบ <b>รหัสนี้จะไม่แสดงอีก</b></p>
    <div class="actions"><button type="button" class="btn primary" id="pwCopy">คัดลอกรหัส</button><button type="button" class="btn" id="pwClose">ปิด</button></div></dialog>
<?php endif; ?>