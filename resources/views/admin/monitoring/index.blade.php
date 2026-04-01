<x-layouts.admin title="Pemantauan Wajib Pajak" header="Pemantauan & Penagihan WP">
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <form action="{{ route('admin.monitoring.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="year" class="block text-sm font-medium text-slate-700 mb-1">Tahun Anggaran</label>
                    <select name="year" id="year" class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="district_id" class="block text-sm font-medium text-slate-700 mb-1">Kecamatan</label>
                    <select name="district_id" id="district_id" class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Kecamatan</option>
                        @foreach($districts as $d)
                            <option value="{{ $d->id }}" {{ $selectedDistrict == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="search" class="block text-sm font-medium text-slate-700 mb-1">Cari Nama/NPWPD</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Contoh: Budi atau 01.234..." 
                        class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                        Filter Data
                    </button>
                    <a href="{{ route('admin.monitoring.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Monitoring Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[11px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-4 border-r border-slate-200">NPWPD & WP</th>
                            <th class="px-4 py-4 border-r border-slate-200">Alamat & Objek</th>
                            <th class="px-4 py-4 border-r border-slate-200 text-right">Ketetapan</th>
                            <th class="px-4 py-4 border-r border-slate-200 text-right">Dibayar</th>
                            <th class="px-4 py-4 border-r border-slate-200 text-right">Tunggakan</th>
                            <th class="px-4 py-4 text-center">Aksi / Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-[13px]">
                        @forelse($taxPayers as $wp)
                            @php
                                $task = $existingTasks->get($wp->npwpd)?->first();
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-4 border-r border-slate-200">
                                    <div class="font-bold text-slate-900">{{ $wp->npwpd }}</div>
                                    <div class="text-slate-600 uppercase">{{ $wp->nm_wp }}</div>
                                </td>
                                <td class="px-4 py-4 border-r border-slate-200">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block px-0">OBJET PAJAK:</span>
                                    <div class="font-medium text-slate-800">{{ $wp->nm_op }}</div>
                                    <div class="text-[11px] text-slate-500 italic">{{ $wp->almt_op }}</div>
                                </td>
                                <td class="px-4 py-4 border-r border-slate-200 text-right font-medium text-slate-900">
                                    Rp {{ number_format($wp->total_ketetapan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 border-r border-slate-200 text-right font-medium text-emerald-600">
                                    Rp {{ number_format($wp->total_bayar, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 border-r border-slate-200 text-right font-bold {{ $wp->total_tunggakan > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                    Rp {{ number_format($wp->total_tunggakan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($task)
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                {{ $task->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $task->status }}
                                            </span>
                                            <span class="text-[10px] text-slate-500">Petugas: {{ $task->officer->name }}</span>
                                        </div>
                                    @elseif($wp->total_tunggakan > 0)
                                        <button type="button" 
                                            onclick="openAssignModal('{{ $wp->npwpd }}', '{{ addslashes($wp->nm_wp) }}', '{{ addslashes($wp->almt_op) }}', {{ $wp->total_ketetapan }}, {{ $wp->total_bayar }})"
                                            class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-1.5 rounded-lg font-bold text-xs transition-colors">
                                            Tugaskan Petugas
                                        </button>
                                    @else
                                        <span class="text-emerald-500 font-bold text-[10px] uppercase">Lunas</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    Tidak ada data Wajib Pajak ditemukan.
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
                    <input type="hidden" name="district_id" value="{{ $selectedDistrict }}">

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
    <script>
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
