<x-layouts.admin :title="'Detail Realisasi - ' . $taxType->name" header="Detail Realisasi">
    <div class="p-6">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex text-slate-500 text-xs mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700 transition-colors">Dashboard</a></li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 mx-1" fill="currentColor" viewBox="0 0 20 20"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
                                <a href="{{ route('admin.tax-targets.manage') }}" class="hover:text-slate-700 transition-colors">Kelola Target</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center text-slate-900 font-medium">
                                <svg class="w-3 h-3 mx-1" fill="currentColor" viewBox="0 0 20 20"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
                                Detail Realisasi
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    {{ $taxType->name }}
                    <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-500 text-xs font-mono rounded">
                        {{ $taxType->simpadu_code }}
                    </span>
                </h1>
                <p class="text-slate-500 text-sm mt-1">
                    Tahun Anggaran {{ $year }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.tax-targets.manage', ['year' => $year]) }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-medium">
                    Kembali
                </a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Target Tahunan</div>
                <div class="text-2xl font-bold text-slate-900 font-mono">
                    Rp {{ number_format($summary['target_total'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="mt-2 text-[10px] text-slate-400">
                    Berdasarkan penetapan APBD {{ $year }}
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Realisasi (WP)</div>
                <div class="text-2xl font-bold text-slate-900 font-mono">
                    Rp {{ number_format($summary['total_realization'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="mt-2 flex items-center gap-1.5">
                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 font-bold text-[10px] rounded border border-slate-200">
                        {{ number_format($summary['achievement_percentage'] ?? 0, 1) }}%
                    </span>
                    <span class="text-[10px] text-slate-400 italic">dari target</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Lebih / (Kurang)</div>
                <div class="text-2xl font-bold {{ ($summary['more_less'] ?? 0) >= 0 ? 'text-slate-900' : 'text-slate-400' }} font-mono">
                    Rp {{ number_format($summary['more_less'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="mt-2 text-[10px] text-slate-400">
                    Sisa target yang harus dicapai
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Rincian Pembayaran Per Wajib Pajak</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">Menampilkan {{ number_format($payers->total(), 0, ',', '.') }} data hasil sinkronisasi</p>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center gap-3">
                    <form action="{{ route('admin.tax-targets.show', $taxType->id) }}" method="GET" class="flex items-center gap-2" id="filterForm">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="district" id="selectedDistrictInput" value="{{ $selectedDistrict }}">
                        
                        {{-- Unified Filter Toolbar --}}
                        <div class="flex items-center bg-white border border-slate-200 rounded-lg focus-within:ring-1 focus-within:ring-slate-400 focus-within:border-slate-400 transition-all divide-x divide-slate-100 shadow-sm">
                            {{-- Name/NPWPD Search --}}
                            <div class="flex items-center">
                                <div class="pl-2.5 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" name="search" id="liveSearchInput" value="{{ $search }}" 
                                       placeholder="Cari Nama / NPWPD..." 
                                       class="pl-2 pr-3 py-1.5 text-xs border-none focus:ring-0 w-40 md:w-56 bg-transparent outline-none">
                            </div>

                            {{-- Searchable District Trigger --}}
                            <div class="relative" id="districtDropdown">
                                <button type="button" id="districtTrigger"
                                        class="flex items-center justify-between w-44 px-3 py-1.5 text-xs bg-transparent border-none hover:bg-slate-50 transition-colors text-left group">
                                    <span id="districtLabel" class="truncate text-slate-600 font-medium">
                                        {{ $selectedDistrict ? ($districts->firstWhere('simpadu_code', $selectedDistrict)->name ?? 'Semua Kecamatan') : 'Kecamatan...' }}
                                    </span>
                                    <svg class="w-3 h-3 ml-2 text-slate-300 group-hover:text-slate-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                {{-- Dropdown Menu --}}
                                <div id="districtMenu" class="hidden absolute right-0 mt-1 w-64 bg-white border border-slate-200 rounded-xl shadow-xl z-50 overflow-hidden transform transition-all">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50">
                                        <input type="text" id="districtMenuSearch" placeholder="Filter Kecamatan..." 
                                               class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg focus:ring-1 focus:ring-slate-400 focus:border-slate-400 bg-white outline-none">
                                    </div>
                                    <div class="max-h-60 overflow-y-auto pt-1 pb-1 custom-scrollbar">
                                        <div class="district-option px-4 py-2.5 text-xs hover:bg-slate-50 cursor-pointer transition-colors text-slate-500 hover:text-slate-900 border-l-2 border-transparent hover:border-slate-400 flex items-center gap-2" 
                                             data-value="" data-label="Kecamatan...">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                            Semua Kecamatan
                                        </div>
                                        @foreach($districts as $district)
                                            <div class="district-option px-4 py-2.5 text-xs hover:bg-slate-50 cursor-pointer transition-colors text-slate-700 hover:text-slate-900 border-l-2 border-transparent hover:border-slate-800 flex items-center justify-between group" 
                                                 data-value="{{ $district->simpadu_code }}" data-label="{{ $district->name }}">
                                                <span class="font-medium">{{ $district->name }}</span>
                                                <span class="text-[9px] text-slate-300 group-hover:text-slate-400 font-mono">{{ $district->simpadu_code }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($search || $selectedDistrict)
                            <a href="{{ route('admin.tax-targets.show', $taxType->id) }}?year={{ $year }}" 
                               class="flex items-center px-3 py-1.5 bg-white border border-slate-200 text-red-500 hover:bg-red-50 hover:border-red-200 rounded-lg transition-colors group shadow-sm" title="Reset Filter">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-[10px] font-bold uppercase ml-1 hidden lg:block">Reset</span>
                            </a>
                        @endif
                    </form>

                    {{-- Unified jQuery Filter Management --}}
                    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                    <script>
                        $(document).ready(function() {
                            const $form = $('#filterForm');
                            const $trigger = $('#districtTrigger');
                            const $menu = $('#districtMenu');
                            const $searchInMenu = $('#districtMenuSearch');
                            const $options = $('.district-option');
                            const $hiddenDistrict = $('#selectedDistrictInput');
                            const $liveSearch = $('#liveSearchInput');
                            
                            let searchTimer;

                            // 1. Live Search Logic
                            $liveSearch.on('input', function() {
                                clearTimeout(searchTimer);
                                searchTimer = setTimeout(() => {
                                    $form.submit();
                                }, 600);
                            });

                            // Maintain cursor position
                            const val = $liveSearch.val();
                            $liveSearch.focus().val('').val(val);

                            // 2. Custom Dropdown Logic
                            $trigger.on('click', function(e) {
                                e.stopPropagation();
                                $menu.toggleClass('hidden');
                                if (!$menu.hasClass('hidden')) {
                                    $searchInMenu.focus();
                                }
                            });

                            $(document).on('click', function(e) {
                                if (!$('#districtDropdown').has(e.target).length) {
                                    $menu.addClass('hidden');
                                }
                            });

                            $searchInMenu.on('input', function() {
                                const term = $(this).val().toLowerCase();
                                $options.each(function() {
                                    const text = $(this).data('label').toLowerCase();
                                    const code = $(this).data('value').toString().toLowerCase();
                                    $(this).toggle(text.includes(term) || code.includes(term));
                                });
                            });

                            $options.on('click', function() {
                                const val = $(this).data('value');
                                $hiddenDistrict.val(val);
                                $menu.addClass('hidden');
                                $form.submit();
                            });
                        });
                    </script>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                    <thead class="bg-white text-slate-400 font-bold uppercase text-[10px] border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">NPWPD</th>
                            <th class="px-6 py-4 text-left">Nama Wajib Pajak</th>
                            <th class="px-6 py-4 text-right">Total Realisasi</th>
                            <th class="px-6 py-4 text-right text-slate-300">Kontribusi (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payers as $payer)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-500">
                                    {{ $payer->npwpd }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-slate-800 uppercase text-xs">{{ $payer->nm_wp }}</div>
                                    @if($selectedDistrict == '' && $payer->kd_kecamatan)
                                        <div class="text-[9px] text-slate-400 flex items-center gap-1 mt-0.5">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            Code: {{ $payer->kd_kecamatan }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono text-slate-900 font-bold">
                                    {{ number_format($payer->total_realization, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    @php 
                                        $share = $summary['total_realization'] > 0 
                                            ? ($payer->total_realization / $summary['total_realization']) * 100 
                                            : 0;
                                    @endphp
                                    <span class="text-[10px] text-slate-400">{{ number_format($share, 1) }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-10 h-10 mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Tidak ada data pembayaran Wajib Pajak yang sesuai dengan filter.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payers->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50">
                    {{ $payers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
