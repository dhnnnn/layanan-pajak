<x-layouts.admin title="Laporan Perbandingan Target UPT" header="Laporan Perbandingan Target UPT">
    <x-slot:headerActions>
        <a href="{{ route('admin.upt-comparisons.report.export', array_filter(['year' => request('year')])) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors shadow-sm border border-slate-200">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export Excel
        </a>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Filter Section --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.upt-comparisons.report') }}" class="p-6" id="filterForm">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-slate-700 mb-2">Cari Jenis Pajak</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama pajak..."
                            class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-50 text-slate-700 focus:bg-white focus:ring-2 focus:ring-blue-500/20 text-sm">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="w-full md:w-52">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Filter Tahun</label>
                    <div class="relative" id="yearDropdownWrapper">
                        <button type="button" id="yearDropdownBtn"
                            class="w-full flex items-center justify-between px-4 py-2 rounded-lg bg-slate-50 text-slate-700 text-sm border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <span id="yearDropdownLabel">{{ request('year', $year) }}</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <input type="hidden" name="year" id="yearValue" value="{{ request('year', $year) }}">

                        <div id="yearDropdownMenu" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
                            <ul class="max-h-48 overflow-y-auto py-1">
                                @foreach($availableYears as $availableYear)
                                    <li>
                                        <button type="button" data-value="{{ $availableYear }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ request('year', $year) == $availableYear ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                            {{ $availableYear }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                @if(request()->hasAny(['search', 'year']))
                    <a href="{{ route('admin.upt-comparisons.report') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(request()->hasAny(['search', 'year']))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-medium text-blue-900">Filter aktif:</span>
                    @if(request('search'))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-white border border-blue-300 rounded-full text-sm text-blue-700">
                            Pencarian: "{{ request('search') }}"
                        </span>
                    @endif
                    @if(request('year'))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-white border border-blue-300 rounded-full text-sm text-blue-700">
                            Tahun {{ request('year') }}
                        </span>
                    @endif
                    <span class="text-sm text-blue-700">({{ $taxTypes->total() }} data)</span>
                </div>
                <a href="{{ route('admin.upt-comparisons.report') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Hapus semua filter</a>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-3 py-3 sticky left-0 bg-slate-50 z-10 border-r border-slate-200" rowspan="2">No.</th>
                        <th class="px-4 py-3 sticky left-9 bg-slate-50 z-10 border-r border-slate-200 min-w-56" rowspan="2">Jenis Pajak</th>
                        <th class="px-4 py-3 text-right border-r border-slate-200 min-w-40" rowspan="2">Target APBD {{ $year }}</th>
                        @foreach($upts as $upt)
                            <th class="px-4 py-3 text-center border-r border-slate-200 min-w-64" colspan="2">{{ $upt->name }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-right border-r border-slate-200 min-w-40" rowspan="2">Total Target Semua UPT</th>
                        <th class="px-4 py-3 text-right border-r border-slate-200 min-w-40" rowspan="2">Total Realisasi Semua UPT</th>
                        <th class="px-4 py-3 text-center border-r border-slate-200" rowspan="2">% Target</th>
                        <th class="px-4 py-3 text-center border-r border-slate-200" rowspan="2">% Selisih</th>
                        <th class="px-4 py-3 text-right min-w-40" rowspan="2">Selisih (Rp.)</th>
                    </tr>
                    <tr class="border-t border-slate-200">
                        @foreach($upts as $upt)
                            <th class="px-3 py-2 text-right text-[10px] border-r border-slate-200/50 font-medium text-slate-500 min-w-32">Target</th>
                            <th class="px-3 py-2 text-right text-[10px] border-r border-slate-200/50 font-medium text-slate-500 min-w-32">Realisasi</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxTypes as $parentTaxType)
                        @php
                            $hasChildren = $parentTaxType->children->isNotEmpty();
                            
                            // Calculate parent totals by summing children (or use own if no children)
                            $parentTargetAmount = 0;
                            $parentTotalRealization = 0;
                            $parentUptAmounts = [];
                            $parentUptTargetAmounts = [];

                            foreach ($upts as $upt) {
                                $parentUptAmounts[$upt->id] = 0;
                                $parentUptTargetAmounts[$upt->id] = 0;
                            }

                            if ($hasChildren) {
                                foreach ($parentTaxType->children as $child) {
                                    $parentTargetAmount += (float) ($targets[$child->id] ?? 0);
                                    foreach ($upts as $upt) {
                                        $childRealization = (float) ($uptRealizationTotals[$upt->id][$child->id] ?? 0);
                                        $parentUptAmounts[$upt->id] += $childRealization;
                                        $parentTotalRealization += $childRealization;
                                        $parentUptTargetAmounts[$upt->id] += (float) ($uptTargets[$upt->id][$child->id] ?? 0);
                                    }
                                }
                            } else {
                                $parentTargetAmount = (float) ($targets[$parentTaxType->id] ?? 0);
                                foreach ($upts as $upt) {
                                    $realization = (float) ($uptRealizationTotals[$upt->id][$parentTaxType->id] ?? 0);
                                    $parentUptAmounts[$upt->id] = $realization;
                                    $parentTotalRealization += $realization;
                                    $parentUptTargetAmounts[$upt->id] = (float) ($uptTargets[$upt->id][$parentTaxType->id] ?? 0);
                                }
                            }

                            $parentPercentTarget = $parentTargetAmount > 0 ? ($parentTotalRealization / $parentTargetAmount) * 100 : 0;
                            $parentSelisih = $parentTargetAmount - $parentTotalRealization;
                            $parentPercentSelisih = $parentTargetAmount > 0 ? ($parentSelisih / $parentTargetAmount) * 100 : 0;
                        @endphp

                        {{-- Parent Row --}}
                        <tr class="{{ $hasChildren ? 'bg-white font-bold border-t-2 border-slate-200' : 'hover:bg-slate-50 transition-colors' }}">
                            <td class="px-3 py-3 text-slate-500 sticky left-0 bg-white border-r border-slate-200 z-10 text-center">
                                {{ $taxTypes->firstItem() + $loop->index }}
                            </td>
                            <td class="px-4 py-3 sticky left-9 bg-white border-r border-slate-200 z-10">
                                <div class="text-slate-800 whitespace-normal leading-snug">{{ $parentTaxType->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900 text-xs border-r border-slate-200">
                                Rp {{ number_format($parentTargetAmount, 0, ',', '.') }}
                            </td>
                            @foreach($upts as $upt)
                                <td class="px-3 py-3 text-right text-[11px] border-r border-slate-100">
                                    Rp {{ number_format($parentUptTargetAmounts[$upt->id], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-right text-[11px] border-r border-slate-200">
                                    Rp {{ number_format($parentUptAmounts[$upt->id], 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right text-xs font-bold border-r border-slate-200 bg-slate-50/30">
                                Rp {{ number_format(array_sum($parentUptTargetAmounts), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-bold border-r border-slate-200 bg-slate-50/30">
                                Rp {{ number_format($parentTotalRealization, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-200 font-bold text-slate-900">
                                {{ number_format($parentPercentTarget, 1) }}%
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-200 font-bold text-slate-900">
                                {{ number_format($parentPercentSelisih, 1) }}%
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-bold text-slate-900">
                                Rp {{ number_format($parentSelisih, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Children Rows --}}
                        @if($hasChildren)
                            @foreach($parentTaxType->children as $child)
                                @php
                                    $childTargetAmount = (float) ($targets[$child->id] ?? 0);
                                    $childTotalRealization = 0;
                                    $childTotalUptTarget = 0;
                                    $childUptAmounts = [];
                                    $childUptTargetAmounts = [];

                                    foreach ($upts as $upt) {
                                        $amount = (float) ($uptRealizationTotals[$upt->id][$child->id] ?? 0);
                                        $uptTarg = (float) ($uptTargets[$upt->id][$child->id] ?? 0);
                                        $childUptAmounts[$upt->id] = $amount;
                                        $childTotalRealization += $amount;
                                        $childUptTargetAmounts[$upt->id] = $uptTarg;
                                        $childTotalUptTarget += $uptTarg;
                                    }

                                    $childPercentTarget = $childTargetAmount > 0 ? ($childTotalRealization / $childTargetAmount) * 100 : 0;
                                    $childSelisih = $childTargetAmount - $childTotalRealization;
                                    $childPercentSelisih = $childTargetAmount > 0 ? ($childSelisih / $childTargetAmount) * 100 : 0;
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors italic text-xs">
                                    <td class="px-3 py-2 text-slate-400 sticky left-0 bg-white border-r border-slate-200 z-10"></td>
                                    <td class="px-4 py-2 sticky left-9 bg-white border-r border-slate-200 z-10">
                                        <div class="text-slate-600 pl-4">- {{ $child->name }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-right text-slate-500 border-r border-slate-200">
                                        Rp {{ number_format($childTargetAmount, 0, ',', '.') }}
                                    </td>
                                    @foreach($upts as $upt)
                                        <td class="px-3 py-2 text-right text-slate-400 border-r border-slate-100 italic">
                                            Rp {{ number_format($childUptTargetAmounts[$upt->id], 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2 text-right text-slate-400 border-r border-slate-200 italic">
                                            Rp {{ number_format($childUptAmounts[$upt->id], 0, ',', '.') }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-2 text-right border-r border-slate-200 font-medium text-slate-500 italic">
                                        Rp {{ number_format($childTotalUptTarget, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 text-right border-r border-slate-200 font-medium text-slate-500 italic">
                                        Rp {{ number_format($childTotalRealization, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 text-center border-r border-slate-200">
                                        <span class="text-[10px] text-slate-500">{{ number_format($childPercentTarget, 1) }}%</span>
                                    </td>
                                    <td class="px-4 py-2 text-center border-r border-slate-200">
                                        <span class="text-[10px] text-slate-500">{{ number_format($childPercentSelisih, 1) }}%</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-slate-400">
                                        Rp {{ number_format($childSelisih, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ 3 + ($upts->count() * 2) + 4 }}" class="px-6 py-10 text-center text-slate-500">
                                <p>{{ request('search') ? 'Tidak ada hasil yang ditemukan.' : 'Belum ada data.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($taxTypes->isNotEmpty())
                    @php
                        $grandSelisih = $grandTotalTarget - $grandTotalAllUpt;
                        $grandPercentTarget = $grandTotalTarget > 0 ? ($grandTotalAllUpt / $grandTotalTarget) * 100 : 0;
                        $grandPercentSelisih = $grandTotalTarget > 0 ? ($grandSelisih / $grandTotalTarget) * 100 : 0;
                    @endphp
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-slate-800 text-xs border-t-2 border-slate-300">
                            <td class="px-3 py-3 sticky left-0 bg-slate-100 z-10 border-r border-slate-200"></td>
                            <td class="px-4 py-3 sticky left-9 bg-slate-100 z-10 border-r border-slate-200">TOTAL</td>
                            <td class="px-4 py-3 text-right border-r border-slate-200">Rp {{ number_format($grandTotalTarget, 0, ',', '.') }}</td>
                            @foreach($upts as $upt)
                                <td class="px-3 py-3 text-right border-r border-slate-200">
                                    Rp {{ number_format($grandTotalUptTarget[$upt->id], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-right border-r border-slate-200">
                                    Rp {{ number_format($grandTotalUpt[$upt->id], 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right border-r border-slate-200">Rp {{ number_format($grandTotalAllUptTarget, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right border-r border-slate-200">Rp {{ number_format($grandTotalAllUpt, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center border-r border-slate-200">
                                {{ number_format($grandPercentTarget, 1) }}%
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-200">
                                {{ number_format($grandPercentSelisih, 1) }}%
                            </td>
                            <td class="px-4 py-3 text-right">
                                Rp {{ number_format($grandSelisih, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if($taxTypes->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $taxTypes->links() }}
            </div>
        @endif
    </div>
</div>

    <script>
        // Debounced search
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => document.getElementById('filterForm').submit(), 500);
        });

        // Year dropdown
        const btn = document.getElementById('yearDropdownBtn');
        const menu = document.getElementById('yearDropdownMenu');
        const yearValue = document.getElementById('yearValue');
        const yearLabel = document.getElementById('yearDropdownLabel');

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
                yearValue.value = this.dataset.value;
                yearLabel.textContent = this.textContent.trim();
                menu.classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</x-layouts.admin>
