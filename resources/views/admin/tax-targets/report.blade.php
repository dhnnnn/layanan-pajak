<x-layouts.admin title="Laporan Target APBD" header="Laporan Target APBD">
    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.tax-targets.manage', array_filter(['year' => request('year')])) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Kelola Target
            </a>
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
                <div class="flex-1">
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

                        <div id="yearDropdownMenu" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            const searchInput = document.getElementById('search');

            // Debounced search
            let searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => form.submit(), 700);
            });

            // Year dropdown logic
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

    @php
        $totalTarget = $totals['target'];
        $totalRealization = $totals['realization'];
        $totalMoreLess = $totals['more_less'];
    @endphp

    <div class="space-y-6">
        {{-- Realization Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
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
                                <td colspan="15" class="px-6 py-16 text-center text-slate-500 bg-white">
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
    </div>
</x-layouts.admin>
