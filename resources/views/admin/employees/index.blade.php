<x-layouts.admin :title="auth()->user()->isKepalaUpt() ? 'Kinerja Petugas' : 'Daftar Pegawai'" :header="auth()->user()->isKepalaUpt() ? 'Kinerja Petugas' : 'Pengelolaan Pegawai'">
    @if(auth()->user()->isKepalaUpt())
        <x-slot:headerActions>
            <form method="GET" action="{{ route('admin.employees.index') }}" id="filterForm" class="flex items-center gap-2">
                {{-- Year Dropdown --}}
                <div class="relative" id="yearDropdownWrapper">
                    <button type="button" id="yearDropdownBtn"
                        class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span id="yearDropdownLabel">{{ $year }}</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                    <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                        @foreach($availableYears as $y)
                            <button type="button" data-value="{{ $y }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $year == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                {{ $y }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </form>
        </x-slot:headerActions>

        {{-- Summary Stats for Kepala UPT --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Petugas UPT</p>
                <p class="text-2xl font-black text-slate-900">{{ count($employeeData) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Ketetapan (SPTPD)</p>
                <p class="text-2xl font-black text-blue-600">Rp {{ number_format($uptSptpd, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
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
                <div class="relative" id="empDistrictDropdownWrapper">
                    <button type="button" id="empDistrictDropdownBtn"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="empDistrictDropdownLabel">Semua Kecamatan</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="empDistrictDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-52 bg-white border border-slate-200 rounded-xl shadow-xl py-1 max-h-64 overflow-y-auto">
                        <button type="button" data-id="all" data-label="Semua Kecamatan"
                            class="emp-district-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 font-black text-blue-600 bg-blue-50/50">
                            Semua Kecamatan
                        </button>
                        @foreach($upt->districts->sortBy('name') as $district)
                            <button type="button" data-id="{{ $district->id }}" data-label="{{ $district->name }}"
                                class="emp-district-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 text-slate-700 font-bold">
                                {{ $district->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="empForecastLoading" class="hidden px-6 py-12 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                    <img src="{{ asset('img/generating_report.gif') }}" alt="Memuat..." class="w-24 h-24 object-contain">
                    <p class="text-xs text-slate-400 font-medium">Sedang memproses prediksi...</p>
                </div>
            </div>
            <div id="empForecastError" class="hidden px-6 py-8 text-center">
                <p class="text-rose-500 text-sm font-bold" id="empForecastErrorMsg">Gagal memuat prediksi.</p>
            </div>
            <div id="empForecastChartArea" class="hidden p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800" id="empForecastTitle">—</p>
                        <p class="text-xs text-slate-400 mt-0.5" id="empForecastSubtitle"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 shrink-0">
                        <span class="flex items-center gap-1.5"><span class="inline-block w-6 h-0.5 bg-blue-500"></span> Ketetapan</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-6 h-0.5 bg-emerald-500"></span> Realisasi</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-6 border-t-2 border-dashed border-orange-400"></span> Prediksi</span>
                    </div>
                </div>
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-full sm:w-auto mb-4">
                    <button data-range="year" class="emp-range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors bg-white text-blue-600 shadow-sm text-center">Tahun Ini</button>
                    <button data-range="1y"   class="emp-range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">1 Thn Terakhir</button>
                    <button data-range="2y"   class="emp-range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">2 Thn Terakhir</button>
                </div>
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Model:</span>
                        <span id="empForecastModel" class="text-[10px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200"></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">sMAPE:</span>
                        <span id="empForecastMape" class="text-[10px] font-bold"></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">MAE:</span>
                        <span id="empForecastMae" class="text-[10px] font-bold text-slate-700"></span>
                    </div>
                </div>
                <div id="empForecastChartWrapper" class="w-full"><canvas id="empForecastChart"></canvas></div>
            </div>
        </div>

        {{-- Ranked Achievement Table --}}
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
                                $pct = $data['attainment_pct'];
                                $rank = $index + 1;
                                
                                $statusLabel = 'Belum Tercapai';
                                $statusColor = 'bg-rose-100 text-rose-700 border-rose-200';
                                if($pct >= 99) {
                                    $statusLabel = 'Tercapai';
                                    $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                } elseif($pct >= 50) {
                                    $statusLabel = 'Hampir Tercapai';
                                    $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
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
            // Year dropdown
            const yearBtn = document.getElementById('yearDropdownBtn');
            const yearMenu = document.getElementById('yearDropdownMenu');
            const yearValue = document.getElementById('yearValue');
            const yearLabel = document.getElementById('yearDropdownLabel');

            if (yearBtn && yearMenu) {
                yearBtn.addEventListener('click', () => yearMenu.classList.toggle('hidden'));
                document.addEventListener('click', function (e) {
                    if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                        yearMenu.classList.add('hidden');
                    }
                });
                document.querySelectorAll('.year-option').forEach(function (opt) {
                    opt.addEventListener('click', function () {
                        yearValue.value = this.dataset.value;
                        yearLabel.textContent = this.textContent.trim();
                        yearMenu.classList.add('hidden');
                        document.getElementById('filterForm').submit();
                    });
                });
            }

            // Forecast chart
            const forecastUrl   = '{{ route('admin.realization-monitoring.district-forecast', $upt) }}';
            const selectedYear  = {{ $year }};
            const CACHE_VERSION = 'v4';
            let chartInstance   = null;
            let currentRange    = 'year';
            let lastRawData     = null;
            let resizeTimer     = null;

            const elLoading   = document.getElementById('empForecastLoading');
            const elError     = document.getElementById('empForecastError');
            const elErrorMsg  = document.getElementById('empForecastErrorMsg');
            const elChartArea = document.getElementById('empForecastChartArea');
            const distMenu    = document.getElementById('empDistrictDropdownMenu');
            const distLbl     = document.getElementById('empDistrictDropdownLabel');

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

            document.getElementById('empDistrictDropdownBtn').addEventListener('click', () => distMenu.classList.toggle('hidden'));
            document.addEventListener('click', e => {
                if (!document.getElementById('empDistrictDropdownWrapper').contains(e.target)) distMenu.classList.add('hidden');
            });

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
                document.querySelectorAll('.emp-range-btn').forEach(btn => {
                    const active = btn.dataset.range === range;
                    btn.classList.toggle('bg-white', active);
                    btn.classList.toggle('text-blue-600', active);
                    btn.classList.toggle('shadow-sm', active);
                    btn.classList.toggle('text-slate-500', !active);
                    btn.classList.toggle('hover:text-slate-700', !active);
                });
                if (lastRawData) renderChart(lastRawData);
            }

            document.querySelectorAll('.emp-range-btn').forEach(btn => {
                btn.addEventListener('click', () => setRange(btn.dataset.range));
            });

            function renderChart(data) {
                lastRawData = data;
                const filteredH = filterByRange(data.historis ?? [], currentRange).filter(h => h.nilai > 0);
                const forecast  = data.forecast ?? [];
                const fitted    = data.fitted ?? [];
                const ketetapan = filterByRange(data.total_ketetapan ?? [], currentRange).filter(k => k.nilai > 0);

                const hMap      = Object.fromEntries(filteredH.map(h => [h.periode, h.nilai]));
                const fMap      = Object.fromEntries(forecast.map(f => [f.periode, f.nilai]));
                const fittedMap = Object.fromEntries(fitted.map(f => [f.periode, f.nilai]));
                const kMap      = Object.fromEntries(ketetapan.map(k => [k.periode, k.nilai]));

                const lastHPeriode = filteredH.length > 0 ? filteredH[filteredH.length - 1].periode : null;
                const allValidH    = (data.historis ?? []).filter(h => h.nilai > 0);
                const globalLastH  = allValidH.length > 0 ? allValidH[allValidH.length - 1].periode : null;
                const showForecast = lastHPeriode && globalLastH && lastHPeriode === globalLastH;
                const forecastVisible = showForecast ? forecast.filter(f => f.periode > lastHPeriode) : [];

                const allPeriods = [...new Set([
                    ...filteredH.map(h => h.periode),
                    ...forecastVisible.map(f => f.periode),
                ])].sort();

                const labels = allPeriods.map(p => fmtPeriode(p));

                const predDataset = allPeriods.map(p => {
                    if (fMap[p] !== undefined && p > (lastHPeriode ?? '')) return fMap[p];
                    if (p === lastHPeriode) return fittedMap[p] ?? hMap[p];
                    if (hMap[p] !== undefined && fittedMap[p] !== undefined) return fittedMap[p];
                    return null;
                });

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

                const wrapper = document.getElementById('empForecastChartWrapper');
                if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
                wrapper.innerHTML = '<canvas id="empForecastChart"></canvas>';

                chartInstance = new Chart(document.getElementById('empForecastChart'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Ketetapan',        data: allPeriods.map(p => kMap[p] ?? null), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true, spanGaps: false },
                            { label: 'Realisasi Aktual', data: allPeriods.map(p => hMap[p] ?? null), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.07)', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true, spanGaps: false },
                            { label: 'Prediksi Pendapatan', data: predDataset, borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.06)', borderWidth: 2, borderDash: [6, 4], pointRadius: 3, tension: 0.3, fill: true, spanGaps: false },
                        ],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ctx.parsed.y !== null ? `${ctx.dataset.label}: ${fmtFull(ctx.parsed.y)}` : null } },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 18 } },
                            y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, callback: v => fmtCompact(v) } },
                        },
                    },
                });

                const kec = data.kecamatan ?? '';
                const firstP = filteredH[0]?.periode ?? '';
                const lastFP = forecastVisible.length > 0 ? forecastVisible[forecastVisible.length - 1].periode : (lastHPeriode ?? '');
                document.getElementById('empForecastTitle').textContent    = `Prediksi Realisasi Pendapatan${kec ? ' — ' + kec : ''}`;
                document.getElementById('empForecastSubtitle').textContent = `${firstP ? fmtPeriode(firstP) : ''} s/d ${lastFP ? fmtPeriode(lastFP) : ''} · ${filteredH.length} bulan historis`;
                document.getElementById('empForecastModel').textContent    = data.model_used ?? '-';

                const mape   = data.mape ?? 0;
                const mapeEl = document.getElementById('empForecastMape');
                mapeEl.textContent = mape.toFixed(2) + '%';
                mapeEl.className   = 'text-[10px] font-bold ' + (mape < 20 ? 'text-emerald-600' : mape < 40 ? 'text-amber-500' : 'text-rose-600');
                document.getElementById('empForecastMae').textContent = fmtFull(Math.round(data.mae ?? 0));
            }

            async function loadForecast(districtId) {
                const cacheKey = `forecast_${CACHE_VERSION}_{{ $upt->id }}_${districtId}_{{ $year }}`;
                const cached   = sessionStorage.getItem(cacheKey);
                if (cached) {
                    try { renderChart(JSON.parse(cached)); showState('chart'); return; }
                    catch (e) { sessionStorage.removeItem(cacheKey); }
                }
                showState('loading');
                try {
                    const res = await fetch(`${forecastUrl}?district_id=${districtId}`);
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        elErrorMsg.textContent = err.error ?? `Error ${res.status}`;
                        showState('error'); return;
                    }
                    const data = await res.json();
                    sessionStorage.setItem(cacheKey, JSON.stringify(data));
                    renderChart(data); showState('chart');
                } catch (e) {
                    elErrorMsg.textContent = 'Forecasting service tidak dapat dijangkau.';
                    showState('error');
                }
            }

            document.querySelectorAll('.emp-district-opt').forEach(opt => {
                opt.addEventListener('click', function () {
                    document.querySelectorAll('.emp-district-opt').forEach(o => {
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

    @else
        <x-slot:headerActions>
            <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Tambah Pegawai
            </a>
        </x-slot:headerActions>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Search --}}
            <div class="px-6 py-4 border-b border-slate-200">
                <form method="GET" action="{{ route('admin.employees.index') }}">
                    <div class="relative max-w-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama atau email..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Nama Pegawai</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Wilayah Tugas (Kecamatan)</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $employee->name }}</div>
                                    <div class="text-[10px] text-slate-400">Terdaftar pada {{ $employee->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    {{ $employee->email }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($employee->districts as $district)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ $district->name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400 italic">Belum ditugaskan</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.employees.show', $employee) }}" class="flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 text-[10px] font-black uppercase tracking-wider rounded-md transition-colors whitespace-nowrap" title="Lihat Kinerja">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                            Pantau Kinerja
                                        </a>
                                        <a href="{{ route('admin.employees.edit', $employee) }}" class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pegawai ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <p>{{ request('search') ? 'Tidak ada pegawai yang cocok.' : 'Belum ada pegawai yang terdaftar.' }}</p>
                                        @if(!request('search'))
                                            <a href="{{ route('admin.employees.create') }}" class="mt-4 text-blue-600 hover:underline font-medium">Tambah pegawai pertama</a>
                                        @endif                                </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($employees->hasPages())
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>
    @endif
</x-layouts.admin>
