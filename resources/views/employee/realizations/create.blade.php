<x-layouts.employee title="Input Realisasi Pajak" header="Input Realisasi Pajak Baru">
    <x-slot:headerActions>
        <a href="{{ route('field-officer.realizations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('field-officer.realizations.store') }}" method="POST" class="p-6 space-y-8">
                @csrf

                {{-- Header Info --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-6 border-b border-slate-100">
                    <div>
                        <label for="tax_type_id" class="block text-sm font-semibold text-slate-700 mb-1">Jenis Pajak <span class="text-red-500">*</span></label>
                        <select name="tax_type_id" id="tax_type_id" class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 @error('tax_type_id') ring-2 ring-red-500/20 @endif" required>
                            <option value="" disabled selected>Pilih Jenis Pajak</option>
                            @foreach($taxTypes as $parent)
                                <option value="{{ $parent->id }}" @selected(old('tax_type_id', $realization->tax_type_id ?? '') == (string) $parent->id)>{{ $parent->name }}</option>
                                @foreach($parent->children as $child)
                                    <option value="{{ $child->id }}" @selected(old('tax_type_id', $realization->tax_type_id ?? '') == (string) $child->id)>&nbsp;&nbsp;&nbsp;&nbsp;— {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('tax_type_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="district_id" class="block text-sm font-semibold text-slate-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                        <select name="district_id" id="district_id" class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 @error('district_id') ring-2 ring-red-500/20 @endif" required>
                            <option value="" disabled selected>Pilih Kecamatan</option>
                            @foreach($districts as $district)
                                <option value="{{ $district->id }}" @selected(old('district_id') == (string) $district->id)>{{ $district->name }}</option>
                            @endforeach
                        </select>
                        @error('district_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="year" class="block text-sm font-semibold text-slate-700 mb-1">Tahun <span class="text-red-500">*</span></label>
                        <select name="year" id="year" class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 @error('year') ring-2 ring-red-500/20 @endif" required>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" @selected(old('year', date('Y')) == $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                        @error('year')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Monthly Inputs --}}
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Input Realisasi Bulanan (Angka Riil)</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($months as $month)
                            <div class="p-3 bg-slate-50/50 rounded-lg border border-slate-100 focus-within:border-emerald-200 focus-within:ring-1 focus-within:ring-emerald-100 transition-all">
                                <label for="{{ $month->column_name }}" class="block text-[10px] font-bold text-slate-500 uppercase mb-1">{{ $month->name }}</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-slate-400 text-[10px] font-bold">Rp</span>
                                    <input type="number" name="{{ $month->column_name }}" id="{{ $month->column_name }}" 
                                        value="{{ old($month->column_name, 0) }}" 
                                        class="pl-7 w-full border-0 bg-transparent p-0 text-sm font-bold text-slate-800 focus:ring-0" 
                                        placeholder="0">
                                </div>
                                @error($month->column_name)
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Info Alert --}}
                <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-100 flex gap-3 text-emerald-800">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[11px] leading-relaxed">
                        <strong>Catatan:</strong> Data yang Anda input akan diakumulasikan secara otomatis ke dalam dashboard laporan Q1-Q4. Pastikan angka yang diinput adalah jumlah realisasi komulatif atau riil sesuai kebijakan pelaporan.
                    </p>
                </div>

                <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="reset" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Reset</button>
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-all shadow-md transform active:scale-95 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Realisasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.employee>
