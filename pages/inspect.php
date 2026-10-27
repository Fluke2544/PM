<?php
require_admin();
if (is_post() && post('action') === 'inspect_save') {
    $did = (int)post('device_id'); $err = [];
    if (!q('SELECT id FROM devices WHERE id=?', [$did])->fetch()) $err[] = 'กรุณาเลือกเครื่องที่ต้องการตรวจ';
    $name = post('inspector_name'); if ($name === '' || mb_strlen($name) > 100) $err[] = 'กรุณากรอกชื่อผู้ดำเนินการตรวจเช็ค';
    $date = post('inspected_at'); if (!valid_date($date)) $err[] = 'วันที่ตรวจไม่ถูกต้อง';
    $uname = post('user_name'); $upos = post('user_position');
    if ($uname === '' || mb_strlen($uname) > 100) $err[] = 'กรุณากรอกชื่อผู้ใช้งาน';
    if (mb_strlen($upos) > 100) $err[] = 'ตำแหน่งยาวเกิน 100 ตัวอักษร';
    $items = []; $nm = $_POST['nm'] ?? []; $rs = $_POST['res'] ?? [];
    if (is_array($nm)) foreach ($nm as $i => $n) {
        $n = trim((string)$n); if ($n === '') continue;
        if (mb_strlen($n) > 100) { $err[] = 'ชื่อรายการยาวเกิน 100 ตัวอักษร: ' . mb_substr($n, 0, 20) . '...'; continue; }
        $r = is_array($rs) ? (string)($rs[$i] ?? '') : '';
        if (!in_array($r, ['ok', 'no'], true)) { $err[] = 'กรุณาเลือก ✓ หรือ ✕ ที่รายการ: ' . $n; continue; }
        $items[] = [$n, $r];
    }
    if (!$items) $err[] = 'ต้องมีรายการตรวจอย่างน้อย 1 รายการ';
    if (count($items) > 200) $err[] = 'รายการตรวจมากเกิน 200 รายการ';
    $sigI = post('inspector_signature'); $sigU = post('user_signature');
    if (!sig_valid($sigI)) $err[] = 'กรุณาเซ็นลายเซ็นผู้ดำเนินการตรวจเช็ค';
    if (!sig_valid($sigU)) $err[] = 'กรุณาให้ผู้ใช้งานอุปกรณ์เซ็นลายเซ็น';
    $overall = calc_overall($items);
    $prob = post('problem_note');
    if (mb_strlen($prob) > 5000) $err[] = 'ปัญหาที่พบยาวเกิน 5000 ตัวอักษร';
    if ($err) { $_SESSION['old'] = $_POST; foreach ($err as $m) flash('err', $m); go('?p=inspect&device_id=' . $did); }
    $pdo = db(); $pdo->beginTransaction();
    try {
        q('INSERT INTO inspections (device_id,inspector_name,inspected_at,user_name,user_position,overall_result,problem_note,inspector_signature,user_signature) VALUES (?,?,?,?,?,?,?,?,?)',
            [$did, $name, $date, $uname, $upos ?: null, $overall, $prob ?: null, $sigI, $sigU]);
        $iid = (int)$pdo->lastInsertId();
        foreach ($items as [$n, $r]) q('INSERT INTO inspection_items (inspection_id,item_name,result) VALUES (?,?,?)', [$iid, $n, $r]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    flash('ok', 'บันทึกผลตรวจแล้ว ผลรวม: ' . $overall);
    go('?p=history&device_id=' . $did);
}
$did = (int)($_GET['device_id'] ?? 0);
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
if ($old && !$did) $did = (int)($old['device_id'] ?? 0);
$dev = $did ? q('SELECT * FROM devices WHERE id=?', [$did])->fetch() : false;
if (!$dev) { flash('err', 'กรุณาเลือกเครื่องจากทะเบียนเครื่อง แล้วกดปุ่ม "ตรวจ"'); go('?p=devices'); }
$lastI = $dev ? q('SELECT user_name,user_position FROM inspections WHERE device_id=? ORDER BY id DESC LIMIT 1', [$did])->fetch() : false;
$v = fn($k, $def = '') => (string)($old[$k] ?? $def);

function item_row($i, string $n, string $r): string {
    $o = '<div class="irow"><input class="iname" name="nm[' . $i . ']" value="' . h($n) . '" maxlength="100" placeholder="ชื่อรายการ" list="soft-list" autocomplete="off"><div class="seg2">';
    foreach (['ok' => ['✓', 'ไม่มีปัญหา'], 'no' => ['✕', 'มีปัญหา']] as $val => [$mark, $tip])
        $o .= '<input type="radio" id="r' . $i . $val . '" name="res[' . $i . ']" value="' . $val . '"' . ($r === $val ? ' checked' : '') . '><label class="' . $val . '" for="r' . $i . $val . '" title="' . $tip . '">' . $mark . '</label>';
    return $o . '</div><button type="button" class="btn small danger rm">ลบ</button></div>';
}
?>
<h1>ตรวจเช็คเครื่อง</h1>
<?php
    $type = $dev['device_type'] ?: 'Notebook'; $cat = device_category($type); $isCam = $cat === 'cctv';
    $date = $v('inspected_at', date('Y-m-d'));
    if (isset($old['nm']) && is_array($old['nm'])) {
        $rows = []; foreach ($old['nm'] as $i => $n) $rows[] = [(string)$n, (string)($old['res'][$i] ?? '')];
    } else $rows = array_map(fn($n) => [$n, ''], default_items($cat));
    $sigs = ['inspector_signature' => 'ผู้ดำเนินการตรวจเช็ค', 'user_signature' => 'ผู้ใช้งานอุปกรณ์'];
?>
<form method="post" class="card form" id="inspect-form"><?= csrf_field() ?><input type="hidden" name="action" value="inspect_save"><input type="hidden" name="device_id" value="<?= (int)$dev['id'] ?>">
<div class="head"><div><b><?= h($dev['asset_tag']) ?></b> <span class="badge b-na"><?= h($type) ?></span><br><small class="muted"><?= $dev['device_name'] ? h($dev['device_name']) . ' · ' : '' ?><?= h(trim($dev['brand'] . ' ' . $dev['model'])) ?><?= $dev['owner'] ? ' · ' . h($dev['owner']) : '' ?></small></div><a class="btn small" href="?p=devices">← ทะเบียนเครื่อง</a></div>
<div class="grid two">
    <?= fld('inspector_name', 'ชื่อผู้ดำเนินการตรวจเช็ค *', $v('inspector_name', $_SESSION['display'] ?? ''), 'text', 'required maxlength="100"') ?>
    <?= fld('inspected_at', 'วันที่ตรวจ *', $date, 'date', 'required') ?>
    <?= fld('user_name', 'ผู้ใช้งาน *', $v('user_name', ($lastI['user_name'] ?? '') ?: (string)($dev['owner'] ?? '')), 'text', 'required maxlength="100"') ?>
    <?= fld('user_position', 'ตำแหน่ง', $v('user_position', (string)($lastI['user_position'] ?? '')), 'text', 'maxlength="100"') ?>
</div>
<div class="head"><h2>รายการตรวจสอบ — <?= h(CATEGORY_LABEL[$cat]) ?></h2><button type="button" class="btn small" id="allpass">✓ ทุกรายการ</button></div>
<p class="muted">รายการไม่มีปัญหาให้เลือก ✓ · รายการมีปัญหาให้เลือก ✕</p>
<div id="items"><?php foreach ($rows as $i => [$n, $r]) echo item_row($i, $n, $r); ?></div>
<div class="actions">
<?php if ($isCam): ?><button type="button" class="btn" id="addCam">+ เพิ่มกล้อง</button><button type="button" class="btn" id="addRow">+ เพิ่มรายการอื่น</button>
<?php else: ?><button type="button" class="btn primary" id="addRow">+ เพิ่มโปรแกรมที่ตรวจเช็ค</button><?php endif; ?></div>
<datalist id="soft-list"><?php foreach (software_list($dev['software']) as $s) echo '<option value="' . h($s) . '">'; ?></datalist>
<template id="rowTpl"><?= item_row('__I__', '', '') ?></template>
<label>ปัญหาที่พบ / หมายเหตุ<textarea name="problem_note" rows="3"><?= h($v('problem_note')) ?></textarea></label>
<h2>ลายเซ็น</h2>
<div class="grid two">
<?php foreach ($sigs as $name => $title): $sv = $v($name); $has = sig_valid($sv); ?>
    <div><div class="lbl"><?= h($title) ?> *</div>
    <div class="sig-box<?= $has ? ' has' : '' ?>" role="button" tabindex="0" data-target="<?= $name ?>" data-title="<?= h($title) ?>"><span class="sig-hint">✍️ แตะที่นี่เพื่อเซ็นชื่อ</span><img alt="<?= h($title) ?>"<?= $has ? ' src="' . h($sv) . '"' : '' ?>></div>
    <input type="hidden" id="<?= $name ?>" name="<?= $name ?>" value="<?= $has ? h($sv) : '' ?>"></div>
<?php endforeach; ?></div>
<div class="actions"><button class="btn primary big">บันทึกผลตรวจ</button><a class="btn big" href="?p=devices">ยกเลิก</a></div></form>

<dialog id="sigModal"><div class="sig-head"><strong class="sig-title">ลายเซ็น</strong><span class="muted">วาดลายเซ็นด้วยนิ้วหรือเมาส์</span></div>
    <canvas id="sigCanvas"></canvas>
    <div class="actions"><button type="button" class="btn primary" id="sigOk">ตกลง</button><button type="button" class="btn" id="sigClear">ล้างใหม่</button><button type="button" class="btn" id="sigCancel">ยกเลิก</button></div></dialog>

