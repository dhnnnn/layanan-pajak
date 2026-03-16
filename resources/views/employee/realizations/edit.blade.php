<x-layouts.employee title="Edit Realisasi Pajak" :header="'Edit Realisasi: ' . $realization->taxType->name . ' (' . $realization->year . ')'">
    <x-slot:headerActions>
        <a href="{{ route('pegawai.realizations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('pegawai.realizations.update', $realization) }}" method="POST" class="p-6 space-y-8">
                @csrf
                @method('PUT')

                {{-- Header Info (Read-only as they define the record identity) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-6 border-b border-slate-100">
                    <input type="hidden" name="tax_type_id" value="{{ $realization->tax_type_id }}">
                    <input type="hidden" name="district_id" value="{{ $realization->district_id }}">
                    <input type="hidden" name="year" value="{{ $realization->year }}">

                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jenis Pajak</p>
                        <p class="text-sm font-bold text-slate-800">{{ $realization->taxType->name }}</p>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kecamatan</p>
                        <p class="text-sm font-bold text-slate-800">{{ $realization->district->name }}</p>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tahun Anggaran</p>
                        <p class="text-sm font-bold text-slate-800">{{ $realization->year }}</p>
                    </div>
                </div>

                {{-- Monthly Inputs --}}
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Update Realisasi Bulanan</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($months as $month)
                            @php $fieldName = $month->column_name; @endphp
                            <div class="p-3 bg-slate-50/50 rounded-lg border border-slate-100 focus-within:border-emerald-200 focus-within:ring-1 focus-within:ring-emerald-100 transition-all">
                                <label for="{{ $fieldName }}" class="block text-[10px] font-bold text-slate-500 uppercase mb-1">{{ $month->name }}</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-slate-400 text-[10px] font-bold">Rp</span>
                                    <input type="number" name="{{ $fieldName }}" id="{{ $fieldName }}" 
                                        value="{{ old($fieldName, $realization->$fieldName) }}" 
                                        class="pl-7 w-full border-0 bg-transparent p-0 text-sm font-bold text-slate-800 focus:ring-0" 
                                        placeholder="0">
                                </div>
                                @error($fieldName)
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('pegawai.realizations.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Batal</a>
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-all shadow-md transform active:scale-95 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.employee>
