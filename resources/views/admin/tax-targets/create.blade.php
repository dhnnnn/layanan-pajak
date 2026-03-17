<x-layouts.admin title="Tambah Target APBD" header="Tambah Target APBD Baru">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('admin.tax-targets.store') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <div>
                    <label for="tax_type_id" class="block text-sm font-semibold text-slate-700 mb-1">Jenis Pajak <span class="text-red-500">*</span></label>
                    <select name="tax_type_id" id="tax_type_id" class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('tax_type_id') ring-2 ring-red-500/20 @endif" required>
                        <option value="" disabled selected>Pilih Jenis Pajak</option>
                        @foreach($taxTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('tax_type_id') == $type->id)>{{ $type->name }} ({{ $type->code }})</option>
                        @endforeach
                    </select>
                    @error('tax_type_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="year" class="block text-sm font-semibold text-slate-700 mb-1">Tahun Anggaran <span class="text-red-500">*</span></label>
                    <input type="number" name="year" id="year" value="{{ old('year', date('Y')) }}" 
                        class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('year') ring-2 ring-red-500/20 @endif" 
                        placeholder="Contoh: {{ date('Y') }}" min="2000" max="2100" required>
                    @error('year')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="target_amount" class="block text-sm font-semibold text-slate-700 mb-1">Nilai Target (APBD) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-sm font-bold">Rp</div>
                        <input type="number" name="target_amount" id="target_amount" value="{{ old('target_amount') }}" 
                            class="pl-10 w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('target_amount') ring-2 ring-red-500/20 @endif" 
                            placeholder="Contoh: 15000000000" required>
                    </div>
                    <p class="mt-1 text-[10px] text-slate-500 italic">Masukkan angka saja tanpa titik atau koma.</p>
                    @error('target_amount')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="reset" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Reset</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Target APBD
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
