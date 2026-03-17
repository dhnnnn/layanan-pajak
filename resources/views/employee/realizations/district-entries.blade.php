<x-layouts.employee
    :title="'Input Realisasi - ' . $district->name"
    :header="'Input Realisasi Pajak: ' . $district->name">

    <x-slot:headerActions>
        <a href="{{ route('pegawai.realizations.index', ['year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    @php
        $today = date('Y-m-d');
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    @endphp

    <!-- Info bar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-slate-900">{{ $district->name }}</p>
                <p class="text-xs text-slate-500 font-mono">{{ $district->code }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm font-semibold text-slate-700">Tahun:</label>
            <form method="GET" action="{{ route('pegawai.daily-entries.show', $district->id) }}">
                <select name="year" onchange="this.form.submit()"
                    class="text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-1.5 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                    @for($y = (int) date('Y'); $y <= (int) date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    <!-- Daftar Jenis Pajak -->
    <div class="space-y-4">
        @foreach($taxTypes as $index => $taxType)
            @php
                $total = $yearlyTotals[$taxType->id] ?? 0;
                $hasData = (float) $total > 0;

                // Entries bulan ini untuk jenis pajak ini
                $entries = $monthlyEntries->where('tax_type_id', $taxType->id)->values();
                $monthTotal = $entries->sum('amount');
            @endphp

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" id="card-{{ $taxType->id }}">
                <!-- Header jenis pajak -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900">{{ $index + 1 }}. {{ $taxType->name }}</p>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $taxType->code }}</p>
                    </div>
                    <div class="text-right">
                        @if($hasData)
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Ada Data
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold">Belum Input</span>
                        @endif
                        <p class="text-xs text-slate-500 mt-1.5">
                            Total {{ $year }}: <span class="font-bold text-emerald-700" id="total-{{ $taxType->id }}" data-raw="{{ $total }}">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Form input harian -->
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Input Harian</p>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal</label>
                                    <input type="date" id="date-{{ $taxType->id }}"
                                        value="{{ $today }}" max="{{ $today }}"
                                        class="w-full text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-2 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah (Rp)</label>
                                    <input type="number" id="amount-{{ $taxType->id }}"
                                        min="0" placeholder="0"
                                        class="w-full text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-2 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Catatan (opsional)</label>
                                <input type="text" id="note-{{ $taxType->id }}"
                                    placeholder="Keterangan tambahan..."
                                    class="w-full text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-2 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                            </div>
                            <button id="btn-{{ $taxType->id }}"
                                onclick="submitEntry({{ $taxType->id }}, {{ $district->id }})"
                                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2">Data harian otomatis diakumulasi ke laporan bulanan.</p>
                    </div>

                    <!-- Riwayat bulan ini -->
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                            Riwayat {{ $monthNames[$currentMonth] }} {{ $year }}
                            <span class="ml-2 font-semibold text-emerald-700" id="month-total-{{ $taxType->id }}">
                                ({{ 'Rp ' . number_format($monthTotal, 0, ',', '.') }})
                            </span>
                        </p>
                        <div id="history-{{ $taxType->id }}">
                            @if($entries->isEmpty())
                                <p class="text-xs text-slate-400 italic py-2">Belum ada entri bulan ini.</p>
                            @else
                                <div class="border border-slate-200 rounded-lg overflow-hidden">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50 text-slate-600 font-semibold">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Tanggal</th>
                                                <th class="px-3 py-2 text-right">Jumlah</th>
                                                <th class="px-3 py-2 text-left">Catatan</th>
                                                <th class="px-3 py-2 w-12"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100" id="history-body-{{ $taxType->id }}">
                                            @foreach($entries as $entry)
                                                <tr id="entry-row-{{ $entry->id }}">
                                                    <td class="px-3 py-2 text-slate-700">{{ $entry->entry_date->format('d/m/Y') }}</td>
                                                    <td class="px-3 py-2 text-right font-semibold text-emerald-700">Rp {{ number_format($entry->amount, 0, ',', '.') }}</td>
                                                    <td class="px-3 py-2 text-slate-500">{{ $entry->note ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-right">
                                                        <button onclick="deleteEntry({{ $entry->id }}, {{ $taxType->id }}, {{ $district->id }})"
                                                            class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const currentYear = {{ $year }};
        const currentMonth = {{ $currentMonth }};

        function formatRp(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        }

        async function submitEntry(taxTypeId, districtId) {
            const date = document.getElementById(`date-${taxTypeId}`).value;
            const amount = document.getElementById(`amount-${taxTypeId}`).value;
            const note = document.getElementById(`note-${taxTypeId}`).value;
            const btn = document.getElementById(`btn-${taxTypeId}`);

            if (!date || !amount || parseFloat(amount) <= 0) {
                alert('Tanggal dan jumlah wajib diisi dengan nilai lebih dari 0.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Menyimpan...`;

            try {
                const res = await fetch('/pegawai/daily-entries', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ tax_type_id: taxTypeId, district_id: districtId, entry_date: date, amount, note }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menyimpan.');

                // Update total tahunan
                const totalEl = document.getElementById(`total-${taxTypeId}`);
                const newTotal = parseFloat(totalEl.dataset.raw || 0) + parseFloat(amount);
                totalEl.dataset.raw = newTotal;
                totalEl.textContent = formatRp(newTotal);

                // Reset input
                document.getElementById(`amount-${taxTypeId}`).value = '';
                document.getElementById(`note-${taxTypeId}`).value = '';

                // Refresh riwayat bulan ini
                await refreshHistory(taxTypeId, districtId);

            } catch (e) {
                alert(e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Simpan`;
            }
        }

        async function deleteEntry(entryId, taxTypeId, districtId) {
            if (!confirm('Hapus entri ini?')) return;
            try {
                const res = await fetch(`/pegawai/daily-entries/${entryId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                });
                if (!res.ok) throw new Error('Gagal menghapus.');

                await refreshHistory(taxTypeId, districtId);

                // Refresh total tahunan
                const res2 = await fetch(`/pegawai/daily-entries?tax_type_id=${taxTypeId}&district_id=${districtId}&year=${currentYear}&month=1`);
                // Recalculate from all months - simpler: just subtract from display
                const totalEl = document.getElementById(`total-${taxTypeId}`);
                // Re-fetch yearly total
                const res3 = await fetch(`/pegawai/realizations/district/${districtId}/tax-types?year=${currentYear}`);
                const d = await res3.json();
                const newTotal = d.yearlyTotals?.[taxTypeId] ?? 0;
                totalEl.dataset.raw = newTotal;
                totalEl.textContent = formatRp(newTotal);

            } catch (e) {
                alert(e.message);
            }
        }

        async function refreshHistory(taxTypeId, districtId) {
            const histEl = document.getElementById(`history-${taxTypeId}`);
            const monthTotalEl = document.getElementById(`month-total-${taxTypeId}`);

            try {
                const res = await fetch(`/pegawai/daily-entries?tax_type_id=${taxTypeId}&district_id=${districtId}&year=${currentYear}&month=${currentMonth}`);
                const data = await res.json();
                const entries = data.entries || [];

                const monthTotal = entries.reduce((sum, e) => sum + parseFloat(e.amount), 0);
                if (monthTotalEl) monthTotalEl.textContent = `(${formatRp(monthTotal)})`;

                if (entries.length === 0) {
                    histEl.innerHTML = `<p class="text-xs text-slate-400 italic py-2">Belum ada entri bulan ini.</p>`;
                    return;
                }

                let html = `<div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-semibold">
                            <tr>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                                <th class="px-3 py-2 text-left">Catatan</th>
                                <th class="px-3 py-2 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">`;

                entries.forEach(e => {
                    html += `<tr>
                        <td class="px-3 py-2 text-slate-700">${e.entry_date}</td>
                        <td class="px-3 py-2 text-right font-semibold text-emerald-700">${formatRp(e.amount)}</td>
                        <td class="px-3 py-2 text-slate-500">${e.note || '-'}</td>
                        <td class="px-3 py-2 text-right">
                            <button onclick="deleteEntry(${e.id}, ${taxTypeId}, ${districtId})" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                histEl.innerHTML = html;

            } catch (e) {
                console.error(e);
            }
        }
    </script>
</x-layouts.employee>
