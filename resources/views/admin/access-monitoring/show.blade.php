<x-layouts.admin title="Detail Akses: {{ $role->name }}" header="Detail Akses: {{ $role->name }}">
    <x-slot:headerActions>
        <a href="{{ route('admin.access-monitoring.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @forelse($permissionsByGroup as $group => $permissions)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-3 bg-slate-50 border-b border-slate-200">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ $group }}</h3>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($permissions as $permission)
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-100">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-slate-700">{{ $permission->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center text-slate-500">
                    Role ini belum memiliki permission.
                    <a href="{{ route('admin.roles.show', $role) }}" class="text-blue-600 hover:underline ml-1">Kelola permission</a>
                </div>
            @endforelse
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-fit">
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
    </div>
</x-layouts.admin>
