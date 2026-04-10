<x-layouts.admin title="Prediksi Penerimaan" header="Prediksi Penerimaan">

<div class="space-y-5">

    {{-- Filter --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Pajak</label>
                <x-searchable-select
                    target-input-id="ayat-hidden"
                    :value="$selectedAyat"
                    placeholder="Semua Jenis Pajak"
                    :options="collect([['id' => 'all', 'name' => 'Semua Jenis Pajak']])->merge(
                        $availableAyat->map(fn($nama, $kode) => ['id' => $kode, 'name' => $kode . ' — ' . $nama])->values()
                    )->toArray()"
                />
                <input type="hidden" id="ayat-hidden" value="{{ $selectedAyat }}">
            </div>
            <div class="flex items-center gap-2 pb-2">
                <p class="text-xs text-slate-400">Data diambil dari realisasi bulanan historis</p>
                <button onclick="document.getElementById('forecastInfoModal').classList.remove('hidden')"
                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors shrink-0"
                    title="Informasi tentang prediksi ini">
                    <span class="text-[10px] font-black leading-none">i</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="infoCards">
        @foreach(range(1,3) as $_)
        <div class="bg-white rounded-xl border border-slate-200 p-4 animate-pulse">
            <div class="h-3 bg-slate-200 rounded w-24 mb-2"></div>
            <div class="h-6 bg-slate-200 rounded w-32"></div>
        </div>
        @endforeach
    </div>

    {{-- Chart --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="mb-4">
            <div class="flex items-start justify-between gap-2 flex-wrap mb-3">
                <div>
                    <p class="text-sm font-semibold text-slate-800" id="chartTitle">Memuat data...</p>
                    <p class="text-xs text-slate-400 mt-0.5" id="chartSubtitle"></p>
                </div>
                {{-- Legend --}}
                <div class="flex items-center gap-4 text-xs text-slate-500 shrink-0">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-6 h-0.5 bg-blue-500"></span> Realisasi Aktual
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-6 border-t-2 border-dashed border-orange-400"></span> Prediksi
                    </span>
                </div>
            </div>
            {{-- Filter range — full width, wrap di mobile --}}
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-full sm:w-auto">
                <button data-range="year" onclick="setRange('year')"
                    class="range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors bg-white text-blue-600 shadow-sm text-center">
                    Tahun Ini
                </button>
                <button data-range="1y" onclick="setRange('1y')"
                    class="range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">
                    1 Thn Terakhir
                </button>
                <button data-range="2y" onclick="setRange('2y')"
                    class="range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">
                    2 Thn Terakhir
                </button>
            </div>
        </div>
        <div id="chartError" class="hidden text-center py-12 text-sm text-red-500"></div>
        <div id="chartLoading" class="flex flex-col items-center justify-center py-8 gap-3">
            <img src="{{ asset('img/generating_report.gif') }}" alt="Memuat..." class="w-24 h-24 object-contain">
            <p class="text-xs text-slate-400 font-medium">Sedang memproses prediksi...</p>
        </div>
        <div id="chartWrapper" class="w-full hidden">
            <canvas id="forecastChart"></canvas>
        </div>
    </div>

    {{-- Tabel prediksi --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-800">Rincian Prediksi 12 Bulan ke Depan</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Periode</th>
                        <th class="px-4 py-3 text-right">Prediksi Realisasi</th>
                    </tr>
                </thead>
                <tbody id="forecastTableBody" class="divide-y divide-slate-100">
                    <tr><td colspan="2" class="px-4 py-8 text-center text-slate-400">Memuat data...</td></tr>
                </tbody>
                <tfoot id="forecastTableFoot" class="bg-slate-50 font-semibold text-sm hidden">
                    <tr>
                        <td class="px-4 py-3 text-slate-700">Total Prediksi</td>
                        <td class="px-4 py-3 text-right text-slate-800" id="forecastTotal">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

{{-- Modal: Informasi Prediksi --}}
<div id="forecastInfoModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('forecastInfoModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
        <div class="flex items-start justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                    <span class="text-orange-500 font-black text-base leading-none">i</span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800">Dari Mana Data Prediksi Ini Berasal?</h3>
                </div>
            </div>
            <button onclick="document.getElementById('forecastInfoModal').classList.add('hidden')"
                class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="space-y-4 text-sm text-slate-600">
            <div class="flex gap-3">
                <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <span class="text-blue-600 font-black text-xs">1</span>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-xs mb-0.5">Berbasis Data Historis</p>
                    <p class="text-xs text-slate-500 leading-relaxed">Sistem menganalisis riwayat penerimaan pajak dari periode sebelumnya — mulai dari beberapa bulan hingga bertahun-tahun ke belakang. Dari data tersebut, sistem mengidentifikasi pola seperti periode ramai, periode sepi, serta kecenderungan tren naik atau turun.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="w-7 h-7 bg-orange-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <span class="text-orange-500 font-black text-xs">2</span>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-xs mb-0.5">Diproses dengan Model Statistik (SARIMA/ARIMA)</p>
                    <p class="text-xs text-slate-500 leading-relaxed">Prediksi tidak dibuat secara acak. Sistem menggunakan model statistik yang telah terbukti akurat dalam menganalisis data deret waktu (time series), khususnya yang memiliki pola musiman. Pendekatan ini mirip seperti prakiraan cuaca — menggunakan data masa lalu untuk memperkirakan kondisi di masa depan.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <span class="text-emerald-600 font-black text-xs">3</span>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-xs mb-0.5">Hasil Berupa Estimasi, Bukan Kepastian</p>
                    <p class="text-xs text-slate-500 leading-relaxed">Perlu dipahami bahwa hasil prediksi merupakan estimasi terbaik berdasarkan data yang tersedia. Semakin lengkap dan konsisten data historis, semakin tinggi tingkat akurasi prediksi. Gunakan hasil ini sebagai acuan dalam perencanaan, bukan sebagai angka pasti.</p>
                </div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2.5">
                <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs text-amber-700 leading-relaxed">Nilai sMAPE menunjukkan seberapa akurat model ini. Semakin kecil angkanya, semakin baik. Di bawah 20% berarti prediksi sangat dapat diandalkan.</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal penjelasan sMAPE --}}
<div id="smapeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('smapeModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Apa itu sMAPE?</h3>
            </div>
            <button onclick="document.getElementById('smapeModal').classList.add('hidden')"
                class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="space-y-3 text-sm text-slate-600">
            <p><span class="font-semibold text-slate-800">sMAPE</span> (Symmetric Mean Absolute Percentage Error) adalah ukuran akurasi model prediksi — seberapa jauh hasil prediksi dari nilai sebenarnya.</p>
            <div class="border border-slate-200 rounded-lg overflow-hidden text-xs">
                <div class="grid grid-cols-3 bg-slate-100 font-semibold text-slate-600 px-3 py-2">
                    <span>Nilai sMAPE</span><span class="text-center">Kategori</span><span class="text-right">Interpretasi</span>
                </div>
                <div class="grid grid-cols-3 px-3 py-2 border-t border-slate-100">
                    <span class="text-green-600 font-semibold">&lt; 20%</span><span class="text-center">Akurat</span><span class="text-right text-slate-500">Prediksi sangat baik</span>
                </div>
                <div class="grid grid-cols-3 px-3 py-2 border-t border-slate-100">
                    <span class="text-yellow-500 font-semibold">20% – 40%</span><span class="text-center">Cukup akurat</span><span class="text-right text-slate-500">Masih dapat diandalkan</span>
                </div>
                <div class="grid grid-cols-3 px-3 py-2 border-t border-slate-100">
                    <span class="text-red-500 font-semibold">&gt; 40%</span><span class="text-center">Kurang akurat</span><span class="text-right text-slate-500">Data historis terbatas</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let currentAyat = '{{ $selectedAyat }}';
const DATA_URL = '{{ route('admin.forecasting.data') }}';
let chartInstance = null;
let currentRange = 'year';
let lastRawData = null;

const fmt = val => {
    if (val >= 1e9) return 'Rp ' + (val/1e9).toFixed(2) + ' M';
    if (val >= 1e6) return 'Rp ' + (val/1e6).toFixed(1) + ' Jt';
    return 'Rp ' + val.toLocaleString('id-ID');
};
const fmtFull = val => 'Rp ' + Math.round(val).toLocaleString('id-ID');
const fmtPeriode = str => {
    const [y, m] = str.split('-');
    return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'][+m-1] + ' ' + y;
};

async function loadForecast(ayat) {
    document.getElementById('chartError').classList.add('hidden');
    document.getElementById('chartWrapper').classList.add('hidden');
    document.getElementById('chartLoading').classList.remove('hidden');
    document.getElementById('chartTitle').textContent = '';
    document.getElementById('chartSubtitle').textContent = '';
    document.getElementById('forecastTableBody').innerHTML =
        '<tr><td colspan="2" class="px-4 py-8 text-center text-slate-400">Memuat data...</td></tr>';
    document.getElementById('forecastTableFoot').classList.add('hidden');

    try {
        const res = await fetch(`${DATA_URL}?ayat=${ayat}`);
        if (!res.ok) throw new Error((await res.json()).error || 'Gagal memuat data.');
        const data = await res.json();
        renderCards(data);
        renderChart(data);
        renderTable(data);
    } catch (e) {
        document.getElementById('chartError').textContent = e.message;
        document.getElementById('chartError').classList.remove('hidden');
        document.getElementById('chartLoading').classList.add('hidden');
        document.getElementById('chartWrapper').classList.add('hidden');
        document.getElementById('forecastTableBody').innerHTML =
            `<tr><td colspan="2" class="px-4 py-8 text-center text-red-400">${e.message}</td></tr>`;
    }
}

function renderCards(data) {
    const total = data.forecast.reduce((s, f) => s + f.nilai, 0);
    const mape = data.mape?.toFixed(2) ?? '—';
    const n = parseFloat(mape);
    const color = n < 20 ? 'text-green-600' : n < 40 ? 'text-yellow-500' : 'text-red-500';
    const label = n < 20 ? 'Akurat' : n < 40 ? 'Cukup akurat' : 'Kurang akurat';
    document.getElementById('infoCards').innerHTML = `
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">Total Prediksi 12 Bulan</p>
            <p class="text-lg font-bold text-blue-600">${fmtFull(total)}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">Model Digunakan</p>
            <p class="text-sm font-semibold text-slate-800">${data.model_used}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">sMAPE (Error Rate)
                <button onclick="document.getElementById('smapeModal').classList.remove('hidden')"
                    class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200 hover:bg-blue-100 text-slate-500 hover:text-blue-600 transition-colors ml-0.5 align-middle">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </p>
            <p class="text-lg font-bold ${color}">${mape}%</p>
            <p class="text-xs text-slate-400 mt-0.5">${label}</p>
        </div>`;
}

function renderTable(data) {
    document.getElementById('forecastTableBody').innerHTML = data.forecast
        .map((f, i) => `<tr class="${i%2?'bg-slate-50/50':''}">
            <td class="px-4 py-2.5 text-slate-700">${fmtPeriode(f.periode)}</td>
            <td class="px-4 py-2.5 text-right font-mono text-slate-800">${fmtFull(f.nilai)}</td>
        </tr>`).join('');
    document.getElementById('forecastTotal').textContent =
        fmtFull(data.forecast.reduce((s, f) => s + f.nilai, 0));
    document.getElementById('forecastTableFoot').classList.remove('hidden');
}

// Fix chart mengecil permanen setelah resize dari mobile ke desktop.
// Chart.js terkunci di ukuran container saat dibuat — solusinya recreate canvas + chart.
let lastChartData = null;
let resizeTimer = null;

// Trigger load saat searchable-select berubah
document.getElementById('ayat-hidden').addEventListener('change', function () {
    currentAyat = this.value;
    loadForecast(this.value);
});

function filterByRange(historis, range) {
    const now = new Date();
    const thisYear = now.getFullYear();
    if (range === 'year') {
        return historis.filter(h => h.periode >= `${thisYear}-01`);
    }
    if (range === '1y') {
        return historis.filter(h => h.periode >= `${thisYear - 1}-01`);
    }
    // 2y
    return historis.filter(h => h.periode >= `${thisYear - 2}-01`);
}

function setRange(range) {
    currentRange = range;
    document.querySelectorAll('.range-btn').forEach(btn => {
        const isActive = btn.dataset.range === range;
        btn.classList.toggle('bg-white', isActive);
        btn.classList.toggle('text-blue-600', isActive);
        btn.classList.toggle('shadow-sm', isActive);
        btn.classList.toggle('text-slate-500', !isActive);
        btn.classList.toggle('hover:text-slate-700', !isActive);
    });
    // Reload karena from_jan bisa berubah
    loadForecast(currentAyat);
}

function renderChart(data) {
    lastRawData = data;

    const showFromJan = true; // semua range tampilkan fitted values dari awal historis
    const filteredH   = filterByRange(data.historis, currentRange);
    const forecast    = data.forecast ?? [];
    const fitted      = data.fitted ?? [];

    const hMap      = Object.fromEntries(filteredH.map(h => [h.periode, h.nilai]));
    const fMap      = Object.fromEntries(forecast.map(f => [f.periode, f.nilai]));
    const fittedMap = Object.fromEntries(fitted.map(f => [f.periode, f.nilai]));

    // Gabungkan semua periode unik: historis + forecast
    const allPeriodes = [...new Set([
        ...filteredH.map(h => h.periode),
        ...forecast.map(f => f.periode),
    ])].sort();
    const allLabels = allPeriodes.map(p => fmtPeriode(p));

    // Dataset historis: nilai aktual saja
    const historisDataset = allPeriodes.map(p => hMap[p] ?? null);

    // Dataset prediksi:
    // - Di bulan historis (year/1y): pakai fitted values
    // - Di bulan forecast: pakai nilai forecast
    // - Titik sambung: di bulan terakhir historis, pakai nilai historis agar garis nyambung
    let forecastDataset;
    if (showFromJan && fitted.length > 0) {
        const lastHPeriode = filteredH[filteredH.length - 1]?.periode;
        // Tampilkan fitted mulai 2024-01. Untuk sebelumnya, gunakan nilai aktual
        // sebagai retroaktif (model warm-up period) agar garis tidak kosong
        const fittedCutoff = '2024-01';
        forecastDataset = allPeriodes.map(p => {
            if (fMap[p] !== undefined) return fMap[p];
            if (fittedMap[p] !== undefined && p >= fittedCutoff) return fittedMap[p];
            if (hMap[p] !== undefined && p < fittedCutoff) return hMap[p]; // retroaktif = nilai aktual
            return null;
        });
        // Pastikan titik sambung di akhir historis → awal forecast tidak putus
        if (lastHPeriode && fMap[lastHPeriode] === undefined) {
            const lastHIdx = allPeriodes.indexOf(lastHPeriode);
            const firstFIdx = allPeriodes.findIndex(p => fMap[p] !== undefined);
            if (firstFIdx > lastHIdx + 1) {
                // Ada gap — isi dengan nilai historis terakhir sebagai bridge
                forecastDataset[lastHIdx] = hMap[lastHPeriode];
            }
        }
    } else {
        const lastH = filteredH[filteredH.length - 1];
        forecastDataset = allPeriodes.map(p => {
            if (fMap[p] !== undefined) return fMap[p];
            if (lastH && p === lastH.periode) return lastH.nilai;
            return null;
        });
    }

    const lastForecastPeriode = forecast[forecast.length - 1]?.periode ?? '';
    const firstHistorisPeriode = filteredH[0]?.periode ?? '';

    document.getElementById('chartTitle').textContent =
        `Forecasting: ${data.jenis_pajak} — ${data.label ?? ''}`;
    document.getElementById('chartSubtitle').textContent =
        `${fmtPeriode(firstHistorisPeriode)} s/d ${fmtPeriode(lastForecastPeriode)} · ${filteredH.length} bulan historis · garis oranye = prediksi model (fitted + forecast)`;

    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
    document.getElementById('chartLoading').classList.add('hidden');
    document.getElementById('chartWrapper').classList.remove('hidden');
    document.getElementById('chartWrapper').innerHTML = '<canvas id="forecastChart"></canvas>';

    chartInstance = new Chart(document.getElementById('forecastChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: allLabels,
            datasets: [
                {
                    label: 'Realisasi Aktual',
                    data: historisDataset,
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)',
                    borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true,
                    spanGaps: false,
                },
                {
                    label: 'Prediksi',
                    data: forecastDataset,
                    borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.06)',
                    borderWidth: 2, borderDash: [6, 4], pointRadius: 3, tension: 0.3, fill: true,
                    spanGaps: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true, position: 'top', align: 'end',
                    labels: { boxWidth: 24, font: { size: 10 }, padding: 12 },
                },
                tooltip: {
                    callbacks: {
                        label: c => c.parsed.y !== null ? `${c.dataset.label}: ${fmtFull(c.parsed.y)}` : null,
                    },
                },
            },
            scales: {
                x: { ticks: { maxTicksLimit: 18, font: { size: 10 } }, grid: { display: false } },
                y: { ticks: { font: { size: 10 }, callback: v => fmt(v) }, grid: { color: 'rgba(0,0,0,0.05)' } },
            },
        },
    });
}

window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (lastRawData) { renderChart(lastRawData); }
    }, 300);
});

loadForecast(currentAyat);
</script>
@endpush

</x-layouts.admin>
