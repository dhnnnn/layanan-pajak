<x-layouts.admin title="Dashboard Admin" header="Dashboard Realisasi Pajak">
    <x-slot:headerActions>
        <form action="{{ route('admin.dashboard') }}" method="GET" class="flex items-center gap-2">
            <label for="year" class="text-sm font-medium text-slate-600">Tahun:</label>
            <select name="year" id="year" onchange="this.form.submit()" class="no-search text-sm rounded-lg bg-slate-50 text-slate-700 py-1.5 px-3 focus:bg-white focus:ring-2 focus:ring-blue-500/20 block">
                @forelse($availableYears as $year)
                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                @empty
                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                @endforelse
            </select>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        @php
            $totalTarget = $totals['target'];
            $totalRealization = $totals['realization'];
            $avgPercentage = $totals['percentage'];
            $totalMoreLess = $totals['more_less'];
        @endphp

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Target (APBD)</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-blue-500">
                <p class="text-blue-600 text-xs font-bold uppercase tracking-wider mb-1">Total Realisasi</p>
                <p class="text-2xl font-bold text-blue-700">Rp {{ number_format($totalRealization, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Lebih/(Kurang)</p>
                <p class="text-2xl font-bold {{ $totalMoreLess >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    Rp {{ number_format($totalMoreLess, 0, ',', '.') }}
                </p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <p class="text-2xl font-bold text-emerald-600">{{ number_format($avgPercentage, 2, ',', '.') }}%</p>
            </div>
        </div>

        {{-- Realization Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Realisasi Penerimaan Pajak Daerah Per-Tribulan ({{ $selectedYear }})
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-white text-slate-700 font-bold uppercase border-b-2 border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-3 py-5 border border-slate-200 text-left min-w-[220px] sticky left-0 bg-white z-20">Nama Pajak</th>
                            <th rowspan="2" class="px-3 py-5 border border-slate-200 text-right min-w-[140px]">Target Total</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-200 text-center bg-slate-50">Tribulan 1</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-200 text-center bg-white">Tribulan 2</th>
                             <th colspan="3" class="px-3 py-3 border border-slate-200 text-center bg-slate-50">Tribulan 3</th>
                            <th colspan="3" class="px-3 py-3 border border-slate-200 text-center bg-white">Tribulan 4</th>
                            <th rowspan="2" class="px-3 py-5 border border-slate-200 text-right min-w-[140px]">Lebih/(Kurang)</th>
                        </tr>
                        <tr>
                            @for($i = 1; $i <= 4; $i++)
                                <th class="px-3 py-3 border border-slate-200 text-right min-w-[110px] {{ $i % 2 == 1 ? 'bg-slate-50' : 'bg-white' }}">Target</th>
                                <th class="px-3 py-3 border border-slate-200 text-right min-w-[110px] {{ $i % 2 == 1 ? 'bg-slate-50' : 'bg-white' }}">Realisasi</th>
                                <th class="px-3 py-3 border border-slate-200 text-center min-w-[50px] {{ $i % 2 == 1 ? 'bg-slate-50' : 'bg-white' }}">%</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dashboard as $item)
                            @php
                                $isParent = $item['is_parent'];
                                $hasParent = $item['tax_type_parent_id'] !== null;
                            @endphp
                            <tr class="{{ $isParent ? 'bg-slate-50 font-bold' : 'hover:bg-slate-50' }} transition-colors">
                                <td class="px-4 py-3 border border-slate-200 sticky left-0 {{ $isParent ? 'bg-slate-50' : 'bg-white' }} z-10">
                                    <div class="{{ $hasParent ? 'pl-5 italic text-slate-600' : 'text-slate-900 text-[13px] font-bold' }} whitespace-nowrap">
                                        {{ $hasParent ? '- ' : '' }}{{ $item['tax_type_name'] }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 border border-slate-200 text-right {{ $isParent ? 'text-slate-900 text-[13px]' : 'text-slate-600' }}">
                                    {{ number_format($item['target_total'], 0, ',', '.') }}
                                </td>
                                
                                {{-- Quarters --}}
                                @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                    <td class="px-3 py-3 border border-slate-200 text-right text-slate-600">
                                        {{ number_format($item['targets'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border border-slate-200 text-right {{ $isParent ? 'text-blue-700' : 'text-blue-600 font-medium' }}">
                                        {{ number_format($item['realizations'][$q], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 border border-slate-200 text-center font-bold {{ $item['percentages'][$q] >= 100 ? 'text-emerald-600' : ($item['percentages'][$q] >= 50 ? 'text-blue-600' : 'text-rose-600') }}">
                                        {{ number_format($item['percentages'][$q], 0, ',', '.') }}
                                    </td>
                                @endforeach

                                <td class="px-4 py-3 border border-slate-200 text-right font-bold {{ $item['more_less'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} bg-slate-50/30 text-[13px]">
                                    {{ number_format($item['more_less'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="px-6 py-16 text-center text-slate-500 bg-white">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-base font-medium">Belum ada data realisasi untuk tahun {{ $selectedYear }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($dashboard->isNotEmpty())
                    <tfoot class="bg-slate-100 text-slate-900 font-bold border-t-2 border-slate-300">
                        <tr>
                            <td class="px-4 py-4 border border-slate-300 sticky left-0 bg-slate-100 z-10 text-[13px]">JUMLAH TOTAL</td>
                            <td class="px-4 py-4 border border-slate-300 text-right text-[13px]">{{ number_format($totalTarget, 0, ',', '.') }}</td>
                            
                            @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                <td class="px-3 py-4 border border-slate-300 text-right">
                                    {{ number_format($totals['quarters'][$q]['target'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border border-slate-300 text-right">
                                    {{ number_format($totals['quarters'][$q]['realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 border border-slate-300 text-center">
                                    {{ number_format($totals['quarters'][$q]['percentage'], 0, ',', '.') }}
                                </td>
                            @endforeach

                            <td class="px-4 py-4 border border-slate-300 text-right bg-slate-200 text-[13px]">
                                {{ number_format($totalMoreLess, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-[10px] text-slate-500 italic">
                * Angka Target dan Realisasi pada kolom Tribulan bersifat kumulatif (contoh: Tribulan 2 adalah akumulasi T1 + T2).
            </div>
        </div>
    </div>
</x-layouts.admin>
