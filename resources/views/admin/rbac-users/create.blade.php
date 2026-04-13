<x-layouts.admin title="Tambah User" header="Tambah User Baru">
    <x-slot:headerActions>
        <a href="{{ route('admin.rbac-users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form action="{{ route('admin.rbac-users.store') }}" method="POST" class="space-y-5" id="rbacUserForm">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                            class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('name') ring-2 ring-red-500/20 @enderror"
                            required>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('email') ring-2 ring-red-500/20 @enderror"
                            required>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password"
                            class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('password') ring-2 ring-red-500/20 @enderror"
                            required>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                            required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Role <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" id="rolesContainer">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:border-blue-200 hover:bg-blue-50/50 transition-colors cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    @checked(is_array(old('roles')) && in_array($role->id, old('roles')))
                                    class="role-checkbox rounded text-blue-600 focus:ring-2 focus:ring-blue-500"
                                    data-role-name="{{ $role->name }}">
                                <span class="text-sm text-slate-700">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- UPT field — shown only when kepala_upt is selected --}}
                <div id="uptField" class="{{ old('upt_id') ? '' : 'hidden' }}">
                    <label for="upt_id" class="block text-sm font-semibold text-slate-700 mb-1">UPT <span class="text-red-500">*</span></label>
                    <select name="upt_id" id="upt_id"
                        class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('upt_id') ring-2 ring-red-500/20 @enderror">
                        <option value="">-- Pilih UPT --</option>
                        @foreach($upts as $upt)
                            <option value="{{ $upt->id }}" @selected(old('upt_id') === $upt->id)>{{ $upt->name }}</option>
                        @endforeach
                    </select>
                    @error('upt_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.rbac-users.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.role-checkbox');
            const uptField = document.getElementById('uptField');

            function toggleUptField() {
                const hasKepalaUpt = Array.from(checkboxes).some(
                    cb => cb.checked && cb.dataset.roleName === 'kepala_upt'
                );
                uptField.classList.toggle('hidden', !hasKepalaUpt);
            }

            checkboxes.forEach(cb => cb.addEventListener('change', toggleUptField));
            toggleUptField();
        });
    </script>
</x-layouts.admin>
