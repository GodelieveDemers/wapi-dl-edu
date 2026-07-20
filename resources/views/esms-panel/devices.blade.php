<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel WAPI e-SMS</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #0f2742;
            --muted: #61738a;
            --line: #dbe7f3;
            --blue: #2563eb;
            --green: #059669;
            --red: #dc2626;
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
        .pill { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; border-radius: 999px; padding: 8px 12px; font-weight: 700; white-space: nowrap; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; margin-top: 14px; }
        .metric { border: 1px solid var(--line); border-radius: 14px; background: var(--card); padding: 14px; }
        .metric strong { display: block; font-size: 20px; }
        .metric span { color: var(--muted); font-size: 12px; }
        .table-card { margin-top: 14px; border: 1px solid var(--line); border-radius: 16px; background: var(--card); overflow: hidden; }
        .table-head { padding: 14px 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center; }
        .table-head h2 { margin: 0; font-size: 17px; }
        .form-card { margin-top: 14px; border: 1px solid var(--line); border-radius: 16px; background: #fff; padding: 16px; }
        .form-title { margin: 0 0 12px; font-size: 17px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; align-items: end; }
        label { display: block; font-weight: 800; margin-bottom: 6px; }
        .field { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 9px 10px; font: inherit; color: #334155; background: #fff; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1080px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        th { background: #f8fafc; color: #52647a; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        tr:last-child td { border-bottom: 0; }
        .status { display: inline-flex; width: 12px; height: 12px; border-radius: 50%; background: var(--red); box-shadow: 0 0 0 4px rgba(220, 38, 38, .1); }
        .status.is-connected { background: var(--green); box-shadow: 0 0 0 4px rgba(5, 150, 105, .12); }
        .owner { min-width: 180px; }
        .token { display: flex; gap: 8px; align-items: center; min-width: 260px; }
        .token input { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 8px 10px; font: inherit; color: #334155; background: #f8fafc; }
        .btn { border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; border-radius: 10px; padding: 8px 10px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn.is-danger { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .btn.is-success { border-color: #bbf7d0; background: #f0fdf4; color: #047857; }
        .btn:hover { background: #dbeafe; }
        .btn.is-danger:hover { background: #ffe4e6; }
        .btn.is-success:hover { background: #dcfce7; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; min-width: 190px; }
        .row-action { position: relative; display: inline-flex; }
        .dot-btn { width: 38px; height: 38px; border: 1px solid #bfdbfe; border-radius: 12px; background: linear-gradient(180deg, #ffffff, #eff6ff); color: #1d4ed8; font-size: 22px; line-height: 1; cursor: pointer; box-shadow: 0 8px 18px rgba(37, 99, 235, .10); }
        .dot-btn:hover { background: #dbeafe; color: #1e40af; transform: translateY(-1px); }
        .action-menu { position: fixed; z-index: 60; display: none; min-width: 180px; border: 1px solid #dbe7f3; border-radius: 14px; background: #fff; box-shadow: 0 18px 45px rgba(15, 23, 42, .16); padding: 8px; }
        .action-menu.is-open { display: block; }
        .action-menu a, .action-menu button { width: 100%; border: 0; background: transparent; color: #334155; border-radius: 10px; padding: 9px 10px; text-align: left; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; display: block; }
        .action-menu a:hover, .action-menu button:hover { background: #eff6ff; color: #1d4ed8; }
        .action-menu .is-danger { color: #be123c; }
        .action-menu .is-danger:hover { background: #fff1f2; color: #be123c; }
        .inline-form { display: flex; gap: 8px; align-items: center; min-width: 260px; }
        .inline-form .field { min-width: 180px; }
        .option-panel { width: min(560px, 100%); border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fbff; padding: 10px; }
        .options-dialog { position: fixed; inset: 0; z-index: 55; display: none; align-items: center; justify-content: center; padding: 18px; background: rgba(15, 23, 42, .42); }
        .options-dialog.is-open { display: flex; }
        .options-box { width: min(620px, 100%); border-radius: 18px; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .28); overflow: hidden; }
        .options-head { padding: 14px 16px; border-bottom: 1px solid #e5edf5; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .options-head h3 { margin: 0; font-size: 18px; }
        .options-body { padding: 16px; }
        .option-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px 12px; align-items: center; }
        .option-check { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 700; color: #334155; }
        .option-check input { width: 16px; height: 16px; }
        .option-delay { display: flex; align-items: center; gap: 8px; }
        .option-delay input { width: 92px; }
        details summary { cursor: pointer; font-weight: 800; color: #1d4ed8; }
        .notice { margin-top: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; border-radius: 14px; padding: 12px 14px; font-weight: 700; }
        .notice.is-error { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .modal { position: fixed; inset: 0; z-index: 50; display: none; align-items: center; justify-content: center; padding: 18px; background: rgba(15, 23, 42, .48); }
        .modal.show { display: flex; }
        .modal-dialog { width: min(560px, 100%); transform: translateY(12px) scale(.98); transition: transform .18s ease; }
        .modal.show .modal-dialog { transform: translateY(0) scale(1); }
        .modal-content { border-radius: 18px; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .28); overflow: hidden; }
        .modal-header, .modal-footer { padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; border-bottom: 1px solid #e5edf5; }
        .modal-footer { border-top: 1px solid #e5edf5; border-bottom: 0; justify-content: flex-end; }
        .modal-title { margin: 0; font-size: 18px; }
        .modal-body { padding: 16px; }
        .btn-close { border: 0; background: #f1f5f9; color: #334155; border-radius: 10px; width: 36px; height: 36px; font-size: 20px; cursor: pointer; line-height: 1; }
        .empty { margin-top: 14px; border: 1px dashed #b8c9dc; border-radius: 16px; background: #fff; padding: 28px; text-align: center; color: var(--muted); }
        .small { color: var(--muted); font-size: 12px; }
        @media (max-width: 640px) {
            .wrap { padding: 10px; }
            h1 { font-size: 19px; }
            .hero, .metric, .table-head { border-radius: 12px; }
            table { min-width: 980px; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="hero">
            <div>
                <p class="eyebrow">WAPI Embedded Panel</p>
                <h1>Panel Perangkat untuk e-SMS</h1>
                <p class="copy">
                    Akses ini dibuka melalui signed URL e-SMS. Tidak memakai halaman login WAPI biasa, sehingga aman untuk ditanam di Master WAPI e-SMS.
                </p>
            </div>
            <div class="pill">
                {{ $isManager ? 'Mode manager' : 'User' }}:
                {{ optional($user)->email ?: $accessEmail }}
            </div>
        </section>

        <section class="grid" aria-label="Ringkasan">
            <div class="metric">
                <strong>{{ $devices->count() }}</strong>
                <span>Total perangkat yang terlihat</span>
            </div>
            <div class="metric">
                <strong>{{ $devices->where('status', 'Connected')->count() }}</strong>
                <span>Perangkat terkoneksi</span>
            </div>
            <div class="metric">
                <strong>{{ $devices->sum('message_sent') }}</strong>
                <span>Total pesan terkirim</span>
            </div>
        </section>

        @if (session('panel_status'))
            <div class="notice">{{ session('panel_status') }}</div>
        @endif

        @if (session('panel_error'))
            <div class="notice is-error">{{ session('panel_error') }}</div>
        @endif

        <section class="table-card">
            <div class="table-head">
                <div>
                    <h2>Daftar Perangkat</h2>
                    <div class="small">Token yang tampil adalah token device per nomor, digunakan e-SMS untuk pengiriman WhatsApp.</div>
                </div>
                <div class="actions">
                    <button type="button" class="btn is-success js-add-wapi-device">+ Tambah Device</button>
                    <button type="button" class="btn" onclick="window.location.reload()">Refresh</button>
                </div>
            </div>
        </section>

        <div class="modal fade floating-work-modal" id="wapiDeviceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="addDeviceTitle">
                    <div class="modal-header">
                        <h2 class="modal-title" id="addDeviceTitle">Tambah Device WAPI</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">&times;</button>
                    </div>
                    <form method="POST" action="{{ $storeUrl }}">
                        <div class="modal-body">
                            <div class="form-grid">
                                <div>
                                    <label for="sender">Nomor Sender</label>
                                    <input id="sender" class="field" type="text" name="sender" inputmode="numeric" placeholder="6281234567890" required>
                                    <div class="small">Gunakan kode negara tanpa tanda +.</div>
                                </div>
                                @if ($isManager && $user)
                                    <input type="hidden" name="owner_user_id" value="{{ $user->id }}">
                                @endif
                                <div>
                                    <label for="webhook">Webhook URL</label>
                                    <input id="webhook" class="field" type="url" name="webhook" placeholder="Opsional">
                                    <div class="small">Boleh dikosongkan.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn is-success">Simpan Device</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($devices->isEmpty())
            <div class="empty">
                Belum ada perangkat WAPI untuk akun ini. Klik Tambah Device untuk membuat nomor pengirim baru.
            </div>
        @else
            <section class="table-card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                @if ($isManager)
                                    <th>Pemilik</th>
                                @endif
                                <th>Nomor</th>
                                <th>API Token</th>
                                <th>Webhook URL</th>
                                <th>Terkirim</th>
                                <th>Status</th>
                                <th>Options</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($devices as $device)
                                <tr>
                                    @if ($isManager)
                                        <td class="owner">
                                            <strong>{{ optional($device->user)->name ?: 'Tanpa pemilik' }}</strong>
                                            <div class="small">{{ optional($device->user)->email ?: '-' }}</div>
                                        </td>
                                    @endif
                                    <td>{{ $device->body }}</td>
                                    <td>
                                        <div class="token">
                                            <input type="text" value="{{ $device->api_token }}" readonly>
                                            <button type="button" class="btn" data-copy="{{ $device->api_token }}">Copy</button>
                                        </div>
                                    </td>
                                    <td>{{ $device->message_sent ?? 0 }}</td>
                                    <td>
                                        <span class="status {{ $device->status === 'Connected' ? 'is-connected' : '' }}" title="{{ $device->status }}"></span>
                                        <div class="small">{{ $device->status }}</div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn" data-open-options="options-{{ $device->id }}">Options</button>
                                        <div class="options-dialog" id="options-{{ $device->id }}" aria-hidden="true">
                                            <div class="options-box">
                                                <div class="options-head">
                                                    <h3>Options {{ $device->body }}</h3>
                                                    <button type="button" class="btn-close" data-close-options>&times;</button>
                                                </div>
                                                <div class="options-body">
                                                    <details class="option-panel" open>
                                                        <summary>Webhook URL</summary>
                                                        <form method="POST" action="{{ $actionUrl }}" class="inline-form" style="margin-top: 10px;">
                                                            <input type="hidden" name="device_id" value="{{ $device->id }}">
                                                            <input type="hidden" name="action" value="update">
                                                            <input class="field" type="url" name="webhook" value="{{ $device->webhook }}" placeholder="Belum diisi">
                                                            <button type="submit" class="btn">Simpan</button>
                                                        </form>
                                                    </details>
                                                    <details class="option-panel" open style="margin-top: 12px;">
                                                        <summary>Pengaturan Device</summary>
                                                        <form method="POST" action="{{ $actionUrl }}" class="option-grid" style="margin-top: 10px;">
                                                            <input type="hidden" name="device_id" value="{{ $device->id }}">
                                                            <input type="hidden" name="action" value="options">
                                                            <input type="hidden" name="webhook_full" value="0">
                                                            <input type="hidden" name="webhook_read" value="0">
                                                            <input type="hidden" name="webhook_reject_call" value="0">
                                                            <input type="hidden" name="webhook_typing" value="0">
                                                            <input type="hidden" name="set_available" value="0">
                                                            <label class="option-check">
                                                                <input type="checkbox" name="webhook_full" value="1" {{ $device->webhook_full ? 'checked' : '' }}>
                                                                Full Response
                                                            </label>
                                                            <label class="option-check">
                                                                <input type="checkbox" name="webhook_read" value="1" {{ $device->webhook_read ? 'checked' : '' }}>
                                                                Read
                                                            </label>
                                                            <label class="option-check">
                                                                <input type="checkbox" name="webhook_reject_call" value="1" {{ $device->webhook_reject_call ? 'checked' : '' }}>
                                                                Reject Call
                                                            </label>
                                                            <label class="option-check">
                                                                <input type="checkbox" name="webhook_typing" value="1" {{ $device->webhook_typing ? 'checked' : '' }}>
                                                                Typing
                                                            </label>
                                                            <label class="option-check">
                                                                <input type="checkbox" name="set_available" value="1" {{ $device->set_available ? 'checked' : '' }}>
                                                                Available
                                                            </label>
                                                            <label class="option-delay">
                                                                <span>Delay</span>
                                                                <input class="field" type="number" name="delay" min="0" max="3600" step="1" value="{{ $device->delay ?? 0 }}">
                                                            </label>
                                                            <button type="submit" class="btn">Simpan Options</button>
                                                        </form>
                                                    </details>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-action">
                                            <button type="button" class="dot-btn" data-action-menu="action-{{ $device->id }}" aria-label="Aksi {{ $device->body }}">⋮</button>
                                            <div class="action-menu" id="action-{{ $device->id }}">
                                                <a href="{{ route('esms-panel.devices.scan', $device).'?'.request()->getQueryString() }}">Konek QR</a>
                                                <a href="{{ route('esms-panel.devices.code', $device).'?'.request()->getQueryString() }}">Konek Code</a>
                                            <form method="POST" action="{{ $actionUrl }}" onsubmit="return confirm('Buat ulang token API perangkat {{ $device->body }}? Token lama tidak bisa dipakai lagi.');">
                                                <input type="hidden" name="device_id" value="{{ $device->id }}">
                                                <input type="hidden" name="action" value="regenerate_token">
                                                    <button type="submit">Token Baru</button>
                                            </form>
                                            <form method="POST" action="{{ $actionUrl }}" onsubmit="return confirm('Diskonek perangkat {{ $device->body }}?');">
                                                <input type="hidden" name="device_id" value="{{ $device->id }}">
                                                <input type="hidden" name="action" value="disconnect">
                                                    <button type="submit" class="is-danger">Diskonek</button>
                                            </form>
                                            <form method="POST" action="{{ $actionUrl }}" onsubmit="return confirm('Hapus device {{ $device->body }}? Device yang masih Connected harus didiskonek dulu.');">
                                                <input type="hidden" name="device_id" value="{{ $device->id }}">
                                                <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="is-danger">Hapus</button>
                                            </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </main>

    <script>
        @if (session('panel_status'))
            try {
                window.parent?.postMessage({
                    type: 'wapi-devices-changed',
                    source: 'wapi-esms-panel',
                    noProvision: {{ str_contains((string) session('panel_status'), 'berhasil dihapus') ? 'true' : 'false' }},
                }, '*');
            } catch (error) {}
        @endif

        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copy || '');
                    button.textContent = 'Tersalin';
                    setTimeout(() => button.textContent = 'Copy', 1500);
                } catch (error) {
                    button.textContent = 'Gagal';
                    setTimeout(() => button.textContent = 'Copy', 1500);
                }
            });
        });

        window.bootstrap = window.bootstrap || {};
        window.bootstrap.Modal = window.bootstrap.Modal || class {
            constructor(element) {
                this.element = element;
            }

            show() {
                this.element.classList.add('show');
                this.element.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                this.element.querySelector('input, button, select, textarea')?.focus();
            }

            hide() {
                this.element.classList.remove('show');
                this.element.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
        };

        const wapiDeviceModalElement = document.getElementById('wapiDeviceModal');
        const wapiDeviceModal = wapiDeviceModalElement ? new bootstrap.Modal(wapiDeviceModalElement) : null;

        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('.js-add-wapi-device');
            if (openButton && wapiDeviceModal) {
                event.preventDefault();
                wapiDeviceModal.show();
                return;
            }

            const closeButton = event.target.closest('[data-bs-dismiss="modal"]');
            if (closeButton && wapiDeviceModal) {
                event.preventDefault();
                wapiDeviceModal.hide();
                return;
            }

            if (event.target === wapiDeviceModalElement && wapiDeviceModal) {
                wapiDeviceModal.hide();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && wapiDeviceModalElement?.classList.contains('show')) {
                wapiDeviceModal?.hide();
            }
        });

        function closeActionMenus() {
            document.querySelectorAll('.action-menu.is-open').forEach((menu) => {
                menu.classList.remove('is-open');
                menu.style.left = '';
                menu.style.top = '';
            });
        }

        document.addEventListener('click', (event) => {
            const actionButton = event.target.closest('[data-action-menu]');

            if (actionButton) {
                event.preventDefault();
                const menu = document.getElementById(actionButton.dataset.actionMenu);
                if (!menu) return;
                const wasOpen = menu.classList.contains('is-open');
                closeActionMenus();
                if (wasOpen) return;
                const rect = actionButton.getBoundingClientRect();
                menu.classList.add('is-open');
                const menuWidth = menu.offsetWidth || 180;
                const menuHeight = menu.offsetHeight || 220;
                const left = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, rect.right - menuWidth));
                const top = rect.bottom + menuHeight > window.innerHeight
                    ? Math.max(8, rect.top - menuHeight)
                    : rect.bottom + 6;
                menu.style.left = left + 'px';
                menu.style.top = top + 'px';
                return;
            }

            if (!event.target.closest('.action-menu')) {
                closeActionMenus();
            }
        });

        document.querySelectorAll('[data-open-options]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = document.getElementById(button.dataset.openOptions);
                if (!dialog) return;
                dialog.classList.add('is-open');
                dialog.setAttribute('aria-hidden', 'false');
                dialog.querySelector('input, button, select, textarea')?.focus();
            });
        });

        document.querySelectorAll('.options-dialog').forEach((dialog) => {
            const close = () => {
                dialog.classList.remove('is-open');
                dialog.setAttribute('aria-hidden', 'true');
            };

            dialog.querySelectorAll('[data-close-options]').forEach((button) => {
                button.addEventListener('click', close);
            });

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) close();
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            closeActionMenus();
            document.querySelectorAll('.options-dialog.is-open').forEach((dialog) => {
                dialog.classList.remove('is-open');
                dialog.setAttribute('aria-hidden', 'true');
            });
        });
    </script>
</body>
</html>
