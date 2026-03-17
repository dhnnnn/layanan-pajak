<x-layouts.admin title="Daftar Target Pajak" header="Pengelolaan Target APBD">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Target APBD
        </a>
    </x-slot:headerActions>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <form method="GET" action="{{ route('admin.tax-targets.index') }}" class="p-6" id="filterForm">
            <div class="flex flex-col md:flex-row gap-4">
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
                            class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-50 text-slate-700 focus:bg-white focus:ring-2 focus:ring-blue-500/20 text-sm"
                        >
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Year Filter -->
                <div class="w-full md:w-48">
                    <label for="year" class="block text-sm font-medium text-slate-700 mb-2">Filter Tahun</label>
                    <select 
                        id="year" 
                        name="year"
                        class="w-full px-4 py-2 rounded-lg bg-slate-50 text-slate-700 focus:bg-white focus:ring-2 focus:ring-blue-500/20 text-sm"
                    >
                        <option value="">Semua Tahun</option>
                        @foreach($availableYears as $availableYear)
                            <option value="{{ $availableYear }}" {{ request('year') == $availableYear ? 'selected' : '' }}>
                                {{ $availableYear }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    @if(request()->hasAny(['search', 'year']))
                        <a 
                            href="{{ route('admin.tax-targets.index') }}"
                            class="px-6 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <script>
        // Auto-submit form on input change with debounce for search
        let searchTimeout;
        const form = document.getElementById('filterForm');
        const searchInput = document.getElementById('search');
        const yearSelect = document.getElementById('year');

        // Debounced search - wait 500ms after user stops typing
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                form.submit();
            }, 500);
        });

        // Instant submit on year change
        yearSelect.addEventListener('change', function() {
            form.submit();
        });
    </script>

    <!-- Active Filters Info -->
    @if(request()->hasAny(['search', 'year']))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-medium text-blue-900">Filter aktif:</span>
                    @if(request('search'))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-white border border-blue-300 rounded-full text-sm text-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Pencarian: "{{ request('search') }}"
                        </span>
                    @endif
                    @if(request('year'))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-white border border-blue-300 rounded-full text-sm text-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Tahun: {{ request('year') }}
                        </span>
                    @endif
                    <span class="text-sm text-blue-700">
                        ({{ $taxTargets->total() }} hasil ditemukan)
                    </span>
                </div>
                <a href="{{ route('admin.tax-targets.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Hapus semua filter
                </a>
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
                            <td class="px-3 py-3 text-right space-x-2">
                                <a href="{{ route('admin.tax-targets.edit', $target) }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">Edit</a>
                                <form action="{{ route('admin.tax-targets.destroy', $target) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition-colors">Hapus</button>
                                </form>
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
                                        <p class="text-sm mb-4">Coba ubah kata kunci pencarian atau filter yang Anda gunakan</p>
                                        <a href="{{ route('admin.tax-targets.index') }}" class="text-blue-600 hover:underline font-medium">Reset filter</a>
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
