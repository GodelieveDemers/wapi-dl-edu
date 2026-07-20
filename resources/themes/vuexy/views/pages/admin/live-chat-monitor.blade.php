<x-layout-dashboard title="Monitor Chat">
    <style>
        .chat-monitor-hero { border: 1px solid var(--bs-border-color); border-radius: 8px; background: linear-gradient(180deg, rgba(40,199,111,.08), rgba(255,255,255,0)); }
        .chat-table th { font-size: .76rem; text-transform: uppercase; color: var(--bs-secondary-color); white-space: nowrap; }
        .chat-table td { vertical-align: top; }
        .chat-message { max-width: 460px; white-space: normal; word-break: break-word; }
        .live-dot { width: .65rem; height: .65rem; border-radius: 999px; display: inline-block; background: #a8aaae; }
        .live-dot.is-online { background: #28c76f; box-shadow: 0 0 0 .25rem rgba(40,199,111,.12); }
        .filters-grid { display: grid; grid-template-columns: repeat(6, minmax(130px, 1fr)); gap: .75rem; }
        @media (max-width: 1199.98px) { .filters-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 575.98px) { .filters-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="card mb-4">
        <div class="card-body chat-monitor-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-label-success rounded-2 p-3"><i class="ti tabler-messages icon-32px"></i></span>
                    <div>
                        <h4 class="mb-1">Monitor Chat Real-time</h4>
                        <div class="text-muted">Pantau semua pesan Live Chat dari database WAPI dengan pembaruan otomatis.</div>
                    </div>
                </div>
                <span class="badge bg-label-secondary d-inline-flex align-items-center gap-2" id="socketStatus">
                    <span class="live-dot" id="liveDot"></span>
                    <span id="socketText">Menghubungkan</span>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4" id="statsCards">
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Pesan</div><h4 class="mb-0" data-stat="total">{{ number_format($stats['total']) }}</h4></div></div></div>
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Masuk</div><h4 class="mb-0" data-stat="incoming">{{ number_format($stats['incoming']) }}</h4></div></div></div>
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Keluar</div><h4 class="mb-0" data-stat="outgoing">{{ number_format($stats['outgoing']) }}</h4></div></div></div>
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Hari Ini</div><h4 class="mb-0" data-stat="today">{{ number_format($stats['today']) }}</h4></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0">Data Pesan</h5>
                <small class="text-muted">Sumber data: <code>chat_sessions</code> dan <code>chat_messages</code>.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                    <label class="form-check-label" for="autoRefresh">Auto</label>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="refreshBtn"><i class="ti tabler-refresh me-1"></i>Refresh</button>
            </div>
        </div>
        <div class="card-body">
            <div class="filters-grid mb-3">
                <input type="search" class="form-control" id="filterQ" placeholder="Cari pesan, nama, nomor">
                <select class="form-select" id="filterDevice">
                    <option value="">Semua device</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device }}">{{ $device }}</option>
                    @endforeach
                </select>
                <select class="form-select" id="filterDirection">
                    <option value="">Semua arah</option>
                    <option value="incoming">Masuk</option>
                    <option value="outgoing">Keluar</option>
                </select>
                <input type="date" class="form-control" id="filterFrom">
                <input type="date" class="form-control" id="filterTo">
                <select class="form-select" id="filterLimit">
                    <option value="50">50 baris</option>
                    <option value="100" selected>100 baris</option>
                    <option value="200">200 baris</option>
                    <option value="300">300 baris</option>
                </select>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <small class="text-muted" id="lastSync">Belum dimuat</small>
                <small class="text-muted"><span id="rowCount">0</span> baris tampil</small>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-hover mb-0 chat-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Device</th>
                            <th>Kontak</th>
                            <th>Arah</th>
                            <th>Tipe</th>
                            <th>Pesan</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody id="chatRows">
                        <tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.socket.io/4.8.1/socket.io.min.js"></script>
    <script>
        (() => {
            const endpoint = @json(route('admin.live-chat-monitor.data'));
            const socketUrl = @json($socketUrl);
            const tbody = document.getElementById('chatRows');
            const rowCount = document.getElementById('rowCount');
            const lastSync = document.getElementById('lastSync');
            const refreshBtn = document.getElementById('refreshBtn');
            const autoRefresh = document.getElementById('autoRefresh');
            const liveDot = document.getElementById('liveDot');
            const socketText = document.getElementById('socketText');
            const filters = ['filterQ', 'filterDevice', 'filterDirection', 'filterFrom', 'filterTo', 'filterLimit']
                .reduce((carry, id) => ({ ...carry, [id]: document.getElementById(id) }), {});
            let refreshTimer = null;
            let loading = false;

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[char]));
            }

            function badge(direction) {
                return direction === 'incoming'
                    ? '<span class="badge bg-label-success">Masuk</span>'
                    : '<span class="badge bg-label-primary">Keluar</span>';
            }

            function buildUrl() {
                const params = new URLSearchParams({
                    q: filters.filterQ.value,
                    device: filters.filterDevice.value,
                    direction: filters.filterDirection.value,
                    date_from: filters.filterFrom.value,
                    date_to: filters.filterTo.value,
                    limit: filters.filterLimit.value,
                });
                return `${endpoint}?${params.toString()}`;
            }

            function renderRows(rows) {
                rowCount.textContent = rows.length;
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data sesuai filter.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map(row => `
                    <tr data-id="${row.id}">
                        <td class="text-nowrap"><small>${escapeHtml(row.created_at)}</small></td>
                        <td><span class="badge bg-label-secondary">${escapeHtml(row.device)}</span></td>
                        <td><div class="fw-semibold">${escapeHtml(row.contact_name)}</div><small class="text-muted">${escapeHtml(row.phone_number)}</small></td>
                        <td>${badge(row.direction)}</td>
                        <td><span class="badge bg-label-dark">${escapeHtml(row.type)}</span></td>
                        <td class="chat-message">${escapeHtml(row.message || row.type)}</td>
                        <td><div>${escapeHtml(row.owner_name)}</div><small class="text-muted">${escapeHtml(row.owner_email)}</small></td>
                    </tr>
                `).join('');
            }

            function updateStats(stats) {
                Object.entries(stats || {}).forEach(([key, value]) => {
                    const el = document.querySelector(`[data-stat="${key}"]`);
                    if (el) el.textContent = Number(value || 0).toLocaleString('id-ID');
                });
            }

            async function loadRows() {
                if (loading) return;
                loading = true;
                refreshBtn.disabled = true;
                try {
                    const response = await fetch(buildUrl(), { headers: { 'Accept': 'application/json' } });
                    const payload = await response.json();
                    renderRows(payload.rows || []);
                    updateStats(payload.stats || {});
                    lastSync.textContent = `Terakhir sinkron: ${payload.server_time || '-'}`;
                } catch (error) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data chat.</td></tr>';
                } finally {
                    loading = false;
                    refreshBtn.disabled = false;
                }
            }

            function scheduleRefresh() {
                clearTimeout(refreshTimer);
                if (!autoRefresh.checked) return;
                refreshTimer = setTimeout(loadRows, 700);
            }

            Object.values(filters).forEach(input => input.addEventListener('input', scheduleRefresh));
            refreshBtn.addEventListener('click', loadRows);
            loadRows();

            try {
                const socket = io(socketUrl, { transports: ['websocket', 'polling', 'flashsocket'] });
                socket.on('connect', () => {
                    liveDot.classList.add('is-online');
                    socketText.textContent = 'Real-time aktif';
                    socket.emit('authenticate', { userId: {{ auth()->id() }} });
                });
                socket.on('disconnect', () => {
                    liveDot.classList.remove('is-online');
                    socketText.textContent = 'Real-time terputus';
                });
                socket.on('message:new', scheduleRefresh);
                socket.on('session:updated', scheduleRefresh);
            } catch (error) {
                socketText.textContent = 'Mode refresh manual';
            }
        })();
    </script>
</x-layout-dashboard>
