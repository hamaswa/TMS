<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $displaySerial }} — سیریل</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#eef2f6;font-family:Arial,sans-serif}
        nav{text-align:center;padding:16px}button{padding:10px 18px;cursor:pointer}
        .serial{width:70mm;min-height:20mm;margin:20px auto;padding:4mm;background:white;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;white-space:nowrap;direction:ltr}
        @media print{@page{size:80mm 30mm;margin:0}body{background:white}nav{display:none}.serial{margin:0;width:80mm;height:30mm;padding:3mm}}
    </style>
</head>
<body>
    <nav><button type="button" onclick="window.print()">صرف سیریل پرنٹ کریں</button></nav>
    <main class="serial">{{ $displaySerial }}</main>
</body>
</html>
