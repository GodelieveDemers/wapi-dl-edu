<x-layout-dashboard title="Analitik & Laporan">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom-icon">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">{{ __('Reports') }}</a>
                <i class="breadcrumb-icon icon-base ti tabler-chevron-right align-middle icon-xs"></i>
            </li>
            <li class="breadcrumb-item active">Analitik & Laporan</li>
        </ol>
    </nav>

    <style>
        .analytics-hero {
            background: linear-gradient(135deg, rgba(39, 105, 255, .12), rgba(0, 186, 199, .10));
            border: 1px solid rgba(39, 105, 255, .14);
        }
        .analytics-metric {
            min-height: 148px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .analytics-metric:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.75rem 1.5rem rgba(47, 43, 61, .10) !important;
        }
        .analytics-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }
        .analytics-progress {
            height: 8px;
        }
    </style>

    <div class="card analytics-hero shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge bg-label-primary mb-2">Kinerja Pesan WhatsApp</span>
                <h4 class="mb-1">Analitik dan Laporan</h4>
                <p class="mb-0 text-muted">
                    Pantau jumlah terkirim, gagal, sumber pengiriman API/Web, respons masuk, performa perangkat, dan tren harian.
                </p>
            </div>
            <div class="text-lg-end">
                <div class="small text-muted mb-1">Periode aktif</div>
                <div class="fw-semibold">{{ $from->format('d M Y') }} - {{ $to->format('d M Y') }}</div>
                <div class="small text-muted">Data berdasarkan riwayat pesan yang tersimpan.</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('analytics.reports') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Dari tanggal</label>
                    <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai tanggal</label>
                    <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Perangkat</label>
                    <select name="device_id" class="form-select">
                        <option value="">Semua perangkat</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}" @selected((string) request('device_id') === (string) $device->id)>
                                {{ $device->body }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="success" @selected(request('status') === 'success')>Terkirim</option>
                        <option value="failed" @selected(request('status') === 'failed')>Gagal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Via</label>
                    <select name="send_by" class="form-select">
                        <option value="">API & Web</option>
                        <option value="api" @selected(request('send_by') === 'api')>API</option>
                        <option value="web" @selected(request('send_by') === 'web')>Web</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit">
                        <i class="ti tabler-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card analytics-metric shadow-sm border-0">
                <div class="card-body">
                    <div class="analytics-icon bg-primary-subtle text-primary mb-3"><i class="ti tabler-send"></i></div>
                    <div class="text-muted small">Total pesan</div>
                    <h3 class="mb-1">{{ number_format($summary['total']) }}</h3>
                    <div class="small text-muted">Semua percobaan kirim pada periode ini.</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card analytics-metric shadow-sm border-0">
                <div class="card-body">
                    <div class="analytics-icon bg-success-subtle text-success mb-3"><i class="ti tabler-circle-check"></i></div>
                    <div class="text-muted small">Terkirim</div>
                    <h3 class="mb-1">{{ number_format($summary['success']) }}</h3>
                    <div class="progress analytics-progress">
                        <div class="progress-bar bg-success" style="width: {{ $summary['delivery_rate'] }}%"></div>
                    </div>
                    <div class="small text-muted mt-2">Rasio sukses {{ $summary['delivery_rate'] }}%</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card analytics-metric shadow-sm border-0">
                <div class="card-body">
                    <div class="analytics-icon bg-danger-subtle text-danger mb-3"><i class="ti tabler-alert-triangle"></i></div>
                    <div class="text-muted small">Gagal</div>
                    <h3 class="mb-1">{{ number_format($summary['failed']) }}</h3>
                    <div class="small text-muted">Rasio gagal {{ $summary['failure_rate'] }}%</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card analytics-metric shadow-sm border-0">
                <div class="card-body">
                    <div class="analytics-icon bg-info-subtle text-info mb-3"><i class="ti tabler-message-reply"></i></div>
                    <div class="text-muted small">Tanggapan pelanggan</div>
                    <h3 class="mb-1">{{ number_format($summary['incoming_replies']) }}</h3>
                    <div class="small text-muted">Dihitung dari chat masuk pada periode ini.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Tren pengiriman harian</h5>
                        <small class="text-muted">Perbandingan total, terkirim, dan gagal.</small>
                    </div>
                </div>
                <div class="card-body">
                    <div id="deliveryTrendChart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan kanal</h5>
                    <small class="text-muted">Sumber data dan jangkauan penerima.</small>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Via API</span>
                        <strong>{{ number_format($summary['api']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Via Web</span>
                        <strong>{{ number_format($summary['web']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Penerima unik</span>
                        <strong>{{ number_format($summary['unique_recipients']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Perangkat aktif</span>
                        <strong>{{ number_format($summary['active_devices']) }}</strong>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Status terbaca:</strong> belum dihitung karena riwayat pesan belum menyimpan event read receipt.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h5 class="mb-0">Performa perangkat</h5>
                    <small class="text-muted">Nomor pengirim dengan aktivitas tertinggi.</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Perangkat</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Terkirim</th>
                                <th class="text-end">Gagal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deviceBreakdown as $device)
                                <tr>
                                    <td>{{ $device->device_name }}</td>
                                    <td class="text-end">{{ number_format($device->total) }}</td>
                                    <td class="text-end text-success">{{ number_format($device->success) }}</td>
                                    <td class="text-end text-danger">{{ number_format($device->failed) }}</td>
                                </tr>
                            @empty
                                <x-no-data colspan="4" text="Belum ada data perangkat pada periode ini" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h5 class="mb-0">Tipe pesan</h5>
                    <small class="text-muted">Komposisi pesan text, media, button, dan lainnya.</small>
                </div>
                <div class="card-body">
                    <div id="messageTypeChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">
            <h5 class="mb-0">Pesan gagal terbaru</h5>
            <small class="text-muted">Membantu mencari nomor, perangkat, atau payload yang perlu dicek ulang.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Perangkat</th>
                        <th>Nomor</th>
                        <th>Pesan</th>
                        <th>Via</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentFailures as $message)
                        <tr>
                            <td class="text-nowrap">{{ $message->created_at->format('d M Y H:i') }}</td>
                            <td><span class="badge bg-label-primary">{{ optional($message->device)->body ?? '-' }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($message->number, 18) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit(strip_tags($message->message), 55) }}</td>
                            <td><span class="badge bg-label-warning">{{ strtoupper($message->send_by) }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($message->note ?: '-', 45) }}</td>
                        </tr>
                    @empty
                        <x-no-data colspan="6" text="Tidak ada pesan gagal pada periode ini" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const daily = @json($daily);
            const typeBreakdown = @json($typeBreakdown);

            const textColor = '#566a7f';
            const borderColor = '#e6e8ef';

            new ApexCharts(document.querySelector('#deliveryTrendChart'), {
                chart: { type: 'area', height: 330, toolbar: { show: false } },
                series: [
                    { name: 'Total', data: daily.map(item => item.total) },
                    { name: 'Terkirim', data: daily.map(item => item.success) },
                    { name: 'Gagal', data: daily.map(item => item.failed) }
                ],
                xaxis: {
                    categories: daily.map(item => item.date),
                    labels: { style: { colors: textColor } }
                },
                yaxis: { labels: { style: { colors: textColor } } },
                colors: ['#2f6bff', '#28c76f', '#ea5455'],
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { opacityFrom: .28, opacityTo: .04 } },
                dataLabels: { enabled: false },
                grid: { borderColor },
                legend: { position: 'top' }
            }).render();

            new ApexCharts(document.querySelector('#messageTypeChart'), {
                chart: { type: 'donut', height: 310 },
                labels: typeBreakdown.map(item => item.label || 'unknown'),
                series: typeBreakdown.map(item => Number(item.total)),
                colors: ['#2f6bff', '#00bad1', '#ff9f43', '#28c76f', '#ea5455', '#7367f0', '#a8aaae', '#00cfe8'],
                dataLabels: { enabled: true },
                legend: { position: 'bottom' },
                noData: { text: 'Belum ada data' }
            }).render();
        });
    </script>
</x-layout-dashboard>
