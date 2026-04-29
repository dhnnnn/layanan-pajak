<x-layouts.admin title="Data Potensi WP" header="Data Potensi Wajib Pajak">
    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            @if($stats['belum_dicek'] > 0)
            @can('manage maps-discovery')
            <button id="syncBtn" onclick="syncData()" 
                    class="inline-flex items-center gap-2 px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-sm transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sinkronkan (<span id="syncCount">{{ $stats['belum_dicek'] }}</span>)
            </button>
            @endcan
            @endif
            @can('manage maps-discovery')
            <a href="{{ route('admin.maps-discovery.index') }}"
               class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Crawl Baru
            </a>
            @endcan
        </div>
    </x-slot:headerActions>

    <div class="space-y-4">
        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-xs text-slate-500">Belum Dicek</p>
                <p class="text-2xl font-bold text-amber-500" id="belumDicekStat">{{ number_format($stats['belum_dicek']) }}</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 relative" style="z-index: 1000;">
            <form id="filterForm" method="GET" action="{{ route('admin.maps-discovery.report') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cari Nama / Alamat</label>
                    <input type="text" name="search" id="searchInput" value="{{ $filters['search'] ?? '' }}" placeholder="Ketik nama atau alamat..."
                           class="w-full rounded-xl border border-slate-200 px-4 py-2 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all" />
                </div>

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
                            ['id' => 'belum_dicek', 'name' => 'Belum Dicek'],
                        ]"
                    />
                </div>

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

        {{-- Map --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="height: 320px; position: relative; z-index: 1;">
            <div id="reportMap" style="height: 100%; width: 100%;"></div>
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
                            <th class="px-4 py-3 text-center">Aksi</th>
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
                                @elseif($item->status === 'potensi_baru')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Potensi Baru</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Belum Dicek</span>
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
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.maps-discovery.analisis-detail', $item->id) }}" 
                                   class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors"
                                   title="Analisis Potensi Pajak">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    Analisis
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-400">Belum ada data. Mulai crawling untuk menemukan potensi WP baru.</td>
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // Initialize map
    var reportMap = L.map('reportMap').setView([-7.6455, 112.9075], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(reportMap);

    var markers = [];

    function escHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function statusColor(status) {
        if (status === 'terdaftar') return { bg: 'bg-green-500', border: 'border-green-700', label: 'Terdaftar', cls: 'bg-green-100 text-green-700' };
        if (status === 'potensi_baru') return { bg: 'bg-red-500', border: 'border-red-700', label: 'Potensi Baru', cls: 'bg-red-100 text-red-700' };
        return { bg: 'bg-amber-500', border: 'border-amber-700', label: 'Belum Dicek', cls: 'bg-amber-100 text-amber-700' };
    }

    function loadMapData() {
        var params = new URLSearchParams({
            status: document.getElementById('statusHidden')?.value || '',
            tax_type_code: document.getElementById('taxTypeHidden')?.value || '',
            district_name: document.getElementById('districtHidden')?.value || '',
            search: document.getElementById('searchInput')?.value || '',
        });

        fetch('{{ route("admin.maps-discovery.report.map-data") }}?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(function(points) {
            // Clear existing markers
            markers.forEach(m => reportMap.removeLayer(m));
            markers = [];

            points.forEach(function(p) {
                if (!p.latitude || !p.longitude) return;
                var s = statusColor(p.status);
                var icon = L.divIcon({
                    className: '',
                    html: '<div class="w-3.5 h-3.5 rounded-full ' + s.bg + ' border-2 ' + s.border + ' shadow-md"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                    popupAnchor: [0, -10],
                });

                var popup = '<div class="text-sm max-w-[240px]">'
                    + '<p class="font-semibold text-slate-800">' + escHtml(p.title) + '</p>'
                    + '<p class="text-xs text-slate-500 mt-0.5">' + escHtml(p.subtitle) + '</p>'
                    + (p.category ? '<p class="text-xs text-slate-400 mt-0.5">' + escHtml(p.category) + '</p>' : '')
                    + (p.rating ? '<p class="text-xs text-amber-500 mt-1">★ ' + p.rating + (p.reviews ? ' (' + p.reviews + ')' : '') + '</p>' : '')
                    + '<div class="mt-1.5"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' + s.cls + '">' + s.label + '</span></div>'
                    + (p.matched_npwpd ? '<p class="text-xs text-slate-600 mt-1"><span class="font-medium">NPWPD:</span> ' + escHtml(p.matched_npwpd) + '</p>' : '')
                    + (p.matched_name ? '<p class="text-xs text-slate-600"><span class="font-medium">WP:</span> ' + escHtml(p.matched_name) + '</p>' : '')
                    + (p.url ? '<a href="' + escHtml(p.url) + '" target="_blank" class="inline-flex items-center gap-1 mt-1.5 text-xs text-blue-600 hover:text-blue-800">Buka Maps ↗</a>' : '')
                    + '</div>';

                var m = L.marker([p.latitude, p.longitude], { icon: icon })
                    .bindPopup(popup, { maxWidth: 240 })
                    .addTo(reportMap);
                markers.push(m);
            });

            if (markers.length > 0) {
                var group = L.featureGroup(markers);
                reportMap.fitBounds(group.getBounds().pad(0.1));
            }
        })
        .catch(function(e) { console.error('Map data error:', e); });
    }

    // Load on page ready
    document.addEventListener('DOMContentLoaded', function() {
        loadMapData();

        // Reload map when filters change
        ['statusHidden', 'taxTypeHidden', 'districtHidden'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', function() { setTimeout(loadMapData, 300); });
        });

        var searchInput = document.getElementById('searchInput');
        var searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                clearTimeout(searchTimer);
                if (e.key === 'Enter') { loadMapData(); return; }
                searchTimer = setTimeout(loadMapData, 600);
            });
        }

        // Auto-submit filter saat dropdown berubah
        var form = document.getElementById('filterForm');
        if (!form) return;
        ['statusHidden', 'taxTypeHidden', 'districtHidden'].forEach(function(id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', function() { form.submit(); });
            }
        });
        if (searchInput) {
            var timer = null;
            searchInput.addEventListener('keyup', function(e) {
                clearTimeout(timer);
                if (e.key === 'Enter') { form.submit(); return; }
                timer = setTimeout(function() { form.submit(); }, 500);
            });
        }
    });

    // Sinkronkan data — batch per 50, loop sampai selesai
    async function syncData() {
        var btn = document.getElementById('syncBtn');
        var countEl = document.getElementById('syncCount');
        var statEl = document.getElementById('belumDicekStat');
        if (!btn) return;

        btn.disabled = true;
        btn.classList.add('opacity-60', 'cursor-not-allowed');
        var originalText = btn.innerHTML;
        btn.innerHTML = '<svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Sinkronisasi...';

        var totalSynced = 0;

        try {
            while (true) {
                var response = await fetch('{{ route("admin.maps-discovery.sync") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                var data = await response.json();
                totalSynced += data.synced || 0;

                if (data.remaining === 0 || data.synced === 0) {
                    break;
                }

                if (countEl) countEl.textContent = data.remaining;
                if (statEl) statEl.textContent = data.remaining.toLocaleString('id-ID');
            }

            window.location.reload();
        } catch (e) {
            alert('Gagal sinkronkan data: ' + e.message);
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
            btn.innerHTML = originalText;
        }
    }
    </script>
    @endpush

</x-layouts.admin>
