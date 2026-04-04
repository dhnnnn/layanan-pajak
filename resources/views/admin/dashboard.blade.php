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
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 text-[10px] text-slate-400 italic">
                * Angka Tribulan bersifat kumulatif (Tribulan 2 = T1 + T2)
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
    </script>
</x-layouts.admin>
