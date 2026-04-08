@php
    $nm = $wpInfo?->nm_wp ?? $npwpd;
    // Aggregate semua tahun yang dipilih
    $allRows = collect($tableData)->flatten(1);
    $totalSptpdAll = $allRows->sum('total_ketetapan');
    $totalBayarAll = $allRows->sum('total_bayar');
    $totalTunggakanAll = $allRows->sum('total_tunggakan');
    $pctAll = $totalSptpdAll > 0 ? ($totalBayarAll / $totalSptpdAll) * 100 : 0;
    // Label rentang tahun
    $yearLabel = count($years) > 1
        ? min($years) . ' – ' . max($years)
        : (string) $years[0];
@endphp
<x-layouts.admin :title="'Detail WP — ' . $nm" :header="'Detail Wajib Pajak'">
    <x-slot:headerActions>
        <a href="{{ $backRoute }}"
            class="inline-flex items-center gap-2 px-3 py-2 text-slate-500 hover:text-slate-900 text-xs font-bold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>

        {{-- Export Excel --}}
        <a href="{{ $isFieldOfficer
            ? route('field-officer.monitoring.wp-detail.export-excel', [$npwpd, $nop, 'year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo, 'multi_year' => $multiYear])
            : route('admin.monitoring.wp-detail.export-excel', [$npwpd, $nop, 'year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo, 'multi_year' => $multiYear]) }}"
            class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all active:scale-95">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Excel
        </a>

        {{-- Export PDF --}}
        <a href="{{ $isFieldOfficer
            ? route('field-officer.monitoring.wp-detail.export-pdf', [$npwpd, $nop, 'year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo, 'multi_year' => $multiYear])
            : route('admin.monitoring.wp-detail.export-pdf', [$npwpd, $nop, 'year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo, 'multi_year' => $multiYear]) }}"
            target="_blank"
            class="inline-flex items-center gap-2 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all active:scale-95">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            PDF
        </a>

        {{-- Year Dropdown di header --}}
        <div class="relative" id="yearDropdownWrapper">
            <button type="button" id="yearDropdownBtn"
                class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span id="yearLabel">{{ $selectedYear }}</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-40 bg-white border border-slate-200 rounded-xl shadow-xl py-1">
                @foreach(range(date('Y'), 2021, -1) as $y)
                    <button type="button" data-year="{{ $y }}"
                        class="year-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 {{ $selectedYear == $y ? 'font-black text-blue-600 bg-blue-50/50' : 'text-slate-700 font-bold' }}">
                        Tahun {{ $y }}
                    </button>
                @endforeach
            </div>
        </div>
    </x-slot:headerActions>

    <form method="GET" action="{{ $isFieldOfficer
        ? route('field-officer.monitoring.wp-detail', [$npwpd, $nop])
        : route('admin.monitoring.wp-detail', [$npwpd, $nop]) }}" id="filterForm">
        <input type="hidden" name="year" id="yearValue" value="{{ $selectedYear }}">
        <input type="hidden" name="month_from" id="monthFromValue" value="{{ $selectedMonthFrom }}">
        <input type="hidden" name="month_to" id="monthToValue" value="{{ $selectedMonthTo }}">
        <input type="hidden" name="multi_year" id="multiYearValue" value="{{ $multiYear }}">

        <div class="space-y-5">

            {{-- WP Info Header --}}
            <div class="bg-slate-900 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -top-8 opacity-[0.04]">
                    <svg class="w-48 h-48 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                    </svg>
                </div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Wajib Pajak</p>
                        <h2 class="text-xl font-black text-white uppercase leading-tight">{{ $wpInfo?->nm_wp ?? '-' }}</h2>
                        @if($wpInfo?->nm_op && $wpInfo->nm_op !== $wpInfo->nm_wp)
                            <p class="text-slate-400 text-xs mt-0.5 uppercase">{{ $wpInfo->nm_op }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <span class="text-[10px] bg-white/10 text-slate-300 px-2.5 py-1 rounded-lg font-mono font-bold">{{ $npwpd }}</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase">{{ $wpInfo?->tax_type_name ?? '-' }}</span>
                            <span class="text-[10px] text-slate-400">📍 {{ $districtName }}</span>
                            @if($wpInfo?->almt_op)
                                <span class="text-[10px] text-slate-500 italic">{{ $wpInfo->almt_op }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0">
                        @if(($wpInfo?->status ?? '') === '1')
                            <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">AKTIF</span>
                        @else
                            <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">NON-AKTIF</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                {{-- Desktop: 1 baris | Mobile: 2 baris --}}
                <div class="flex flex-col md:flex-row md:items-center gap-3">

                    {{-- Bulan Dari + Sampai --}}
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        @php
                            $monthOptions = collect(range(1, 12))->map(fn($m) => ['id' => $m, 'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F')])->toArray();
                        @endphp

                        {{-- Dari --}}
                        <div class="flex-1 min-w-0" x-data='{
                            open: false,
                            search: "",
                            value: {{ $selectedMonthFrom }},
                            options: {{ json_encode($monthOptions) }},
                            get label() { return this.options.find(o => o.id == this.value)?.name ?? "Dari Bulan"; },
                            get filtered() { return this.search ? this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase())) : this.options; },
                            select(opt) {
                                this.value = opt.id; this.open = false; this.search = "";
                                document.getElementById("monthFromValue").value = opt.id;
                                document.getElementById("filterForm").submit();
                            }
                        }'>
                            <button type="button" @click="open = !open" @click.away="open = false"
                                class="w-full flex items-center justify-between px-4 py-2 min-h-[38px] bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 focus:outline-none transition-all">
                                <span x-text="label" class="text-slate-900 font-bold"></span>
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-50 mt-1 w-48 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden" style="display:none;">
                                <div class="p-2 border-b border-slate-50 bg-slate-50/50">
                                    <input type="text" x-model="search" @click.stop placeholder="Cari bulan..."
                                        class="w-full pl-3 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 outline-none">
                                </div>
                                <div class="max-h-48 overflow-y-auto py-1">
                                    <template x-for="opt in filtered" :key="opt.id">
                                        <button type="button" @click="select(opt)"
                                            class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
                                            :class="value == opt.id ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                                            <span x-text="opt.name" class="uppercase"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest shrink-0">–</span>

                        {{-- Sampai --}}
                        <div class="flex-1 min-w-0" x-data='{
                            open: false,
                            search: "",
                            value: {{ $selectedMonthTo }},
                            options: {{ json_encode($monthOptions) }},
                            get label() { return this.options.find(o => o.id == this.value)?.name ?? "Sampai Bulan"; },
                            get filtered() { return this.search ? this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase())) : this.options; },
                            select(opt) {
                                this.value = opt.id; this.open = false; this.search = "";
                                document.getElementById("monthToValue").value = opt.id;
                                document.getElementById("filterForm").submit();
                            }
                        }'>
                            <button type="button" @click="open = !open" @click.away="open = false"
                                class="w-full flex items-center justify-between px-4 py-2 min-h-[38px] bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 focus:outline-none transition-all">
                                <span x-text="label" class="text-slate-900 font-bold"></span>
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-50 mt-1 w-48 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden" style="display:none;">
                                <div class="p-2 border-b border-slate-50 bg-slate-50/50">
                                    <input type="text" x-model="search" @click.stop placeholder="Cari bulan..."
                                        class="w-full pl-3 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 outline-none">
                                </div>
                                <div class="max-h-48 overflow-y-auto py-1">
                                    <template x-for="opt in filtered" :key="opt.id">
                                        <button type="button" @click="select(opt)"
                                            class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
                                            :class="value == opt.id ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                                            <span x-text="opt.name" class="uppercase"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Multi Year Dropdown (pojok kanan) --}}
                    <div class="relative md:ml-auto shrink-0" id="multiYearDropdownWrapper">
                        <button type="button" id="multiYearDropdownBtn"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white border rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm whitespace-nowrap {{ $multiYear > 1 ? 'border-blue-400 text-blue-600' : 'border-slate-200 text-slate-700' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $multiYear > 1 ? 'text-blue-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span id="multiYearLabel">@if($multiYear >= 2){{ $multiYear }} Tahun Terakhir @else Tahun Ini Saja @endif</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="multiYearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-44 bg-white border border-slate-200 rounded-xl shadow-xl py-1">
                            @foreach([1 => 'Tahun Ini Saja', 2 => '2 Tahun Terakhir', 3 => '3 Tahun Terakhir', 4 => '4 Tahun Terakhir', 5 => '5 Tahun Terakhir'] as $val => $lbl)
                                <button type="button" data-val="{{ $val }}" data-label="{{ $lbl }}"
                                    class="multi-year-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 {{ $multiYear == $val ? 'font-black text-blue-600 bg-blue-50/50' : 'text-slate-700 font-bold' }}">
                                    {{ $lbl }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 leading-tight">Total SPTPD {{ $yearLabel }}</p>
                    <p class="text-lg font-black text-blue-600 leading-tight break-all">{{ number_format($totalSptpdAll, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 leading-tight">Total Bayar {{ $yearLabel }}</p>
                    <p class="text-lg font-black text-emerald-600 leading-tight break-all">{{ number_format($totalBayarAll, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 leading-tight">Tunggakan {{ $yearLabel }}</p>
                    <p class="text-lg font-black text-rose-600 leading-tight break-all">{{ number_format($totalTunggakanAll, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 leading-tight">Kepatuhan {{ $yearLabel }}</p>
                    <p class="text-lg font-black leading-tight {{ $pctAll >= 90 ? 'text-emerald-600' : ($pctAll >= 50 ? 'text-amber-500' : 'text-rose-600') }}">
                        {{ number_format($pctAll, 1) }}%
                    </p>
                </div>
            </div>

            {{-- Chart --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-1 h-5 bg-blue-500 rounded-full"></div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Grafik SPTPD & Realisasi Bayar</h3>
                </div>
                <div class="relative h-72">
                    <canvas id="wpChart"></canvas>
                </div>
            </div>

            {{-- Monthly Table --}}
            @foreach($tableData as $yr => $rows)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                        <div class="w-1 h-4 rounded-full {{ $loop->first ? 'bg-blue-500' : ($loop->iteration == 2 ? 'bg-amber-400' : 'bg-emerald-500') }}"></div>
                        <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">Rincian Bulanan — {{ $yr }}</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                <tr>
                                    <th class="px-6 py-3">Bulan</th>
                                    <th class="px-6 py-3">Tgl Lapor</th>
                                    <th class="px-6 py-3 text-right">SPTPD (Ketetapan)</th>
                                    <th class="px-6 py-3 text-right">Realisasi Bayar</th>
                                    <th class="px-6 py-3 text-right">Tunggakan</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($rows as $row)
                                    @php
                                        $hasSptpd = $row['total_ketetapan'] > 0;
                                        $lunas = $hasSptpd && $row['total_tunggakan'] <= 0;
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-3 font-black text-slate-800 text-xs">{{ $row['month_name'] }}</td>
                                        <td class="px-6 py-3 text-xs text-slate-500 font-mono">{{ $row['tgl_lapor'] }}</td>
                                        <td class="px-6 py-3 text-right font-bold text-xs {{ $hasSptpd ? 'text-blue-600' : 'text-slate-300' }}">
                                            {{ $hasSptpd ? number_format($row['total_ketetapan'], 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-right font-bold text-xs {{ $row['total_bayar'] > 0 ? 'text-emerald-600' : 'text-slate-300' }}">
                                            {{ $row['total_bayar'] > 0 ? number_format($row['total_bayar'], 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-right font-black text-xs {{ $row['total_tunggakan'] > 0 ? 'text-rose-600' : 'text-slate-300' }}">
                                            {{ $row['total_tunggakan'] > 0 ? number_format($row['total_tunggakan'], 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            @if(!$hasSptpd)
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-slate-100 text-slate-400 border border-slate-200">Belum Lapor</span>
                                            @elseif($lunas)
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-100 text-emerald-700 border border-emerald-200">Lunas</span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-100 text-rose-700 border border-rose-200">Tunggakan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- Total row --}}
                                @php
                                    $sumSptpd = collect($rows)->sum('total_ketetapan');
                                    $sumBayar = collect($rows)->sum('total_bayar');
                                    $sumTunggakan = collect($rows)->sum('total_tunggakan');
                                @endphp
                                <tr class="bg-slate-50 font-black">
                                    <td class="px-6 py-3 text-xs text-slate-700 uppercase tracking-widest" colspan="2">Total</td>
                                    <td class="px-6 py-3 text-right text-xs text-blue-700">{{ number_format($sumSptpd, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-right text-xs text-emerald-700">{{ number_format($sumBayar, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-right text-xs text-rose-700">{{ number_format($sumTunggakan, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

        </div>
    </form>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const chartData = @json(['labels' => $chartLabels, 'datasets' => $chartDatasets]);
        let chartInstance = null;

        function renderChart(data) {
            const canvas = document.getElementById('wpChart');
            if (chartInstance) { chartInstance.destroy(); }
            chartInstance = new Chart(canvas, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { font: { size: 10, weight: 'bold' }, boxWidth: 20, padding: 14 }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${new Intl.NumberFormat('id-ID').format(ctx.parsed.y)}`
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                font: { size: 9 },
                                callback: v => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v)
                            }
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderChart(chartData);

            // Year dropdown (di header)
            const yearBtn  = document.getElementById('yearDropdownBtn');
            const yearMenu = document.getElementById('yearDropdownMenu');
            yearBtn.addEventListener('click', () => yearMenu.classList.toggle('hidden'));
            document.addEventListener('click', e => {
                if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                    yearMenu.classList.add('hidden');
                }
                if (!document.getElementById('multiYearDropdownWrapper').contains(e.target)) {
                    document.getElementById('multiYearDropdownMenu').classList.add('hidden');
                }
            });
            document.querySelectorAll('.year-opt').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('yearValue').value = this.dataset.year;
                    document.getElementById('yearLabel').textContent = this.dataset.year;
                    // update highlight
                    document.querySelectorAll('.year-opt').forEach(b => b.classList.remove('font-black','text-blue-600','bg-blue-50/50'));
                    this.classList.add('font-black','text-blue-600','bg-blue-50/50');
                    yearMenu.classList.add('hidden');
                    document.getElementById('filterForm').submit();
                });
            });

            // Multi year dropdown
            const myBtn  = document.getElementById('multiYearDropdownBtn');
            const myMenu = document.getElementById('multiYearDropdownMenu');
            myBtn.addEventListener('click', () => myMenu.classList.toggle('hidden'));
            document.querySelectorAll('.multi-year-opt').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('multiYearValue').value = this.dataset.val;
                    document.getElementById('multiYearLabel').textContent = this.dataset.label;
                    myMenu.classList.add('hidden');
                    document.getElementById('filterForm').submit();
                });
            });
        });
    })();
    </script>
    @endpush
</x-layouts.admin>
