<x-layouts.admin title="Pemantauan Wajib Pajak" header="Pemantauan & Penagihan WP">
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm mb-6">
            <form id="filterForm" action="{{ route('admin.monitoring.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 items-end">
                <div class="lg:col-span-1">
                    <label for="year" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest text-[10px]">Tahun Anggaran</label>
                    <select name="year" id="year" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs py-3 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all filter-input font-semibold">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label for="month_from" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest text-[10px]">Dari Bulan</label>
                    <select name="month_from" id="month_from" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs py-3 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all filter-input font-semibold">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $selectedMonthFrom == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label for="month_to" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest text-[10px]">Sampai Bulan</label>
                    <select name="month_to" id="month_to" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs py-3 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all filter-input font-semibold">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $selectedMonthTo == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="search" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest text-[10px]">Cari Nama / NPWPD (Minimal 3 Karakter)</label>
                    <div class="relative group">
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Cari berdasarkan nama atau NPWPD..." 
                            class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[13px] py-3.5 pl-5 pr-12 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all filter-input font-bold text-slate-800 placeholder-slate-400">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <div class="p-1.5 bg-blue-50 rounded-lg group-focus-within:bg-blue-600 group-focus-within:text-white transition-colors">
                                <svg class="h-4 w-4 text-blue-600 group-focus-within:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-6 flex justify-end gap-3">
                    <a href="{{ route('admin.monitoring.index') }}" class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-6 rounded-2xl transition-all text-[11px] uppercase tracking-widest border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset Filter
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[9px] tracking-widest border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-4 py-4 border-r border-slate-200 align-middle">NPWPD</th>
                            <th rowspan="2" class="px-4 py-4 border-r border-slate-200 align-middle">Wajib Pajak</th>
                            <th rowspan="2" class="px-4 py-4 border-r border-slate-200 align-middle text-center uppercase">Kecamatan</th>
                            @for($m = $selectedMonthFrom; $m <= $selectedMonthTo; $m++)
                                <th colspan="3" class="px-4 py-3 border-r border-slate-200 border-b border-slate-200 text-center bg-slate-100/50">
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </th>
                            @endfor
                            <th rowspan="2" class="px-4 py-4 text-center align-middle">Status</th>
                        </tr>
                        <tr class="bg-slate-50/80">
                            @for($m = $selectedMonthFrom; $m <= $selectedMonthTo; $m++)
                                <th class="px-2 py-2 text-center border-r border-slate-200 text-[9px] font-bold text-slate-500">Tgl SPTPD</th>
                                <th class="px-2 py-2 text-center border-r border-slate-200 text-[9px] font-bold text-slate-500">Masa Pajak</th>
                                <th class="px-2 py-2 text-center border-r border-slate-200 text-[9px] font-bold text-slate-500">Jml SPTPD</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-[11px]">
                        @forelse($taxPayers as $wp)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 border-r border-slate-200 font-bold text-slate-900">
                                    {{ $wp->npwpd }}
                                </td>
                                <td class="px-4 py-3 border-r border-slate-200 text-slate-600 font-bold uppercase text-[10px]">
                                    {{ $wp->nm_wp }}
                                </td>
                                <td class="px-4 py-3 border-r border-slate-200 text-center uppercase text-slate-600 font-medium whitespace-nowrap">
                                    {{ $wp->nm_kecamatan }}
                                </td>
                                @for($m = $selectedMonthFrom; $m <= $selectedMonthTo; $m++)
                                    @php $data = $wp->monthly_data[$m]; @endphp
                                    <td class="px-2 py-3 border-r border-slate-200 text-center text-slate-500 font-medium">
                                        {{ $data['tgl_lapor'] }}
                                    </td>
                                    <td class="px-2 py-3 border-r border-slate-200 text-center text-slate-500 font-medium">
                                        {{ $data['masa_pajak'] }}
                                    </td>
                                    <td class="px-2 py-3 border-r border-slate-200 text-right font-bold {{ $data['jml_lapor'] > 0 ? 'text-blue-600' : 'text-slate-300' }}">
                                        {{ $data['jml_lapor'] > 0 ? number_format($data['jml_lapor'], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                                <td class="px-4 py-3 text-center">
                                    @if($wp->status == '1')
                                        <span class="text-emerald-500 font-bold text-[10px]">AKTIF</span>
                                    @else
                                        <span class="text-rose-500 font-bold text-[10px]">NON-AKTIF</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + ($selectedMonthTo - $selectedMonthFrom + 1) * 3 }}" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="p-4 bg-slate-50 rounded-full">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-slate-500 font-medium">Data tidak ditemukan dalam rentang periode tersebut.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($taxPayers->hasPages())
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                    {{ $taxPayers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Assign Modal --}}
    <div id="assignModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Penugasan Petugas Lapangan</h3>
                    <button onclick="closeAssignModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.monitoring.assign') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="tax_payer_id" id="modal_wp_id">
                    <input type="hidden" name="tax_payer_name" id="modal_wp_name">
                    <input type="hidden" name="tax_payer_address" id="modal_wp_address">
                    <input type="hidden" name="amount_sptpd" id="modal_amount_sptpd">
                    <input type="hidden" name="amount_paid" id="modal_amount_paid">

                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 mb-4">
                        <p class="text-xs text-blue-600 font-bold uppercase mb-1">Wajib Pajak Terpilih:</p>
                        <p class="font-bold text-slate-800" id="display_wp_name"></p>
                        <p class="text-xs text-slate-500" id="display_wp_id"></p>
                    </div>

                    <div>
                        <label for="officer_id" class="block text-sm font-bold text-slate-700 mb-1">Pilih Petugas Penagihan</label>
                        <select name="officer_id" id="officer_id" required class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Pilih Petugas --</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Instruksi penagihan..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeAssignModal()" class="flex-1 px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">
                            Simpan Penugasan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto submit on select change or debounced input
            let searchTimer;
            $('.filter-input').on('change keyup', function(e) {
                if (e.type === 'keyup') {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        $('#filterForm').submit();
                    }, 500); // 500ms debounce
                } else {
                    $('#filterForm').submit();
                }
            });

            // Prevent form submit on enter inside search (since we auto-submit)
            $('#search').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#filterForm').submit();
                }
            });
        });

        function openAssignModal(id, name, address, sptpd, paid) {
            document.getElementById('modal_wp_id').value = id;
            document.getElementById('modal_wp_name').value = name;
            document.getElementById('modal_wp_address').value = address;
            document.getElementById('modal_amount_sptpd').value = sptpd;
            document.getElementById('modal_amount_paid').value = paid;
            
            document.getElementById('display_wp_name').innerText = name;
            document.getElementById('display_wp_id').innerText = id;
            
            document.getElementById('assignModal').classList.remove('hidden');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }
    </script>
    @endpush
</x-layouts.admin>
