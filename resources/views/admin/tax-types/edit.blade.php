<x-layouts.admin title="Edit Jenis Pajak" :header="'Edit Jenis Pajak: ' . $taxType->name">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-types.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Success Flash --}}
        @if(session('success'))
            <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Edit Jenis Pajak Utama --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Jenis Pajak Utama</h2>
                    <p class="text-xs text-slate-500">Ubah nama jenis pajak ini</p>
                </div>
            </div>

            <form action="{{ route('admin.tax-types.update', $taxType) }}" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nama Jenis Pajak <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $taxType->name) }}"
                        class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('name') ring-2 ring-red-500/20 @enderror"
                        placeholder="Contoh: Pajak Penerangan Jalan" required>
                    @error('name')
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

        {{-- Manajemen Subbab --}}
        @if($taxType->parent_id === null)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h8m-8 4h8"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Daftar Subbab</h2>
                            <p class="text-xs text-slate-500">{{ $taxType->children->count() }} subbab terdaftar</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        id="toggleSubtypeForm"
                        onclick="
                            document.getElementById('subtypeFormWrapper').classList.toggle('hidden');
                            document.getElementById('subtypeEmptyState')?.classList.toggle('hidden');
                            this.classList.toggle('bg-purple-600');
                            this.classList.toggle('bg-slate-100');
                            this.classList.toggle('text-white');
                            this.classList.toggle('text-slate-700');
                        "
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-lg transition-all hover:bg-purple-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Tambah Subbab
                    </button>
                </div>

                {{-- Form Tambah Subbab (tersembunyi by default) --}}
                <div id="subtypeFormWrapper" class="hidden border-b border-dashed border-purple-200 bg-purple-50/50">
                    <form action="{{ route('admin.tax-types.subtypes.store', $taxType) }}" method="POST" class="p-5 space-y-4">
                        @csrf

                        <div>
                            <label for="subtype_name" class="block text-sm font-semibold text-slate-700 mb-1">
                                Nama Subbab
                                <span class="text-red-500">*</span>
                                <span class="ml-2 font-normal text-purple-600 text-xs">Induk: {{ $taxType->name }} ({{ $taxType->code }})</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="subtype_name"
                                value="{{ old('name') }}"
                                class="w-full rounded-lg bg-white text-slate-700 py-2.5 px-4 border border-purple-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 @error('name') ring-2 ring-red-500/20 @enderror"
                                placeholder="Contoh: Reklame Billboard"
                                autofocus>
                            <p class="mt-1 text-xs text-slate-500 italic">Kode akan dibuat otomatis: <code class="font-mono">{{ $taxType->code }}-XX</code></p>
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                onclick="this.disabled=true; this.textContent='Menyimpan…'; this.closest('form').submit()"
                                class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                                Simpan Subbab
                            </button>
                            <button type="button"
                                onclick="
                                    document.getElementById('subtypeFormWrapper').classList.add('hidden');
                                    document.getElementById('subtypeEmptyState')?.classList.remove('hidden');
                                    document.getElementById('toggleSubtypeForm').classList.remove('bg-slate-100','text-slate-700');
                                    document.getElementById('toggleSubtypeForm').classList.add('bg-purple-600','text-white');
                                "
                                class="px-4 py-2 text-sm text-slate-500 hover:text-slate-800 transition-colors">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Daftar Subbab --}}
                @if($taxType->children->isNotEmpty())
                    <ul class="divide-y divide-slate-100">
                        @foreach($taxType->children as $child)
                            <li class="hover:bg-slate-50 transition-colors" id="subtype-row-{{ $child->id }}">

                                {{-- Tampilan Normal --}}
                                <div id="subtype-display-{{ $child->id }}" class="flex items-center justify-between px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="text-slate-300 text-sm">↳</span>
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">{{ $child->name }}</p>
                                            <span class="font-mono text-xs text-slate-400">{{ $child->code }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            onclick="toggleEditSubtype('{{ $child->id }}')"
                                            class="p-2 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Edit subbab">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <form action="{{ route('admin.tax-types.destroy', $child) }}" method="POST"
                                            onsubmit="return confirm('Hapus subbab \'{{ $child->name }}\'? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-400 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Hapus subbab">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Form Edit Inline (tersembunyi) --}}
                                <div id="subtype-form-{{ $child->id }}" class="hidden px-6 py-3 bg-blue-50/40 border-t border-blue-100">
                                    <form action="{{ route('admin.tax-types.subtypes.update', [$taxType, $child]) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ $child->name }}"
                                                class="flex-1 rounded-lg bg-white text-slate-700 py-2 px-3 text-sm border border-blue-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400"
                                                placeholder="Nama subbab" required>
                                            <button
                                                type="submit"
                                                onclick="this.disabled=true; this.textContent='Menyimpan…'; this.closest('form').submit()"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors whitespace-nowrap">
                                                Simpan
                                            </button>
                                            <button
                                                type="button"
                                                onclick="toggleEditSubtype('{{ $child->id }}')"
                                                class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 transition-colors">
                                                Batal
                                            </button>
                                        </div>
                                    </form>
                                </div>

                            </li>
                        @endforeach
                    </ul>
                @else
                    <div id="subtypeEmptyState" class="px-6 py-8 text-center text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h8m-8 4h8"/>
                        </svg>
                        <p class="text-sm">Belum ada subbab. Klik <strong>Tambah Subbab</strong> untuk menambahkan.</p>
                    </div>
                @endif
            </div>
        @else
            {{-- Ini adalah subbab, tampilkan info induknya --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold mb-0.5">Ini merupakan subbab</p>
                    <p class="text-amber-700">Induk jenis pajak: <strong>{{ $taxType->parent?->name }}</strong> <span class="font-mono text-xs">({{ $taxType->parent?->code }})</span></p>
                    <p class="text-xs mt-1 text-amber-600">Subbab tidak dapat memiliki subbab sendiri.</p>
                </div>
            </div>
        @endif

    </div>

    {{-- Auto-buka form jika ada error validasi pada subbab --}}
    @if($errors->has('name') && old('_from_subtype'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const wrapper = document.getElementById('subtypeFormWrapper');
                const btn = document.getElementById('toggleSubtypeForm');
                if (wrapper) { wrapper.classList.remove('hidden'); }
                if (btn) { btn.classList.remove('bg-purple-600','text-white'); btn.classList.add('bg-slate-100','text-slate-700'); }
            });
        </script>
    @endif

    <script>
        function toggleEditSubtype(id) {
            const display = document.getElementById('subtype-display-' + id);
            const form    = document.getElementById('subtype-form-'    + id);
            if (!display || !form) return;
            display.classList.toggle('hidden');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                form.querySelector('input[name="name"]')?.focus();
            }
        }
    </script>
</x-layouts.admin>
