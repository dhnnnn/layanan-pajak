<x-layouts.admin title="Perbandingan Target UPT" header="Import Perbandingan Target UPT">
    <div class="space-y-6">
        {{-- Upload Form --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Unggah File Perbandingan Target UPT</h3>
            
            <form action="{{ route('admin.upt-comparisons.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="year" class="block text-sm font-semibold text-slate-700 mb-1">Tahun Target <span class="text-red-500">*</span></label>
                    <select name="year" id="year" class="block w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('year') border-red-500 @enderror" required>
                        @php $currentYear = date('Y'); @endphp
                        @for($y = $currentYear; $y <= $currentYear + 2; $y++)
                            <option value="{{ $y }}" {{ old('year', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('year')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col md:flex-row items-end gap-4 pt-2">
                    <div class="flex-1 w-full">
                        <label for="file" class="block text-sm font-semibold text-slate-700 mb-1">Pilih File Excel (.xlsx, .xls) <span class="text-red-500">*</span></label>
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
            
            <div class="mt-4 p-4 bg-purple-50/50 rounded-lg border border-purple-100">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-purple-700 leading-relaxed">
                        <p class="font-bold mb-1">Petunjuk Import Perbandingan Target UPT:</p>
                        <ul class="list-disc ml-4 space-y-1">
                            <li>Download template "Perbandingan Target UPT" terlebih dahulu.</li>
                            <li>Kolom <strong>Jenis Pajak</strong> dan <strong>Target</strong> sudah terisi otomatis dari database.</li>
                            <li>Isi target untuk setiap UPT pada kolom yang tersedia.</li>
                            <li>Kolom <strong>TOTAL, % TARGET, % SELISIH, dan SELISIH</strong> akan dihitung otomatis.</li>
                            <li>Pastikan total dari semua UPT sesuai dengan target APBD.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Link to Report --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 mb-1">Lihat Laporan Perbandingan</h3>
                    <p class="text-xs text-slate-600">Lihat perbandingan target APBD dengan target per UPT</p>
                </div>
                <a href="{{ route('admin.upt-comparisons.report') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Lihat Laporan
                </a>
            </div>
        </div>
    </div>
</x-layouts.admin>
