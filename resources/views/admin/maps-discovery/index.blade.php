<x-layouts.admin title="Maps WP Discovery" header="Maps WP Discovery">
    <x-slot:headerActions>
        <a href="{{ route('admin.maps-discovery.report') }}"
           class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Riwayat Crawling
        </a>
    </x-slot:headerActions>

    <div x-data="mapsDiscovery()" class="flex flex-col lg:flex-row gap-5 -mx-6 px-6" style="overflow: hidden;">

        {{-- Sidebar Kiri (w-1/3) — scrollable, no visible scrollbar --}}
        <div class="w-full lg:w-1/3 overflow-y-auto scrollbar-hide space-y-4 shrink-0">

            {{-- Filter Form --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                <p class="text-sm font-semibold text-slate-800">Filter Pencarian</p>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Pajak</label>
                    <input type="hidden" id="taxTypeCodeInput" value="">
                    <x-searchable-select
                        target-input-id="taxTypeCodeInput"
                        placeholder="Pilih Jenis Pajak"
                        :options="$taxTypes->map(fn($t) => ['id' => $t->simpadu_code, 'name' => $t->name])->toArray()"
                        :value="''"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kecamatan</label>
                    <input type="hidden" id="districtIdInput" value="">
                    <x-searchable-select
                        target-input-id="districtIdInput"
                        placeholder="Semua Kecamatan"
                        :options="$districts->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toArray()"
                        :value="''"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Keyword Tambahan</label>
                    <input type="text" x-model="keyword" placeholder="Contoh: warung, toko..."
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Maks. Hasil Crawling</label>
                    <input type="number" x-model.number="maxResults" min="5" max="100" step="5"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                </div>

                <button @click="crawl()" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition-colors">
                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Crawling...' : 'Crawl Data'"></span>
                </button>
            </div>

            {{-- Error Notification --}}
            <div x-show="error" x-cloak class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span x-text="error"></span>
            </div>

            {{-- Message Notification --}}
            <div x-show="message && !error" x-cloak class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-700 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span x-text="message"></span>
            </div>

            {{-- Result List --}}
            <div class="bg-white rounded-xl border border-slate-200 flex flex-col overflow-hidden" style="min-height: 200px; max-height: 50vh;">
                <div class="px-4 py-3 border-b border-slate-100 shrink-0">
                    <p class="text-sm font-semibold text-slate-800">
                        <span x-show="results.length > 0" x-text="'Ditemukan ' + results.length + ' lokasi'"></span>
                        <span x-show="results.length === 0 && !loading">Hasil Pencarian</span>
                        <span x-show="loading">Mencari lokasi...</span>
                    </p>
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    {{-- Loading skeleton --}}
                    <template x-if="loading">
                        <div class="p-4 space-y-3">
                            <template x-for="i in 4" :key="i">
                                <div class="animate-pulse space-y-2">
                                    <div class="h-3 bg-slate-200 rounded w-3/4"></div>
                                    <div class="h-2 bg-slate-200 rounded w-full"></div>
                                    <div class="h-2 bg-slate-200 rounded w-1/3"></div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="!loading && results.length === 0">
                        <div class="p-6 text-center text-sm text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Gunakan filter di atas untuk mencari lokasi bisnis.
                        </div>
                    </template>

                    {{-- Result items --}}
                    <template x-if="!loading && results.length > 0">
                        <div>
                            <template x-for="(item, index) in results" :key="item.place_id || index">
                                <button @click="panToMarker(index)"
                                        class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-800 truncate" x-text="item.title"></p>
                                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2" x-text="item.subtitle"></p>
                                            <p class="text-xs text-slate-400 mt-1" x-text="item.category"></p>
                                            <div class="flex items-center gap-2 mt-1" x-show="item.rating || item.reviews">
                                                <span class="flex items-center gap-0.5 text-xs text-amber-500" x-show="item.rating">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    <span x-text="item.rating"></span>
                                                </span>
                                                <span class="text-xs text-slate-400" x-show="item.reviews" x-text="'(' + item.reviews + ' ulasan)'"></span>
                                            </div>
                                        </div>
                                        <span x-show="item.status === 'terdaftar'"
                                              class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            Terdaftar
                                        </span>
                                        <span x-show="item.status === 'potensi_baru'"
                                              class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            Potensi Baru
                                        </span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Main Content Kanan (w-2/3) — fixed, no scroll --}}
        <div class="w-full lg:w-2/3 flex flex-col gap-4 overflow-hidden relative">

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 gap-4 shrink-0">
                {{-- Terdaftar --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Terdaftar</p>
                        <p class="text-xl font-bold text-green-600" x-text="stats.terdaftar">0</p>
                    </div>
                </div>

                {{-- Potensi Baru --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Potensi Baru</p>
                        <p class="text-xl font-bold text-red-600" x-text="stats.potensi_baru">0</p>
                    </div>
                </div>
            </div>

            {{-- Leaflet Map --}}
            <div class="bg-white rounded-xl border border-slate-200 flex-1 overflow-hidden min-h-[400px]">
                <div x-ref="mapContainer" class="w-full h-full"></div>
            </div>

            {{-- Loading Overlay — covers entire right panel, centered --}}
            <div x-show="loading" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-white/80 backdrop-blur-sm z-[1000] flex items-center justify-center"
                 style="display: none;">
                <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 w-72 text-center">
                    <div class="relative w-14 h-14 mx-auto mb-3">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4"/>
                            <circle cx="32" cy="32" r="28" fill="none" stroke="#3b82f6" stroke-width="4"
                                    stroke-linecap="round" :stroke-dasharray="'175.9'"
                                    :stroke-dashoffset="175.9 - (175.9 * progress / 100)"
                                    class="transition-all duration-500"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-blue-600"
                              x-text="Math.round(progress) + '%'"></span>
                    </div>
                    <p class="font-bold text-slate-800 text-sm mb-1">Crawling Google Maps</p>
                    <p class="text-xs text-slate-500 mb-3">Mencari lokasi bisnis...</p>
                    <div class="space-y-1.5 text-left mb-4">
                        <template x-for="(step, i) in [{n:1,t:'Mengirim request'},{n:2,t:'Scraping data lokasi'},{n:3,t:'Mencocokkan database WP'}]" :key="i">
                            <div class="flex items-center gap-2 text-xs">
                                <svg x-show="crawlStep > step.n" class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <svg x-show="crawlStep === step.n" class="w-3.5 h-3.5 text-blue-500 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <div x-show="crawlStep < step.n" class="w-3.5 h-3.5 rounded-full border-2 border-slate-300 shrink-0"></div>
                                <span :class="crawlStep >= step.n ? 'text-slate-700' : 'text-slate-400'" x-text="step.t" class="flex-1"></span>
                                <span x-show="crawlStep > step.n" class="text-green-600 font-bold text-[9px]">SELESAI</span>
                                <span x-show="crawlStep === step.n" class="text-blue-600 font-bold text-[9px]">PROSES</span>
                            </div>
                        </template>
                    </div>
                    <button @click="cancelCrawl()" class="w-full px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg border border-red-200 transition-colors">
                        Batalkan Crawling
                    </button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    /* Override parent main scroll — maps page is full viewport */
    main.flex-1 { overflow: hidden !important; }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Fix footer spacing for maps page
document.addEventListener('DOMContentLoaded', () => {
    // Calculate exact available height: viewport - header - main padding - footer
    const header = document.querySelector('header');
    const footer = document.querySelector('main > footer');
    const main = document.querySelector('main.flex-1');
    const container = document.querySelector('[x-data="mapsDiscovery()"]');
    if (header && footer && main && container) {
        const mainPadding = parseFloat(getComputedStyle(main).paddingTop) + parseFloat(getComputedStyle(main).paddingBottom);
        const footerHeight = footer.offsetHeight + parseFloat(getComputedStyle(footer).marginTop);
        const available = window.innerHeight - header.offsetHeight - mainPadding - footerHeight;
        container.style.height = available + 'px';
    }
});
</script>
<script>
function mapsDiscovery() {
    const JATIM_BOUNDS = { latMin: -8.5, latMax: -7.0, lngMin: 111.0, lngMax: 114.5 };
    const CRAWL_URL = '{{ route('admin.maps-discovery.crawl') }}';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    return {
        loading: false,
        results: [],
        error: null,
        message: null,
        stats: { terdaftar: 0, potensi_baru: 0 },
        map: null,
        markers: [],
        taxTypeCode: '',
        districtId: '',
        keyword: '',
        maxResults: 20,
        progress: 0,
        crawlStep: 0,
        abortController: null,
        progressInterval: null,

        init() {
            this.$nextTick(() => {
                this.map = L.map(this.$refs.mapContainer).setView([-7.6455, 112.9075], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.map);

                // Sync searchable-select hidden inputs ke Alpine state
                document.getElementById('taxTypeCodeInput').addEventListener('change', (e) => {
                    this.taxTypeCode = e.target.value;
                });
                document.getElementById('districtIdInput').addEventListener('change', (e) => {
                    this.districtId = e.target.value;
                });
            });
        },

        async crawl() {
            if (!this.taxTypeCode && !this.keyword.trim()) {
                this.error = 'Pilih jenis pajak atau isi keyword pencarian.';
                return;
            }

            this.loading = true;
            this.error = null;
            this.message = null;
            this.progress = 0;
            this.crawlStep = 1;

            // AbortController for cancel
            this.abortController = new AbortController();

            // Simulate progress
            this.progressInterval = setInterval(() => {
                if (this.progress < 30 && this.crawlStep === 1) {
                    this.progress += 2;
                } else if (this.progress < 70 && this.crawlStep === 2) {
                    this.progress += 1.5;
                } else if (this.progress < 90 && this.crawlStep === 3) {
                    this.progress += 3;
                }
            }, 300);

            // Move to step 2 after short delay (scraper is working)
            setTimeout(() => { if (this.loading) this.crawlStep = 2; }, 2000);

            try {
                const response = await fetch(CRAWL_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        tax_type_code: this.taxTypeCode,
                        district_id: this.districtId,
                        keyword: this.keyword,
                        max_results: this.maxResults,
                    }),
                    signal: this.abortController.signal,
                });

                // Step 3: matching
                this.crawlStep = 3;
                this.progress = 80;

                const data = await response.json();

                if (!response.ok) {
                    this.error = data.error || 'Terjadi kesalahan saat mengambil data.';
                    if (data.errors) {
                        const firstError = Object.values(data.errors)[0];
                        this.error = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                    return;
                }

                // Done
                this.crawlStep = 4;
                this.progress = 100;

                this.results = data.results || [];
                this.stats = data.stats || { terdaftar: 0, potensi_baru: 0 };
                this.message = data.message || null;

                // Small delay to show 100% before hiding
                await new Promise(r => setTimeout(r, 500));
                this.renderMarkers();
            } catch (e) {
                if (e.name === 'AbortError') {
                    this.error = 'Crawling dibatalkan.';
                } else {
                    this.error = 'Gagal terhubung ke server. Periksa koneksi jaringan Anda.';
                }
            } finally {
                clearInterval(this.progressInterval);
                this.loading = false;
                this.abortController = null;
            }
        },

        cancelCrawl() {
            if (this.abortController) {
                this.abortController.abort();
            }
        },

        isValidCoord(lat, lng) {
            return lat !== null && lng !== null &&
                   lat >= JATIM_BOUNDS.latMin && lat <= JATIM_BOUNDS.latMax &&
                   lng >= JATIM_BOUNDS.lngMin && lng <= JATIM_BOUNDS.lngMax;
        },

        renderMarkers() {
            this.markers.forEach(m => this.map.removeLayer(m));
            this.markers = [];

            this.results.forEach((item, index) => {
                if (!this.isValidCoord(item.latitude, item.longitude)) return;

                const isRegistered = item.status === 'terdaftar';
                const colorClass = isRegistered ? 'bg-green-500' : 'bg-red-500';
                const borderClass = isRegistered ? 'border-green-700' : 'border-red-700';

                const icon = L.divIcon({
                    className: '',
                    html: `<div class="w-4 h-4 rounded-full ${colorClass} border-2 ${borderClass} shadow-md"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                    popupAnchor: [0, -10],
                });

                let popupContent = `
                    <div class="text-sm max-w-[260px]">
                        <p class="font-semibold text-slate-800">${this.escHtml(item.title)}</p>
                        <p class="text-xs text-slate-500 mt-1">${this.escHtml(item.subtitle)}</p>
                        <p class="text-xs text-slate-400 mt-1">${this.escHtml(item.category)}</p>`;

                // Rating, reviews, price_range
                if (item.rating || item.reviews || item.price_range) {
                    popupContent += `<div class="mt-1.5 flex items-center gap-2 text-xs text-slate-500">`;
                    if (item.rating) {
                        popupContent += `<span class="flex items-center gap-0.5"><svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>${item.rating}</span>`;
                    }
                    if (item.reviews) {
                        popupContent += `<span>(${item.reviews} ulasan)</span>`;
                    }
                    if (item.price_range) {
                        popupContent += `<span class="text-slate-400">${this.escHtml(item.price_range)}</span>`;
                    }
                    popupContent += `</div>`;
                }

                popupContent += `
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${isRegistered ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${isRegistered ? 'Terdaftar' : 'Potensi Baru'}
                            </span>
                        </div>`;

                if (isRegistered && item.matched_npwpd) {
                    popupContent += `
                        <div class="mt-2 pt-2 border-t border-slate-100 text-xs">
                            <p class="text-slate-600"><span class="font-medium">NPWPD:</span> ${this.escHtml(item.matched_npwpd)}</p>
                            ${item.matched_name ? `<p class="text-slate-600"><span class="font-medium">Nama WP:</span> ${this.escHtml(item.matched_name)}</p>` : ''}
                        </div>`;
                }

                if (item.url) {
                    popupContent += `
                        <a href="${this.escHtml(item.url)}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 mt-2 text-xs text-blue-600 hover:text-blue-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Buka di Google Maps
                        </a>`;
                }

                popupContent += '</div>';

                const marker = L.marker([item.latitude, item.longitude], { icon })
                    .bindPopup(popupContent)
                    .addTo(this.map);

                marker._resultIndex = index;
                this.markers.push(marker);
            });

            const validMarkers = this.markers.filter(m => m.getLatLng());
            if (validMarkers.length > 0) {
                const group = L.featureGroup(validMarkers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        },

        panToMarker(index) {
            const marker = this.markers.find(m => m._resultIndex === index);
            if (marker) {
                this.map.setView(marker.getLatLng(), 16);
                marker.openPopup();
            }
        },

        escHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },
    };
}
</script>
@endpush

</x-layouts.admin>
