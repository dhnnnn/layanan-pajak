<x-layouts.admin title="Daftar Target Pajak" header="Pengelolaan Target APBD">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.export', array_filter(['year' => request('year')])) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors shadow-sm border border-slate-200">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export Excel
        </a>
        <a href="{{ route('admin.tax-targets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Target APBD
        </a>
    </x-slot:headerActions>

    <!-- Filter Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
        <form method="GET" action="{{ route('admin.tax-targets.index') }}" class="p-6" id="filterForm">
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
                            class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-50 text-slate-700 focus:bg-white focus:ring-2 focus:ring-blue-500/20 text-sm">
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
                            <span id="yearDropdownLabel">{{ request('year') ?: 'Semua Tahun' }}</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <input type="hidden" name="year" id="yearValue" value="{{ request('year') }}">

                        <div id="yearDropdownMenu" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
                            <ul id="yearList" class="max-h-48 overflow-y-auto py-1">
                                <li>
                                    <button type="button" data-value="" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ !request('year') ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                        Semua Tahun
                                    </button>
                                </li>
                                @foreach($availableYears as $availableYear)
                                    <li>
                                        <button type="button" data-value="{{ $availableYear }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ request('year') == $availableYear ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                            {{ $availableYear }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                @if(request()->hasAny(['search', 'year']))
                    <a href="{{ route('admin.tax-targets.index') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <script>
        const form = document.getElementById('filterForm');

        // Debounced search
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => form.submit(), 500);
        });

        // Searchable year dropdown
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

        yearSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#yearList li').forEach(function (li) {
                const text = li.textContent.trim().toLowerCase();
                li.style.display = text.includes(q) ? '' : 'none';
            });
        });

        yearSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#yearList li').forEach(function (li) {
                const text = li.textContent.trim().toLowerCase();
                li.style.display = text.includes(q) ? '' : 'none';
            });
        });

        document.querySelectorAll('.year-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                yearValue.value = this.dataset.value;
                yearLabel.textContent = this.textContent.trim();
                menu.classList.add('hidden');
                form.submit();
            });
        });
    </script>

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
                    <span class="text-sm text-blue-700">({{ $taxTargets->total() }} data)</span>
                </div>
                <a href="{{ route('admin.tax-targets.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Hapus semua filter</a>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Tahun</th>
                        <th class="px-4 py-3">Jenis Pajak</th>
                        <th class="px-4 py-3 text-right">Target APBD</th>
                        <th class="px-4 py-3 text-right">Q1</th>
                        <th class="px-4 py-3 text-right">Q2</th>
                        <th class="px-4 py-3 text-right">Q3</th>
                        <th class="px-4 py-3 text-right">Q4</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxTargets as $target)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-3 py-3 font-bold text-slate-900">
                                {{ $target->year }}
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium text-slate-800">{{ $target->taxType->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $target->taxType->code }}</div>
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-blue-600">
                                Rp {{ number_format($target->target_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="text-slate-600 text-xs">Rp {{ number_format($target->q1_target ?? ($target->target_amount * 0.25), 0, ',', '.') }}</div>
                                <div class="text-[10px] text-slate-400">{{ number_format($target->getQ1Percentage(), 1) }}%</div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="text-slate-600 text-xs">Rp {{ number_format($target->q2_target ?? ($target->target_amount * 0.50), 0, ',', '.') }}</div>
                                <div class="text-[10px] text-slate-400">{{ number_format($target->getQ2Percentage(), 1) }}%</div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="text-slate-600 text-xs">Rp {{ number_format($target->q3_target ?? ($target->target_amount * 0.75), 0, ',', '.') }}</div>
                                <div class="text-[10px] text-slate-400">{{ number_format($target->getQ3Percentage(), 1) }}%</div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="text-slate-600 text-xs">Rp {{ number_format($target->q4_target ?? $target->target_amount, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-slate-400">{{ number_format($target->getQ4Percentage(), 1) }}%</div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.tax-targets.edit', $target) }}" class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.tax-targets.destroy', $target) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    @if(request()->hasAny(['search', 'year']))
                                        <p class="font-medium text-slate-700 mb-1">Tidak ada hasil yang ditemukan</p>
                                        <a href="{{ route('admin.tax-targets.index') }}" class="mt-2 text-blue-600 hover:underline font-medium">Reset filter</a>
                                    @else
                                        <p>Belum ada target APBD yang terdaftar.</p>
                                        <a href="{{ route('admin.tax-targets.create') }}" class="mt-4 text-blue-600 hover:underline font-medium">Tambah target pertama</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($taxTargets->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $taxTargets->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
