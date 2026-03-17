<x-layouts.admin title="Unduh Template" header="Unduh Template Excel">
    <div class="max-w-4xl mx-auto space-y-6">
        {{-- Template 1: Target APBD --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Template Target APBD</h3>
                        <p class="text-sm text-slate-600 mb-4">Template untuk import data realisasi pajak master yang akan diterapkan ke semua kecamatan.</p>
                        
                        <form action="{{ route('admin.template.download') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                            <input type="hidden" name="type" value="apbd">
                            <div class="flex-1">
                                <label for="year_apbd" class="block text-xs font-semibold text-slate-700 mb-1">Pilih Tahun</label>
                                <select name="year" id="year_apbd" class="w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    @php $currentYear = date('Y'); @endphp
                                    @for($y = $currentYear; $y >= $currentYear - 5; $y--)
                                        <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Download Template
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <p class="text-xs text-blue-700 font-semibold mb-1">Kegunaan:</p>
                            <ul class="text-xs text-blue-700 list-disc ml-4 space-y-0.5">
                                <li>Import realisasi pajak untuk semua kecamatan sekaligus</li>
                                <li>Data jenis pajak dan target sudah terisi otomatis</li>
                                <li>Isi data realisasi per triwulan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Template 2: Perbandingan Target UPT --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Template Perbandingan Target UPT</h3>
                        <p class="text-sm text-slate-600 mb-4">Template untuk import perbandingan target APBD dengan target per UPT.</p>
                        
                        <form action="{{ route('admin.template.download') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                            <input type="hidden" name="type" value="upt">
                            <div class="flex-1">
                                <label for="year_upt" class="block text-xs font-semibold text-slate-700 mb-1">Pilih Tahun</label>
                                <select name="year" id="year_upt" class="w-full text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    @php $currentYear = date('Y'); @endphp
                                    @for($y = $currentYear; $y <= $currentYear + 2; $y++)
                                        <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Download Template
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-100">
                            <p class="text-xs text-purple-700 font-semibold mb-1">Kegunaan:</p>
                            <ul class="text-xs text-purple-700 list-disc ml-4 space-y-0.5">
                                <li>Import target per UPT untuk perbandingan dengan target APBD</li>
                                <li>Kolom jenis pajak dan target APBD sudah terisi otomatis</li>
                                <li>Isi target untuk setiap UPT</li>
                                <li>Formula perhitungan otomatis (TOTAL, %, SELISIH)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Box --}}
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl border border-slate-200 p-6">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-slate-700">
                    <p class="font-semibold mb-2">Catatan Penting:</p>
                    <ul class="list-disc ml-4 space-y-1">
                        <li>Pastikan memilih tahun yang sesuai sebelum download template</li>
                        <li>Jangan mengubah struktur kolom atau header pada template</li>
                        <li>Data jenis pajak dan target yang sudah terisi otomatis jangan diubah</li>
                        <li>Simpan file dalam format .xlsx atau .xls</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
