<x-layouts.field-officer title="Pemantau Wajib Pajak" header="Pemantau Wajib Pajak">
    <x-slot:headerActions>
        <div class="flex items-center gap-3">
            <a id="exportExcelBtn"
                href="{{ route('field-officer.monitoring.tax-payers.export-excel', array_filter(['year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo, 'district' => $selectedDistrict, 'ayat' => $selectedAyat, 'status_filter' => $statusFilter])) }}"
                class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                Export Excel
            </a>
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="yearDropdownLabel">{{ $selectedYear }}</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-xl py-1">
                    @foreach($availableYears as $y)
                        <button type="button" data-year="{{ $y }}" class="year-option w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 {{ $selectedYear == $y ? 'font-black text-blue-600 bg-blue-50/50' : 'text-slate-700 font-bold' }}">
                            Tahun {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </x-slot:headerActions>

    {{-- Embed the monitoring table content --}}
    <div class="space-y-6">
        <form id="filterForm" action="{{ route('field-officer.monitoring.tax-payers') }}" method="GET">
            <input type="hidden" name="year" id="yearValue" value="{{ $selectedYear }}">
            <input type="hidden" name="district" id="districtValue" value="{{ $selectedDistrict }}">
            <input type="hidden" name="status_filter" id="statusFilterValue" value="{{ $statusFilter }}">
            <input type="hidden" name="ayat" id="ayatValue" value="{{ $selectedAyat }}">
            <input type="hidden" name="sort_by" id="sortByValue" value="{{ request('sort_by', '') }}">
            <input type="hidden" name="sort_dir" id="sortDirValue" value="{{ request('sort_dir', 'desc') }}">

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-4 border-b border-slate-100 bg-white space-y-3">
                    <div id="wpCountContainer">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest leading-none">Daftar Wajib Pajak Penanganan</h4>
                        <p id="totalWpText" class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ $taxPayers->total() }} WP Terdeteksi</p>
                    </div>

                    <div class="flex gap-2 w-full">
                        <div class="flex-1 min-w-0">
                            <x-searchable-select target-input-id="districtValue" :value="$selectedDistrict"
                                placeholder="Semua Kecamatan"
                                :options="$districts->map(fn($d) => ['id' => $d->simpadu_code, 'name' => $d->name])->toArray()" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <x-searchable-select target-input-id="ayatValue" :value="$selectedAyat"
                                placeholder="Semua Jenis Pajak"
                                :options="$taxTypes->map(fn($t) => ['id' => $t->simpadu_code, 'name' => $t->name])->toArray()" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1 min-w-0">
                            <input type="hidden" name="month_from" id="monthFromValue" value="{{ $selectedMonthFrom }}">
                            <x-searchable-select target-input-id="monthFromValue" :value="$selectedMonthFrom" placeholder="Dari"
                                :options="collect(range(1, 12))->map(fn($m) => ['id' => $m, 'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F')])->toArray()" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <input type="hidden" name="month_to" id="monthToValue" value="{{ $selectedMonthTo }}">
                            <x-searchable-select target-input-id="monthToValue" :value="$selectedMonthTo" placeholder="Sampai"
                                :options="collect(range(1, 12))->map(fn($m) => ['id' => $m, 'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F')])->toArray()" />
                        </div>
                        <div class="flex-1 min-w-0 relative" x-data='{
                            open: false, value: "{{ $statusFilter }}",
                            options: [{id:"1",name:"WP Aktif"},{id:"0",name:"Non Aktif"},{id:"all",name:"Semua"}],
                            get label() { return this.options.find(o => o.id === this.value)?.name ?? "WP Aktif"; },
                            select(opt) { this.value = opt.id; this.open = false; document.getElementById("statusFilterValue").value = opt.id; document.getElementById("statusFilterValue").dispatchEvent(new Event("change",{bubbles:true})); }
                        }'>
                            <button type="button" @click="open = !open" @click.away="open = false"
                                class="w-full flex items-center justify-between px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 transition-all">
                                <span x-text="label" class="font-bold text-slate-900 truncate"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                class="absolute right-0 z-50 mt-2 w-36 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden" style="display:none;">
                                <div class="py-1">
                                    <template x-for="opt in options" :key="opt.id">
                                        <button type="button" @click="select(opt)" class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
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

                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                                placeholder="Cari Nama atau NPWPD..."
                                class="w-full pl-9 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            <div class="absolute left-3 top-2.5">
                                <svg id="searchIcon" class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <div id="searchSpinner" class="hidden absolute right-3 top-2.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <button type="button" id="resetBtn"
                            class="flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-500 font-bold py-2 px-3 rounded-xl transition-all text-xs uppercase tracking-widest border border-slate-200 active:scale-95 group shrink-0">
                            <svg class="w-3.5 h-3.5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>

                <div id="tableContainer" class="relative min-h-[400px]">
                    @include('admin.monitoring._table')
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let searchTimeout;

        function refreshTable(url = null) {
            const form = $('#filterForm');
            const container = $('#tableContainer');
            if (!url) url = form.attr('action') + '?' + form.serialize();
            container.addClass('opacity-50 pointer-events-none');
            $('#searchSpinner').removeClass('hidden');

            // Update export button URL
            const exportBase = "{{ route('field-officer.monitoring.tax-payers.export-excel') }}";
            $('#exportExcelBtn').attr('href', exportBase + '?' + form.serialize());

            $.ajax({
                url: url, type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    container.html(response);
                    const newTotal = container.find('[data-total-wp]').data('total-wp');
                    if (newTotal !== undefined) $('#totalWpText').text(newTotal + ' WP Terdeteksi');
                    window.history.pushState({}, '', url);
                },
                error: function() { alert('Gagal memuat data.'); },
                complete: function() { container.removeClass('opacity-50 pointer-events-none'); $('#searchSpinner').addClass('hidden'); }
            });
        }

        $(document).ready(function() {
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => refreshTable(), 500);
            });
            $(document).on('change', '#districtValue, #monthFromValue, #monthToValue, #yearValue, #statusFilterValue, #ayatValue', function() {
                refreshTable();
            });
            $('#resetBtn').on('click', function() {
                window.location.href = "{{ route('field-officer.monitoring.tax-payers') }}";
            });
            $('#yearDropdownBtn').on('click', function(e) {
                e.stopPropagation();
                $('#yearDropdownMenu').toggleClass('hidden');
            });
            $(document).on('click', '.year-option', function() {
                const year = $(this).data('year');
                $('#yearValue').val(year);
                $('#yearDropdownLabel').text(year);
                $('#yearDropdownMenu').addClass('hidden');
                refreshTable();
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#yearDropdownWrapper').length) $('#yearDropdownMenu').addClass('hidden');
            });
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                if (url) { refreshTable(url); window.scrollTo({ top: 0, behavior: 'smooth' }); }
            });
            $(document).on('click', '[data-sort-col]', function() {
                const col = $(this).data('sort-col');
                const currentCol = $('#sortByValue').val();
                const currentDir = $('#sortDirValue').val();
                const newDir = (currentCol === col && currentDir === 'desc') ? 'asc' : 'desc';
                $('#sortByValue').val(col);
                $('#sortDirValue').val(newDir);
                refreshTable();
            });
            $('#filterForm').on('submit', function(e) { e.preventDefault(); refreshTable(); });        });
    </script>
</x-layouts.field-officer>
