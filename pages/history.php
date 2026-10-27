<?php
if (is_post() && post('action') === 'inspect_delete') {
    require_admin();
    $n = q('DELETE FROM inspections WHERE id=?', [(int)post('id')])->rowCount();
    flash($n ? 'ok' : 'err', $n ? 'ลบรายการตรวจแล้ว' : 'ไม่พบรายการ');
    go('?p=history');
}
$did = (int)($_GET['device_id'] ?? 0); $yr = (int)($_GET['year'] ?? 0); $br = (string)($_GET['branch'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 20;
$w = []; $a = [];
if ($did) { $w[] = 'i.device_id=?'; $a[] = $did; }
if ($yr >= 1900 && $yr <= 2999) { $w[] = 'YEAR(i.inspected_at)=?'; $a[] = $yr; } else $yr = 0;
if ($br !== '') { $w[] = 'd.location=?'; $a[] = $br; }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$total = (int)q("SELECT COUNT(*) FROM inspections i JOIN devices d ON d.id=i.device_id $where", $a)->fetchColumn();
$rows = q("SELECT i.*,d.asset_tag,d.device_type,d.brand,d.model,d.owner FROM inspections i JOIN devices d ON d.id=i.device_id $where ORDER BY i.inspected_at DESC,i.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $a)->fetchAll();
$itemsBy = [];
if ($rows) {
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    foreach (q('SELECT inspection_id,item_name,result FROM inspection_items WHERE inspection_id IN (' . implode(',', $ids) . ') ORDER BY id')->fetchAll() as $it) $itemsBy[$it['inspection_id']][] = $it;
}
$years = q('SELECT DISTINCT YEAR(inspected_at) y FROM inspections ORDER BY y DESC')->fetchAll(PDO::FETCH_COLUMN);
$branches = BRANCHES;
$devOne = $did ? q('SELECT asset_tag FROM devices WHERE id=?', [$did])->fetchColumn() : '';
?>
<h1>ประวัติการตรวจ</h1>
<form class="card filters" method="get"><input type="hidden" name="p" value="history"><?php if ($did) echo '<input type="hidden" name="device_id" value="' . $did . '">'; ?>
    <select name="year" data-autofilter><option value="">ทุกปีที่ตรวจ</option><?php foreach ($years as $y) echo '<option value="' . (int)$y . '"' . ((int)$y === $yr ? ' selected' : '') . '>ปี ' . ((int)$y + 543) . '</option>'; ?></select>
    <select name="branch" data-autofilter><option value="">ทุกสาขา</option><?php foreach ($branches as $b) echo '<option' . ($b === $br ? ' selected' : '') . '>' . h($b) . '</option>'; ?></select>
    <noscript><button class="btn primary">กรอง</button></noscript><a class="btn" href="?p=history">ล้าง</a></form>
<?php if ($did && $devOne): ?><p class="muted">แสดงเฉพาะเครื่อง <b><?= h($devOne) ?></b> · <a href="?p=history<?= $yr ? '&year=' . $yr : '' ?><?= $br !== '' ? '&branch=' . urlencode($br) : '' ?>">ดูทุกเครื่อง</a></p><?php endif; ?>
<?php if (!$rows) echo '<div class="card muted">ไม่พบประวัติการตรวจ</div>'; foreach ($rows as $r): ?>
<details class="card hist"><summary><span><b><?= h($r['asset_tag']) ?></b> <small class="muted"><?= h($r['device_type'] ?: 'Notebook') ?></small> <?= h(trim($r['brand'] . ' ' . $r['model'])) ?></span><span><?= fdate($r['inspected_at']) ?> · <?= h($r['inspector_name']) ?></span><?= badge($r['overall_result']) ?></summary>
    <?php $its = $itemsBy[$r['id']] ?? []; ?>
    <?php if ($its): ?><div class="chips"><?php foreach ($its as $it) echo '<span class="chip ' . ($it['result'] === 'ok' ? 'c-ok' : 'c-no') . '">' . ($it['result'] === 'ok' ? '✓ ' : '✕ ') . h($it['item_name']) . '</span>'; ?></div>
    <?php elseif (isset($r['body_ok'])): ?><div class="chips"><?php foreach (LEGACY_CHECKS as $k => $l) echo '<span class="chip ' . (['ผ่าน' => 'c-ok', 'ไม่ผ่าน' => 'c-no'][$r[$k]] ?? 'c-na') . '">' . h($l) . ': ' . h($r[$k]) . '</span>'; ?></div><?php endif; ?>
    <?php if ($r['problem_note']): ?><p><b>ปัญหา:</b> <?= nl2br(h($r['problem_note'])) ?></p><?php endif; ?>
        <div class="grid two sigs">
    <?php foreach (['inspector_signature' => 'ผู้ดำเนินการตรวจเช็ค: ' . $r['inspector_name'], 'user_signature' => 'ผู้ใช้งานอุปกรณ์' . (($r['user_name'] ?? '') !== '' ? ': ' . $r['user_name'] . ($r['user_position'] ? ' (' . $r['user_position'] . ')' : '') : ($r['owner'] ? ': ' . $r['owner'] : ''))] as $k => $cap): ?>
        <figure><?php if (sig_valid((string)$r[$k])): ?><img class="sigimg" alt="ลายเซ็น" src="<?= h($r[$k]) ?>"><?php else: ?><div class="sigimg empty muted">ไม่มีลายเซ็น</div><?php endif; ?><figcaption class="muted"><?= h($cap) ?></figcaption></figure>
    <?php endforeach; ?></div>
    <?php if (is_admin()): ?><form method="post" class="inl" data-confirm="ยืนยันการลบรายการตรวจนี้?"><?= csrf_field() ?><input type="hidden" name="action" value="inspect_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small danger">ลบรายการนี้</button></form><?php endif; ?>
</details>
<?php endforeach; ?>
<?= pager($total, $per, $page, ['p' => 'history', 'device_id' => $did ?: '', 'year' => $yr ?: '', 'branch' => $br]) ?>
