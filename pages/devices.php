<?php
if (is_post() && post('action') === 'device_delete') {
    require_admin();
    $n = q('DELETE FROM devices WHERE id=?', [(int)post('id')])->rowCount();
    flash($n ? 'ok' : 'err', $n ? 'ลบเครื่องและประวัติการตรวจของเครื่องนั้นแล้ว' : 'ไม่พบเครื่องที่ต้องการลบ');
    go('?p=devices');
}
$qs = trim((string)($_GET['q'] ?? '')); $dp = (string)($_GET['dept'] ?? ''); $br = (string)($_GET['branch'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 20;
$w = []; $a = [];
if ($qs !== '') { $like = '%' . addcslashes($qs, '%_\\') . '%'; $w[] = '(d.asset_tag LIKE ? OR d.device_name LIKE ? OR d.serial_number LIKE ? OR d.brand LIKE ? OR d.model LIKE ? OR d.owner LIKE ? OR d.software LIKE ?)'; array_push($a, $like, $like, $like, $like, $like, $like, $like); }
if ($dp !== '') { $w[] = 'd.department=?'; $a[] = $dp; }
if ($br !== '') { $w[] = 'd.location=?'; $a[] = $br; }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$total = (int)q("SELECT COUNT(*) FROM devices d $where", $a)->fetchColumn();
$yStart = date('Y') . '-01-01'; $yTh = (int)date('Y') + 543;
$rows = q("SELECT d.*,(SELECT overall_result FROM inspections i WHERE i.device_id=d.id AND i.inspected_at>=? ORDER BY i.inspected_at DESC,i.id DESC LIMIT 1) last_result FROM devices d $where ORDER BY d.asset_tag LIMIT $per OFFSET " . (($page - 1) * $per), [$yStart, ...$a])->fetchAll();
$depts = DEPARTMENTS;
?>
<div class="head"><h1>ทะเบียนเครื่อง</h1><?php if (is_admin()): ?><a class="btn primary" href="?p=device_form">+ เพิ่มเครื่อง</a><?php endif; ?></div>
<form class="card filters" method="get" data-live="#devList"><input type="hidden" name="p" value="devices">
    <input name="q" value="<?= h($qs) ?>" placeholder="ค้นหา รหัส/ชื่อเครื่อง/ซีเรียล/ยี่ห้อ/รุ่น/ผู้ใช้/โปรแกรม">
    <select name="branch" data-autofilter><option value="">ทุกสาขา</option><?php foreach (BRANCHES as $s) echo '<option' . ($s === $br ? ' selected' : '') . '>' . h($s) . '</option>'; ?></select>
    <select name="dept" data-autofilter><option value="">ทุกฝ่าย</option><?php foreach ($depts as $s) echo '<option' . ($s === $dp ? ' selected' : '') . '>' . h($s) . '</option>'; ?></select>
    <noscript><button class="btn primary">ค้นหา</button></noscript><a class="btn" href="?p=devices">ล้าง</a></form>
<div id="devList">
<div class="card tw"><table class="resp"><thead><tr><th>รหัสทรัพย์สิน</th><th>เครื่อง</th><th>ผู้ใช้/ฝ่าย/สาขา</th><th>โปรแกรม</th><th>ตรวจปีนี้ (<?= $yTh ?>)</th><th></th></tr></thead><tbody>
<?php if (!$rows) echo '<tr><td colspan="6" class="muted">ไม่พบข้อมูล</td></tr>'; foreach ($rows as $r): ?>
    <tr><td data-label="รหัส"><div class="cv"><b><?= h($r['asset_tag']) ?></b><?= $r['device_name'] ? '<br>' . h($r['device_name']) : '' ?><br><small class="muted"><?= h($r['serial_number']) ?></small></div></td>
    <td data-label="เครื่อง"><div class="cv"><span class="badge b-na"><?= h($r['device_type'] ?: 'Notebook') ?></span> <?= h(trim($r['brand'] . ' ' . $r['model'])) ?><br><small class="muted"><?= h(implode(' · ', array_filter([$r['cpu'], $r['ram_gb'] ? $r['ram_gb'] . ' GB' : '', $r['storage']]))) ?></small></div></td>
    <td data-label="ผู้ใช้"><div class="cv"><?= h($r['owner']) ?><?= $r['owner'] ? '<br>' : '' ?><small class="muted"><?= h($r['department']) ?><?= $r['department'] && $r['location'] ? ' / ' : '' ?><?= $r['location'] ? 'สาขา' . h($r['location']) : '' ?></small></div></td>
    <td data-label="โปรแกรม"><div class="cv"><?= count(software_list($r['software'])) ?> รายการ</div></td>
    <td data-label="ตรวจล่าสุด"><div class="cv"><?= $r['last_result'] ? '<span class="badge b-ok">ตรวจแล้ว</span>' : '<span class="muted">ยังไม่ตรวจ</span>' ?></div></td>
    <td class="acts"><a class="btn small" href="?p=device_view&id=<?= $r['id'] ?>">ดูรายละเอียด</a>
    <?php if (is_admin()): ?><a class="btn small primary" href="?p=inspect&device_id=<?= $r['id'] ?>">ตรวจ</a><a class="btn small" href="?p=device_form&id=<?= $r['id'] ?>">แก้ไข</a>
    <form method="post" class="inl" data-confirm="ยืนยันการลบเครื่อง <?= h($r['asset_tag']) ?>? ประวัติการตรวจทั้งหมดของเครื่องนี้จะถูกลบด้วย"><?= csrf_field() ?><input type="hidden" name="action" value="device_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small danger">ลบ</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?= pager($total, $per, $page, ['p' => 'devices', 'q' => $qs, 'dept' => $dp, 'branch' => $br]) ?>
</div>