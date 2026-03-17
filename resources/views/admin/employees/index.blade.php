<x-layouts.admin title="Daftar Pegawai" header="Pengelolaan Pegawai">
    <x-slot:headerActions>
        <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Tambah Pegawai
        </a>
    </x-slot:headerActions>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        {{-- Search --}}
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" action="{{ route('admin.employees.index') }}">
                <div class="relative max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama atau email..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Nama Pegawai</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Wilayah Tugas (Kecamatan)</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $employee->name }}</div>
                                <div class="text-[10px] text-slate-400">Terdaftar pada {{ $employee->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                {{ $employee->email }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($employee->districts as $district)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $district->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Belum ditugaskan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors" title="Lihat">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.employees.edit', $employee) }}" class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pegawai ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <p>{{ request('search') ? 'Tidak ada pegawai yang cocok.' : 'Belum ada pegawai yang terdaftar.' }}</p>
                                    @if(!request('search'))
                                        <a href="{{ route('admin.employees.create') }}" class="mt-4 text-blue-600 hover:underline font-medium">Tambah pegawai pertama</a>
                                    @endif                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
