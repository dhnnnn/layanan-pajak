<x-layouts.employee title="Pratinjau Bulk Import" :header="'Pratinjau Data: ' . $fileName">
    <x-slot:headerActions>
        <a href="{{ route('pegawai.import.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Batal
        </a>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Info Alert --}}
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex gap-3 text-emerald-800 shadow-sm">
            <svg class="w-5 h-5 text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-xs leading-relaxed">
                <h4 class="font-bold mb-1">Konfirmasi Data Bulk Import</h4>
                <p>Ditemukan <strong>{{ $totalRows }} baris data</strong>. Hanya data untuk kecamatan yang Anda ampu yang akan diproses. Klik <strong>"Simpan Sekarang"</strong> jika data sudah sesuai.</p>
            </div>
        </div>

        {{-- Preview Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/20">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Cuplikan 10 Baris Pertama</h3>
                <span class="text-[10px] text-slate-400 font-mono italic">{{ $fileName }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[10px] text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-3">Jenis Pajak</th>
                            <th class="px-3 py-3">Kecamatan</th>
                            <th class="px-3 py-3">Tahun</th>
                            <th class="px-3 py-3 text-center">Jan</th>
                            <th class="px-3 py-3 text-center">Feb</th>
                            <th class="px-3 py-3 text-center">Mar</th>
                            <th class="px-3 py-3 text-center">Apr</th>
                            <th class="px-3 py-3 text-center">Mei</th>
                            <th class="px-3 py-3 text-center">Jun</th>
                            <th class="px-3 py-3 text-center">Jul</th>
                            <th class="px-3 py-3 text-center">Agu</th>
                            <th class="px-3 py-3 text-center">Sep</th>
                            <th class="px-3 py-3 text-center">Okt</th>
                            <th class="px-3 py-3 text-center">Nov</th>
                            <th class="px-3 py-3 text-center">Des</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 italic">
                        @foreach($previewData as $row)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-3 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ $row['jenis_pajak'] }}</td>
                                <td class="px-3 py-3">{{ $row['kecamatan'] }}</td>
                                <td class="px-3 py-3 font-mono">{{ $row['tahun'] }}</td>
                                @foreach(['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'] as $month)
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        {{ number_format($row[$month] ?? 0, 0, ',', '.') }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-[10px] text-slate-500">
                    * Data yang valid akan memperbarui (overwrite) nilai realisasi yang sudah ada.
                </div>
                
                <form action="{{ route('pegawai.confirm') }}" method="POST">
                    @csrf
                    <input type="hidden" name="stored_path" value="{{ $storedPath }}">
                    <input type="hidden" name="file_name" value="{{ $fileName }}">
                    
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-all shadow-lg transform active:scale-95 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Simpan Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.employee>
