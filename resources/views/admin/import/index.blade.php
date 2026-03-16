<x-layouts.admin title="Import Realisasi Pajak" header="Import Data Realisasi dari Excel">
    <div class="space-y-6">
        {{-- Upload Form --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Unggah File Baru</h3>
            
            <form action="{{ route('admin.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="year" class="block text-sm font-semibold text-slate-700 mb-1">Tahun Realisasi</label>
                    <select name="year" id="year" class="block w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('year') border-red-500 @enderror" required>
                        @php $currentYear = date('Y'); @endphp
                        @for($y = $currentYear; $y >= $currentYear - 5; $y--)
                            <option value="{{ $y }}" {{ old('year', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('year')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col md:flex-row items-end gap-4 pt-2">
                    <div class="flex-1 w-full">
                        <label for="file" class="block text-sm font-semibold text-slate-700 mb-1">Pilih File Excel (.xlsx, .xls)</label>
                        <input type="file" name="file" id="file" accept=".xlsx, .xls" 
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-lg @error('file') border-red-500 @enderror" 
                            required>
                        @error('file')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Pratinjau Data
                    </button>
                </div>
            </form>
            
            <div class="mt-4 p-4 bg-blue-50/50 rounded-lg border border-blue-100">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-blue-700 leading-relaxed">
                        <p class="font-bold mb-1">Petunjuk Import Master:</p>
                        <ul class="list-disc ml-4 space-y-1">
                            <li>Download template master dari menu Template.</li>
                            <li>Isi data realisasi untuk setiap jenis pajak.</li>
                            <li>Pastikan nama <strong>Jenis Pajak</strong> sesuai dengan dropdown yang tersedia.</li>
                            <li>Data akan otomatis diterapkan ke semua kecamatan.</li>
                            <li>Kolom bulan (Januari - Desember) harus berisi angka atau 0.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Import Logs --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Riwayat Import</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-white font-bold uppercase text-[10px] text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Waktu</th>
                            <th class="px-6 py-4">File</th>
                            <th class="px-6 py-4">Oleh</th>
                            <th class="px-6 py-4 text-center">Total</th>
                            <th class="px-6 py-4 text-center">Berhasil</th>
                            <th class="px-6 py-4 text-center">Gagal</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($importLogs as $log)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $log->file_name }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $log->user->name }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold">
                                    {{ $log->total_rows }}
                                </td>
                                <td class="px-6 py-4 text-center text-emerald-600 font-bold">
                                    {{ $log->success_rows }}
                                </td>
                                <td class="px-6 py-4 text-center text-red-600 font-bold">
                                    {{ $log->failed_rows }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($log->failed_rows == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-tighter">Sukses Total</span>
                                    @elseif($log->success_rows == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 uppercase tracking-tighter">Gagal Total</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 uppercase tracking-tighter">Sebagian</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Belum ada riwayat import data.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($importLogs->hasPages())
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                    {{ $importLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
