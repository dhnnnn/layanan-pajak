<x-layouts.admin title="Daftar Target Pajak" header="Pengelolaan Target APBD">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.report') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors shadow-sm border border-slate-200">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div class="space-y-6">
        <!-- Filter Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
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
                            <span id="yearDropdownLabel">{{ request('year') ?: $year }}</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <input type="hidden" name="year" id="yearValue" value="{{ request('year', $year) }}">

                        <div id="yearDropdownMenu" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
                            <ul id="yearList" class="max-h-48 overflow-y-auto py-1">
                                @forelse($availableYears as $availableYear)
                                    <li>
                                        <button type="button" data-value="{{ $availableYear }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ request('year', $year) == $availableYear ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
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
                    <a href="{{ route('admin.tax-targets.index') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors shrink-0">
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

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Jenis Pajak</th>
                        <th class="px-6 py-4 text-right">Target APBD ({{ $year }})</th>
                        <th class="px-6 py-4 text-right">Tribulan 1</th>
                        <th class="px-6 py-4 text-right">Tribulan 2</th>
                        <th class="px-6 py-4 text-right">Tribulan 3</th>
                        <th class="px-6 py-4 text-right border-r border-slate-100">Tribulan 4</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs md:text-sm">
                    @forelse($taxTypes as $taxType)
                        {{-- Parent row --}}
                        @php $parentTarget = $targets[$taxType->id] ?? null; @endphp
                        <tr class="bg-slate-50/60 hover:bg-slate-100/60 transition-colors font-semibold">
                            <td class="px-6 py-3.5 text-slate-900 border-r border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span>{{ $taxType->name }}</span>
                                    @if($taxType->children->isNotEmpty())
                                        <span class="px-1.5 py-0.5 bg-slate-200 text-slate-600 text-[10px] rounded uppercase tracking-wider">Total</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-right font-bold text-blue-700 bg-blue-50/20">
                                @if($parentTarget)
                                    Rp {{ number_format($parentTarget->target_amount, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right text-slate-600">
                                @if($parentTarget)
                                    Rp {{ number_format($parentTarget->q1_target ?? ($parentTarget->target_amount * 0.25), 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right text-slate-600">
                                @if($parentTarget)
                                    Rp {{ number_format($parentTarget->q2_target ?? ($parentTarget->target_amount * 0.50), 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right text-slate-600">
                                @if($parentTarget)
                                    Rp {{ number_format($parentTarget->q3_target ?? ($parentTarget->target_amount * 0.75), 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right text-slate-600 border-r border-slate-100">
                                @if($parentTarget)
                                    Rp {{ number_format($parentTarget->q4_target ?? $parentTarget->target_amount, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($taxType->children->isNotEmpty())
                                        <span class="text-[10px] text-slate-400 italic px-2">Akumulasi subbab</span>
                                    @elseif($parentTarget)
                                        <a href="{{ route('admin.tax-targets.edit', $parentTarget) }}" class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Target">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.tax-targets.destroy', $parentTarget) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Target">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.tax-targets.create', ['tax_type_id' => $taxType->id, 'year' => $year]) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-bold uppercase rounded-lg hover:bg-blue-100 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Buat Target
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Children rows --}}
                        @foreach($taxType->children as $child)
                            @php $childTarget = $targets[$child->id] ?? null; @endphp
                            <tr class="hover:bg-slate-50 transition-colors border-l-2 border-purple-200">
                                <td class="px-8 py-3 text-slate-700 border-r border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-300 text-sm">↳</span>
                                        <span>{{ $child->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right text-blue-600 font-medium">
                                    @if($childTarget)
                                        Rp {{ number_format($childTarget->target_amount, 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-slate-500 text-[13px]">
                                    @if($childTarget)
                                        Rp {{ number_format($childTarget->q1_target ?? ($childTarget->target_amount * 0.25), 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-slate-500 text-[13px]">
                                    @if($childTarget)
                                        Rp {{ number_format($childTarget->q2_target ?? ($childTarget->target_amount * 0.50), 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-slate-500 text-[13px]">
                                    @if($childTarget)
                                        Rp {{ number_format($childTarget->q3_target ?? ($childTarget->target_amount * 0.75), 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-slate-500 text-[13px] border-r border-slate-100">
                                    @if($childTarget)
                                        Rp {{ number_format($childTarget->q4_target ?? $childTarget->target_amount, 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($childTarget)
                                            <a href="{{ route('admin.tax-targets.edit', $childTarget) }}" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors" title="Edit subbab">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.tax-targets.destroy', $childTarget) }}" method="POST" onsubmit="return confirm('Hapus target subbab \'{{ $child->name }}\'?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-red-400 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Hapus subbab">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.tax-targets.create', ['tax_type_id' => $child->id, 'year' => $year]) }}"
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-white border border-blue-100 text-blue-500 text-[10px] font-bold uppercase rounded-lg hover:bg-blue-50 transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Target
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-10 h-10 mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm font-medium">Data jenis pajak tidak ditemukan untuk tahun {{ $year }}</p>
                                    <p class="text-xs">Coba cari dengan kata kunci lain atau ubah filter tahun.</p>
                                </div>
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
    </div>
</x-layouts.admin>
