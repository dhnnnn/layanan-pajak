<x-layouts.admin :title="$baselineAmount > 0 ? 'Edit Target Sistem' : 'Tambah Target APBD'" :header="$baselineAmount > 0 ? 'Sesuaikan Target Sistem' : 'Tambah Target APBD Baru'">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.manage', ['year' => request('year', $year)]) }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Kelola Target
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('admin.tax-targets.store') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <div>
                    <label for="tax_type_id" class="block text-sm font-semibold text-slate-700 mb-1">Jenis Pajak <span class="text-red-500">*</span></label>
                    <select name="tax_type_id" id="tax_type_id" class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('tax_type_id') ring-2 ring-red-500/20 @endif" required>
                        <option value="" disabled>Pilih Jenis Pajak</option>
                        @foreach($taxTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('tax_type_id', request('tax_type_id')) == (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('tax_type_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="year" class="block text-sm font-semibold text-slate-700 mb-1">Tahun Anggaran <span class="text-red-500">*</span></label>
                    <input type="number" name="year" id="year" value="{{ old('year', request('year', $year)) }}" 
                        class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('year') ring-2 ring-red-500/20 @endif" 
                        placeholder="Contoh: {{ date('Y') }}" min="2000" max="2100" required>
                    @error('year')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="target_amount" class="block text-sm font-semibold text-slate-700 mb-1">Nilai Total Target (APBD) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-sm font-bold">Rp</div>
                        <input type="number" name="target_amount" id="target_amount" value="{{ old('target_amount', ($baselineAmount > 0 ? (int)$baselineAmount : '')) }}" 
                            class="pl-10 w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('target_amount') ring-2 ring-red-500/20 @endif" 
                            placeholder="Contoh: 15000000000" required>
                    </div>
                    @if($baselineAmount > 0)
                        <p class="mt-1 text-[10px] text-blue-500 italic">Nilai ditarik otomatis dari Anggaran Sistem (Simpadu).</p>
                    @else
                        <p class="mt-1 text-[10px] text-slate-500 italic">Masukkan angka saja tanpa titik atau koma.</p>
                    @endif
                    @error('target_amount')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Quarterly Targets --}}
                <div class="pt-2">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Target per Triwulan
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="q1_target" class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Triwulan I</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-[10px] font-bold">Rp</div>
                                <input type="number" name="q1_target" id="q1_target" value="{{ old('q1_target', ($q1_baseline > 0 ? (int)$q1_baseline : '')) }}" 
                                    class="pl-8 w-full rounded-lg bg-slate-50 text-slate-600 text-sm py-2 px-3 focus:bg-white focus:ring-2 focus:ring-blue-500/10 border-transparent focus:border-blue-200">
                            </div>
                        </div>
                        <div>
                            <label for="q2_target" class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Triwulan II</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-[10px] font-bold">Rp</div>
                                <input type="number" name="q2_target" id="q2_target" value="{{ old('q2_target', ($q2_baseline > 0 ? (int)$q2_baseline : '')) }}" 
                                    class="pl-8 w-full rounded-lg bg-slate-50 text-slate-600 text-sm py-2 px-3 focus:bg-white focus:ring-2 focus:ring-blue-500/10 border-transparent focus:border-blue-200">
                            </div>
                        </div>
                        <div>
                            <label for="q3_target" class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Triwulan III</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-[10px] font-bold">Rp</div>
                                <input type="number" name="q3_target" id="q3_target" value="{{ old('q3_target', ($q3_baseline > 0 ? (int)$q3_baseline : '')) }}" 
                                    class="pl-8 w-full rounded-lg bg-slate-50 text-slate-600 text-sm py-2 px-3 focus:bg-white focus:ring-2 focus:ring-blue-500/10 border-transparent focus:border-blue-200">
                            </div>
                        </div>
                        <div>
                            <label for="q4_target" class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Triwulan IV</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-[10px] font-bold">Rp</div>
                                <input type="number" name="q4_target" id="q4_target" value="{{ old('q4_target', ($q4_baseline > 0 ? (int)$q4_baseline : '')) }}" 
                                    class="pl-8 w-full rounded-lg bg-slate-50 text-slate-600 text-sm py-2 px-3 focus:bg-white focus:ring-2 focus:ring-blue-500/10 border-transparent focus:border-blue-200">
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400 italic">Isi opsional untuk merinci distribusi arus kas bulanan.</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.tax-targets.manage', ['year' => request('year', $year)]) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        {{ $baselineAmount > 0 ? 'Simpan Perubahan' : 'Simpan Target APBD' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
