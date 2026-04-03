<x-layouts.employee
    :title="'Input Realisasi - ' . $district->name"
    :header="'Input Realisasi Pajak: ' . $district->name">

    <x-slot:headerActions>
        <a href="{{ route('field-officer.realizations.index', ['year' => $year]) }}"
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
            <form method="GET" action="{{ route('field-officer.daily-entries.show', $district->id) }}">
                <select name="year" onchange="this.form.submit()"
                    class="no-search text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-1.5 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                    @for($y = (int) date('Y'); $y <= (int) date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    <!-- Daftar Jenis Pajak -->
    <div class="space-y-8" id="tax-cards">
        @foreach($taxTypes as $parentIndex => $parentType)
            @php
                $hasChildren = $parentType->children->isNotEmpty();
                $parentYearlyTotal = 0;
                
                if ($hasChildren) {
                    foreach ($parentType->children as $child) {
                        $parentYearlyTotal += (float) ($yearlyTotals[$child->id] ?? 0);
                    }
                } else {
                    $parentYearlyTotal = (float) ($yearlyTotals[$parentType->id] ?? 0);
                }
            @endphp

            @if($hasChildren)
                {{-- Parent Header --}}
                <div class="bg-white rounded-xl shadow-sm p-5 mb-4 border border-slate-200 border-l-4 border-l-emerald-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-slate-900 font-bold text-lg flex items-center gap-2">
                                <span class="bg-emerald-500 text-white w-7 h-7 rounded-lg flex items-center justify-center text-sm">{{ $parentIndex + 1 }}</span>
                                {{ $parentType->name }}
                            </h2>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-1">Total Akumulasi {{ $year }}</p>
                            <p class="text-xl font-bold text-emerald-600">Rp {{ number_format($parentYearlyTotal, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Children Cards --}}
                <div class="grid grid-cols-1 gap-4 ml-6 lg:ml-10">
                    @foreach($parentType->children as $childIndex => $taxType)
                        @php
                            $total = $yearlyTotals[$taxType->id] ?? 0;
                            $hasData = (float) $total > 0;
                            $entries = $monthlyEntries->where('tax_type_id', $taxType->id)->values();
                            $monthTotal = $entries->sum('amount');
                        @endphp
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" id="card-{{ $taxType->id }}">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $parentIndex + 1 }}.{{ $childIndex + 1 }} - {{ $taxType->name }}</p>
                                </div>
                                <div class="text-right">
                                    @if($hasData)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] font-semibold">
                                            ADA DATA
                                        </span>
                                    @endif
                                    <p class="text-xs text-slate-600 mt-1">
                                        Total: <span class="font-bold text-emerald-700" id="total-{{ $taxType->id }}" data-raw="{{ $total }}">
                                            Rp {{ number_format($total, 0, ',', '.') }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {{-- Form Input --}}
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Input Realisasi Harian</p>
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-500 mb-1">Tanggal</label>
                                                <input type="date" id="date-{{ $taxType->id }}" value="{{ $today }}" max="{{ $today }}" class="w-full text-xs rounded-lg bg-slate-50 text-slate-700 px-3 py-2 border-0 focus:ring-2 focus:ring-emerald-500/20">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-500 mb-1">Jumlah (Rp)</label>
                                                <input type="number" id="amount-{{ $taxType->id }}" min="0" placeholder="0" class="entry-amount w-full text-xs rounded-lg bg-slate-50 text-slate-700 px-3 py-2 border-0 focus:ring-2 focus:ring-emerald-500/20" data-tax-type-id="{{ $taxType->id }}">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-500 mb-1">Catatan</label>
                                            <input type="text" id="note-{{ $taxType->id }}" placeholder="Opsional..." class="w-full text-xs rounded-lg bg-slate-50 text-slate-700 px-3 py-2 border-0 focus:ring-2 focus:ring-emerald-500/20">
                                        </div>
                                    </div>
                                </div>

                                {{-- History --}}
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">
                                        Riwayat {{ $monthNames[$currentMonth] }} 
                                        <span class="text-emerald-600" id="month-total-{{ $taxType->id }}">
                                            (Rp {{ number_format($monthTotal, 0, ',', '.') }})
                                        </span>
                                    </p>
                                    <div id="history-{{ $taxType->id }}">
                                        @if($entries->isEmpty())
                                            <div class="text-center py-4 bg-slate-50 rounded-lg border border-dashed border-slate-200">
                                                <p class="text-xs text-slate-400 italic">Belum ada entri bulan ini.</p>
                                            </div>
                                        @else
                                            <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-100">
                                                <table class="w-full text-[10px]">
                                                    <thead class="bg-slate-50 text-slate-500 sticky top-0">
                                                        <tr>
                                                            <th class="px-3 py-2 text-left">Tgl</th>
                                                            <th class="px-3 py-2 text-right">Rp</th>
                                                            <th class="px-3 py-2 w-8"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-50 bg-white">
                                                        @foreach($entries as $entry)
                                                            <tr id="entry-row-{{ $entry->id }}">
                                                                <td class="px-3 py-2 text-slate-600">{{ $entry->entry_date->format('d/m') }}</td>
                                                                <td class="px-3 py-2 text-right font-semibold text-emerald-700">Rp{{ number_format($entry->amount, 0, ',', '.') }}</td>
                                                                <td class="px-3 py-2 text-right">
                                                                    <button onclick="deleteEntry('{{ $entry->id }}', '{{ $taxType->id }}', '{{ $district->id }}')" class="text-red-400 hover:text-red-600">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                    </button>
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
            @else
                {{-- Root Type (No Children) --}}
                @php
                    $taxType = $parentType;
                    $total = $yearlyTotals[$taxType->id] ?? 0;
                    $hasData = (float) $total > 0;
                    $entries = $monthlyEntries->where('tax_type_id', $taxType->id)->values();
                    $monthTotal = $entries->sum('amount');
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" id="card-{{ $taxType->id }}">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-slate-900">{{ $parentIndex + 1 }}. {{ $taxType->name }}</p>
                        </div>
                        <div class="text-right">
                            @if($hasData)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    ADA DATA
                                </span>
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
                                            class="w-full text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-2 border-0 focus:ring-2 focus:ring-emerald-500/20">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah (Rp)</label>
                                        <input type="number" id="amount-{{ $taxType->id }}"
                                            min="0" placeholder="0"
                                            class="entry-amount w-full text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-2 border-0 focus:ring-2 focus:ring-emerald-500/20"
                                            data-tax-type-id="{{ $taxType->id }}">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Catatan (opsional)</label>
                                    <input type="text" id="note-{{ $taxType->id }}"
                                        placeholder="Keterangan tambahan..."
                                        class="w-full text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-2 border-0 focus:ring-2 focus:ring-emerald-500/20">
                                </div>
                            </div>
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
                                                    <th class="px-3 py-2 text-left text-slate-400">Note</th>
                                                    <th class="px-3 py-2 w-10"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($entries as $entry)
                                                    <tr id="entry-row-{{ $entry->id }}">
                                                        <td class="px-3 py-2 text-slate-700">{{ $entry->entry_date->format('d/m') }}</td>
                                                        <td class="px-3 py-2 text-right font-semibold text-emerald-700">Rp{{ number_format($entry->amount, 0, ',', '.') }}</td>
                                                        <td class="px-3 py-2 text-slate-500 truncate max-w-[80px]">{{ $entry->note ?: '-' }}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            <button onclick="deleteEntry('{{ $entry->id }}', '{{ $taxType->id }}', '{{ $district->id }}')"
                                                                class="text-red-500 hover:text-red-700 font-medium">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
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
            @endif
        @endforeach
    </div>

    <!-- Tombol Simpan Semua -->
    <div class="sticky bottom-0 mt-6 bg-white border-t border-slate-200 shadow-lg px-6 py-4 -mx-6 flex items-center justify-between gap-4">
        <p class="text-xs text-slate-400">Hanya baris yang diisi jumlahnya yang akan disimpan.</p>
        <button id="btn-save-all" onclick="submitAll('{{ $district->id }}')"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Semua
        </button>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const currentYear = {{ $year }};
        const currentMonth = {{ $currentMonth }};
        const districtId = '{{ $district->id }}';

        function formatRp(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        }

        async function submitAll(districtId) {
            const amountInputs = document.querySelectorAll('.entry-amount');
            const entries = [];

            amountInputs.forEach(input => {
                const taxTypeId = input.dataset.taxTypeId;
                const amount = parseFloat(input.value);
                if (!amount || amount <= 0) return;

                const date = document.getElementById(`date-${taxTypeId}`).value;
                const note = document.getElementById(`note-${taxTypeId}`).value;
                entries.push({ tax_type_id: taxTypeId, entry_date: date, amount, note });
            });

            if (entries.length === 0) {
                alert('Tidak ada data yang diisi. Masukkan jumlah minimal satu jenis pajak.');
                return;
            }

            const btn = document.getElementById('btn-save-all');
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Menyimpan...`;

            try {
                const res = await fetch('/pegawai/daily-entries/batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ district_id: districtId, entries }),
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menyimpan.');

                // Reload halaman untuk refresh semua total & riwayat
                window.location.reload();
            } catch (e) {
                alert(e.message);
                btn.disabled = false;
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Simpan Semua`;
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

                const res3 = await fetch(`/pegawai/realizations/district/${districtId}/tax-types?year=${currentYear}`);
                const d = await res3.json();
                const newTotal = d.yearlyTotals?.[taxTypeId] ?? 0;
                const totalEl = document.getElementById(`total-${taxTypeId}`);
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
                            <button onclick="deleteEntry('${e.id}', '${taxTypeId}', '${districtId}')" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
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
