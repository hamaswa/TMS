<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clothBrand->bundle_code }} - Brand QR</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:24px;background:#eef2f7;color:#111;font-family:Arial,sans-serif}.toolbar{max-width:380px;margin:0 auto 14px;display:flex;gap:8px}.toolbar button,.toolbar a{flex:1;padding:10px;border:1px solid #ccd5e0;border-radius:7px;background:#fff;color:#17324f;text-align:center;text-decoration:none;font-weight:700}.toolbar button{border-color:#1769ef;background:#1769ef;color:#fff}.label{width:80mm;min-height:54mm;margin:auto;padding:5mm;border:1px solid #bbb;background:#fff;text-align:center}.label h1{margin:0 0 1mm;font-size:19px}.label p{margin:1mm 0;font-size:12px}.qr{display:flex;justify-content:center;margin:2mm auto}.qr svg{width:32mm;height:32mm}.code{direction:ltr;font-size:15px!important;font-weight:800;letter-spacing:.7px}.hint{font-size:10px!important}@media print{@page{size:80mm auto;margin:0}body{padding:0;background:#fff}.toolbar{display:none}.label{border:0;margin:0}}
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">پرنٹ کریں</button><a href="{{ route('admin.clothbrand.index') }}">واپس</a></div>
    <main class="label">
        <h1>{{ $clothBrand->name }}</h1>
        <p>{{ $clothBrand->cloths_count }} کپڑے کی اقسام</p>
        <div class="qr">{!! $qrSvg !!}</div>
        <p class="code">{{ $clothBrand->bundle_code }}</p>
        <p class="hint">سپلائر خریداری میں اسکین کرنے سے برانڈ کی تمام اقسام شامل ہوں گی۔</p>
    </main>
</body>
</html>
