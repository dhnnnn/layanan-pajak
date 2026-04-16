<x-layouts.admin title="Target Tambahan APBD" header="Target Tambahan APBD">
    <x-slot:headerActions>
        <a href="{{ route('admin.upt-additional-targets.create', ['year' => $selectedYear]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Target
        </a>
    </x-slot:headerActions>

    <div class="space-y-5">

        {{-- Filter Tahun --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.upt-additional-targets.index') }}" class="flex items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                    <select name="year" onchange="this.form.submit()"
                        class="rounded-lg bg-slate-50 text-slate-700 py-2 px-3 text-sm border border-slate-200 focus:ring-2 focus:ring-blue-500/20">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $yr == $selectedYear ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-slate-400 pb-2">Target tambahan global di luar target APBD awal, berlaku untuk semua UPP.</p>
            </form>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl px-4 py-3 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-800">Daftar Target Tambahan APBD — {{ $selectedYear }}</p>
                @if($additionalTargets->isNotEmpty())
                    <p class="text-xs text-slate-500">
                        Total:
                        <span class="font-bold text-blue-700">Rp {{ number_format($additionalTargets->sum('additional_target'), 0, ',', '.') }}</span>
                    </p>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Kode Ayat</th>
                            <th class="px-4 py-2.5 text-left">Jenis Pajak</th>
                            <th class="px-4 py-2.5 text-right">Target Tambahan</th>
                            <th class="px-4 py-2.5 text-left">Catatan</th>
                            <th class="px-4 py-2.5 text-left">Diperbarui Oleh</th>
                            <th class="px-4 py-2.5 text-left">Tanggal</th>
                            <th class="px-4 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($additionalTargets as $target)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $target->no_ayat }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $ayatLabels[$target->no_ayat] ?? $target->no_ayat }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-blue-700">
                                    +Rp {{ number_format($target->additional_target, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs max-w-xs truncate">{{ $target->notes ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 text-xs">{{ $target->creator->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-400 text-xs">{{ $target->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="{{ route('admin.upt-additional-targets.create', ['no_ayat' => $target->no_ayat, 'year' => $target->year]) }}"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                        <form method="POST" action="{{ route('admin.upt-additional-targets.destroy', $target) }}"
                                            onsubmit="return confirm('Hapus target tambahan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <svg class="w-12 h-12 text-slate-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <p class="text-slate-400 text-sm">Belum ada target tambahan untuk tahun {{ $selectedYear }}.</p>
                                    <a href="{{ route('admin.upt-additional-targets.create', ['year' => $selectedYear]) }}"
                                        class="mt-3 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Tambah sekarang
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
