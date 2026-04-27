<x-layouts.admin title="Detail Crawling" header="Detail Hasil Crawling">
    <x-slot:headerActions>
        <a href="{{ route('admin.maps-discovery.report') }}"
           class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div class="space-y-4">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Total</p>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Terdaftar</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['terdaftar'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Potensi Baru</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['potensi_baru'] }}</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Alamat</th>
                            <th class="px-4 py-3 text-left">Kategori</th>
                            <th class="px-4 py-3 text-center">Rating</th>
                            <th class="px-4 py-3 text-center">Ulasan</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-left">NPWPD Match</th>
                            <th class="px-4 py-3 text-left">Nama WP Match</th>
                            <th class="px-4 py-3 text-center">Maps</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($results as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-800 font-medium max-w-[200px] truncate">{{ $item->title }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs max-w-[250px] truncate">{{ $item->subtitle }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $item->category }}</td>
                            <td class="px-4 py-3 text-center text-amber-500 font-medium">{{ $item->rating ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ $item->reviews ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->status === 'terdaftar')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Terdaftar</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Potensi Baru</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs font-mono">{{ $item->matched_npwpd ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $item->matched_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->url)
                                <a href="{{ $item->url }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
