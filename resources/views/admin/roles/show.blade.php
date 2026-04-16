<x-layouts.admin title="Detail Role: {{ $role->name }}" header="Detail Role: {{ $role->name }}">
    <x-slot:headerActions>
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Permission Assignment --}}
        <div class="lg:col-span-2 space-y-4">
            @if($role->isSystemRole() && $role->name === 'admin')
                <div class="flex items-start gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Ini adalah role Administrator sistem. Perubahan permission akan mempengaruhi akses admin.
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-800">Kelola Hak Akses</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Centang akses yang ingin diberikan ke role ini.</p>
                </div>

                <form action="{{ route('admin.roles.permissions.sync', $role) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Hidden inputs untuk semua permission yang dicentang --}}
                    {{-- Diisi via JS saat submit --}}

                    <div class="divide-y divide-slate-100">
                        @foreach($featureMatrix as $group)
                            <div class="px-6 py-4">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">{{ $group['group'] }}</p>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-slate-500">
                                            <th class="text-left font-medium pb-2 w-1/2">Fitur</th>
                                            <th class="text-center font-medium pb-2 w-1/6">Lihat</th>
                                            <th class="text-center font-medium pb-2 w-1/6">Kelola</th>
                                            <th class="text-center font-medium pb-2 w-1/6">Hapus</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        @foreach($group['features'] as $feature)
                                            <tr class="hover:bg-slate-50/60 transition-colors">
                                                <td class="py-2.5 pr-4 font-medium text-slate-700">{{ $feature['label'] }}</td>

                                                @foreach(['lihat', 'kelola', 'hapus'] as $col)
                                                    @php
                                                        $permName = $feature['permissions'][$col] ?? null;
                                                        $perm = $permName ? $permissionMap->get($permName) : null;
                                                    @endphp
                                                    <td class="py-2.5 text-center">
                                                        @if($perm)
                                                            <input type="checkbox"
                                                                name="permissions[]"
                                                                value="{{ $perm->id }}"
                                                                @checked(in_array($permName, $rolePermissionNames))
                                                                class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                                                title="{{ $permName }}">
                                                        @else
                                                            <span class="inline-block w-4 h-px bg-slate-200 mx-auto"></span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                            Simpan Hak Akses
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Users with this role --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-800">User dengan Role Ini</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $role->users->count() }} user</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($role->users as $user)
                        <div class="px-6 py-3 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold text-slate-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-6 text-center text-sm text-slate-400">Belum ada user dengan role ini.</div>
                    @endforelse
                </div>
            </div>

            {{-- Legend --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-2">
                <p class="text-xs font-semibold text-slate-600 mb-2">Keterangan Kolom</p>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-700 w-12">Lihat</span>
                    <span>Hanya bisa melihat data</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-700 w-12">Kelola</span>
                    <span>Bisa tambah & ubah data</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-700 w-12">Hapus</span>
                    <span>Bisa menghapus data</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
