<x-layout-dashboard title="Integrasi e-SMS">
    <style>
        .esms-hero { border: 1px solid var(--bs-border-color); border-radius: 8px; background: linear-gradient(180deg, rgba(105,108,255,.08), rgba(255,255,255,0)); }
        .origin-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .02em; color: var(--bs-secondary-color); }
        .origin-row input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
        .status-dot { width: .65rem; height: .65rem; border-radius: 999px; display: inline-block; background: #28c76f; }
    </style>

    <div class="card mb-4">
        <div class="card-body esms-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-label-primary rounded-2 p-3">
                        <i class="ti tabler-plug-connected icon-32px"></i>
                    </span>
                    <div>
                        <h4 class="mb-1">Integrasi e-SMS</h4>
                        <div class="text-muted">Kelola domain e-SMS yang boleh menampilkan panel WAPI internal.</div>
                    </div>
                </div>
                <span class="badge {{ $ssoSecretFilled ? 'bg-label-success' : 'bg-label-danger' }} d-inline-flex align-items-center gap-2">
                    <span class="status-dot" style="{{ $ssoSecretFilled ? '' : 'background:#ea5455' }}"></span>
                    {{ $ssoSecretFilled ? 'SSO Secret Aktif' : 'SSO Secret Kosong' }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge bg-label-info rounded-2 p-2"><i class="ti tabler-world icon-24px"></i></span>
                    <div>
                        <div class="fw-semibold">Domain WAPI</div>
                        <small class="text-muted">{{ $appUrl }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge bg-label-primary rounded-2 p-2"><i class="ti tabler-layout-dashboard icon-24px"></i></span>
                    <div>
                        <div class="fw-semibold">Panel Internal</div>
                        <small class="text-muted">/en/esms-panel/devices</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge bg-label-success rounded-2 p-2"><i class="ti tabler-refresh icon-24px"></i></span>
                    <div>
                        <div class="fw-semibold">Sinkron Device</div>
                        <small class="text-muted">/en/esms-panel/devices/sync</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0">Domain yang Diizinkan</h5>
                <small class="text-muted">Tambah, ubah, atau hapus domain asal aplikasi e-SMS.</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addOriginBtn">
                <i class="ti tabler-plus me-1"></i>Tambah Domain
            </button>
        </div>
        <div class="card-body">
            @if (session('alert'))
                <div class="alert alert-{{ session('alert.type', 'info') }}">{{ session('alert.msg') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.esms-integration.update') }}">
                @csrf
                <input type="hidden" id="allowed_origins" name="allowed_origins" value="">

                <div class="table-responsive border rounded">
                    <table class="table mb-0 origin-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">No</th>
                                <th>Domain e-SMS</th>
                                <th style="width: 160px;">Status</th>
                                <th style="width: 90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="originRows"></tbody>
                    </table>
                </div>

                <div class="form-text mt-2">
                    Gunakan format lengkap, contoh <code>https://e-sms.dl-edu.my.id</code>. Jangan isi path seperti <code>/master-data/wapi</code>.
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti tabler-device-floppy me-1"></i>Simpan Integrasi
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="resetDefaultBtn">
                        <i class="ti tabler-refresh-dot me-1"></i>Isi Bawaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $originList = collect(preg_split('/[\r\n,]+/', old('allowed_origins', $allowedOrigins)) ?: [])
            ->map(fn ($origin) => rtrim(trim((string) $origin), '/'))
            ->filter()
            ->values()
            ->all();
    @endphp

    <script>
        (() => {
            const rows = document.getElementById('originRows');
            const addButton = document.getElementById('addOriginBtn');
            const resetButton = document.getElementById('resetDefaultBtn');
            const hidden = document.getElementById('allowed_origins');
            const defaults = [
                'https://esms.dl-edu.my.id',
                'https://e-sms.dl-edu.my.id',
                'https://e-sms.vpntunnel.my.id',
            ];
            let origins = @json($originList);

            function validOrigin(value) {
                return /^https?:\/\/[a-z0-9.-]+(?::[0-9]+)?$/i.test(String(value || '').trim());
            }

            function normalize(value) {
                return String(value || '').trim().replace(/\/+$/, '');
            }

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[char]));
            }

            function syncHidden() {
                hidden.value = origins.map(normalize).filter(Boolean).join('\n');
            }

            function render() {
                rows.innerHTML = '';
                if (origins.length === 0) origins.push('');

                origins.forEach((origin, index) => {
                    const value = normalize(origin);
                    const isValid = validOrigin(value);
                    const tr = document.createElement('tr');
                    tr.className = 'origin-row';
                    tr.innerHTML = `
                        <td class="align-middle text-muted">${index + 1}</td>
                        <td><input type="url" class="form-control" value="${escapeHtml(value)}" placeholder="https://e-sms.dl-edu.my.id" data-index="${index}"></td>
                        <td class="align-middle"><span class="badge ${isValid ? 'bg-label-success' : 'bg-label-warning'}">${isValid ? 'Valid' : 'Belum valid'}</span></td>
                        <td class="align-middle">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-remove="${index}" title="Hapus">
                                <i class="ti tabler-trash"></i>
                            </button>
                        </td>
                    `;
                    rows.appendChild(tr);
                });

                rows.querySelectorAll('input[data-index]').forEach(input => {
                    input.addEventListener('input', () => {
                        origins[Number(input.dataset.index)] = normalize(input.value);
                        syncHidden();
                    });
                    input.addEventListener('blur', render);
                });

                rows.querySelectorAll('[data-remove]').forEach(button => {
                    button.addEventListener('click', () => {
                        origins.splice(Number(button.dataset.remove), 1);
                        render();
                    });
                });

                syncHidden();
            }

            addButton?.addEventListener('click', () => {
                origins.push('');
                render();
                rows.querySelector('tr:last-child input')?.focus();
            });

            resetButton?.addEventListener('click', () => {
                origins = [...defaults];
                render();
            });

            render();
        })();
    </script>
</x-layout-dashboard>
