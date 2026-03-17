<x-layouts.admin title="Laporan Perbandingan Target UPT" header="Laporan Perbandingan Target UPT">
    <x-slot:headerActions>
        <a href="{{ route('admin.upt-comparisons.report.export', array_filter(['year' => request('year')])) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors shadow-sm border border-slate-200">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export Excel
        </a>
        <a href="{{ route('admin.upt-comparisons.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    {{-- Filter Section --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
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
                            placeholder="Cari berdasarkan nama atau kode pajak..."
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
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
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
                        <th class="px-4 py-3">No.</th>
                        <th class="px-4 py-3">Jenis Pajak</th>
                        <th class="px-4 py-3 text-right">Target {{ $year }}</th>
                        @foreach($upts as $upt)
                            <th class="px-4 py-3 text-right">{{ $upt->name }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-right bg-blue-50">Total {{ $upts->count() }} UPT</th>
                        <th class="px-4 py-3 text-center bg-green-50">% Target</th>
                        <th class="px-4 py-3 text-center bg-orange-50">% Selisih</th>
                        <th class="px-4 py-3 text-right bg-red-50">Selisih (Rp.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxTypes as $taxType)
                        @php
                            $targetAmount = (float) ($targets[$taxType->id] ?? 0);
                            $totalUpt = 0;
                            $uptAmounts = [];

                            foreach ($upts as $upt) {
                                $comparison = $upt->comparisons->where('tax_type_id', $taxType->id)->first();
                                $amount = (float) ($comparison?->target_amount ?? 0);
                                $uptAmounts[$upt->id] = $amount;
                                $totalUpt += $amount;
                            }

                            $percentTarget = $targetAmount > 0 ? ($totalUpt / $targetAmount) * 100 : 0;
                            $selisih = $targetAmount - $totalUpt;
                            $percentSelisih = $targetAmount > 0 ? ($selisih / $targetAmount) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-500">{{ $taxTypes->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $taxType->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $taxType->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-600 text-xs">
                                Rp {{ number_format($targetAmount, 0, ',', '.') }}
                            </td>
                            @foreach($upts as $upt)
                                <td class="px-4 py-3 text-right text-xs">
                                    Rp {{ number_format($uptAmounts[$upt->id], 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right text-xs font-bold bg-blue-50">
                                Rp {{ number_format($totalUpt, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center bg-green-50">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $percentTarget >= 100 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ number_format($percentTarget, 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center bg-orange-50">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ abs($percentSelisih) < 0.1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ number_format($percentSelisih, 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-bold {{ abs($selisih) < 1000 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                Rp {{ number_format($selisih, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + $upts->count() }}" class="px-6 py-10 text-center text-slate-500">
                                <p>{{ request('search') ? 'Tidak ada hasil yang ditemukan.' : 'Belum ada data.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($taxTypes->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $taxTypes->links() }}
            </div>
        @endif
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
