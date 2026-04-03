<x-layouts.admin title="Dashboard Admin" header="Dashboard Realisasi Pajak">
    <x-slot:headerActions>
        <form action="{{ route('admin.dashboard') }}" method="GET" id="filterForm" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Tahun:</span>
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

    <div class="space-y-6">
        @php
            $totalTarget      = $totals['target'];
            $totalRealization = $totals['realization'];
            $avgPercentage    = $totals['percentage'];
            $totalMoreLess    = $totals['more_less'];
        @endphp

        {{-- Info Bar --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Rekapitulasi</p>
                    <p class="text-sm font-bold text-slate-800">Pajak Daerah Kabupaten Pasuruan — Tahun {{ $selectedYear }}</p>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span> Data APBD
            </div>
        </div>

        {{-- Statistik --}}
        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest pl-1 flex items-center gap-2">
            <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
            Statistik
        </h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Total Target (APBD)</p>
                <p class="text-xl font-bold text-slate-900">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Total Realisasi</p>
                <p class="text-xl font-bold text-blue-600">Rp {{ number_format($totalRealization, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Lebih/(Kurang)</p>
                <p class="text-xl font-bold {{ $totalMoreLess >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    Rp {{ number_format($totalMoreLess, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <p class="text-xl font-black {{ $avgPercentage >= 100 ? 'text-emerald-600' : ($avgPercentage >= 50 ? 'text-amber-500' : 'text-rose-600') }}">
                    {{ number_format($avgPercentage, 2, ',', '.') }}%
                </p>
            </div>
        </div>

        {{-- Realization Table --}}
        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest pl-1 flex items-center gap-2">
            <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
            Realisasi Per-Tribulan
        </h3>
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
                            <th rowspan="2" class="px-3 py-4 border border-slate-300 text-right min-w-[140px]">Target Total</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-300 text-center bg-slate-100">Tribulan 1</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-300 text-center bg-slate-200/50">Tribulan 2</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-300 text-center bg-slate-100">Tribulan 3</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-300 text-center bg-slate-200/50">Tribulan 4</th>
                        </tr>
                        <tr>
                            @for($i = 1; $i <= 4; $i++)
                                <th class="px-3 py-3 border border-slate-300 text-right min-w-[110px] bg-slate-50 font-bold">Target</th>
                                <th class="px-3 py-3 border border-slate-300 text-right min-w-[110px] bg-slate-50 font-bold">Realisasi</th>
                                <th class="px-3 py-3 border border-slate-300 text-center min-w-[40px] bg-slate-50 font-bold">%</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dashboard as $item)
                            @php
                                $isParent = $item['is_parent'];
                                $hasParent = $item['tax_type_parent_id'] !== null;
                            @endphp
                            <tr class="{{ $isParent ? 'bg-slate-100 font-extrabold' : 'hover:bg-slate-50' }} transition-colors group">
                                <td class="px-4 py-3 border-x border-slate-200 sticky left-0 {{ $isParent ? 'bg-slate-100' : 'bg-white group-hover:bg-slate-50' }} z-10 transition-colors shadow-[1px_0_0_rgba(0,0,0,0.05)]">
                                    <div class="{{ $hasParent ? 'pl-6 text-slate-600 font-medium' : 'text-slate-900 font-black' }} whitespace-nowrap">
                                        {{ $hasParent ? '- ' : '' }}{{ $item['tax_type_name'] }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 border-r border-slate-200 text-right {{ $isParent ? 'text-slate-900 bg-slate-100' : 'text-slate-700' }} font-bold">
                                    {{ number_format($item['target_total'], 0, ',', '.') }}
                                </td>
                                @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                    <td class="px-3 py-3 border-r border-slate-200 text-right text-slate-600 {{ $isParent ? 'bg-slate-100' : '' }}">
                                        {{ number_format($item['targets'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-right {{ $isParent ? 'text-slate-900 bg-slate-100' : 'text-slate-900 font-bold' }}">
                                        {{ number_format($item['realizations'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border-r border-slate-200 text-center font-black {{ $isParent ? 'bg-slate-100' : '' }} text-slate-800">
                                        {{ number_format($item['percentages'][$q], 0, ',', '.') }}%
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-6 py-16 text-center text-slate-500 bg-white">
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
                            @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-slate-700 font-bold">
                                    {{ number_format($totals['quarters'][$q]['target'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-right text-slate-900 underline">
                                    {{ number_format($totals['quarters'][$q]['realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border-r border-slate-300 text-center">
                                    {{ number_format($totals['quarters'][$q]['percentage'], 0, ',', '.') }}%
                                </td>
                            @endforeach
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

    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
    </style>

    <script>
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
    </script>
</x-layouts.admin>
