/* ยืนยันก่อนลบ/ทำรายการสำคัญ */
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
/* เลือกเครื่องแล้วไปต่ออัตโนมัติ */
document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { if (sel.value) sel.form.submit(); });
});
/* ตัวกรอง: เปลี่ยนตัวเลือกแล้วกรองทันที (รวมถึงเลือก "ทั้งหมด") */
document.querySelectorAll('select[data-autofilter]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
});
/* ค้นหาอัตโนมัติ: พิมพ์แล้วรายการอัปเดตเอง (ไม่ต้องกดปุ่ม) */
document.querySelectorAll('form[data-live]').forEach(function (f) {
    var box = document.querySelector(f.dataset.live), inp = f.querySelector('input[name="q"]'), timer = null, seq = 0;
    if (!box || !inp || !window.fetch || !window.DOMParser) return;
    function run() {
        var url = f.getAttribute('action') || location.pathname, qs = new URLSearchParams(new FormData(f)).toString(), my = ++seq;
        fetch(url + '?' + qs, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
            if (my !== seq) return;
            var n = new DOMParser().parseFromString(t, 'text/html').querySelector(f.dataset.live);
            if (n) { box.innerHTML = n.innerHTML; history.replaceState(null, '', url + '?' + qs); }
        }).catch(function () { f.submit(); });
    }
    inp.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(run, 300); });
    f.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); run(); });
    f.querySelectorAll('select').forEach(function (s) { s.addEventListener('change', function (e) { e.stopImmediatePropagation(); run(); }, true); });
});
/* รายการตรวจ: ✓ ทุกรายการ / เพิ่มแถว / ลบแถว */
(function () {
    var box = document.getElementById('items'), tpl = document.getElementById('rowTpl');
    if (!box || !tpl) return;
    var n = 1000;
    function addRow(name) {
        var d = document.createElement('div'); d.innerHTML = tpl.innerHTML.replace(/__I__/g, ++n);
        var row = d.firstElementChild; box.appendChild(row);
        var inp = row.querySelector('.iname'); inp.value = name || '';
        row.scrollIntoView({ block: 'center' }); inp.focus();
    }
    var ap = document.getElementById('allpass');
    if (ap) ap.addEventListener('click', function () { box.querySelectorAll('.seg2 input[value="ok"]').forEach(function (i) { i.checked = true; }); });
    var ar = document.getElementById('addRow'); if (ar) ar.addEventListener('click', function () { addRow(''); });
    var ac = document.getElementById('addCam');
    if (ac) ac.addEventListener('click', function () {
        var max = 0; box.querySelectorAll('.iname').forEach(function (i) { var m = /^camera\s*(\d+)$/i.exec(i.value.trim()); if (m) max = Math.max(max, +m[1]); });
        addRow('Camera ' + String(max + 1).padStart(2, '0'));
    });
    box.addEventListener('click', function (e) {
        var b = e.target.closest('.rm'); if (!b) return;
        var row = b.closest('.irow'), nm = row.querySelector('.iname').value.trim();
        if (nm && !confirm('ลบรายการ "' + nm + '" ออกจากการตรวจครั้งนี้?')) return;
        row.remove();
    });
    var form = document.getElementById('inspect-form');
    if (form) form.addEventListener('submit', function (e) {
        var rows = Array.prototype.slice.call(box.querySelectorAll('.irow'));
        var bad = rows.filter(function (r) { return r.querySelector('.iname').value.trim() && !r.querySelector('input[type=radio]:checked'); });
        if (bad.length) {
            e.preventDefault(); alert('กรุณาเลือก ✓ หรือ ✕ ให้ครบทุกรายการ (ยังเหลือ ' + bad.length + ' รายการ: ' + bad[0].querySelector('.iname').value + ')');
            bad[0].scrollIntoView({ block: 'center' }); bad[0].style.outline = '2px solid #dc2626'; setTimeout(function () { bad[0].style.outline = ''; }, 3000);
        }
    });
})();
/* ป๊อปอัพเซ็นลายเซ็นด้วยการวาด (รองรับนิ้วบนมือถือและเมาส์) */
(function () {
    var dlg = document.getElementById('sigModal');
    if (!dlg || !dlg.showModal) return;
    var cv = document.getElementById('sigCanvas'), ctx, drawing = false, drawn = false, box = null;
    function setup() {
        var r = cv.getBoundingClientRect(), d = window.devicePixelRatio || 1;
        cv.width = Math.round(r.width * d); cv.height = Math.round(r.height * d);
        ctx = cv.getContext('2d'); ctx.setTransform(d, 0, 0, d, 0, 0);
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, r.width, r.height);
        ctx.lineWidth = 2.6; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111';
        drawn = false;
    }
    function pos(e) { var r = cv.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
    cv.addEventListener('pointerdown', function (e) {
        e.preventDefault(); cv.setPointerCapture(e.pointerId); drawing = true; drawn = true;
        var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(p.x + 0.1, p.y + 0.1); ctx.stroke();
    });
    cv.addEventListener('pointermove', function (e) {
        if (!drawing) return; e.preventDefault();
        var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); ctx.beginPath(); ctx.moveTo(p.x, p.y);
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (n) { cv.addEventListener(n, function () { drawing = false; }); });
    document.querySelectorAll('.sig-box').forEach(function (b) {
        function open() { box = b; dlg.querySelector('.sig-title').textContent = b.dataset.title; dlg.showModal(); setup(); }
        b.addEventListener('click', open);
        b.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
    });
    document.getElementById('sigClear').addEventListener('click', setup);
    document.getElementById('sigCancel').addEventListener('click', function () { dlg.close(); });
    document.getElementById('sigOk').addEventListener('click', function () {
        if (!drawn) { alert('กรุณาเซ็นชื่อก่อนกดตกลง'); return; }
        var data = cv.toDataURL('image/png');
        document.getElementById(box.dataset.target).value = data;
        box.querySelector('img').src = data; box.classList.add('has');
        dlg.close();
    });
    var form = document.getElementById('inspect-form');
    if (form) form.addEventListener('submit', function (e) {
        var miss = ['inspector_signature', 'user_signature'].filter(function (n) { return !document.getElementById(n).value; });
        if (miss.length) {
            e.preventDefault();
            alert('กรุณาเซ็นลายเซ็นให้ครบทั้งผู้ตรวจเช็คและผู้ใช้งาน');
            var b = document.querySelector('.sig-box[data-target="' + miss[0] + '"]'); if (b) { b.scrollIntoView({ block: 'center' }); b.focus(); }
        }
    });
})();
/* ป๊อปอัพรหัสผ่านชั่วคราว (หน้าผู้ใช้งาน) */
(function () {
    var dlg = document.getElementById('pwModal');
    if (!dlg || !dlg.showModal) return;
    dlg.showModal();
    document.getElementById('pwClose').addEventListener('click', function () { dlg.close(); });
    document.getElementById('pwCopy').addEventListener('click', function () {
        var t = document.getElementById('pwText').textContent.trim(), b = this;
        function done() { b.textContent = 'คัดลอกแล้ว ✓'; }
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(t).then(done, function () { sel(); });
        else sel();
        function sel() { var r = document.createRange(); r.selectNodeContents(document.getElementById('pwText')); var s = getSelection(); s.removeAllRanges(); s.addRange(r); try { document.execCommand('copy') ? done() : (b.textContent = 'เลือกข้อความแล้ว กด Ctrl+C'); } catch (e) { b.textContent = 'เลือกข้อความแล้ว กด Ctrl+C'; } }
    });
})();
/* โหมด Light / Dark (จำไว้ในเบราว์เซอร์ของแต่ละเครื่อง) */
(function () {
    var btn = document.getElementById('themeToggle'), lab = document.getElementById('themeLabel'), root = document.documentElement;
    if (!btn) return;
    function show() {
        var dark = root.getAttribute('data-theme') === 'dark';
        btn.setAttribute('aria-checked', dark ? 'true' : 'false');
        btn.title = 'โหมด ' + (dark ? 'Dark' : 'Light') + ' (กดเพื่อสลับ)';
        if (lab) lab.textContent = dark ? 'Dark' : 'Light';
    }
    btn.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('nbtheme', next); } catch (e) {}
        show();
    });
    show();
})();