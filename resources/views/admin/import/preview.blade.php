<x-layouts.admin title="Pratinjau Import Realisasi" :header="'Pratinjau Data: ' . $fileName">
    <x-slot:headerActions>
        <a href="{{ route('admin.import.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Batal & Kembali
        </a>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Info Alert --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex gap-3 text-blue-800 shadow-sm">
            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-xs leading-relaxed">
                <h4 class="font-bold mb-1">Konfirmasi Data Import</h4>
                <p>Ditemukan <strong>{{ $totalRows }} baris data</strong> dalam file Anda. Silakan periksa sampel data di bawah ini. Klik <strong>"Konfirmasi & Simpan"</strong> untuk memproses seluruh data ke dalam sistem.</p>
            </div>
        </div>

        {{-- Preview Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm italic uppercase tracking-wider">Sampel 10 Baris Pertama</h3>
                <span class="text-[10px] text-slate-400 font-mono">{{ $fileName }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Jenis Pajak</th>
                            <th class="px-4 py-3">Kecamatan</th>
                            <th class="px-4 py-3">Tahun</th>
                            <th class="px-4 py-3 text-center">Jan</th>
                            <th class="px-4 py-3 text-center">Feb</th>
                            <th class="px-4 py-3 text-center">Mar</th>
                            <th class="px-4 py-3 text-center">Apr</th>
                            <th class="px-4 py-3 text-center">Mei</th>
                            <th class="px-4 py-3 text-center">Jun</th>
                            <th class="px-4 py-3 text-center">Jul</th>
                            <th class="px-4 py-3 text-center">Agu</th>
                            <th class="px-4 py-3 text-center">Sep</th>
                            <th class="px-4 py-3 text-center">Okt</th>
                            <th class="px-4 py-3 text-center">Nov</th>
                            <th class="px-4 py-3 text-center">Des</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($previewData as $row)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $row['jenis_pajak'] }}</td>
                                <td class="px-4 py-3">{{ $row['kecamatan'] }}</td>
                                <td class="px-4 py-3 font-mono">{{ $row['tahun'] }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['januari'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['februari'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['maret'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['april'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['mei'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['juni'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['juli'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['agustus'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['september'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['oktober'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['november'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($row['desember'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-[11px] text-slate-500 italic">
                    * Data yang sama (Tahun, Jenis Pajak, Kecamatan) akan diperbarui (overwrite).
                </div>
                
                <form action="{{ route('admin.confirm') }}" method="POST">
                    @csrf
                    <input type="hidden" name="stored_path" value="{{ $storedPath }}">
                    <input type="hidden" name="file_name" value="{{ $fileName }}">
                    
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-all shadow-md transform active:scale-95 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Konfirmasi & Simpan Semua Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
