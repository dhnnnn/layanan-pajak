@php
    $user = auth()->user();
    if ($user->hasRole('admin') || $user->isKepalaUpt()) {
        $layout = 'layouts.admin';
    } elseif ($user->hasRole('pegawai')) {
        $layout = 'layouts.field-officer';
    } else {
        $layout = 'layouts.employee';
    }
@endphp

<x-dynamic-component :component="$layout" title="Edit Profil" header="Edit Profil">

    <div class="max-w-lg mx-auto space-y-6">

        {{-- Info Card --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-black shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-bold text-slate-900">{{ $user->name }}</p>
                <p class="text-xs text-slate-400">{{ $user->email }}</p>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    {{ $user->getRoleNames()->implode(', ') }}
                </p>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('profile.update') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            @csrf
            @method('PATCH')

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">Informasi Akun</h3>
            </div>

            <div class="p-6 space-y-5">

                {{-- Nama --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-widest mb-1.5">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all @error('name') border-rose-400 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-widest mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all @error('email') border-rose-400 @enderror">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Ganti Password <span class="font-normal normal-case text-slate-400">(kosongkan jika tidak ingin mengubah)</span></p>

                    {{-- Password Lama --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-widest mb-1.5">Password Lama</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all @error('current_password') border-rose-400 @enderror">
                        @error('current_password')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Baru --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-widest mb-1.5">Password Baru</label>
                        <input type="password" name="password" autocomplete="new-password"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all @error('password') border-rose-400 @enderror">
                        @error('password')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-widest mb-1.5">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all">
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <a href="{{ url()->previous() }}"
                    class="text-sm text-slate-500 hover:text-slate-700 transition-colors">Batal</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>

    </div>

</x-dynamic-component>
