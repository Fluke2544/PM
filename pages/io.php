<?php
$years = q('SELECT DISTINCT YEAR(inspected_at) y FROM inspections ORDER BY y DESC')->fetchAll(PDO::FETCH_COLUMN);
$branches = BRANCHES;
$branchSel = function () use ($branches) {
    echo '<label>สาขา<select name="branch"><option value="">ทุกสาขา</option>';
    foreach ($branches as $b) echo '<option>' . h($b) . '</option>';
    echo '</select></label>';
};
$deptSel = function () {
    echo '<label>ฝ่าย<select name="dept"><option value="">ทุกฝ่าย</option>';
    foreach (DEPARTMENTS as $d) echo '<option>' . h($d) . '</option>';
    echo '</select></label>';
};
$typeSel = function () {
    echo '<label>ประเภทอุปกรณ์<select name="dtype"><option value="">ทุกประเภท</option>';
    foreach (DEVICE_TYPES as $cat => $ts) { echo '<optgroup label="' . h(CATEGORY_LABEL[$cat]) . '">'; foreach ($ts as $t) echo '<option>' . h($t) . '</option>'; echo '</optgroup>'; }
    echo '</select></label>';
};
?>
<h1>ส่งออกข้อมูล (CSV)</h1>
<p class="muted">ไฟล์ UTF-8 พร้อม BOM เปิดภาษาไทยใน Excel ได้ทันที เลือกตัวกรองแล้วกดส่งออก (ไม่เลือก = ส่งออกทั้งหมด)</p>
<section class="grid two">
<form class="card form" method="get"><input type="hidden" name="p" value="export"><input type="hidden" name="type" value="devices">
    <h2>ทะเบียนเครื่อง</h2>
    <?php $branchSel(); $deptSel(); $typeSel(); ?>
    <div class="actions"><button class="btn primary big">ส่งออกทะเบียนเครื่อง</button></div></form>
<form class="card form" method="get"><input type="hidden" name="p" value="export"><input type="hidden" name="type" value="inspections">
    <h2>ผลการตรวจ</h2>
    <label>ปีที่ตรวจ<select name="year"><option value="">ทุกปี</option><?php foreach ($years as $y) echo '<option value="' . (int)$y . '">ปี ' . ((int)$y + 543) . '</option>'; ?></select></label>
    <?php $branchSel(); $deptSel(); $typeSel(); ?>
    <div class="actions"><button class="btn primary big">ส่งออกผลการตรวจ</button></div></form>
</section>
