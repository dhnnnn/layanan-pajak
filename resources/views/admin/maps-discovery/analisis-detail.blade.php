<x-layouts.admin title="Analisis Potensi Pajak" :header="'Analisis Potensi - ' . $result->title">
    <x-slot:headerActions>
        <a href="{{ route('admin.maps-discovery.report') }}"
           class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg shadow-sm transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div x-data="analisisCalculator()" class="space-y-4">
        {{-- Info WP --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-slate-500 mb-1">Nama Tempat</p>
                    <p class="font-semibold text-slate-800">{{ $result->title }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-1">Alamat</p>
                    <p class="text-sm text-slate-600">{{ $result->subtitle }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-1">Kategori</p>
                    <p class="text-sm text-slate-600">{{ $result->category ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-1">Rating</p>
                    <p class="text-sm text-amber-500 font-medium">
                        @if($result->rating)
                            ★ {{ $result->rating }} <span class="text-slate-400">({{ number_format($result->reviews) }} ulasan)</span>
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>

        @if($isLoading)
        {{-- Loading Animation --}}
        <div x-data="loadingState()" x-init="start()" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            {{-- Header gradient --}}
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                <p class="text-white/80 text-xs font-medium uppercase tracking-wider">Analisis Potensi Pajak</p>
                <p class="text-white font-semibold text-sm mt-0.5">{{ $result->title }}</p>
            </div>

            <div class="p-8 flex flex-col items-center text-center">
                {{-- Animated icon --}}
                <div class="relative mb-8">
                    {{-- Outer pulse ring --}}
                    <div class="absolute inset-0 rounded-full bg-blue-100 animate-ping opacity-30 scale-110"></div>
                    {{-- Spinning ring --}}
                    <div class="absolute inset-0 rounded-full border-4 border-blue-100 border-t-blue-500 animate-spin"></div>
                    {{-- Icon container --}}
                    <div class="relative w-24 h-24 rounded-full bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center shadow-inner">
                        <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                </div>

                {{-- Title & status --}}
                <h3 class="text-xl font-bold text-slate-800 mb-1">Mengambil Data Statistik</h3>
                <p class="text-sm text-slate-500 mb-8 min-h-[20px]" x-text="status"></p>

                {{-- Step progress --}}
                <div class="w-full max-w-md mb-8">
                    <div class="flex items-start justify-between relative">
                        {{-- Connector line --}}
                        <div class="absolute top-4 left-0 right-0 h-0.5 bg-slate-100 mx-8 z-0"></div>
                        <div class="absolute top-4 left-0 h-0.5 bg-blue-400 mx-8 z-0 transition-all duration-700"
                             :style="`width: calc(${Math.min(100, (step / 3) * 100)}% - 4rem)`"></div>

                        {{-- Step 1: Membuka Maps --}}
                        <div class="flex flex-col items-center gap-2 z-10 w-1/4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-500 border-2"
                                 :class="step >= 1 ? 'bg-blue-500 border-blue-500 shadow-md shadow-blue-200' : 'bg-white border-slate-200'">
                                <template x-if="step >= 1">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="step < 1">
                                    <span class="text-xs text-slate-400 font-medium">1</span>
                                </template>
                            </div>
                            <span class="text-xs font-medium transition-colors" :class="step >= 1 ? 'text-blue-600' : 'text-slate-400'">Membuka Maps</span>
                        </div>

                        {{-- Step 2: Mengambil Data --}}
                        <div class="flex flex-col items-center gap-2 z-10 w-1/4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-500 border-2"
                                 :class="step >= 2 ? 'bg-blue-500 border-blue-500 shadow-md shadow-blue-200' : step === 1 ? 'bg-white border-blue-400 animate-pulse' : 'bg-white border-slate-200'">
                                <template x-if="step >= 2">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="step < 2">
                                    <span class="text-xs font-medium" :class="step === 1 ? 'text-blue-500' : 'text-slate-400'">2</span>
                                </template>
                            </div>
                            <span class="text-xs font-medium transition-colors" :class="step >= 2 ? 'text-blue-600' : step === 1 ? 'text-blue-400' : 'text-slate-400'">Mengambil Data</span>
                        </div>

                        {{-- Step 3: Analisis --}}
                        <div class="flex flex-col items-center gap-2 z-10 w-1/4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-500 border-2"
                                 :class="step >= 3 ? 'bg-blue-500 border-blue-500 shadow-md shadow-blue-200' : step === 2 ? 'bg-white border-blue-400 animate-pulse' : 'bg-white border-slate-200'">
                                <template x-if="step >= 3">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="step < 3">
                                    <span class="text-xs font-medium" :class="step === 2 ? 'text-blue-500' : 'text-slate-400'">3</span>
                                </template>
                            </div>
                            <span class="text-xs font-medium transition-colors" :class="step >= 3 ? 'text-blue-600' : step === 2 ? 'text-blue-400' : 'text-slate-400'">Analisis</span>
                        </div>

                        {{-- Step 4: Selesai --}}
                        <div class="flex flex-col items-center gap-2 z-10 w-1/4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-500 border-2"
                                 :class="step >= 4 ? 'bg-green-500 border-green-500 shadow-md shadow-green-200' : step === 3 ? 'bg-white border-blue-400 animate-pulse' : 'bg-white border-slate-200'">
                                <template x-if="step >= 4">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="step < 4">
                                    <span class="text-xs font-medium" :class="step === 3 ? 'text-blue-500' : 'text-slate-400'">4</span>
                                </template>
                            </div>
                            <span class="text-xs font-medium transition-colors" :class="step >= 4 ? 'text-green-600' : step === 3 ? 'text-blue-400' : 'text-slate-400'">Selesai</span>
                        </div>
                    </div>
                </div>

                {{-- Subtle progress bar --}}
                <div class="w-full max-w-md mb-6">
                    <div class="flex justify-between text-xs text-slate-400 mb-1.5">
                        <span x-text="`${Math.round(progress)}%`"></span>
                        <span>~90 detik</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-1000 bg-gradient-to-r from-blue-400 to-indigo-500"
                             :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="w-full max-w-md bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 flex items-start gap-3">
                    <svg class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-blue-600 text-left">
                        <strong>Tips:</strong> Data ini membantu menentukan potensi pajak lebih akurat berdasarkan pola kunjungan nyata dari Google Maps.
                    </p>
                </div>
            </div>
        </div>
        @elseif($statisticsError)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800">Statistik Kunjungan Belum Tersedia</p>
                    <p class="text-xs text-amber-700 mt-1">{{ $statisticsError }}</p>
                </div>
            </div>
        </div>
        @elseif($statisticsData && $statisticsData['grand_total'] > 0)
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-800">Statistik Kunjungan Google Maps</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Data keramaian per jam dalam seminggu</p>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                    Total: {{ number_format($statisticsData['grand_total']) }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium sticky left-0 bg-slate-50">Jam</th>
                            <th class="px-3 py-2 text-center font-medium">Senin</th>
                            <th class="px-3 py-2 text-center font-medium">Selasa</th>
                            <th class="px-3 py-2 text-center font-medium">Rabu</th>
                            <th class="px-3 py-2 text-center font-medium">Kamis</th>
                            <th class="px-3 py-2 text-center font-medium">Jumat</th>
                            <th class="px-3 py-2 text-center font-medium">Sabtu</th>
                            <th class="px-3 py-2 text-center font-medium">Minggu</th>
                            <th class="px-3 py-2 text-center font-medium bg-slate-100">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($statisticsData['table'] as $hour => $days)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-700 sticky left-0 bg-white">{{ $hour }}</td>
                            @foreach(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $day)
                            <td class="px-3 py-2 text-center {{ $days[$day] > 0 ? 'text-slate-800' : 'text-slate-300' }}">
                                {{ $days[$day] > 0 ? $days[$day] : '-' }}
                            </td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-semibold text-slate-800 bg-slate-50">
                                {{ $statisticsData['hour_totals'][$hour] }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-slate-100 font-semibold">
                            <td class="px-3 py-2 text-slate-700 sticky left-0 bg-slate-100">Total</td>
                            @foreach(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $day)
                            <td class="px-3 py-2 text-center text-slate-800">{{ $statisticsData['day_totals'][$day] }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-center text-blue-600 bg-slate-200">{{ number_format($statisticsData['grand_total']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
            <p class="text-sm text-slate-600">Statistik kunjungan belum tersedia untuk tempat ini.</p>
        </div>
        @endif

        @if(!$isLoading)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Input Data Perhitungan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Hasil Checker (Pengunjung saat monitoring)</label>
                    <input type="number" x-model.number="checkerResult" min="1"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                           placeholder="Contoh: 47">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jam Monitoring</label>
                    <select x-model="monitoringHour" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                        <option value="">Pilih jam...</option>
                        @for($h = 0; $h < 24; $h++)
                        <option value="{{ $h }}-{{ $h+1 }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 - {{ str_pad($h+1, 2, '0', STR_PAD_LEFT) }}:00</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Hari Monitoring</label>
                    <select x-model="dayOfWeek" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                        <option value="">Pilih hari...</option>
                        <option value="senin">Senin</option>
                        <option value="selasa">Selasa</option>
                        <option value="rabu">Rabu</option>
                        <option value="kamis">Kamis</option>
                        <option value="jumat">Jumat</option>
                        <option value="sabtu">Sabtu</option>
                        <option value="minggu">Minggu</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Rata-rata Harga Menu per Pax (Rp)</label>
                    <input type="number" x-model.number="avgMenuPrice" min="1000" step="1000"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                           placeholder="Contoh: 50000">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Durasi Kunjungan Rata-rata (Jam)</label>
                    <input type="number" x-model.number="avgDuration" min="0.5" max="12" step="0.5"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                           placeholder="Contoh: 2.5">
                    <p class="text-xs text-slate-500 mt-1">Default: 2.5 jam (di bawah 1 jam dibulatkan menjadi 1 jam)</p>
                </div>
            </div>
            <div class="mt-4">
                <button @click="calculate()" :disabled="loading || !canCalculate()"
                        :class="loading || !canCalculate() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <span x-show="!loading">Hitung Potensi Pajak</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Menghitung...
                    </span>
                </button>
            </div>
            <div x-show="error" x-transition class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800" x-text="error"></p>
            </div>
        </div>

        <div x-show="calculation" x-transition class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
            <h3 class="font-bold text-lg text-slate-800 mb-4">📊 Dashboard Perhitungan Potensi Pajak</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="text-xs text-slate-500 mb-1">Hasil Checker</p>
                    <p class="text-2xl font-bold text-slate-800" x-text="calculation?.checker_result || '-'"></p>
                    <p class="text-xs text-slate-500 mt-1">(a) Pengunjung saat monitoring</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="text-xs text-slate-500 mb-1">Maps Jam Checker</p>
                    <p class="text-2xl font-bold text-slate-800" x-text="calculation?.maps_hour_count || '-'"></p>
                    <p class="text-xs text-slate-500 mt-1">(b) Statistik Maps pada jam monitoring</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="text-xs text-slate-500 mb-1">Total Maps Seminggu</p>
                    <p class="text-2xl font-bold text-slate-800" x-text="formatNumber(calculation?.maps_weekly_total) || '-'"></p>
                    <p class="text-xs text-slate-500 mt-1">(c) Total statistik Maps seminggu</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="text-xs text-slate-500 mb-1">Durasi Kunjungan</p>
                    <p class="text-2xl font-bold text-slate-800" x-text="calculation?.avg_duration_hours || '-'"></p>
                    <p class="text-xs text-slate-500 mt-1">(d) Rata-rata durasi (jam)</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-4 border-2 border-blue-300">
                    <p class="text-xs text-blue-700 mb-1">Jumlah Pengunjung 1 Minggu</p>
                    <p class="text-3xl font-bold text-blue-900" x-text="formatNumber(calculation?.weekly_visitors) || '-'"></p>
                    <p class="text-xs text-blue-700 mt-1">(e) = (c) / (b) × (a) / (d)</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-4 border-2 border-blue-300">
                    <p class="text-xs text-blue-700 mb-1">Rata-rata Menu per Pax</p>
                    <p class="text-3xl font-bold text-blue-900" x-text="formatRupiah(calculation?.avg_menu_price) || '-'"></p>
                    <p class="text-xs text-blue-700 mt-1">(f) Input manual</p>
                </div>
            </div>
            <div class="space-y-3">
                <div class="bg-green-100 rounded-lg p-4 border-2 border-green-300">
                    <p class="text-sm text-green-700 mb-1">Potensi Pajak 1 Minggu</p>
                    <p class="text-3xl font-bold text-green-900" x-text="formatRupiah(calculation?.weekly_potential_tax) || '-'"></p>
                    <p class="text-xs text-green-700 mt-1">(g) = (e) × (f) × 10% tarif pajak</p>
                </div>
                <div class="bg-green-200 rounded-lg p-5 border-2 border-green-400">
                    <p class="text-sm text-green-800 mb-1">Potensi Pajak 1 Bulan</p>
                    <p class="text-4xl font-bold text-green-900" x-text="formatRupiah(calculation?.monthly_potential_tax) || '-'"></p>
                    <p class="text-xs text-green-800 mt-1">(h) = (g) × 30 / 7 (akumulasi ke 1 bulan, asumsi 30 hari)</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-amber-100 rounded-lg p-4 border border-amber-300">
                        <p class="text-xs text-amber-700 mb-1">Potensi Minimal (75%)</p>
                        <p class="text-xl font-bold text-amber-900" x-text="formatRupiah(calculation?.min_potential_tax) || '-'"></p>
                    </div>
                    <div class="bg-red-100 rounded-lg p-4 border border-red-300">
                        <p class="text-xs text-red-700 mb-1">Potensi Maksimal (125%)</p>
                        <p class="text-xl font-bold text-red-900" x-text="formatRupiah(calculation?.max_potential_tax) || '-'"></p>
                    </div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-white/50 rounded-lg border border-blue-200">
                <p class="text-xs text-slate-600">
                    <strong>Catatan:</strong> Perhitungan menggunakan metode Analisis Data Potensi Pajak Daerah
                    berdasarkan data statistik Google Maps dan hasil monitoring lapangan.
                </p>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    function analisisCalculator() {
        return {
            checkerResult: {{ $latestCalculation?->checker_result ?? 'null' }},
            monitoringHour: '{{ $latestCalculation?->monitoring_report?->monitoring_hour ?? '' }}',
            dayOfWeek: '{{ $latestCalculation?->monitoring_report?->day_of_week ?? '' }}',
            avgMenuPrice: {{ $result->avg_menu_price ?? $latestCalculation?->avg_menu_price ?? 50000 }},
            avgDuration: {{ $latestCalculation?->avg_duration_hours ?? 2.5 }},
            calculation: @json($latestCalculation),
            loading: false,
            error: null,

            canCalculate() {
                return this.checkerResult > 0 && this.monitoringHour && this.dayOfWeek
                    && this.avgMenuPrice >= 1000 && this.avgDuration >= 0.5;
            },

            async calculate() {
                if (!this.canCalculate()) return;
                this.loading = true;
                this.error = null;
                try {
                    const response = await fetch('{{ route("admin.maps-discovery.calculate-potential", $result->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            checker_result: this.checkerResult,
                            monitoring_hour: this.monitoringHour,
                            day_of_week: this.dayOfWeek,
                            avg_menu_price: this.avgMenuPrice,
                            avg_duration_hours: this.avgDuration,
                        }),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Gagal menghitung potensi pajak');
                    if (data.success) { this.calculation = data.calculation; this.error = null; }
                    else { this.error = data.message; }
                } catch (err) {
                    this.error = err.message || 'Terjadi kesalahan saat menghitung';
                } finally {
                    this.loading = false;
                }
            },

            formatNumber(num) {
                if (!num) return '0';
                return new Intl.NumberFormat('id-ID').format(num);
            },

            formatRupiah(num) {
                if (!num) return 'Rp 0';
                return 'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
            },
        }
    }

    function loadingState() {
        return {
            progress: 0,
            step: 0,
            status: 'Menghubungkan ke Google Maps...',
            start() {
                const steps = [
                    { at: 2000,  progress: 10, step: 1, status: 'Membuka halaman Google Maps...' },
                    { at: 10000, progress: 22, step: 1, status: 'Menunggu halaman termuat...' },
                    { at: 22000, progress: 35, step: 1, status: 'Halaman terbuka, scroll ke bawah...' },
                    { at: 35000, progress: 50, step: 2, status: 'Mengambil data jam ramai hari ini...' },
                    { at: 50000, progress: 63, step: 2, status: 'Mengumpulkan data hari berikutnya...' },
                    { at: 65000, progress: 75, step: 2, status: 'Mengumpulkan data 7 hari seminggu...' },
                    { at: 78000, progress: 85, step: 3, status: 'Memproses dan menyimpan statistik...' },
                    { at: 88000, progress: 93, step: 3, status: 'Hampir selesai...' },
                ];

                steps.forEach(s => {
                    setTimeout(() => {
                        this.progress = s.progress;
                        this.step = s.step;
                        this.status = s.status;
                    }, s.at);
                });

                fetch('{{ route("admin.maps-discovery.scrape-statistics", $result->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                .then(() => {
                    this.progress = 100;
                    this.step = 4;
                    this.status = 'Selesai! Memuat halaman...';
                    setTimeout(() => window.location.reload(), 800);
                })
                .catch(() => {
                    this.status = 'Terjadi kesalahan, memuat ulang...';
                    setTimeout(() => window.location.reload(), 1500);
                });
            }
        }
    }
    </script>
    @endpush
</x-layouts.admin>
