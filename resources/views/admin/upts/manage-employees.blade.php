<x-layouts.admin title="Kelola Pegawai UPT" :header="'Kelola Pegawai: ' . $upt->name">
    <x-slot:headerActions>
        <a href="{{ route('admin.upts.show', $upt) }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Detail UPT
        </a>
    </x-slot:headerActions>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $upt->name }}</h3>
                        <p class="text-sm text-slate-600">Pilih pegawai yang akan ditugaskan ke UPT ini</p>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="px-6 pt-5 pb-0">
                <form method="GET" action="{{ route('admin.upts.employees.manage', $upt) }}">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama atau email pegawai..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </form>
            </div>

            <form action="{{ route('admin.upts.employees.store', $upt) }}" method="POST" class="p-6">
                @csrf

                {{-- Hidden inputs to preserve already-assigned employees across pages --}}
                @foreach($assignedIds as $assignedId)
                    @if(! $allEmployees->contains('id', $assignedId))
                        <input type="hidden" name="user_ids[]" value="{{ $assignedId }}">
                    @endif
                @endforeach

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-slate-700">
                            Daftar Pegawai
                            @if(request('search'))
                                <span class="text-slate-400 font-normal text-xs ml-1">— hasil pencarian "{{ request('search') }}"</span>
                            @endif
                        </label>
                        <p class="text-xs text-slate-500">
                            Total dipilih: <span class="font-semibold">{{ $assignedIds->count() }}</span>
                        </p>
                    </div>

                    <div class="space-y-2 border border-slate-200 rounded-lg p-4">
                        @forelse($allEmployees as $employee)
                            @php
                                $isInThisUpt = $employee->upt_id === $upt->id;
                                $isInOtherUpt = $employee->upt_id !== null && $employee->upt_id !== $upt->id;
                            @endphp
                            <label class="flex items-center gap-3 p-3 border rounded-lg transition-colors
                                {{ $isInOtherUpt ? 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed' : 'border-transparent hover:border-slate-200 hover:bg-slate-50 cursor-pointer' }}">
                                <input
                                    type="checkbox"
                                    name="user_ids[]"
                                    value="{{ $employee->id }}"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    {{ $isInThisUpt ? 'checked' : '' }}
                                    {{ $isInOtherUpt ? 'disabled' : '' }}>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900">{{ $employee->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $employee->email }}</p>
                                </div>
                                @if($isInThisUpt)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold shrink-0">UPT ini</span>
                                @elseif($isInOtherUpt)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-xs font-semibold shrink-0">{{ $employee->upt->name }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs shrink-0">Belum ada UPT</span>
                                @endif
                            </label>
                        @empty
                            <p class="text-sm text-slate-500 text-center py-6">
                                {{ request('search') ? 'Tidak ada pegawai yang cocok.' : 'Belum ada pegawai terdaftar.' }}
                            </p>
                        @endforelse
                    </div>
                </div>

                {{-- Pagination --}}
                @if($allEmployees->hasPages())
                    <div class="mb-4">
                        {{ $allEmployees->links() }}
                    </div>
                @endif

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.upts.show', $upt) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Pegawai
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
