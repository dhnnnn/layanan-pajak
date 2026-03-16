<x-layouts.admin title="Edit Jenis Pajak" :header="'Edit Jenis Pajak: ' . $taxType->name">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-types.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('admin.tax-types.update', $taxType) }}" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nama Jenis Pajak <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $taxType->name) }}" 
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror" 
                        placeholder="Contoh: Pajak Penerangan Jalan" required>
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-semibold text-slate-700 mb-1">Kode Pajak <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code', $taxType->code) }}" 
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm uppercase @error('code') border-red-500 @enderror" 
                        placeholder="Contoh: PPJ" required>
                    <p class="mt-1 text-xs text-slate-500 italic">Singkatan atau kode unik untuk identifikasi jenis pajak.</p>
                    @error('code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.tax-types.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
