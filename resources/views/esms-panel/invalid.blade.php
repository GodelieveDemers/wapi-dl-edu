<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses WAPI Tidak Valid</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fbff; color: #18324a; font-family: "Segoe UI", Tahoma, sans-serif; }
        .card { max-width: 560px; margin: 20px; border: 1px solid #dbe7f3; border-radius: 18px; background: #fff; padding: 28px; box-shadow: 0 12px 34px rgba(15, 39, 66, .08); text-align: center; }
        h1 { margin: 0 0 10px; font-size: 24px; }
        p { margin: 0; color: #61738a; line-height: 1.55; }
    </style>
</head>
<body>
    <section class="card">
        <h1>Akses panel WAPI tidak valid</h1>
        <p>{{ $message ?? 'Link panel sudah kedaluwarsa atau signature tidak sesuai. Silakan muat ulang halaman Master WAPI dari e-SMS.' }}</p>
    </section>
</body>
</html>
