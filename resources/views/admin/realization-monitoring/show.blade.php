<x-layouts.admin :title="'Monitoring ' . $upt->name" :header="'Monitoring Realisasi: ' . $upt->name">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.realization-monitoring.show', $upt) }}" id="filterForm" class="flex items-center gap-2">
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="yearDropdownLabel">{{ $year }}</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-xl py-1">
                    @foreach($availableYears as $y)
                        <button type="button" data-value="{{ $y }}" class="year-option w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 {{ $year == $y ? 'font-black text-blue-600 bg-blue-50/50' : 'text-slate-700 font-bold' }}">
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </form>

        <a href="{{ route('admin.realization-monitoring.export', [$upt, 'year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-sm transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export Excel
        </a>

        <a href="{{ route('admin.realization-monitoring.index', ['year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Petugas UPT</p>
            <p class="text-2xl font-black text-slate-900">{{ count($employeeData) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Ketetapan (SPTPD)</p>
            <p class="text-2xl font-black text-blue-600">Rp {{ number_format($uptSptpd, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Realisasi Bayar</p>
            <p class="text-2xl font-black text-emerald-600">Rp {{ number_format($uptPay, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Grafik Prediksi Pendapatan per Kecamatan --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="w-1 h-5 bg-orange-400 rounded-full"></div>
                <h3 class="text-sm font-semibold text-slate-800">Prediksi Pendapatan per Kecamatan</h3>
            </div>
            {{-- Dropdown Kecamatan --}}
            <div class="relative" id="districtDropdownWrapper">
                <button type="button" id="districtDropdownBtn"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span id="districtDropdownLabel">Semua Kecamatan</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="districtDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-52 bg-white border border-slate-200 rounded-xl shadow-xl py-1 max-h-64 overflow-y-auto">
                    <button type="button" data-id="all" data-label="Semua Kecamatan"
                        class="district-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 font-black text-blue-600 bg-blue-50/50">
                        Semua Kecamatan
                    </button>
                    @foreach($upt->districts->sortBy('name') as $district)
                        <button type="button" data-id="{{ $district->id }}" data-label="{{ $district->name }}"
                            class="district-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 text-slate-700 font-bold">
                            {{ $district->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Loading --}}
        <div id="forecastLoading" class="hidden px-6 py-12 text-center">
            <div class="flex flex-col items-center justify-center gap-3">
                <img src="{{ asset('img/generating_report.gif') }}" alt="Memuat..." class="w-24 h-24 object-contain">
                <p class="text-xs text-slate-400 font-medium">Sedang memproses prediksi...</p>
            </div>
        </div>

        {{-- Error --}}
        <div id="forecastError" class="hidden px-6 py-8 text-center">
            <p class="text-rose-500 text-sm font-bold" id="forecastErrorMsg">Gagal memuat prediksi.</p>
        </div>

        {{-- Chart --}}
        <div id="forecastChartArea" class="hidden p-5">
            <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                <div>
                    <p class="text-sm font-semibold text-slate-800" id="forecastChartTitle">—</p>
                    <p class="text-xs text-slate-400 mt-0.5" id="forecastChartSubtitle"></p>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 shrink-0">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-6 h-0.5 bg-blue-500"></span> Ketetapan
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-6 h-0.5 bg-emerald-500"></span> Realisasi
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-6 border-t-2 border-dashed border-orange-400"></span> Prediksi
                    </span>
                    <button onclick="document.getElementById('forecastInfoModal').classList.remove('hidden')"
                        class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors"
                        title="Informasi tentang prediksi ini">
                        <span class="text-[10px] font-black leading-none">i</span>
                    </button>
                </div>
            </div>

            {{-- Filter range --}}
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-full sm:w-auto mb-4">
                <button data-range="year"
                    class="range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors bg-white text-blue-600 shadow-sm text-center">
                    Tahun Ini
                </button>
                <button data-range="1y"
                    class="range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">
                    1 Thn Terakhir
                </button>
                <button data-range="2y"
                    class="range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">
                    2 Thn Terakhir
                </button>
            </div>

            {{-- Meta --}}
            <div class="flex flex-wrap items-center gap-4 mb-4">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Model:</span>
                    <span id="forecastModelUsed" class="text-[10px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">sMAPE:</span>
                    <span id="forecastMape" class="text-[10px] font-bold"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">MAE:</span>
                    <span id="forecastMae" class="text-[10px] font-bold text-slate-700"></span>
                </div>
            </div>

            <div id="forecastChartWrapper" class="w-full">
                <canvas id="forecastChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-6 py-4 text-center w-16">Rank</th>
                        <th class="px-6 py-4">Petugas / Kolektor</th>
                        <th class="px-6 py-4 text-center">Status Kinerja</th>
                        <th class="px-6 py-4 text-right">Ketetapan</th>
                        <th class="px-6 py-4 text-right">Realisasi</th>
                        <th class="px-6 py-4">Achievement</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employeeData as $index => $data)
                        @php
                            $pct  = $data['attainment_pct'];
                            $rank = $index + 1;
                            if ($pct >= 100) {
                                $statusLabel = 'Tercapai';
                                $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            } elseif ($pct >= 50) {
                                $statusLabel = 'Hampir Tercapai';
                                $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
                            } else {
                                $statusLabel = 'Belum Tercapai';
                                $statusColor = 'bg-rose-100 text-rose-700 border-rose-200';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-black {{ $rank <= 3 ? 'text-blue-600' : 'text-slate-400' }}">#{{ $rank }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-900 flex items-center justify-center shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                                        <span class="text-xs font-black text-white">{{ strtoupper(substr($data['employee']->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $data['employee']->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                                            {{ $data['districts_count'] }} Kecamatan:
                                            <span class="text-slate-500 italic">{{ $data['employee']->districts->pluck('name')->implode(', ') }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black border uppercase tracking-wider {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-500">
                                Rp {{ number_format($data['sptpd_total'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                Rp {{ number_format($data['pay_total'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 min-w-[180px]">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2 ring-1 ring-slate-100">
                                        <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 100 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-400' : 'bg-rose-500') }}"
                                            style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-slate-600 w-12 text-right">{{ number_format($pct, 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.realization-monitoring.employee', [$upt, $data['employee'], 'year' => $year]) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-black rounded-lg transition-all active:scale-95 shadow-sm">
                                    Lihat WP
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                Belum ada petugas terdaftar di UPT ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        // ── Year dropdown ──────────────────────────────────────────────────
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });
        document.addEventListener('click', e => {
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });
        document.querySelectorAll('.year-option').forEach(opt => {
            opt.addEventListener('click', function () {
                document.getElementById('yearValue').value = this.dataset.value;
                document.getElementById('yearDropdownLabel').textContent = this.textContent.trim();
                document.getElementById('yearDropdownMenu').classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });

        // ── District dropdown ──────────────────────────────────────────────
        const distMenu = document.getElementById('districtDropdownMenu');
        const distLbl  = document.getElementById('districtDropdownLabel');

        document.getElementById('districtDropdownBtn').addEventListener('click', () => distMenu.classList.toggle('hidden'));
        document.addEventListener('click', e => {
            if (!document.getElementById('districtDropdownWrapper').contains(e.target)) distMenu.classList.add('hidden');
        });

        // ── Forecast ──────────────────────────────────────────────────────
        const forecastUrl   = '{{ route('admin.realization-monitoring.district-forecast', $upt) }}';
        const selectedYear  = {{ $year }};
        const CACHE_VERSION = 'v4';
        let chartInstance = null;
        let currentRange  = 'year';
        let lastRawData   = null;
        let resizeTimer   = null;

        const elLoading   = document.getElementById('forecastLoading');
        const elError     = document.getElementById('forecastError');
        const elErrorMsg  = document.getElementById('forecastErrorMsg');
        const elChartArea = document.getElementById('forecastChartArea');

        const fmtFull    = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
        const fmtCompact = v => {
            if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
            if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
            return 'Rp ' + v.toLocaleString('id-ID');
        };
        const fmtPeriode = p => {
            const [y, m] = p.split('-');
            return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][+m - 1] + ' ' + y;
        };

        function showState(state) {
            [elLoading, elError, elChartArea].forEach(el => el.classList.add('hidden'));
            if (state === 'loading') elLoading.classList.remove('hidden');
            if (state === 'error')   elError.classList.remove('hidden');
            if (state === 'chart')   elChartArea.classList.remove('hidden');
        }

        function filterByRange(arr, range) {
            if (range === 'year') return arr.filter(h => h.periode >= `${selectedYear}-01` && h.periode <= `${selectedYear}-12`);
            if (range === '1y')   return arr.filter(h => h.periode >= `${selectedYear - 1}-01` && h.periode <= `${selectedYear}-12`);
            return arr.filter(h => h.periode >= `${selectedYear - 2}-01` && h.periode <= `${selectedYear}-12`);
        }

        function setRange(range) {
            currentRange = range;
            document.querySelectorAll('.range-btn').forEach(btn => {
                const active = btn.dataset.range === range;
                btn.classList.toggle('bg-white', active);
                btn.classList.toggle('text-blue-600', active);
                btn.classList.toggle('shadow-sm', active);
                btn.classList.toggle('text-slate-500', !active);
                btn.classList.toggle('hover:text-slate-700', !active);
            });
            if (lastRawData) renderChart(lastRawData);
        }

        document.querySelectorAll('.range-btn').forEach(btn => {
            btn.addEventListener('click', () => setRange(btn.dataset.range));
        });

        function renderChart(data) {
            lastRawData = data;

            // Filter historis & ketetapan berdasarkan selectedYear, hanya nilai > 0
            const filteredH = filterByRange(data.historis ?? [], currentRange).filter(h => h.nilai > 0);
            const forecast  = data.forecast ?? [];
            const fitted    = data.fitted ?? [];
            const ketetapan = filterByRange(data.total_ketetapan ?? [], currentRange).filter(k => k.nilai > 0);

            const hMap      = Object.fromEntries(filteredH.map(h => [h.periode, h.nilai]));
            const fMap      = Object.fromEntries(forecast.map(f => [f.periode, f.nilai]));
            const fittedMap = Object.fromEntries(fitted.map(f => [f.periode, f.nilai]));
            const kMap      = Object.fromEntries(ketetapan.map(k => [k.periode, k.nilai]));

            // Periode terakhir historis dalam range
            const lastHPeriode = filteredH.length > 0 ? filteredH[filteredH.length - 1].periode : null;

            // Forecast hanya ditampilkan jika historis terakhir dalam range
            // adalah data terbaru yang ada (bukan tahun historis lama)
            // Cek: apakah lastHPeriode sama dengan data terakhir dari semua historis?
            const allValidH = (data.historis ?? []).filter(h => h.nilai > 0);
            const globalLastH = allValidH.length > 0 ? allValidH[allValidH.length - 1].periode : null;
            const showForecast = lastHPeriode && globalLastH && lastHPeriode === globalLastH;

            // Forecast hanya dari bulan setelah historis terakhir, dan hanya jika relevan
            const forecastVisible = showForecast
                ? forecast.filter(f => f.periode > lastHPeriode)
                : [];

            // Gabungkan semua periode: historis + forecast yang relevan
            const allPeriods = [...new Set([
                ...filteredH.map(h => h.periode),
                ...forecastVisible.map(f => f.periode),
            ])].sort();

            const labels = allPeriods.map(p => fmtPeriode(p));

            // Dataset realisasi aktual
            const realisasiDataset = allPeriods.map(p => hMap[p] ?? null);

            // Dataset ketetapan (include periode forecast juga jika ada)
            const ketetapanDataset = allPeriods.map(p => kMap[p] ?? null);

            // Dataset prediksi: fitted untuk historis + forecast untuk masa depan
            // Fitted hanya untuk periode dalam range historis yang dipilih
            const fittedCutoff = `${selectedYear - 2}-01`;
            const predDataset  = allPeriods.map(p => {
                if (fMap[p] !== undefined && p > (lastHPeriode ?? '')) return fMap[p]; // forecast
                if (p === lastHPeriode) return fittedMap[p] ?? hMap[p];               // titik sambung
                if (hMap[p] !== undefined && fittedMap[p] !== undefined) return fittedMap[p]; // fitted in-sample
                return null;
            });

            // Bridge: isi gap antara akhir historis dan awal forecast agar garis tidak putus
            if (lastHPeriode && forecastVisible.length > 0) {
                const lastHIdx  = allPeriods.indexOf(lastHPeriode);
                const firstFIdx = allPeriods.findIndex(p => fMap[p] !== undefined);
                if (firstFIdx > lastHIdx + 1) {
                    const startVal = predDataset[lastHIdx] ?? hMap[lastHPeriode];
                    const endVal   = predDataset[firstFIdx];
                    const steps    = firstFIdx - lastHIdx;
                    for (let i = 1; i < steps; i++) {
                        predDataset[lastHIdx + i] = startVal + (endVal - startVal) * (i / steps);
                    }
                }
            }

            const wrapper = document.getElementById('forecastChartWrapper');
            if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
            wrapper.innerHTML = '<canvas id="forecastChart"></canvas>';

            chartInstance = new Chart(document.getElementById('forecastChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Ketetapan',
                            data: ketetapanDataset,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.06)',
                            borderWidth: 2,
                            pointRadius: 2,
                            tension: 0.3,
                            fill: true,
                            spanGaps: false,
                        },
                        {
                            label: 'Realisasi Aktual',
                            data: realisasiDataset,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.07)',
                            borderWidth: 2,
                            pointRadius: 2,
                            tension: 0.3,
                            fill: true,
                            spanGaps: false,
                        },
                        {
                            label: 'Prediksi Pendapatan',
                            data: predDataset,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249,115,22,0.06)',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 3,
                            tension: 0.3,
                            fill: true,
                            spanGaps: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.parsed.y !== null ? `${ctx.dataset.label}: ${fmtFull(ctx.parsed.y)}` : null,
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 18 } },
                        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, callback: v => fmtCompact(v) } },
                    },
                },
            });

            const kec = data.kecamatan ?? 'Kecamatan';
            const firstP = filteredH[0]?.periode ?? '';
            const lastFP = forecastVisible.length > 0 ? forecastVisible[forecastVisible.length - 1].periode : (lastHPeriode ?? '');
            document.getElementById('forecastChartTitle').textContent    = `Prediksi Realisasi Pendapatan — ${kec}`;
            document.getElementById('forecastChartSubtitle').textContent =
                `${firstP ? fmtPeriode(firstP) : ''} s/d ${lastFP ? fmtPeriode(lastFP) : ''} · ${filteredH.length} bulan historis · garis oranye = prediksi realisasi`;
            document.getElementById('forecastModelUsed').textContent     = data.model_used ?? '-';

            const mape   = data.mape ?? 0;
            const mapeEl = document.getElementById('forecastMape');
            mapeEl.textContent = mape.toFixed(2) + '%';
            mapeEl.className   = 'text-[10px] font-bold ' + (mape < 20 ? 'text-emerald-600' : mape < 40 ? 'text-amber-500' : 'text-rose-600');
            document.getElementById('forecastMae').textContent = fmtFull(Math.round(data.mae ?? 0));
        }

        async function loadForecast(districtId) {
            const cacheKey = `forecast_${CACHE_VERSION}_{{ $upt->id }}_${districtId}_{{ $year }}`;
            const cached   = sessionStorage.getItem(cacheKey);

            if (cached) {
                try {
                    renderChart(JSON.parse(cached));
                    showState('chart');
                    return;
                } catch (e) {
                    sessionStorage.removeItem(cacheKey);
                }
            }

            showState('loading');
            try {
                const res = await fetch(`${forecastUrl}?district_id=${districtId}`);
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    elErrorMsg.textContent = err.error ?? `Error ${res.status}: Gagal memuat prediksi.`;
                    showState('error');
                    return;
                }
                const data = await res.json();
                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                renderChart(data);
                showState('chart');
            } catch (e) {
                elErrorMsg.textContent = 'Forecasting service tidak dapat dijangkau.';
                showState('error');
            }
        }

        document.querySelectorAll('.district-opt').forEach(opt => {
            opt.addEventListener('click', function () {
                document.querySelectorAll('.district-opt').forEach(o => {
                    o.classList.remove('font-black', 'text-blue-600', 'bg-blue-50/50');
                    o.classList.add('text-slate-700', 'font-bold');
                });
                this.classList.add('font-black', 'text-blue-600', 'bg-blue-50/50');
                this.classList.remove('text-slate-700', 'font-bold');
                distLbl.textContent = this.dataset.label;
                distMenu.classList.add('hidden');
                loadForecast(this.dataset.id);
            });
        });

        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => { if (lastRawData) renderChart(lastRawData); }, 300);
        });

        loadForecast('all');
    })();
    </script>

    {{-- Modal: Informasi Prediksi --}}
    <div id="forecastInfoModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('forecastInfoModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                        <span class="text-orange-500 font-black text-base leading-none">i</span>
                    </div>
                    <h3 class="text-sm font-black text-slate-800">Dari Mana Data Prediksi Ini Berasal?</h3>
                </div>
                <button onclick="document.getElementById('forecastInfoModal').classList.add('hidden')"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="space-y-4 text-sm text-slate-600">
                <div class="flex gap-3">
                    <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-blue-600 font-black text-xs">1</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-xs mb-0.5">Berbasis Data Historis</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Sistem menganalisis riwayat penerimaan pajak per kecamatan dari periode sebelumnya. Dari data tersebut, sistem mengidentifikasi pola seperti periode ramai, periode sepi, serta kecenderungan tren naik atau turun.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="w-7 h-7 bg-orange-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-orange-500 font-black text-xs">2</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-xs mb-0.5">Diproses dengan Model Statistik (SARIMA/ARIMA)</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Prediksi menggunakan model statistik yang terbukti akurat untuk data deret waktu (time series), khususnya yang memiliki pola musiman. Pendekatan ini mirip seperti prakiraan cuaca — menggunakan data masa lalu untuk memperkirakan kondisi di masa depan.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-emerald-600 font-black text-xs">3</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-xs mb-0.5">Hasil Berupa Estimasi, Bukan Kepastian</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Hasil prediksi merupakan estimasi terbaik berdasarkan data yang tersedia. Semakin lengkap dan konsisten data historis, semakin tinggi tingkat akurasi prediksi. Gunakan hasil ini sebagai acuan perencanaan, bukan angka pasti.</p>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-xs text-amber-700 leading-relaxed">Nilai sMAPE menunjukkan seberapa akurat model ini. Di bawah 20% berarti prediksi sangat dapat diandalkan. 20–40% cukup baik. Di atas 40% berarti data historis terbatas.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
