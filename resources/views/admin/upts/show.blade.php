<x-layouts.admin title="Detail UPT" :header="'Detail UPT: ' . $upt->name">
    <x-slot:headerActions>
        @if(!auth()->user()->isKepalaUpt())
        <a href="{{ route('admin.upts.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
        @endif
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- UPT Info --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi UPT</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Nama UPT</label>
                    <p class="text-sm font-medium text-slate-900 mt-1">{{ $upt->name }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Kode</label>
                    <p class="text-sm font-mono text-slate-900 mt-1">{{ $upt->code }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Deskripsi</label>
                    <p class="text-sm text-slate-900 mt-1">{{ $upt->description ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Wilayah Cakupan UPT --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Wilayah Cakupan UPT</h3>
                <a href="{{ route('admin.upts.districts', $upt) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Kelola Wilayah
                </a>
            </div>
            <div class="p-6">
                @if($upt->districts->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($upt->districts as $district)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                {{ $district->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">Belum ada wilayah yang ditugaskan ke UPT ini.</p>
                @endif
            </div>
        </div>

        {{-- Pegawai dan Wilayah Mereka --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800">Pegawai dan Wilayah Tugasnya</h3>
                    <p class="text-xs text-slate-500 mt-1">Setiap pegawai dapat ditugaskan ke kecamatan dalam cakupan UPT ini</p>
                </div>
                <a href="{{ route('admin.upts.employees.manage', $upt) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Kelola Pegawai
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Nama Pegawai</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Wilayah Tugas</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($upt->users as $user)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->districts->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->districts as $district)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-50 text-green-700 rounded text-xs">
                                                    {{ $district->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Belum ada wilayah</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.upts.employees.districts', [$upt, $user]) }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                        Atur Wilayah
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                    <p>Belum ada pegawai di UPT ini.</p>
                                    <a href="{{ route('admin.upts.employees.manage', $upt) }}" class="mt-2 text-blue-600 hover:underline text-sm font-medium">
                                        Tambah pegawai sekarang
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.admin>
