<x-layouts.employee title="Data Realisasi Saya" header="Riwayat Input Realisasi Pajak">
    <x-slot:headerActions>
        <a href="{{ route('pegawai.realizations.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Input Realisasi Manual
        </a>
    </x-slot:headerActions>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Tahun</th>
                        <th class="px-6 py-4">Jenis Pajak</th>
                        <th class="px-6 py-4">Kecamatan</th>
                        <th class="px-6 py-4 text-center">Update Terakhir</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($realizations as $realization)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900">
                                {{ $realization->year }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">{{ $realization->taxType->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $realization->taxType->code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                {{ $realization->district->name }}
                            </td>
                            <td class="px-6 py-4 text-center text-[10px] font-mono text-slate-400">
                                {{ $realization->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('pegawai.realizations.show', $realization) }}" class="text-slate-600 hover:text-slate-900 font-medium transition-colors">Lihat</a>
                                <a href="{{ route('pegawai.realizations.edit', $realization) }}" class="text-emerald-600 hover:text-emerald-800 font-medium transition-colors">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p>Anda belum menginput data realisasi pajak.</p>
                                    <a href="{{ route('pegawai.realizations.create') }}" class="mt-4 text-emerald-600 hover:underline font-medium">Mulai input data pertama</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($realizations->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $realizations->links() }}
            </div>
        @endif
    </div>
</x-layouts.employee>
