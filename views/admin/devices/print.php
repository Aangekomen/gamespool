<?php
/** @var array $device */
/** @var ?array $game */
/** @var string $qrUrl */
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Print: <?= e($device['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#171a56; --brand:#35b782; }
        * { box-sizing: border-box; }
        html, body { margin:0; padding:0; font-family:'Inter',system-ui,sans-serif; color:#171a56; background:#fff; }
        .page { width: 148mm; height: 210mm; padding: 14mm; display:flex; flex-direction:column; align-items:center; justify-content:space-between; }
        .top { text-align:center; }
        .brand-mark { display:inline-block; width:20px; height:20px; background:var(--navy); border-radius:5px; position:relative; vertical-align:middle; margin-right:6px; }
        .brand-mark::after { content:''; position:absolute; inset:4px; background:var(--brand); border-radius:3px; }
        h1 { font-size: 36pt; line-height:1; margin: 12mm 0 4mm; font-weight: 800; letter-spacing: -.02em; }
        .meta { font-size: 12pt; color:#3a3d7a; }
        .qr { padding: 6mm; background:#fff; border: 1px solid #e2e8f0; border-radius: 8mm; }
        .code { font-family: ui-monospace, monospace; font-size: 28pt; font-weight: 800; letter-spacing: .25em; color:var(--brand); margin-top: 6mm; }
        .footer { font-size: 10pt; color:#64748b; text-align:center; }
        @media print {
            @page { size: A5 portrait; margin: 0; }
            .no-print { display:none; }
        }
        .no-print { position:fixed; top:8px; right:8px; padding: 8px 14px; background: var(--navy); color:#fff; border-radius:6px; font-size: 12pt; cursor:pointer; border:0; }
    </style>
</head>
<body onload="window.print && setTimeout(window.print, 200);">
    <button class="no-print" onclick="window.print()">Print</button>
    <div class="page">
        <div class="top">
            <p style="margin:0;font-size:12pt;font-weight:600;"><span class="brand-mark"></span>FlexiComp</p>
            <h1><?= e($device['name']) ?></h1>
            <p class="meta">
                <?= e($game['name'] ?? '') ?>
                <?php if (!empty($device['location'])): ?> · <?= e($device['location']) ?><?php endif; ?>
            </p>
        </div>
        <div style="text-align:center;">
            <div class="qr">
                <img src="<?= e(url('/qr.svg?text=' . urlencode($qrUrl) . '&size=520')) ?>"
                     width="380" height="380" alt="QR">
            </div>
            <p class="code"><?= e($device['code']) ?></p>
        </div>
        <div class="footer">
            Scan om mee te doen · <?= e($qrUrl) ?>
        </div>
    </div>
</body>
</html>
