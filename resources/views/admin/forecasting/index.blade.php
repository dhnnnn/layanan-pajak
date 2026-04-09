<x-layouts.admin title="Prediksi Penerimaan" header="Prediksi Penerimaan">

<div class="space-y-5">

    {{-- Filter --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Pajak</label>
                <x-searchable-select
                    name="ayat"
                    :value="$selectedAyat"
                    placeholder="Pilih jenis pajak..."
                    :options="$availableAyat->map(fn($nama, $kode) => ['id' => $kode, 'name' => $kode . ' — ' . $nama])->values()->toArray()"
                    id="ayat-select"
                    target-input-id="ayat-hidden"
                />
                <input type="hidden" id="ayat-hidden" value="{{ $selectedAyat }}">
            </div>
            <p class="text-xs text-slate-400 pb-2">Data diambil dari realisasi bulanan historis</p>
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
        <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
            <div>
                <p class="text-sm font-semibold text-slate-800" id="chartTitle">Memuat data...</p>
                <p class="text-xs text-slate-400 mt-0.5" id="chartSubtitle"></p>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-500 shrink-0">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-6 h-0.5 bg-blue-500"></span> Realisasi Aktual
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-6 border-t-2 border-dashed border-orange-400"></span> Prediksi
                </span>
            </div>
        </div>
        <div id="chartError" class="hidden text-center py-12 text-sm text-red-500"></div>
        <div id="chartWrapper" class="w-full">
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
    document.getElementById('chartWrapper').classList.remove('hidden');
    document.getElementById('chartTitle').textContent = 'Memuat data...';
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

// Trigger load saat searchable-select berubah
document.getElementById('ayat-hidden').addEventListener('change', function () {
    if (this.value && this.value !== currentAyat) {
        currentAyat = this.value;
        loadForecast(this.value);
    }
});

// Fix chart mengecil permanen setelah resize dari mobile ke desktop.
// Chart.js terkunci di ukuran container saat dibuat — solusinya recreate canvas + chart.
let lastChartData = null;
let resizeTimer = null;

const originalRenderChart = renderChart;
window.renderChart = function(data) {
    lastChartData = data;
    originalRenderChart(data);
};

// Override renderChart agar selalu simpan data terakhir
function renderChart(data) {
    lastChartData = data;

    const hLabels = data.historis.map(h => fmtPeriode(h.periode));
    const hVals   = data.historis.map(h => h.nilai);
    const fLabels = data.forecast.map(f => fmtPeriode(f.periode));
    const fVals   = data.forecast.map(f => f.nilai);
    const last    = hVals[hVals.length - 1];

    document.getElementById('chartTitle').textContent =
        `Forecasting: ${data.jenis_pajak} — ${data.label ?? ''}`;
    document.getElementById('chartSubtitle').textContent =
        `${hLabels[0]} s/d ${fLabels[fLabels.length-1]} · ${data.historis.length} bulan historis`;

    // Recreate canvas agar Chart.js tidak terkunci di ukuran lama
    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
    const wrapper = document.getElementById('chartWrapper');
    wrapper.innerHTML = '<canvas id="forecastChart"></canvas>';

    chartInstance = new Chart(document.getElementById('forecastChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: [...hLabels, ...fLabels],
            datasets: [
                {
                    label: 'Realisasi Aktual',
                    data: [...hVals, fVals[0], ...Array(fVals.length - 1).fill(null)],
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)',
                    borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true, spanGaps: false,
                },
                {
                    label: 'Prediksi',
                    data: [...Array(hVals.length - 1).fill(null), last, ...fVals],
                    borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.06)',
                    borderWidth: 2, borderDash: [6,4], pointRadius: 3, tension: 0.3, fill: true, spanGaps: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => `${c.dataset.label}: ${fmtFull(c.parsed.y)}` } },
            },
            scales: {
                x: { ticks: { maxTicksLimit: 18, font: { size: 10 } }, grid: { display: false } },
                y: { ticks: { font: { size: 10 }, callback: v => fmt(v) }, grid: { color: 'rgba(0,0,0,0.05)' } },
            },
        },
    });
}

// Saat window resize, recreate chart dengan data terakhir (debounce 300ms)
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (lastChartData) renderChart(lastChartData);
    }, 300);
});

loadForecast(currentAyat);
</script>
@endpush

</x-layouts.admin>
