<?php
declare(strict_types=1);

function theme_toggle(): string {
    return '<span class="tlabel" id="themeLabel"></span><button type="button" id="themeToggle" class="tswitch" role="switch" aria-checked="false" aria-label="สลับโหมด Light / Dark" title="สลับโหมด Light / Dark"><span class="tk"></span></button>';
}
function render_layout(string $title, string $body, bool $nav = true): void {
    $p = $_GET['p'] ?? 'devices';
    $items = ['devices' => 'ทะเบียนเครื่อง'];
    $items['history'] = 'ประวัติการตรวจ';
    $items['io'] = 'ส่งออกข้อมูล';
    if (is_admin()) $items['users'] = 'ผู้ใช้งาน';
    $active = in_array($p, ['device_form', 'device_view', 'inspect'], true) ? 'devices' : $p;
    $v = @filemtime(__DIR__ . '/../assets/style.css') ?: 1;
    $v2 = @filemtime(__DIR__ . '/../assets/app.js') ?: 1;
    echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light dark"><title>' . h($title) . ' - NB Check</title><link rel="stylesheet" href="assets/style.css?v=' . $v . '"><script>(function(){var d=document.documentElement,t;try{t=localStorage.getItem("nbtheme")}catch(e){}if(t!=="dark"&&t!=="light")t=(window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches)?"dark":"light";d.setAttribute("data-theme",t)})()</script></head><body>';
    if ($nav) {
        echo '<header><div class="wrap bar"><strong class="logo">💻 NB Check</strong><nav class="menu">';
        foreach ($items as $k => $t) echo '<a href="?p=' . $k . '"' . ($active === $k ? ' class="act"' : '') . '>' . h($t) . '</a>';
        echo '</nav><div class="me"><span class="who">' . h($_SESSION['display'] ?? '') . ' <small class="badge ' . (is_admin() ? 'b-ok' : 'b-na') . '">' . (is_admin() ? 'Admin' : 'ผู้ใช้ (อ่านอย่างเดียว)') . '</small></span>'
            . theme_toggle()
            . '<a class="btn small" href="?p=password">รหัสผ่าน</a>'
            . '<form method="post" class="inl">' . csrf_field() . '<input type="hidden" name="action" value="logout"><button class="btn small">ออกจากระบบ</button></form></div></div></header>';
    }
    else echo '<div class="tfloat">' . theme_toggle() . '</div>';
    echo '<main class="wrap">';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) echo '<div class="flash ' . h($t) . '">' . h($m) . '</div>';
    unset($_SESSION['flash']);
    echo $body . '</main><script src="assets/app.js?v=' . $v2 . '"></script></body></html>';
}