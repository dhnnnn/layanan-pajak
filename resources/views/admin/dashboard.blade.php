<x-layouts.admin title="Dashboard Admin" header="Dashboard Realisasi Pajak">
    <x-slot:headerActions>
        <form action="{{ route('admin.dashboard') }}" method="GET" id="filterForm" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 uppercase hidden sm:inline">Tahun:</span>
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span id="yearDropdownLabel">{{ $selectedYear }}</span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $selectedYear }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @forelse($availableYears as $y)
                        <button type="button" data-value="{{ $y }}"
                            class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $selectedYear == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $y }}
                        </button>
                    @empty
                        <button type="button" data-value="{{ date('Y') }}" class="year-option w-full text-left px-4 py-2 text-sm text-slate-700">
                            {{ date('Y') }}
                        </button>
                    @endforelse
                </div>
            </div>
        </form>
    </x-slot:headerActions>

    @php
        $totalTarget      = $totals['target'];
        $totalRealization = $totals['realization'];
        $avgPercentage    = $totals['percentage'];
        $totalMoreLess    = $totals['more_less'];
        $quarters = [
            'q1' => 'Tribulan 1',
            'q2' => 'Tribulan 2',
            'q3' => 'Tribulan 3',
            'q4' => 'Tribulan 4',
        ];
    @endphp

    <div class="space-y-4">

        {{-- Statistik Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Total Target</p>
                <p class="text-base sm:text-lg font-bold text-slate-900 break-all">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Total Realisasi</p>
                <p class="text-base sm:text-lg font-bold text-blue-600 break-all">Rp {{ number_format($totalRealization, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Lebih/(Kurang)</p>
                <p class="text-base sm:text-lg font-bold break-all {{ $totalMoreLess >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    Rp {{ number_format($totalMoreLess, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Capaian</p>
                <p class="text-base sm:text-lg font-black {{ $avgPercentage >= 100 ? 'text-emerald-600' : ($avgPercentage >= 50 ? 'text-amber-500' : 'text-rose-600') }}">
                    {{ number_format($avgPercentage, 1, ',', '.') }}%
                </p>
            </div>
        </div>

        {{-- Forecasting Preview --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-orange-400 rounded-full"></div>
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest">Prediksi Penerimaan 12 Bulan ke Depan</h3>
                </div>
                <a href="{{ route('admin.forecasting.index') }}"
                   class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                    Lihat detail
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            {{-- Filter bar --}}
            <div class="flex flex-wrap items-center gap-2 mb-4">
                {{-- Pilih jenis pajak --}}
                <div class="flex-1 min-w-40 max-w-xs">
                    <select id="dashForecastAyat" class="no-search w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Semua Jenis Pajak</option>
                        @foreach($availableAyat as $kode => $nama)
                            <option value="{{ $kode }}">{{ $kode }} — {{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter tampilan range --}}
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                    <button data-range="year" onclick="setDashRange('year')"
                        class="dash-range-btn px-3 py-1 rounded-md text-xs font-medium transition-colors bg-white text-blue-600 shadow-sm">
                        Tahun Ini
                    </button>
                    <button data-range="1y" onclick="setDashRange('1y')"
                        class="dash-range-btn px-3 py-1 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700">
                        1 Thn Terakhir
                    </button>
                    <button data-range="2y" onclick="setDashRange('2y')"
                        class="dash-range-btn px-3 py-1 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700">
                        2 Thn Terakhir
                    </button>
                    <button data-range="all" onclick="setDashRange('all')"
                        class="dash-range-btn px-3 py-1 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700">
                        Semua
                    </button>
                </div>

                <div id="dashForecastMeta" class="text-xs text-slate-400 w-full sm:w-auto"></div>
            </div>

            <div id="dashChartError" class="hidden text-center py-8 text-sm text-red-400"></div>
            <div id="dashChartWrapper" class="w-full">
                <canvas id="dashForecastChart"></canvas>
            </div>
        </div>

        {{-- Section Title --}}
        <div class="flex items-center gap-2 pt-2">
            <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest">Realisasi Per-Tribulan {{ $selectedYear }}</h3>
        </div>

        {{-- Table: scrollable on all screen sizes --}}
        <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden">
            {{-- Scroll hint on mobile --}}
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-[11px] border-collapse bg-white">
                    <thead class="bg-slate-50 text-slate-900 uppercase font-bold border-b-2 border-slate-300">
                        <tr>
                            <th rowspan="2" class="px-3 py-3 border border-slate-300 text-left min-w-[160px] sticky left-0 bg-slate-50 z-10">Nama Pajak</th>
                            <th rowspan="2" class="px-3 py-3 border border-slate-300 text-right min-w-[130px]">Target Total</th>
                            @foreach($quarters as $qKey => $qLabel)
                                <th colspan="3" class="px-3 py-2 border border-slate-300 text-center {{ $loop->odd ? 'bg-slate-100' : 'bg-slate-50' }}">{{ $qLabel }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($quarters as $qKey => $qLabel)
                                <th class="px-2 py-2 border border-slate-300 text-right min-w-[100px] bg-slate-50">Target</th>
                                <th class="px-2 py-2 border border-slate-300 text-right min-w-[100px] bg-slate-50">Realisasi</th>
                                <th class="px-2 py-2 border border-slate-300 text-center min-w-[40px] bg-slate-50">%</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dashboard as $item)
                            @php
                                $isParent = $item['is_parent'] ?? false;
                                $isChild  = $item['is_child'] ?? false;
                            @endphp
                            <tr class="{{ $isParent ? 'bg-blue-50 font-extrabold' : 'hover:bg-slate-50' }} transition-colors">
                                <td class="px-3 py-3 border-x border-slate-200 sticky left-0 z-10 whitespace-nowrap
                                    {{ $isParent ? 'bg-blue-50 text-blue-900 font-black' : ($isChild ? 'bg-white pl-7 text-slate-600 font-medium' : 'bg-white font-black text-slate-900') }}
                                    hover:bg-slate-50 transition-colors">
                                    {{ $isChild ? '– ' : '' }}{{ $item['tax_type_name'] }}
                                </td>
                                <td class="px-3 py-3 border-r border-slate-200 text-right font-bold {{ $isParent ? 'text-blue-900' : 'text-slate-700' }}">
                                    {{ number_format($item['target_total'], 0, ',', '.') }}
                                </td>
                                @foreach(array_keys($quarters) as $q)
                                    <td class="px-2 py-3 border-r border-slate-200 text-right text-slate-500 text-[10px] {{ $isParent ? 'bg-blue-50' : '' }}">
                                        {{ number_format($item['targets'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-2 py-3 border-r border-slate-200 text-right font-bold {{ $isParent ? 'text-blue-900 bg-blue-50' : 'text-slate-900' }}">
                                        {{ number_format($item['realizations'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-2 py-3 border-r border-slate-200 text-center font-black {{ $isParent ? 'bg-blue-50' : '' }}
                                        {{ $item['percentages'][$q] >= 100 ? 'text-emerald-600' : ($item['percentages'][$q] >= 50 ? 'text-amber-500' : 'text-slate-700') }}">
                                        {{ number_format($item['percentages'][$q], 1, ',', '.') }}%
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-6 py-12 text-center text-slate-400">Belum ada data untuk tahun {{ $selectedYear }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($dashboard->isNotEmpty())
                    <tfoot class="bg-slate-200 font-black border-t-2 border-slate-400 text-[11px]">
                        <tr>
                            <td class="px-3 py-3 border-x border-slate-300 sticky left-0 bg-slate-200 z-10">JUMLAH TOTAL</td>
                            <td class="px-3 py-3 border-r border-slate-300 text-right">{{ number_format($totalTarget, 0, ',', '.') }}</td>
                            @foreach(array_keys($quarters) as $q)
                                <td class="px-2 py-3 border-r border-slate-300 text-right text-slate-600 font-bold">
                                    {{ number_format($totals['quarters'][$q]['target'], 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-3 border-r border-slate-300 text-right underline">
                                    {{ number_format($totals['quarters'][$q]['realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-3 border-r border-slate-300 text-center">
                                    {{ number_format($totals['quarters'][$q]['percentage'], 1, ',', '.') }}%
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>



        <p class="text-[10px] text-slate-400 italic px-1">* Angka Tribulan bersifat kumulatif (Tribulan 2 = T1 + T2)</p>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
    </style>

    <script>
        // Year dropdown
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });
        document.querySelectorAll('.year-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.getElementById('yearValue').value = this.dataset.value;
                document.getElementById('yearDropdownLabel').textContent = this.textContent.trim();
                document.getElementById('yearDropdownMenu').classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });

        // Mobile card accordion
        document.querySelectorAll('.mobile-card-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const body = this.nextElementSibling;
                const chevron = this.querySelector('.mobile-chevron');
                const isOpen = !body.classList.contains('hidden');
                body.classList.toggle('hidden', isOpen);
                chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
                this.setAttribute('aria-expanded', !isOpen);
            });
        });
    </script>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const FORECAST_URL = '{{ route('admin.forecasting.data') }}';
        const SELECTED_YEAR = {{ $selectedYear }};
        let dashChart = null;
        let dashLastData = null;
        let dashResizeTimer = null;
        let dashCurrentRange = 'year'; // default: tampilkan tahun yang dipilih

        const fmt = val => {
            if (val >= 1e9) return 'Rp ' + (val/1e9).toFixed(2) + ' M';
            if (val >= 1e6) return 'Rp ' + (val/1e6).toFixed(1) + ' Jt';
            return 'Rp ' + val.toLocaleString('id-ID');
        };
        const fmtFull = val => 'Rp ' + Math.round(val).toLocaleString('id-ID');
        const fmtP = str => {
            const [y, m] = str.split('-');
            return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'][+m-1] + ' ' + y;
        };

        // Filter data berdasarkan range yang dipilih
        function filterByRange(historis, forecast, range) {
            const now = new Date();
            const currentYear = now.getFullYear();
            let cutoff = null;

            if (range === 'year') {
                const filteredH = historis.filter(h => h.periode.startsWith(SELECTED_YEAR + '-'));
                // Jika tahun yang dipilih sudah lewat → tidak tampilkan prediksi
                const showForecast = SELECTED_YEAR >= currentYear;
                return { historis: filteredH, forecast: showForecast ? forecast : [] };
            } else if (range === '1y') {
                cutoff = new Date(now.getFullYear() - 1, now.getMonth(), 1);
            } else if (range === '2y') {
                cutoff = new Date(now.getFullYear() - 2, now.getMonth(), 1);
            } else {
                // 'all' — tampilkan semua historis + prediksi
                return { historis, forecast };
            }

            const cutoffStr = cutoff.getFullYear() + '-' + String(cutoff.getMonth() + 1).padStart(2, '0');
            const filteredH = historis.filter(h => h.periode >= cutoffStr);
            return { historis: filteredH, forecast };
        }

        function buildDashChart(data) {
            dashLastData = data;

            const { historis: filteredH, forecast: filteredF } = filterByRange(
                data.historis, data.forecast, dashCurrentRange
            );

            if (filteredH.length === 0) {
                document.getElementById('dashChartError').textContent =
                    'Tidak ada data historis untuk periode yang dipilih.';
                document.getElementById('dashChartError').classList.remove('hidden');
                document.getElementById('dashChartWrapper').classList.add('hidden');
                return;
            }
            document.getElementById('dashChartError').classList.add('hidden');
            document.getElementById('dashChartWrapper').classList.remove('hidden');

            const hL = filteredH.map(h => fmtP(h.periode));
            const hV = filteredH.map(h => h.nilai);
            const fL = filteredF.map(f => fmtP(f.periode));
            const fV = filteredF.map(f => f.nilai);
            const last = hV[hV.length - 1];
            const hasForecast = fV.length > 0;

            const mape = data.mape?.toFixed(1) ?? '—';
            const n = parseFloat(mape);
            const mapeColor = n < 20 ? '#16a34a' : n < 40 ? '#d97706' : '#dc2626';
            document.getElementById('dashForecastMeta').innerHTML =
                `${data.historis.length} bulan historis &nbsp;·&nbsp; Model: ${data.model_used}`
                + (hasForecast ? ` &nbsp;·&nbsp; sMAPE: <span style="color:${mapeColor};font-weight:600">${mape}%</span>` : '');

            if (dashChart) { dashChart.destroy(); dashChart = null; }
            document.getElementById('dashChartWrapper').innerHTML = '<canvas id="dashForecastChart"></canvas>';

            const datasets = [
                {
                    label: 'Realisasi Aktual',
                    data: hasForecast
                        ? [...hV, fV[0] ?? null, ...Array(Math.max(0, fV.length - 1)).fill(null)]
                        : hV,
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.07)',
                    borderWidth: 2, pointRadius: 1.5, tension: 0.3, fill: true, spanGaps: false,
                },
            ];

            if (hasForecast) {
                datasets.push({
                    label: 'Prediksi',
                    data: [...Array(Math.max(0, hV.length - 1)).fill(null), last, ...fV],
                    borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.05)',
                    borderWidth: 2, borderDash: [5,4], pointRadius: 2.5, tension: 0.3, fill: true, spanGaps: false,
                });
            }

            dashChart = new Chart(document.getElementById('dashForecastChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: hasForecast ? [...hL, ...fL] : hL,
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: hasForecast,
                            position: 'top', align: 'end',
                            labels: { boxWidth: 24, font: { size: 10 }, padding: 12 },
                        },
                        tooltip: { callbacks: { label: c => `${c.dataset.label}: ${fmtFull(c.parsed.y)}` } },
                    },
                    scales: {
                        x: { ticks: { maxTicksLimit: 14, font: { size: 9 } }, grid: { display: false } },
                        y: { ticks: { font: { size: 9 }, callback: v => fmt(v) }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    },
                },
            });
        }

        function setDashRange(range) {
            dashCurrentRange = range;
            document.querySelectorAll('.dash-range-btn').forEach(btn => {
                const isActive = btn.dataset.range === range;
                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('text-blue-600', isActive);
                btn.classList.toggle('shadow-sm', isActive);
                btn.classList.toggle('text-slate-500', !isActive);
            });
            if (dashLastData) buildDashChart(dashLastData);
        }

        async function loadDashForecast(ayat) {
            document.getElementById('dashChartError').classList.add('hidden');
            document.getElementById('dashChartWrapper').classList.remove('hidden');
            document.getElementById('dashForecastMeta').textContent = 'Memuat...';
            try {
                const res = await fetch(`${FORECAST_URL}?ayat=${ayat}`);
                if (!res.ok) throw new Error((await res.json()).error || 'Gagal memuat.');
                buildDashChart(await res.json());
            } catch (e) {
                document.getElementById('dashChartError').textContent = e.message;
                document.getElementById('dashChartError').classList.remove('hidden');
                document.getElementById('dashChartWrapper').classList.add('hidden');
                document.getElementById('dashForecastMeta').textContent = '';
            }
        }

        document.getElementById('dashForecastAyat').addEventListener('change', function () {
            loadDashForecast(this.value);
        });

        window.addEventListener('resize', () => {
            clearTimeout(dashResizeTimer);
            dashResizeTimer = setTimeout(() => {
                if (dashLastData) buildDashChart(dashLastData);
            }, 300);
        });

        const firstAyat = document.getElementById('dashForecastAyat').value;
        if (firstAyat) loadDashForecast(firstAyat);
    </script>
    @endpush
</x-layouts.admin>
