<x-layouts.admin title="Tambah Kecamatan" header="Tambah Kecamatan Baru">
    <x-slot:headerActions>
        <a href="{{ route('admin.districts.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('admin.districts.store') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nama Kecamatan <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                        class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('name') ring-2 ring-red-500/20 @endif" 
                        placeholder="Contoh: Bangil" required>
                    <p class="mt-1 text-xs text-slate-500 italic">Kode kecamatan akan dibuat otomatis dari nama.</p>
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="reset" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Reset</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Kecamatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
