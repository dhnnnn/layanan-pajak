<x-layouts.admin title="Data Potensi WP" header="Data Potensi Wajib Pajak">
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
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Total Data</p>
                <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Terdaftar</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats['terdaftar']) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Potensi Baru</p>
                <p class="text-2xl font-bold text-red-600">{{ number_format($stats['potensi_baru']) }}</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form id="filterForm" method="GET" action="{{ route('admin.maps-discovery.report') }}" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cari Nama / Alamat</label>
                    <input type="text" name="search" id="searchInput" value="{{ $filters['search'] ?? '' }}" placeholder="Ketik nama atau alamat..."
                           class="w-full rounded-xl border border-slate-200 px-4 py-2 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all" />
                </div>

                {{-- Status --}}
                <div class="w-40">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                    <input type="hidden" name="status" id="statusHidden" value="{{ $filters['status'] ?? '' }}">
                    <x-searchable-select
                        target-input-id="statusHidden"
                        placeholder="Semua Status"
                        :value="$filters['status'] ?? ''"
                        :options="[
                            ['id' => 'terdaftar', 'name' => 'Terdaftar'],
                            ['id' => 'potensi_baru', 'name' => 'Potensi Baru'],
                        ]"
                    />
                </div>

                {{-- Jenis Pajak --}}
                <div class="w-48">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Pajak</label>
                    <input type="hidden" name="tax_type_code" id="taxTypeHidden" value="{{ $filters['tax_type_code'] ?? '' }}">
                    <x-searchable-select
                        target-input-id="taxTypeHidden"
                        placeholder="Semua Jenis Pajak"
                        :value="$filters['tax_type_code'] ?? ''"
                        :options="$taxTypeCodes->map(fn($code) => ['id' => $code, 'name' => $taxTypeNames[$code] ?? $code])->values()->toArray()"
                    />
                </div>

                {{-- Kecamatan --}}
                <div class="w-48">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kecamatan</label>
                    <input type="hidden" name="district_name" id="districtHidden" value="{{ $filters['district_name'] ?? '' }}">
                    <x-searchable-select
                        target-input-id="districtHidden"
                        placeholder="Semua Kecamatan"
                        :value="$filters['district_name'] ?? ''"
                        :options="$districtNames->map(fn($name) => ['id' => $name, 'name' => $name])->toArray()"
                    />
                </div>

                @if(array_filter($filters ?? []))
                    <a href="{{ route('admin.maps-discovery.report') }}" class="px-4 py-2 min-h-[38px] flex items-center bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium rounded-xl transition-colors">
                        Reset
                    </a>
                @endif
            </form>
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
                            <th class="px-4 py-3 text-left">Kecamatan</th>
                            <th class="px-4 py-3 text-center">Rating</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-left">NPWPD Match</th>
                            <th class="px-4 py-3 text-left">Nama WP Match</th>
                            <th class="px-4 py-3 text-center">Maps</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($results as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-800 font-medium max-w-[200px] truncate" title="{{ $item->title }}">{{ $item->title }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs max-w-[250px] truncate" title="{{ $item->subtitle }}">{{ $item->subtitle }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs" title="{{ $item->category }}">{{ $item->category }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $item->district_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->rating)
                                    <span class="text-amber-500 font-medium">{{ $item->rating }}</span>
                                    @if($item->reviews)
                                        <span class="text-slate-400 text-xs">({{ number_format($item->reviews) }})</span>
                                    @endif
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->status === 'terdaftar')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Terdaftar</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Potensi Baru</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs font-mono">{{ $item->matched_npwpd ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs max-w-[180px] truncate" title="{{ $item->matched_name }}">{{ $item->matched_name ?? '-' }}</td>
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
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-400">Belum ada data. Mulai crawling untuk menemukan potensi WP baru.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($results->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $results->links() }}
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('filterForm');
        if (!form) return;

        // Auto-submit saat dropdown berubah
        var hiddenIds = ['statusHidden', 'taxTypeHidden', 'districtHidden'];
        hiddenIds.forEach(function(id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', function() {
                    form.submit();
                });
            }
        });

        // Auto-submit search setelah berhenti mengetik (debounce 500ms)
        var searchInput = document.getElementById('searchInput');
        var searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                clearTimeout(searchTimer);
                if (e.key === 'Enter') {
                    form.submit();
                    return;
                }
                searchTimer = setTimeout(function() {
                    form.submit();
                }, 500);
            });
        }
    });
    </script>
    @endpush

</x-layouts.admin>
