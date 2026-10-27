<?php
$id = (int)($_GET['id'] ?? 0);
$d = q('SELECT * FROM devices WHERE id=?', [$id])->fetch();
if (!$d) { flash('err', 'ไม่พบเครื่องที่ต้องการดู'); go('?p=devices'); }
$ins = q('SELECT id,inspected_at,inspector_name,overall_result FROM inspections WHERE device_id=? ORDER BY inspected_at DESC,id DESC LIMIT 5', [$id])->fetchAll();
$soft = software_list($d['software']);
$info = ['รหัสทรัพย์สิน' => $d['asset_tag'], 'ชื่อเครื่อง' => $d['device_name'], 'ประเภท' => $d['device_type'] ?: 'Notebook', 'หมายเลขซีเรียล' => $d['serial_number'], 'ยี่ห้อ / รุ่น' => trim($d['brand'] . ' ' . $d['model']), 'CPU' => $d['cpu'],
    'RAM' => $d['ram_gb'] ? $d['ram_gb'] . ' GB' : '', 'หน่วยความจำ/ดิสก์' => $d['storage'], 'ระบบปฏิบัติการ' => $d['os'], 'ผู้ใช้งาน' => $d['owner'], 'ฝ่าย' => $d['department'], 'สาขา' => $d['location']];
?>
<div class="head"><h1>เครื่อง <?= h($d['asset_tag']) ?></h1><div class="actions">
    <a class="btn" href="?p=devices">← กลับ</a>
    <?php if (is_admin()): ?><a class="btn" href="?p=device_form&id=<?= $id ?>">แก้ไข</a><?php endif; ?></div></div>
<section class="grid two">
    <div class="card"><h2>ข้อมูลเครื่อง</h2><dl class="dl"><?php foreach ($info as $k => $v) echo '<dt>' . h($k) . '</dt><dd>' . ($v !== '' && $v !== null ? h($v) : '<span class="muted">-</span>') . '</dd>'; ?></dl>
        <?php if ($d['note']): ?><p><b>หมายเหตุ:</b> <?= nl2br(h($d['note'])) ?></p><?php endif; ?></div>
    <div class="card"><h2>โปรแกรมในเครื่อง (<?= count($soft) ?>)</h2>
        <?php if (!$soft): ?><p class="muted">ยังไม่ได้บันทึกรายการโปรแกรม</p><?php else: ?><div class="chips"><?php foreach ($soft as $s) echo '<span class="chip c-na">' . h($s) . '</span>'; ?></div><?php endif; ?></div>
    <div class="card"><h2>ตรวจล่าสุด</h2>
        <?php if (!$ins): ?><p class="muted">ยังไม่เคยตรวจเช็ค</p><?php else: ?><ul class="list"><?php foreach ($ins as $r): ?>
            <li><a href="?p=history&device_id=<?= $id ?>"><?= fdate($r['inspected_at']) ?></a> <?= badge($r['overall_result']) ?><small>ผู้ตรวจ: <?= h($r['inspector_name']) ?></small></li><?php endforeach; ?></ul><?php endif; ?></div>
</section>
