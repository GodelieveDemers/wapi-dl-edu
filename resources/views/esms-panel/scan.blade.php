<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Koneksi WAPI {{ $device->body }}</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #0f2742;
            --muted: #61738a;
            --line: #dbe7f3;
            --blue: #2563eb;
            --green: #059669;
            --red: #dc2626;
            --amber: #d97706;
            --bg: #f7fbff;
            --card: #ffffff;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: "Segoe UI", Tahoma, sans-serif; font-size: 14px; }
        .wrap { padding: 16px; }
        .hero { border: 1px solid var(--line); border-radius: 16px; background: linear-gradient(135deg, #ffffff, #eef7ff); padding: 16px; display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: center; }
        .eyebrow { margin: 0 0 4px; color: var(--blue); font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 22px; line-height: 1.2; }
        .copy { margin: 6px 0 0; color: var(--muted); max-width: 720px; }
        .btn { border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; border-radius: 10px; padding: 9px 12px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn:hover { background: #dbeafe; }
        .grid { display: grid; grid-template-columns: minmax(280px, 1fr) minmax(240px, .7fr); gap: 14px; margin-top: 14px; }
        .card { border: 1px solid var(--line); border-radius: 16px; background: var(--card); padding: 16px; }
        .qr-box { min-height: 340px; border: 1px dashed #b8c9dc; border-radius: 16px; display: flex; align-items: center; justify-content: center; text-align: center; background: #fff; overflow: hidden; }
        .qr-box img { width: min(320px, 88vw); height: min(320px, 88vw); object-fit: contain; }
        .spinner { width: 54px; height: 54px; border: 5px solid #dbeafe; border-top-color: var(--blue); border-radius: 999px; animation: spin 1s linear infinite; margin: 0 auto 12px; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 8px 12px; font-weight: 800; background: #eff6ff; color: #1d4ed8; }
        .badge.is-ok { background: #dcfce7; color: #047857; }
        .badge.is-warn { background: #fef3c7; color: #b45309; }
        .badge.is-error { background: #fee2e2; color: #b91c1c; }
        .info-list { list-style: none; padding: 0; margin: 0; }
        .info-list li { display: flex; justify-content: space-between; gap: 14px; padding: 11px 0; border-bottom: 1px solid #edf2f7; }
        .info-list li:last-child { border-bottom: 0; }
        .small { color: var(--muted); font-size: 12px; }
        pre { margin: 12px 0 0; min-height: 120px; max-height: 220px; overflow: auto; background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 12px; white-space: pre-wrap; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 760px) {
            .wrap { padding: 10px; }
            .grid { grid-template-columns: 1fr; }
            h1 { font-size: 19px; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="hero">
            <div>
                <p class="eyebrow">Koneksi Perangkat WAPI</p>
                <h1>{{ $device->body }}</h1>
                <p class="copy">Scan QR memakai WhatsApp di HP pengirim. Halaman ini dibuka dari signed URL e-SMS dan tidak memakai login WAPI biasa.</p>
            </div>
            <a class="btn" href="{{ $backUrl }}">Kembali ke Panel</a>
        </section>

        <section class="grid">
            <div class="card">
                <div id="qr-code-container" class="qr-box">
                    <div>
                        <div class="spinner"></div>
                        <div class="badge">Menunggu server WAPI...</div>
                    </div>
                </div>
                <div id="connection-status" style="margin-top: 12px;">
                    <span class="badge is-warn">Memulai koneksi...</span>
                </div>
                <pre id="log-output">Menunggu log koneksi...</pre>
            </div>

            <aside class="card">
                <h2 style="margin: 0 0 10px; font-size: 17px;">Informasi Device</h2>
                <ul class="info-list">
                    <li><span>Nomor</span><strong>{{ $device->body }}</strong></li>
                    <li><span>Status awal</span><strong>{{ $device->status }}</strong></li>
                    <li><span>Pemilik</span><strong>{{ optional($device->user)->name ?: '-' }}</strong></li>
                    <li><span>Email</span><strong>{{ optional($device->user)->email ?: '-' }}</strong></li>
                    <li><span>Akses</span><strong>{{ $isManager ? 'Admin/Manager' : 'User Device' }}</strong></li>
                </ul>
                <p class="small">Jika QR tidak muncul, pastikan service Node/WA server WAPI aktif lalu buka ulang halaman ini.</p>
            </aside>
        </section>
    </main>

    <script src="https://cdn.socket.io/4.8.1/socket.io.min.js" crossorigin="anonymous"></script>
    <script>
        const device = @json($device->body);
        const serverType = @json($serverType);
        const waUrlServer = @json($waUrlServer);
        const qrCodeContainer = document.getElementById('qr-code-container');
        const connectionStatus = document.getElementById('connection-status');
        const logOutput = document.getElementById('log-output');
        let logsInitialized = false;

        function appendLog(text) {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = timestamp + ' - ' + text + '\n';
            logOutput.textContent = logsInitialized ? logOutput.textContent + logEntry : logEntry;
            logsInitialized = true;
            logOutput.scrollTop = logOutput.scrollHeight;
        }

        function setStatus(text, type = 'warn') {
            const css = type === 'ok' ? 'is-ok' : (type === 'error' ? 'is-error' : 'is-warn');
            connectionStatus.innerHTML = '<span class="badge ' + css + '">' + text + '</span>';
        }

        try {
            const socket = serverType === 'hosting'
                ? io()
                : io(waUrlServer, { transports: ['websocket', 'polling', 'flashsocket'] });

            if (socket.emit('StartConnection', device)) {
                appendLog('Start connection untuk ' + device);
            }

            socket.on('qrcode', function(response) {
                if (response.token !== device) return;
                qrCodeContainer.innerHTML = '<img src="' + response.data + '" alt="QR Code">';
                setStatus(response.message || 'QR diterima. Silakan scan.', 'warn');
                appendLog('QR diterima. Silakan scan.');
            });

            socket.on('connection-open', function(response) {
                if (response.token !== device) return;
                qrCodeContainer.innerHTML = '<div><div class="badge is-ok">Device connected</div></div>';
                setStatus('Device connected', 'ok');
                appendLog('Device connected.');
            });

            socket.on('Unauthorized', function(response) {
                if (response.token !== device) return;
                qrCodeContainer.innerHTML = '<div><div class="badge is-error">Unauthorized</div></div>';
                setStatus('Unauthorized access', 'error');
                appendLog('Unauthorized access.');
            });

            socket.on('message', function(response) {
                if (response.token !== device) return;
                appendLog(response.message || 'Pesan koneksi diterima.');
                setStatus(response.message || 'Status koneksi berubah.', 'warn');
            });

            socket.on('connect_error', function(error) {
                setStatus('Gagal terhubung ke WA server.', 'error');
                appendLog('Socket error: ' + (error.message || error));
            });
        } catch (error) {
            setStatus('Gagal memulai koneksi.', 'error');
            appendLog('Error: ' + (error.message || error));
        }
    </script>
</body>
</html>
