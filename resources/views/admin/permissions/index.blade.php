<x-layouts.admin title="Manajemen Permission" header="Manajemen Permission">
    <x-slot:headerActions>
        <a href="{{ route('admin.permissions.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Permission
        </a>
    </x-slot:headerActions>

    @if($permissions->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <p class="text-slate-500 mb-3">Belum ada permission yang terdaftar.</p>
            <p class="text-xs text-slate-400">Jalankan <code class="bg-slate-100 px-1.5 py-0.5 rounded">php artisan db:seed --class=PermissionSeeder</code> untuk mengisi permission awal.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($permissions as $group => $groupPermissions)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ $group }}</h2>
                        <span class="text-xs text-slate-400">{{ $groupPermissions->count() }} permission</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($groupPermissions as $permission)
                            <div class="px-6 py-3 flex items-center justify-between">
                                <span class="text-sm text-slate-700 font-medium">{{ $permission->name }}</span>
                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST"
                                    onsubmit="return confirm('Hapus permission \'{{ $permission->name }}\'? Permission ini akan dicabut dari semua role.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
