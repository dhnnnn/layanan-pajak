<x-layouts.admin title="Daftar Jenis Pajak" header="Pengelolaan Jenis Pajak">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-types.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Jenis Pajak
        </a>
    </x-slot:headerActions>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Nama Jenis Pajak</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4 text-center">Jumlah Target</th>
                        <th class="px-6 py-4 text-center">Jumlah Realisasi</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxTypes as $taxType)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $taxType->name }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded border border-slate-200">{{ $taxType->code }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{ $taxType->tax_targets_count }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{ $taxType->tax_realizations_count }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.tax-types.edit', $taxType) }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">Edit</a>
                                <form action="{{ route('admin.tax-types.destroy', $taxType) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jenis pajak ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition-colors">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p>Belum ada jenis pajak yang terdaftar.</p>
                                    <a href="{{ route('admin.tax-types.create') }}" class="mt-4 text-blue-600 hover:underline font-medium">Tambah jenis pajak pertama</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($taxTypes->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $taxTypes->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
