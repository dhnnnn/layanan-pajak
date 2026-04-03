<x-layouts.field-officer title="Pencapaian Target" header="Pencapaian Target Wilayah Tugas">
    <x-slot:headerActions>
        <form action="{{ route('field-officer.monitoring.target-achievement') }}" method="GET" id="filterForm">
            <input type="hidden" name="sort_by" id="sortByValue" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" id="sortDirValue" value="{{ $sortDir }}">
            <input type="hidden" name="search" id="searchHidden" value="{{ $search ?? '' }}">
            <input type="hidden" name="status_filter" id="statusFilterHidden" value="{{ $statusFilter }}">
            <input type="hidden" name="tax_type_id" id="taxTypeIdHidden" value="{{ $taxTypeId }}">
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span id="yearDropdownLabel">{{ $year }}</span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @foreach($availableYears as $y)
                        <button type="button" data-value="{{ $y }}"
                            class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $year == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Progress Banner --}}
        <div class="bg-slate-900 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-10">
                <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/>
                </svg>
            </div>
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-4">
                    <div>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-1">Wilayah Tugas Anda</p>
                        <p class="text-white text-lg font-black uppercase tracking-widest">
                            {{ $assignedDistricts->pluck('name')->implode(', ') }}
                        </p>
                    </div>
                    <span class="text-white text-4xl font-black">{{ number_format($summary['persentase'], 1) }}%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-4 ring-1 ring-white/10 p-1">
                    <div class="h-full rounded-full transition-all duration-1000 ease-out {{ $summary['persentase'] >= 100 ? 'bg-emerald-400' : ($summary['persentase'] >= 50 ? 'bg-amber-400' : 'bg-rose-500') }}"
                        style="width: {{ min($summary['persentase'], 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Ketetapan</p>
                        <p class="text-xl font-black text-slate-900">Rp {{ number_format($summary['total_ketetapan'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Pembayaran</p>
                        <p class="text-xl font-black text-emerald-600">Rp {{ number_format($summary['total_bayar'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Tunggakan</p>
                        <p class="text-xl font-black text-rose-600">Rp {{ number_format($summary['total_tunggakan'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- WP Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div class="px-6 py-5 border-b border-slate-100">
                <div class="flex flex-col gap-4">
                    {{-- Title Area --}}
                    <div>
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Daftar Wajib Pajak</h4>
                        @if(isset($wpData) && method_exists($wpData, 'total'))
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ $wpData->total() }} WP TERDETEKSI</p>
                        @endif
                    </div>

                    {{-- Filters Area --}}
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="grid grid-cols-2 md:flex gap-3 flex-1">
                            {{-- Tax Type Filter --}}
                            <div class="relative" x-data='{
                                open: false,
                                search: "",
                                value: "{{ $taxTypeId ?? "" }}",
                                options: [
                                    {id:"", name:"Semua Jenis Pajak"},
                                    @foreach($taxTypes as $tt)
                                        {id:"{{ $tt->id }}", name:"{{ $tt->name }}"},
                                    @endforeach
                                ],
                                get filteredOptions() {
                                    if (!this.search) return this.options;
                                    return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                get label() { 
                                    let found = this.options.find(o => o.id == this.value);
                                    return found ? found.name : "Semua Jenis Pajak";
                                },
                                select(opt) {
                                    this.value = opt.id; this.open = false;
                                    document.getElementById("taxTypeIdHidden").value = opt.id;
                                    document.getElementById("filterForm").submit();
                                }
                            }'>
                                <button type="button" @click="open = !open" @click.away="open = false; search = &#39;&#39;"
                                    class="w-full flex items-center justify-between px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 transition-all">
                                    <span x-text="label" class="font-bold text-slate-900 truncate"></span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    class="absolute left-0 right-0 md:left-auto md:w-64 z-50 mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden" style="display:none;">
                                    <div class="p-2 border-b border-slate-50">
                                        <div class="relative">
                                            <input type="text" x-model="search" placeholder="Cari..." 
                                                class="w-full pl-8 pr-4 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                            <svg class="absolute left-2.5 top-2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="py-1 max-h-64 overflow-y-auto">
                                        <template x-for="opt in filteredOptions" :key="opt.id">
                                            <button type="button" @click="select(opt)"
                                                class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
                                                :class="value == opt.id ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                                                <div class="flex items-center justify-between">
                                                    <span x-text="opt.name"></span>
                                                    <svg x-show="value == opt.id" class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-xs text-slate-400 italic text-center">
                                            Tidak ada hasil.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Status Filter --}}
                            <div class="relative" x-data='{
                                open: false,
                                value: "{{ $statusFilter }}",
                                options: [{id:"1",name:"WP Aktif"},{id:"0",name:"Non Aktif"},{id:"all",name:"Semua"}],
                                get label() { return this.options.find(o => o.id === this.value)?.name ?? "WP Aktif"; },
                                select(opt) {
                                    this.value = opt.id; this.open = false;
                                    document.getElementById("statusFilterHidden").value = opt.id;
                                    document.getElementById("filterForm").submit();
                                }
                            }'>
                                <button type="button" @click="open = !open" @click.away="open = false"
                                    class="w-full flex items-center justify-between px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 transition-all">
                                    <span x-text="label" class="font-bold text-slate-900 truncate"></span>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 ml-1 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    class="absolute left-0 right-0 md:left-auto md:w-36 z-50 mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden" style="display:none;">
                                    <div class="py-1">
                                        <template x-for="opt in options" :key="opt.id">
                                            <button type="button" @click="select(opt)"
                                                class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
                                                :class="value === opt.id ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                                                <div class="flex items-center justify-between">
                                                    <span x-text="opt.name"></span>
                                                    <svg x-show="value === opt.id" class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Search --}}
                        <div class="relative w-full md:w-64">
                            <input type="text" id="searchInput" value="{{ $search ?? '' }}"
                                placeholder="Cari Nama WP atau NPWPD..."
                                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-blue-500/20 transition-all outline-none">
                            <svg class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-widest">
                        <tr>
                            <th class="px-6 py-4 cursor-pointer hover:text-blue-600" data-sort="name">
                                <div class="flex items-center gap-1">Wajib Pajak / NPWPD
                                    <x-sort-icon column="name" :active-col="$sortBy" :active-dir="$sortDir" />
                                </div>
                            </th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right cursor-pointer hover:text-blue-600" data-sort="sptpd">
                                <div class="flex items-center justify-end gap-1">Jml SPTPD
                                    <x-sort-icon column="sptpd" :active-col="$sortBy" :active-dir="$sortDir" />
                                </div>
                            </th>
                            <th class="px-6 py-4 text-right cursor-pointer hover:text-blue-600" data-sort="bayar">
                                <div class="flex items-center justify-end gap-1">Jml Bayar
                                    <x-sort-icon column="bayar" :active-col="$sortBy" :active-dir="$sortDir" />
                                </div>
                            </th>
                            <th class="px-6 py-4 text-right cursor-pointer hover:text-blue-600" data-sort="tunggakan">
                                <div class="flex items-center justify-end gap-1">Tunggakan
                                    <x-sort-icon column="tunggakan" :active-col="$sortBy" :active-dir="$sortDir" />
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($wpData as $wp)
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer"
                                onclick="toggleAccordion('{{ $wp['npwpd'] }}-{{ $wp['nop'] }}')"
                                data-npwpd="{{ $wp['npwpd'] }}" data-nop="{{ $wp['nop'] }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 accordion-chevron-{{ $wp['npwpd'] }}-{{ $wp['nop'] }}"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        <div>
                                            <div class="font-bold text-slate-900 leading-tight uppercase">{{ $wp['nm_wp'] }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $wp['npwpd'] }} / {{ $wp['tax_type_name'] ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($wp['status_code'] == '1')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-100 text-emerald-700 border border-emerald-200">AKTIF</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-100 text-rose-700 border border-rose-200">NON AKTIF</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-600">{{ number_format($wp['total_sptpd'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600">{{ number_format($wp['total_bayar'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if($wp['tunggakan'] > 0)
                                        <span class="font-black text-rose-600">{{ number_format($wp['tunggakan'], 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                            {{-- Accordion --}}
                            <tr id="accordion-{{ $wp['npwpd'] }}-{{ $wp['nop'] }}" class="hidden">
                                <td colspan="5" class="px-0 py-0 border-b border-slate-100">
                                    <div class="bg-gradient-to-r from-slate-50 to-white px-6 py-4">
                                        <div class="flex items-center gap-2 mb-4">
                                            <div class="w-1 h-4 bg-rose-500 rounded-full"></div>
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Rincian Tunggakan per Bulan — {{ $year }}</span>
                                        </div>
                                        <div class="accordion-data-{{ $wp['npwpd'] }}-{{ $wp['nop'] }}">
                                            <div class="flex gap-2">
                                                @for($i = 0; $i < 4; $i++)
                                                    <div class="flex-1 h-16 bg-slate-100 rounded-xl animate-pulse"></div>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($wpData) && method_exists($wpData, 'hasPages') && $wpData->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $wpData->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

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

        // Search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchHidden').value = this.value;
                document.getElementById('filterForm').submit();
            }, 500);
        });

        // Sort
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', function() {
                const col = this.dataset.sort;
                const sortBy = document.getElementById('sortByValue');
                const sortDir = document.getElementById('sortDirValue');
                sortDir.value = (sortBy.value === col && sortDir.value === 'desc') ? 'asc' : 'desc';
                sortBy.value = col;
                document.getElementById('filterForm').submit();
            });
        });

        // Accordion
        const accordionLoaded = {};
        const tunggakanUrl = "{{ route('field-officer.monitoring.wp-tunggakan') }}";

        window.toggleAccordion = function(key) {
            const row = document.getElementById('accordion-' + key);
            const chevron = document.querySelector('.accordion-chevron-' + key);
            const isHidden = row.classList.contains('hidden');
            row.classList.toggle('hidden');
            if (chevron) chevron.classList.toggle('rotate-180');

            if (isHidden && !accordionLoaded[key]) {
                accordionLoaded[key] = true;
                const tr = document.querySelector(`tr[onclick="toggleAccordion('${key}')"]`);
                const npwpd = tr.dataset.npwpd;
                const nop = tr.dataset.nop;
                const year = document.getElementById('yearValue').value;
                const container = document.querySelector('.accordion-data-' + key);

                fetch(`${tunggakanUrl}?npwpd=${encodeURIComponent(npwpd)}&nop=${encodeURIComponent(nop)}&year=${year}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) {
                            container.innerHTML = '<div class="flex items-center gap-3 py-2"><svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span class="text-xs font-bold text-emerald-600">Lunas — tidak ada tunggakan.</span></div>';
                            return;
                        }
                        const fmt = n => new Intl.NumberFormat('id-ID').format(Math.round(n));
                        const totalK = data.reduce((s,r) => s+r.total_ketetapan, 0);
                        const totalB = data.reduce((s,r) => s+r.total_bayar, 0);
                        const totalT = data.reduce((s,r) => s+r.total_tunggakan, 0);
                        const pct = totalK > 0 ? Math.min((totalB/totalK)*100, 100) : 0;

                        let html = `<div class="mb-4 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <div class="flex flex-wrap gap-4 mb-3">
                                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Ketetapan</p><p class="text-sm font-black text-slate-700">${fmt(totalK)}</p></div>
                                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Terbayar</p><p class="text-sm font-black text-emerald-600">${fmt(totalB)}</p></div>
                                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Sisa Tunggakan</p><p class="text-sm font-black text-rose-600">${fmt(totalT)}</p></div>
                                <div class="ml-auto text-right"><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Progress</p><p class="text-sm font-black ${pct>=100?'text-emerald-600':pct>=50?'text-amber-500':'text-rose-600'}">${pct.toFixed(1)}%</p></div>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2"><div class="h-2 rounded-full ${pct>=100?'bg-emerald-400':pct>=50?'bg-amber-400':'bg-rose-500'}" style="width:${pct}%"></div></div>
                        </div>`;

                        html += '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">';
                        data.forEach(r => {
                            const lunas = !r.total_tunggakan && r.total_bayar > 0;
                            const partial = r.total_tunggakan > 0 && r.total_bayar > 0;
                            const cardClass = lunas ? 'bg-emerald-50 border-emerald-200' : partial ? 'bg-amber-50 border-amber-200' : 'bg-rose-50 border-rose-200';
                            const badgeClass = lunas ? 'bg-emerald-100 text-emerald-700' : partial ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700';
                            const badgeText = lunas ? 'Lunas' : partial ? 'Sebagian' : 'Belum Bayar';
                            const mp = r.total_ketetapan > 0 ? Math.min((r.total_bayar/r.total_ketetapan)*100,100) : 0;
                            html += `<div class="rounded-xl border p-3 ${cardClass}">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-black text-slate-700">${r.bulan}</span>
                                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full ${badgeClass}">${badgeText}</span>
                                </div>
                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Ketetapan</p>
                                <p class="text-xs font-black text-slate-700 mb-1">${fmt(r.total_ketetapan)}</p>
                                ${r.total_tunggakan > 0 ? `<p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Tunggakan</p><p class="text-xs font-black text-rose-600">${fmt(r.total_tunggakan)}</p>` : `<p class="text-[9px] text-emerald-500 font-bold uppercase tracking-widest">Terbayar</p><p class="text-xs font-black text-emerald-600">${fmt(r.total_bayar)}</p>`}
                                <div class="mt-2 w-full bg-white/60 rounded-full h-1"><div class="h-1 rounded-full ${lunas?'bg-emerald-400':partial?'bg-amber-400':'bg-rose-400'}" style="width:${mp}%"></div></div>
                            </div>`;
                        });
                        html += '</div>';
                        container.innerHTML = html;
                    })
                    .catch(() => { container.innerHTML = '<p class="text-xs text-rose-400 italic py-2">Gagal memuat data.</p>'; });
            }
        };
    </script>
</x-layouts.field-officer>
