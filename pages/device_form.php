<?php
require_admin();
$id = (int)($_GET['id'] ?? post('id'));
if (is_post() && post('action') === 'device_save') {
    $id = (int)post('id'); $d = dev_input(); $err = dev_validate($d, $id);
    if ($err) { $_SESSION['old'] = $d; foreach ($err as $m) flash('err', $m); go('?p=device_form' . ($id ? '&id=' . $id : '')); }
    try {
        if ($id) {
            q('UPDATE devices SET ' . implode('=?,', DEV_FIELDS) . '=? WHERE id=?', [...dev_params($d), $id]);
            flash('ok', 'แก้ไขข้อมูลเครื่องเรียบร้อยแล้ว');
        } else {
            q('INSERT INTO devices (' . implode(',', DEV_FIELDS) . ') VALUES (' . implode(',', array_fill(0, count(DEV_FIELDS), '?')) . ')', dev_params($d));
            flash('ok', 'เพิ่มเครื่องเรียบร้อยแล้ว');
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { $_SESSION['old'] = $d; flash('err', 'รหัสทรัพย์สินซ้ำกับเครื่องอื่นในระบบ'); go('?p=device_form' . ($id ? '&id=' . $id : '')); }
        throw $e;
    }
    go('?p=devices');
}
$d = array_fill_keys(DEV_FIELDS, '');
if ($id) {
    $r = q('SELECT * FROM devices WHERE id=?', [$id])->fetch();
    if (!$r) { flash('err', 'ไม่พบเครื่องที่ต้องการแก้ไข'); go('?p=devices'); }
    foreach (DEV_FIELDS as $f) $d[$f] = (string)($r[$f] ?? '');
}
if (!empty($_SESSION['old'])) { foreach ($_SESSION['old'] as $k => $v) if (isset($d[$k])) $d[$k] = (string)$v; unset($_SESSION['old']); }
?>
<h1><?= $id ? 'แก้ไขเครื่อง' : 'เพิ่มเครื่อง' ?></h1>
<form method="post" class="card form"><?= csrf_field() ?><input type="hidden" name="action" value="device_save"><input type="hidden" name="id" value="<?= $id ?>">
<div class="grid two">
    <?= fld('asset_tag', 'รหัสทรัพย์สิน *', $d['asset_tag'], 'text', 'required maxlength="50"') ?>
    <?= fld('device_name', 'ชื่อเครื่อง', $d['device_name'], 'text', 'maxlength="100"') ?>
    <label>ประเภทอุปกรณ์ *<select name="device_type" required><option value="">-- เลือก --</option>
        <?php foreach (DEVICE_TYPES as $cat => $ts): ?><optgroup label="<?= h(CATEGORY_LABEL[$cat]) ?>"><?php foreach ($ts as $t) echo '<option' . ($t === $d['device_type'] ? ' selected' : '') . '>' . h($t) . '</option>'; ?></optgroup><?php endforeach; ?></select></label>
    <?= fld('serial_number', 'หมายเลขซีเรียล', $d['serial_number'], 'text', 'maxlength="100"') ?>
    <?= fld('brand', 'ยี่ห้อ', $d['brand'], 'text', 'maxlength="60"') ?>
    <?= fld('model', 'รุ่น', $d['model'], 'text', 'maxlength="100"') ?>
    <?= fld('cpu', 'CPU', $d['cpu'], 'text', 'maxlength="100"') ?>
    <?= fld('ram_gb', 'RAM (GB)', $d['ram_gb'], 'number', 'min="0" max="4096" inputmode="numeric"') ?>
    <?= fld('storage', 'หน่วยความจำ/ดิสก์', $d['storage'], 'text', 'maxlength="60" placeholder="เช่น SSD 512GB"') ?>
    <?= fld('os', 'ระบบปฏิบัติการ', $d['os'], 'text', 'maxlength="60"') ?>
    <?= select_field('department', 'ฝ่าย', DEPARTMENTS, $d['department']) ?>
    <?= select_field('location', 'สาขา', BRANCHES, $d['location']) ?>
</div>
<label>โปรแกรมที่ติดตั้งในเครื่อง (พิมพ์บรรทัดละ 1 โปรแกรม)<textarea name="software" rows="6" placeholder="Microsoft Office 2021&#10;Google Chrome&#10;Adobe Acrobat Reader"><?= h($d['software']) ?></textarea></label>
<label>หมายเหตุ<textarea name="note" rows="3"><?= h($d['note']) ?></textarea></label>
<div class="actions"><button class="btn primary big">บันทึก</button><a class="btn big" href="?p=devices">ยกเลิก</a></div></form>
