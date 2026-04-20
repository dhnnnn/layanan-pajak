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
                    <button onclick="document.getElementById('forecastInfoModal').classList.remove('hidden')"
                        class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors shrink-0"
                        title="Informasi tentang prediksi ini">
                        <span class="text-[10px] font-black leading-none">i</span>
                    </button>
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
                    <x-searchable-select
                        target-input-id="dashForecastAyat"
                        :value="'all'"
                        placeholder="Semua Jenis Pajak"
                        :options="collect([['id' => 'all', 'name' => 'Semua Jenis Pajak']])->merge(
                            $availableAyat->map(fn($nama, $kode) => ['id' => $kode, 'name' => $kode . ' — ' . $nama])->values()
                        )->toArray()"
                    />
                    <input type="hidden" id="dashForecastAyat" value="all">
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
                </div>

                <div id="dashForecastMeta" class="text-xs text-slate-400 w-full sm:w-auto"></div>
            </div>

            <div id="dashChartError" class="hidden text-center py-8 text-sm text-red-400"></div>
            <div id="dashChartLoading" class="flex flex-col items-center justify-center py-8 gap-3">
                <img src="{{ asset('img/generating_report.gif') }}" alt="Memuat..." class="w-24 h-24 object-contain">
                <p class="text-xs text-slate-400 font-medium">Sedang memproses prediksi...</p>
            </div>
            <div id="dashChartWrapper" class="w-full hidden">
                <canvas id="dashForecastChart"></canvas>
            </div>
        </div>

        {{-- Section Title --}}
        <div class="flex items-center gap-2 pt-2">
            <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest">Realisasi Per-Tribulan {{ $selectedYear }}</h3>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-[11px] border-collapse bg-white">
                    <thead class="bg-slate-50 text-slate-900 uppercase font-bold sticky top-0 z-30 border-b-2 border-slate-300">
                        <tr>
                            <th rowspan="2" class="px-3 py-4 border border-slate-300 text-left min-w-[200px] sticky left-0 bg-slate-50 z-40 shadow-[1px_0_0_rgba(0,0,0,0.1)]">Nama Pajak</th>
                            <th rowspan="2" class="px-3 py-4 border border-slate-300 text-right min-w-[140px]">Target APBD</th>
                            <th rowspan="2" class="px-3 py-4 border border-slate-300 text-right min-w-[140px] bg-amber-50 text-amber-800">Target Tambahan</th>
                            <th rowspan="2" class="px-3 py-4 border border-slate-300 text-right min-w-[140px] bg-blue-50 text-blue-800">Total Target</th>
                            <th colspan="5" class="px-3 py-3 border border-slate-300 text-center bg-slate-100">Tribulan 1</th>
                            <th colspan="5" class="px-3 py-3 border border-slate-300 text-center bg-slate-200/50">Tribulan 2</th>
                            <th colspan="5" class="px-3 py-3 border border-slate-300 text-center bg-slate-100">Tribulan 3</th>
                            <th colspan="5" class="px-3 py-3 border border-slate-300 text-center bg-slate-200/50">Tribulan 4</th>
                            <th rowspan="2" class="px-3 py-4 border border-slate-300 text-right min-w-[140px] bg-slate-50">Lebih/(Kurang)</th>
                        </tr>
                        <tr>
                            @for($i = 1; $i <= 4; $i++)
                                <th class="px-3 py-3 border border-slate-300 text-right min-w-[110px] bg-slate-50 font-bold">Target</th>
                                <th class="px-3 py-3 border border-slate-300 text-right min-w-[90px] bg-amber-50 text-amber-700 font-bold">+Tambahan</th>
                                <th class="px-3 py-3 border border-slate-300 text-right min-w-[110px] bg-slate-50 font-bold">Realisasi</th>
                                <th class="px-3 py-3 border border-slate-300 text-center min-w-[50px] bg-slate-50 font-bold">% Awal</th>
                                <th class="px-3 py-3 border border-slate-300 text-center min-w-[50px] bg-emerald-50 text-emerald-700 font-bold">% Naik</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dashboard as $item)
                            @php
                                $isParent = $item['is_parent'] ?? false;
                                $isChild  = $item['is_child'] ?? false;
                            @endphp
                            <tr class="{{ $isParent ? 'bg-blue-50 font-extrabold' : 'hover:bg-slate-50' }} transition-colors group">
                                <td class="px-4 py-3 border-x border-slate-200 sticky left-0 {{ $isParent ? 'bg-blue-50' : 'bg-white group-hover:bg-slate-50' }} z-10 transition-colors shadow-[1px_0_0_rgba(0,0,0,0.05)]">
                                    <div class="{{ $isChild ? 'pl-6 text-slate-600 font-medium' : 'text-slate-900 font-black' }} whitespace-nowrap">
                                        {{ $isChild ? '– ' : '' }}{{ $item['tax_type_name'] }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 border-r border-slate-200 text-right {{ $isParent ? 'text-blue-900 bg-blue-50' : 'text-slate-700' }} font-bold">
                                    {{ number_format($item['target_total'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 border-r border-slate-200 text-right {{ $isParent ? 'bg-blue-50' : '' }} {{ ($item['additional_target'] ?? 0) > 0 ? 'text-amber-700 font-bold' : 'text-slate-400' }}">
                                    {{ ($item['additional_target'] ?? 0) > 0 ? '+'.number_format($item['additional_target'], 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 border-r border-slate-200 text-right font-bold {{ $isParent ? 'bg-blue-100 text-blue-900' : 'bg-blue-50/50 text-blue-800' }}">
                                    {{ number_format($item['target_with_additional'] ?? $item['target_total'], 0, ',', '.') }}
                                </td>
                                @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                    @php
                                        $targetBase    = (float) ($item['targets_base'][$q] ?? $item['targets'][$q] ?? 0);
                                        $tambQ         = ($item['targets'][$q] ?? 0) - $targetBase;
                                        $pctBase       = $item['percentages_base'][$q] ?? 0;
                                        $hasAdd        = $tambQ > 0;
                                        $pctNaikTarget = $targetBase > 0 && $hasAdd ? ($tambQ / $targetBase) * 100 : 0;
                                    @endphp
                                    <td class="px-3 py-3 border-r border-slate-200 text-right text-slate-600 {{ $isParent ? 'bg-blue-50' : '' }}">
                                        {{ number_format($targetBase, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-right {{ $isParent ? 'bg-blue-50' : '' }} {{ $hasAdd ? 'text-amber-700 font-bold' : 'text-slate-300' }}">
                                        {{ $hasAdd ? '+'.number_format($tambQ, 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-right {{ $isParent ? 'text-blue-900 bg-blue-50' : 'text-slate-900 font-bold' }}">
                                        {{ number_format($item['realizations'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-center font-black {{ $isParent ? 'bg-blue-50' : '' }}
                                        {{ $pctBase >= 100 ? 'text-emerald-600' : ($pctBase >= 50 ? 'text-amber-500' : 'text-slate-800') }}">
                                        {{ number_format($pctBase, 1, ',', '.') }}%
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-center font-black {{ $isParent ? 'bg-emerald-50/40' : 'bg-emerald-50/20' }}
                                        {{ !$hasAdd ? 'text-slate-300' : 'text-amber-600' }}">
                                        {{ $hasAdd ? '+'.number_format($pctNaikTarget, 1, ',', '.').'%' : '—' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 border-r border-slate-200 text-right font-black {{ $isParent ? 'bg-blue-50' : '' }} {{ $item['more_less'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($item['more_less'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="23" class="px-6 py-16 text-center text-slate-500 bg-white">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-base font-medium text-slate-400">Belum ada data realisasi untuk tahun {{ $selectedYear }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($dashboard->isNotEmpty())
                    <tfoot class="bg-slate-200 text-slate-900 font-black border-t-2 border-slate-400">
                        <tr>
                            <td class="px-4 py-4 border-x border-slate-300 sticky left-0 bg-slate-200 z-10 text-[12px] shadow-[1px_0_0_rgba(0,0,0,0.1)]">JUMLAH TOTAL</td>
                            <td class="px-4 py-4 border-r border-slate-300 text-right text-[12px]">{{ number_format($totalTarget, 0, ',', '.') }}</td>
                            <td class="px-4 py-4 border-r border-slate-300 text-right text-[12px] text-amber-700">
                                {{ $totals['additional_target'] > 0 ? '+'.number_format($totals['additional_target'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-4 border-r border-slate-300 text-right text-[12px] text-blue-800">
                                {{ number_format($totals['target_with_additional'], 0, ',', '.') }}
                            </td>
                            @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-slate-700 font-bold">
                                    {{ number_format($totals['quarters'][$q]['target'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-amber-700 font-bold bg-amber-50/50">—</td>
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-slate-900 underline">
                                    {{ number_format($totals['quarters'][$q]['realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-center">
                                    {{ number_format($totals['quarters'][$q]['percentage'], 0, ',', '.') }}%
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-center bg-emerald-50/50 text-emerald-700">—</td>
                            @endforeach
                            <td class="px-4 py-4 border-r border-slate-300 text-right underline">
                                {{ number_format($totalMoreLess, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-[10px] text-slate-400 italic">
                * Angka Target dan Realisasi pada kolom Tribulan bersifat kumulatif (contoh: Tribulan 2 adalah akumulasi T1 + T2).
            </div>
        </div>



        <p class="text-[10px] text-slate-400 italic px-1">* Angka Tribulan bersifat kumulatif (Tribulan 2 = T1 + T2)</p>
    </div>

    {{-- Modal: Informasi Prediksi --}}
    <div id="forecastInfoModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('forecastInfoModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                        <span class="text-orange-500 font-black text-base leading-none">i</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800">Dari Mana Data Prediksi Ini Berasal?</h3>
                    </div>
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
                        <p class="text-xs text-slate-500 leading-relaxed">Sistem menganalisis riwayat penerimaan pajak dari periode sebelumnya — mulai dari beberapa bulan hingga bertahun-tahun ke belakang. Dari data tersebut, sistem mengidentifikasi pola seperti periode ramai, periode sepi, serta kecenderungan tren naik atau turun.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="w-7 h-7 bg-orange-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-orange-500 font-black text-xs">2</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-xs mb-0.5">Diproses dengan Model Statistik (SARIMA/ARIMA)</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Prediksi tidak dibuat secara acak. Sistem menggunakan model statistik yang telah terbukti akurat dalam menganalisis data deret waktu (time series), khususnya yang memiliki pola musiman. Pendekatan ini mirip seperti prakiraan cuaca — menggunakan data masa lalu untuk memperkirakan kondisi di masa depan.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-emerald-600 font-black text-xs">3</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-xs mb-0.5">Hasil Berupa Estimasi, Bukan Kepastian</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Perlu dipahami bahwa hasil prediksi merupakan estimasi terbaik berdasarkan data yang tersedia. Semakin lengkap dan konsisten data historis, semakin tinggi tingkat akurasi prediksi. Gunakan hasil ini sebagai acuan dalam perencanaan, bukan sebagai angka pasti.</p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-xs text-amber-700 leading-relaxed">Nilai sMAPE yang ditampilkan menunjukkan seberapa akurat model ini. Semakin kecil angkanya, semakin baik. Di bawah 20% berarti prediksi sangat dapat diandalkan.</p>
                </div>
            </div>
        </div>
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

            const now = new Date();
            const thisYear = now.getFullYear();
            const fittedCutoff = '2024-01';

            // Filter historis sesuai range
            let filteredH;
            if (dashCurrentRange === 'year') {
                filteredH = data.historis.filter(h => h.periode.startsWith(SELECTED_YEAR + '-'));
            } else if (dashCurrentRange === '1y') {
                filteredH = data.historis.filter(h => h.periode >= `${thisYear - 1}-01`);
            } else {
                filteredH = data.historis.filter(h => h.periode >= `${thisYear - 2}-01`);
            }

            const forecast = (data.forecast ?? []);
            const fitted   = data.fitted ?? [];

            if (filteredH.length === 0) {
                document.getElementById('dashChartError').textContent = 'Tidak ada data historis untuk periode yang dipilih.';
                document.getElementById('dashChartError').classList.remove('hidden');
                document.getElementById('dashChartWrapper').classList.add('hidden');
                return;
            }
            document.getElementById('dashChartError').classList.add('hidden');
            document.getElementById('dashChartLoading').classList.add('hidden');
            document.getElementById('dashChartWrapper').classList.remove('hidden');

            // Batasi forecast hingga Desember tahun ini (SELECTED_YEAR)
            const endPeriode = `${SELECTED_YEAR}-12`;
            const filteredForecast = forecast.filter(f => f.periode <= endPeriode);

            let filteredTarget = (data.target_bulanan ?? []);
            if (dashCurrentRange === 'year') {
                filteredTarget = filteredTarget.filter(t => t.periode.startsWith(SELECTED_YEAR + '-'));
            } else if (dashCurrentRange === '1y') {
                filteredTarget = filteredTarget.filter(t => t.periode >= `${thisYear - 1}-01`);
            } else {
                filteredTarget = filteredTarget.filter(t => t.periode >= `${thisYear - 2}-01`);
            }

            const hMap      = Object.fromEntries(filteredH.map(h => [h.periode, h.nilai]));
            const fMap      = Object.fromEntries(filteredForecast.map(f => [f.periode, f.nilai]));
            const fittedMap = Object.fromEntries(fitted.map(f => [f.periode, f.nilai]));
            const targetMap = Object.fromEntries(filteredTarget.map(t => [t.periode, t.nilai]));
            const tambahanMap = Object.fromEntries(
                ((data.target_tambahan_bulanan ?? [])
                    .filter(t => {
                        if (dashCurrentRange === 'year') return t.periode.startsWith(SELECTED_YEAR + '-');
                        if (dashCurrentRange === '1y') return t.periode >= `${thisYear - 1}-01`;
                        return t.periode >= `${thisYear - 2}-01`;
                    }))
                .map(t => [t.periode, t.nilai])
            );

            // Cek apakah ada data tambahan sama sekali
            const hasTambahan = Object.keys(tambahanMap).some(k => tambahanMap[k] > 0);

            const allPeriodes = [...new Set([
                ...filteredH.map(h => h.periode),
                ...filteredForecast.map(f => f.periode),
            ])].sort();
            const allLabels = allPeriodes.map(p => fmtP(p));

            const historisDataset = allPeriodes.map(p => hMap[p] ?? null);

            const forecastDataset = allPeriodes.map(p => {
                if (fMap[p] !== undefined) return fMap[p];
                if (fittedMap[p] !== undefined && p >= fittedCutoff) return fittedMap[p];
                if (hMap[p] !== undefined && p < fittedCutoff) return hMap[p];
                return null;
            });

            const mape = data.mape?.toFixed(1) ?? '—';
            const n = parseFloat(mape);
            const mapeColor = n < 20 ? '#16a34a' : n < 40 ? '#d97706' : '#dc2626';
            document.getElementById('dashForecastMeta').innerHTML =
                `${data.historis.length} bulan historis &nbsp;·&nbsp; Model: ${data.model_used}`
                + (forecast.length > 0 ? ` &nbsp;·&nbsp; sMAPE: <span style="color:${mapeColor};font-weight:600">${mape}%</span>` : '');

            if (dashChart) { dashChart.destroy(); dashChart = null; }
            document.getElementById('dashChartWrapper').innerHTML = '<canvas id="dashForecastChart"></canvas>';

            dashChart = new Chart(document.getElementById('dashForecastChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [
                        {
                            // Target APBD — hijau teal putus-putus
                            label: 'Target',
                            data: allPeriodes.map(p => targetMap[p] ?? null),
                            borderColor: '#0d9488',
                            backgroundColor: 'transparent',
                            borderWidth: 1.5,
                            borderDash: [4, 3],
                            pointRadius: 0,
                            tension: 0.3,
                            fill: false,
                            spanGaps: false,
                        },
                        ...(hasTambahan ? [{
                            // Target + Tambahan — amber putus-putus
                            label: 'Target + Tambahan',
                            data: allPeriodes.map(p => {
                                const base = targetMap[p];
                                const add = tambahanMap[p] ?? 0;
                                return base !== undefined ? base + add : null;
                            }),
                            borderColor: '#d97706',
                            backgroundColor: 'transparent',
                            borderWidth: 1.5,
                            borderDash: [2, 2],
                            pointRadius: 0,
                            tension: 0.3,
                            fill: false,
                            spanGaps: false,
                        }] : []),
                        {
                            // Realisasi Aktual — biru solid dengan fill
                            label: 'Realisasi Aktual',
                            data: historisDataset,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,0.08)',
                            borderWidth: 2,
                            pointRadius: 1.5,
                            tension: 0.3,
                            fill: true,
                            spanGaps: false,
                        },
                        {
                            // Prediksi — orange putus-putus dengan fill tipis
                            label: 'Prediksi',
                            data: forecastDataset,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249,115,22,0.05)',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 2.5,
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
                        legend: {
                            display: true, position: 'top', align: 'end',
                            labels: { boxWidth: 24, font: { size: 10 }, padding: 12 },
                        },
                        tooltip: { callbacks: { label: c => c.parsed.y !== null ? `${c.dataset.label}: ${fmtFull(c.parsed.y)}` : null } },
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
            document.getElementById('dashChartWrapper').classList.add('hidden');
            document.getElementById('dashChartLoading').classList.remove('hidden');
            document.getElementById('dashForecastMeta').textContent = '';
            try {
                const res = await fetch(`${FORECAST_URL}?ayat=${ayat}`);
                if (!res.ok) throw new Error((await res.json()).error || 'Gagal memuat.');
                buildDashChart(await res.json());
            } catch (e) {
                document.getElementById('dashChartError').textContent = e.message;
                document.getElementById('dashChartError').classList.remove('hidden');
                document.getElementById('dashChartLoading').classList.add('hidden');
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

        const firstAyat = document.getElementById('dashForecastAyat').value || 'all';
        loadDashForecast(firstAyat);
    </script>
    @endpush
</x-layouts.admin>
