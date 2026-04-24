<x-layouts.admin title="Riwayat Crawling" header="Riwayat Crawling WP">
    <x-slot:headerActions>
        <a href="{{ route('admin.maps-discovery.index') }}"
           class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Crawl Baru
        </a>
    </x-slot:headerActions>

    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Riwayat Pencarian</h4>
                <p class="text-xs text-slate-400 mt-0.5">Data hasil crawling Google Maps yang tersimpan</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">Jenis Pajak</th>
                            <th class="px-4 py-3 text-left">Kecamatan</th>
                            <th class="px-4 py-3 text-left">Keyword</th>
                            <th class="px-4 py-3 text-center">Total</th>
                            <th class="px-4 py-3 text-center">Terdaftar</th>
                            <th class="px-4 py-3 text-center">Potensi Baru</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sessions as $session)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($session->crawled_at)->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $users[$session->user_id] ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $session->tax_type_code ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $session->district_name ?? 'Semua' }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $session->keyword ?? '-' }}</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-800">{{ $session->total }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ $session->terdaftar }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ $session->potensi_baru }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.maps-discovery.report-detail', $session->session_id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-400">Belum ada data crawling.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
