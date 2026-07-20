<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Penghapusan Data - WAPI dl-Edu</title>
    <style>body{font-family:Arial,sans-serif;line-height:1.6;margin:0;color:#1f2937;background:#f8fafc}.wrap{max-width:760px;margin:0 auto;padding:40px 20px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:28px}.code{font-family:monospace;background:#f3f4f6;padding:8px;border-radius:6px}</style>
</head>
<body><main class="wrap"><article class="card">
<h1>Status Penghapusan Data</h1>
@if ($request)
    <p>Permintaan penghapusan data telah diterima.</p>
    <p>Status: <strong>{{ $request->status }}</strong></p>
    <p>Kode konfirmasi:</p>
    <p class="code">{{ $request->confirmation_code }}</p>
@else
    <p>Kode konfirmasi tidak ditemukan.</p>
    <p class="code">{{ $code }}</p>
@endif
</article></main></body></html>
