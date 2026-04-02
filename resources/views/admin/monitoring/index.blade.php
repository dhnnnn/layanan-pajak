<x-layouts.admin title="Pemantauan Wajib Pajak" header="Pemantau Wajib Pajak">
    <x-slot:headerActions>
        <div class="flex items-center gap-3">
            {{-- Year Dropdown --}}
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
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-xl py-1 transform transition-all">
                    @foreach($availableYears as $y)
                        <button type="button" data-year="{{ $y }}" class="year-option w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 {{ $selectedYear == $y ? 'font-black text-blue-600 bg-blue-50/50' : 'text-slate-700 font-bold' }}">
                            Tahun {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </x-slot:headerActions>

    <div class="space-y-6">
        <form id="filterForm" action="{{ route('admin.monitoring.index') }}" method="GET">
            <input type="hidden" name="year" id="yearValue" value="{{ $selectedYear }}">
            <input type="hidden" name="district" id="districtValue" value="{{ $selectedDistrict }}">

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                {{-- Table Header / Filter Bar --}}
                <div class="px-6 py-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white">
                    <div id="wpCountContainer">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest leading-none">Daftar Wajib Pajak Penanganan</h4>
                        <p id="totalWpText" class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">{{ $taxPayers->total() }} WP Terdeteksi</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- District Filter --}}
                        <div class="w-full md:w-auto">
                            <x-searchable-select 
                                target-input-id="districtValue"
                                :value="$selectedDistrict" 
                                placeholder="Semua Kecamatan"
                                :options="$districts->map(fn($d) => ['id' => $d->simpadu_code, 'name' => $d->name])->toArray()"
                            />
                        </div>

                        {{-- Month Range Filters --}}
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="month_from" id="monthFromValue" value="{{ $selectedMonthFrom }}">
                            <x-searchable-select 
                                target-input-id="monthFromValue"
                                :value="$selectedMonthFrom" 
                                placeholder="Dari Bulan"
                                :options="collect(range(1, 12))->map(fn($m) => ['id' => $m, 'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F')])->toArray()"
                            />

                            <input type="hidden" name="month_to" id="monthToValue" value="{{ $selectedMonthTo }}">
                            <x-searchable-select 
                                target-input-id="monthToValue"
                                :value="$selectedMonthTo" 
                                placeholder="Sampai Bulan"
                                :options="collect(range(1, 12))->map(fn($m) => ['id' => $m, 'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F')])->toArray()"
                            />
                        </div>

                        {{-- Search and Reset --}}
                        <div class="flex items-center gap-2">
                            <div class="relative w-full md:w-64">
                                <input type="text" name="search" id="searchInput" 
                                    value="{{ request('search') }}" 
                                    placeholder="Cari Nama atau NPWPD..." 
                                    class="w-full pl-10 pr-10 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-bold text-slate-700 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all outline-none shadow-sm">
                                <div class="absolute left-3 top-2.5">
                                    <svg id="searchIcon" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <div id="searchSpinner" class="hidden absolute right-3 top-2.5">
                                    <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>

                            <button type="button" id="resetBtn"
                                class="flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-500 font-bold py-2 px-4 rounded-xl transition-all text-[11px] uppercase tracking-widest border border-slate-200 shadow-sm active:scale-95 group"
                                title="Reset Filter">
                                <svg class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Table Container for AJAX --}}
                <div id="tableContainer" class="relative min-h-[400px]">
                    @include('admin.monitoring._table')
                </div>
            </div>
        </form>
    </div>

    {{-- Assign Modal --}}
    <div id="assignModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-md transition-all">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all border border-slate-100">
                <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/80 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase tracking-widest text-sm">Penugasan Petugas Lapangan</h3>
                    <button onclick="closeAssignModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.monitoring.assign') }}" method="POST" class="p-8 space-y-6">
                    @csrf
                    <input type="hidden" name="tax_payer_id" id="modal_wp_id">
                    <input type="hidden" name="tax_payer_name" id="modal_wp_name">
                    <input type="hidden" name="tax_payer_address" id="modal_wp_address">
                    <input type="hidden" name="amount_sptpd" id="modal_amount_sptpd">
                    <input type="hidden" name="amount_paid" id="modal_amount_paid">

                    <div class="bg-indigo-900 rounded-2xl p-5 shadow-lg relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 opacity-5 transform rotate-12 group-hover:rotate-0 transition-transform duration-500">
                            <svg class="w-24 h-24 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                        </div>
                        <p class="text-[9px] text-indigo-300 font-black uppercase tracking-[0.2em] mb-2 leading-none">Wajib Pajak Terpilih</p>
                        <p class="font-black text-white text-base leading-tight uppercase mb-1" id="display_wp_name"></p>
                        <p class="text-[10px] text-indigo-400 font-mono font-bold" id="display_wp_id"></p>
                    </div>

                    <div>
                        <label for="officer_id" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Pilih Petugas Penagihan</label>
                        <select name="officer_id" id="officer_id" required class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs font-bold py-3.5 px-4 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all">
                            <option value="">-- Pilih Petugas --</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Catatan Tambahan</label>
                        <textarea name="notes" id="notes" rows="3" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs font-bold py-3.5 px-4 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="Instruksi penagihan khusus untuk petugas ini..."></textarea>
                    </div>

                    <div class="flex gap-4 pt-2">
                        <button type="button" onclick="closeAssignModal()" class="flex-1 px-6 py-3.5 border border-slate-200 rounded-2xl text-[11px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-50 active:scale-95 transition-all">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3.5 bg-slate-900 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-xl shadow-slate-900/20 hover:bg-black active:scale-95 transition-all">
                            Simpan Tugas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let searchTimeout;

        function refreshTable(url = null) {
            const form = $('#filterForm');
            const container = $('#tableContainer');
            const totalWpText = $('#totalWpText');

            if (!url) {
                url = form.attr('action') + '?' + form.serialize();
            }

            container.addClass('opacity-50 pointer-events-none');
            $('#searchSpinner').removeClass('hidden');

            $.ajax({
                url: url,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    container.html(response);
                    
                    // Update WP count from the hidden data attribute in the new content
                    const newTotal = container.find('[data-total-wp]').data('total-wp');
                    if (newTotal !== undefined) {
                        totalWpText.text(newTotal + ' WP Terdeteksi');
                    }

                    window.history.pushState({}, '', url);
                },
                error: function() {
                    alert('Gagal memuat data. Silakan coba lagi.');
                },
                complete: function() {
                    container.removeClass('opacity-50 pointer-events-none');
                    $('#searchSpinner').addClass('hidden');
                }
            });
        }

        $(document).ready(function() {
            // 1. Search Debounce
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => refreshTable(), 500);
            });

            // 2. Listen to hidden input changes (triggered by searchable-select's native 'change' event)
            $(document).on('change', '#districtValue, #monthFromValue, #monthToValue, #yearValue', function() {
                refreshTable();
            });

            // 3. Reset Button
            $('#resetBtn').on('click', function(e) {
                e.preventDefault();
                window.location.href = "{{ route('admin.monitoring.index') }}";
            });

            // 4. Year Dropdown
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
                if (!$(e.target).closest('#yearDropdownWrapper').length) {
                    $('#yearDropdownMenu').addClass('hidden');
                }
            });

            // 5. Pagination
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                if (url) {
                    refreshTable(url);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            // 6. Prevent Form standard submit
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                refreshTable();
            });
        });

        function openAssignModal(id, name, address, sptpd, paid) {
            $('#modal_wp_id').val(id);
            $('#modal_wp_name').val(name);
            $('#modal_wp_address').val(address);
            $('#modal_amount_sptpd').val(sptpd);
            $('#modal_amount_paid').val(paid);
            
            $('#display_wp_name').text(name);
            $('#display_wp_id').text(id);
            
            $('#assignModal').removeClass('hidden');
        }

        function closeAssignModal() {
            $('#assignModal').addClass('hidden');
        }
    </script>
    @endpush
</x-layouts.admin>
