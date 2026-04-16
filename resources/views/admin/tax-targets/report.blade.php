<x-layouts.admin title="Laporan Target APBD" header="Laporan Target APBD">
    <x-slot:headerActions>
        <div class="flex items-center gap-3">
            @can('manage additional-targets')
            <a href="{{ route('admin.upt-additional-targets.create', ['year' => $selectedYear]) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Target Tambahan APBD
            </a>
            @endcan
            <a href="{{ route('admin.tax-targets.export', array_filter(['year' => request('year')])) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors shadow-sm border border-slate-200">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Excel
            </a>
        </div>
    </x-slot:headerActions>

    <div class="space-y-6">
        <!-- Filter Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.tax-targets.report') }}" class="p-6" id="filterForm">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <!-- Search Input -->
                <div class="flex-1 w-full md:w-52">
                    <label for="search" class="block text-sm font-medium text-slate-700 mb-2">Cari Jenis Pajak</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama atau kode pajak..."
                            class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-50 text-slate-700 focus:bg-white focus:ring-2 focus:ring-blue-500/20 text-sm border-0">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Year Dropdown -->
                <div class="w-full md:w-52">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Filter Tahun</label>
                    <div class="relative" id="yearDropdownWrapper">
                        <button type="button" id="yearDropdownBtn"
                            class="w-full flex items-center justify-between px-4 py-2 rounded-lg bg-slate-50 text-slate-700 text-sm border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <span id="yearDropdownLabel">{{ request('year') ?: $selectedYear }}</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <input type="hidden" name="year" id="yearValue" value="{{ request('year', $selectedYear) }}">

                        <div id="yearDropdownMenu" class="hidden absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
                            <ul id="yearList" class="max-h-48 overflow-y-auto py-1">
                                @forelse($availableYears as $availableYear)
                                    <li>
                                        <button type="button" data-value="{{ $availableYear }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ request('year', $selectedYear) == $availableYear ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                            {{ $availableYear }}
                                        </button>
                                    </li>
                                @empty
                                    <li>
                                        <button type="button" data-value="{{ date('Y') }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 font-semibold text-blue-600">
                                            {{ date('Y') }}
                                        </button>
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                @if(request()->filled('search') || (request()->filled('year') && request('year') != date('Y')))
                    <a href="{{ route('admin.tax-targets.report') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    @php
        $totalTarget = $totals['target'];
        $totalRealization = $totals['realization'];
        $avgPercentage = $totals['percentage'];
        $totalMoreLess = $totals['more_less'];
    @endphp

    <div class="space-y-6">
        {{-- Summary Cards --}}

        {{-- Realization Table --}}
        <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Realisasi Penerimaan Pajak Daerah Per-Tribulan ({{ $selectedYear }})
                </h3>
            </div>
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
                                <th class="px-3 py-3 border border-slate-300 text-center min-w-[50px] bg-emerald-50 text-emerald-700 font-bold">% Baru</th>
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
                                        $tambQ   = ($item['targets'][$q] ?? 0) - ($item['targets_base'][$q] ?? 0);
                                        $pctBase = $item['percentages_base'][$q] ?? 0;
                                        $pctNew  = $item['percentages'][$q] ?? 0;
                                        $hasAdd  = ($item['additional_target'] ?? 0) > 0;
                                    @endphp
                                    <td class="px-3 py-3 border-r border-slate-200 text-right text-slate-600 {{ $isParent ? 'bg-blue-50' : '' }}">
                                        {{ number_format($item['targets_base'][$q] ?? $item['targets'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-right {{ $isParent ? 'bg-blue-50' : '' }} {{ $tambQ > 0 ? 'text-amber-700 font-bold' : 'text-slate-300' }}">
                                        {{ $tambQ > 0 ? '+'.number_format($tambQ, 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-right {{ $isParent ? 'text-blue-900 bg-blue-50' : 'text-slate-900 font-bold' }}">
                                        {{ number_format($item['realizations'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-center font-black {{ $isParent ? 'bg-blue-50' : '' }}
                                        {{ $pctBase >= 100 ? 'text-emerald-600' : ($pctBase >= 50 ? 'text-amber-500' : 'text-slate-800') }}">
                                        {{ number_format($pctBase, 1, ',', '.') }}%
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-center font-black {{ $isParent ? 'bg-emerald-50/40' : 'bg-emerald-50/20' }}
                                        {{ !$hasAdd ? 'text-slate-300' : ($pctNew >= 100 ? 'text-emerald-600' : ($pctNew >= 50 ? 'text-amber-500' : 'text-rose-600')) }}">
                                        {{ $hasAdd ? number_format($pctNew, 1, ',', '.').'%' : '—' }}
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
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-amber-700 font-bold bg-amber-50/50">
                                    —
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-slate-900 underline">
                                    {{ number_format($totals['quarters'][$q]['realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-center">
                                    {{ number_format($totals['quarters'][$q]['percentage'], 0, ',', '.') }}%
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-center bg-emerald-50/50 text-emerald-700">
                                    —
                                </td>
                            @endforeach

                            <td class="px-4 py-4 border-r border-slate-300 text-right underline">
                                {{ number_format($totalMoreLess, 0, ',', '.') }}
                            </td>
                            @php
                                $totalPctAwal = $totals['percentage'] ?? 0;
                                $totalPctBaru = $totals['percentage_with_additional'] ?? $totalPctAwal;
                                $hasTotalTambahan = ($totals['additional_target'] ?? 0) > 0;
                            @endphp
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-[10px] text-slate-400 italic">
                * Angka Target dan Realisasi pada kolom Tribulan bersifat kumulatif (contoh: Tribulan 2 adalah akumulasi T1 + T2).
            </div>
        </div>
    </div>

    {{-- Section: Target Tambahan APBD --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl px-4 py-3 flex items-center gap-2 mt-4">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($additionalTargets->isNotEmpty() || auth()->user()->can('manage additional-targets'))
    @php
        // Buat lookup target awal per no_ayat dari dashboard data
        $baseTargetMap = collect($dashboard)->filter(fn($i) => !($i['is_parent'] ?? false))
            ->pluck('target_total', 'no_ayat');
    @endphp
    <div class="bg-white rounded-xl border border-amber-200 overflow-hidden mt-4">
        <div class="px-5 py-3 border-b border-amber-100 bg-amber-50 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-900">Target Tambahan APBD — {{ $selectedYear }}</p>
                <p class="text-xs text-amber-600 mt-0.5">Target di luar APBD awal, bersifat global untuk semua UPP.</p>
            </div>
            @if($additionalTargets->isNotEmpty())
                <p class="text-xs text-amber-700 font-semibold">
                    Total: +Rp {{ number_format($additionalTargets->sum('additional_target'), 0, ',', '.') }}
                </p>
            @endif
        </div>
        @if($additionalTargets->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Jenis Pajak</th>
                        <th class="px-4 py-2.5 text-right">Target Awal</th>
                        <th class="px-4 py-2.5 text-right">Target Tambahan</th>
                        <th class="px-4 py-2.5 text-center">% Kenaikan</th>
                        <th class="px-4 py-2.5 text-right">Total Target Baru</th>
                        <th class="px-4 py-2.5 text-left">Catatan</th>
                        <th class="px-4 py-2.5 text-left">Diperbarui Oleh</th>
                        @can('manage additional-targets')
                        <th class="px-4 py-2.5 text-center">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($additionalTargets as $at)
                        @php
                            $baseTarget = (float) ($baseTargetMap[$at->no_ayat] ?? 0);
                            $addTarget  = (float) $at->additional_target;
                            $pctNaik    = $baseTarget > 0 ? ($addTarget / $baseTarget) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-2.5 font-medium text-slate-800">
                                <span class="text-xs font-mono text-slate-400 mr-1">{{ $at->no_ayat }}</span>
                                {{ $ayatLabels[$at->no_ayat] ?? $at->no_ayat }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-500">
                                Rp {{ number_format($baseTarget, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono font-semibold text-amber-700">
                                +Rp {{ number_format($addTarget, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold
                                    {{ $pctNaik >= 20 ? 'bg-rose-100 text-rose-700' : ($pctNaik >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    ↑ {{ number_format($pctNaik, 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-blue-700">
                                Rp {{ number_format($baseTarget + $addTarget, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs max-w-xs truncate">{{ $at->notes ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-400 text-xs">{{ $at->creator->name ?? '—' }}</td>
                            @can('manage additional-targets')
                            <td class="px-4 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="{{ route('admin.upt-additional-targets.create', ['no_ayat' => $at->no_ayat, 'year' => $at->year]) }}"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                    <form method="POST" action="{{ route('admin.upt-additional-targets.destroy', $at) }}"
                                        onsubmit="return confirm('Hapus target tambahan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                    </form>
                                </div>
                            </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 border-t-2 border-slate-200 text-xs font-bold text-slate-700">
                    <tr>
                        <td class="px-4 py-2.5">Total</td>
                        <td class="px-4 py-2.5 text-right font-mono text-slate-600">
                            Rp {{ number_format($baseTargetMap->only($additionalTargets->pluck('no_ayat'))->sum(), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-amber-700">
                            +Rp {{ number_format($additionalTargets->sum('additional_target'), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @php
                                $totalBase = $baseTargetMap->only($additionalTargets->pluck('no_ayat'))->sum();
                                $totalAdd  = $additionalTargets->sum('additional_target');
                                $totalPct  = $totalBase > 0 ? ($totalAdd / $totalBase) * 100 : 0;
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold
                                {{ $totalPct >= 20 ? 'bg-rose-100 text-rose-700' : ($totalPct >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                ↑ {{ number_format($totalPct, 1, ',', '.') }}%
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-blue-700">
                            Rp {{ number_format($totalBase + $totalAdd, 0, ',', '.') }}
                        </td>
                        <td colspan="{{ auth()->user()->can('manage additional-targets') ? 3 : 2 }}"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="px-5 py-6 text-center text-sm text-slate-400">
            Belum ada target tambahan untuk tahun {{ $selectedYear }}.
        </div>
        @endif
    </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            const searchInput = document.getElementById('search');

            let searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => form.submit(), 700);
            });

            const btn = document.getElementById('yearDropdownBtn');
            const menu = document.getElementById('yearDropdownMenu');
            const yearValueInput = document.getElementById('yearValue');
            const yearLabel = document.getElementById('yearDropdownLabel');

            if (btn && menu) {
                btn.addEventListener('click', function () {
                    menu.classList.toggle('hidden');
                });

                document.addEventListener('click', function (e) {
                    if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });

                document.querySelectorAll('.year-option').forEach(function (opt) {
                    opt.addEventListener('click', function () {
                        yearValueInput.value = this.dataset.value;
                        yearLabel.textContent = this.textContent.trim();
                        menu.classList.add('hidden');
                        form.submit();
                    });
                });
            }
        });
    </script>
</x-layouts.admin>
