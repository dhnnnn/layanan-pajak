<x-layouts.admin title="Target Tambahan APBD" header="{{ $existing ? 'Edit' : 'Tambah' }} Target Tambahan APBD {{ $currentYear }}">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.report', ['year' => $currentYear]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Laporan Anggaran
        </a>
    </x-slot:headerActions>

    <div class="max-w-2xl mx-auto">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 flex gap-3">
            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-amber-800">
                <p class="font-semibold mb-0.5">Target Tambahan APBD {{ $currentYear }} — Global</p>
                <p class="text-xs text-amber-700">Nominal akan dibagi rata (prorata) mulai dari tribulan ini (T{{ $currentQuarter }}) hingga Tribulan 4.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('admin.upt-additional-targets.store') }}" method="POST"
                class="p-6 space-y-5" id="targetForm"
                x-ref="targetForm"
                @set-total="setTotal($event.detail)"
                @set-pcts="setPcts($event.detail.pcts, $event.detail.base_target)"
                x-data="{
                    total: {{ old('additional_target', $existing?->additional_target ?? 0) }},
                    pctInput: {{ $baseTargetForAyat > 0 && ($existing?->additional_target ?? 0) > 0 ? round(($existing->additional_target / $baseTargetForAyat) * 100, 2) : 0 }},
                    baseTarget: {{ $baseTargetForAyat }},
                    startQ: {{ $currentQuarter }},
                    pcts: {
                        1: {{ $pctPerQ[1] ?? 25 }},
                        2: {{ $pctPerQ[2] ?? 25 }},
                        3: {{ $pctPerQ[3] ?? 25 }},
                        4: {{ $pctPerQ[4] ?? 25 }}
                    },
                    setTotal(val) {
                        this.total = Number(val);
                        if (this.baseTarget > 0 && this.total > 0) {
                            this.pctInput = Math.round((this.total / this.baseTarget) * 10000) / 100;
                        } else {
                            this.pctInput = 0;
                        }
                    },
                    setPcts(p, base) {
                        this.pcts = p;
                        if (base !== undefined) {
                            this.baseTarget = base;
                            this.total = 0;
                            this.pctInput = 0;
                        }
                    },
                    onTotalInput() {
                        if (this.baseTarget > 0 && this.total > 0) {
                            this.pctInput = Math.round((this.total / this.baseTarget) * 10000) / 100;
                        } else {
                            this.pctInput = 0;
                        }
                    },
                    onPctInput() {
                        if (this.baseTarget > 0 && this.pctInput > 0) {
                            this.total = Math.round(this.baseTarget * this.pctInput / 100);
                        } else {
                            this.total = 0;
                        }
                    },
                    get quarters() {
                        if (!this.total || this.total <= 0) return {};
                        const activeSum = [2,3,4].slice(this.startQ - 2 < 0 ? 0 : this.startQ - 2)
                            .reduce((s, q) => s + (q >= this.startQ ? this.pcts[q] : 0), 0)
                            + (this.startQ <= 1 ? this.pcts[1] : 0);
                        let sum = 0;
                        for (let q = this.startQ; q <= 4; q++) sum += this.pcts[q];
                        let result = {1: 0, 2: 0, 3: 0, 4: 0};
                        let distributed = 0;
                        for (let q = this.startQ; q <= 4; q++) {
                            if (q === 4) {
                                result[q] = Math.round((this.total - distributed) * 100) / 100;
                            } else {
                                result[q] = Math.round((this.total * (this.pcts[q] / sum)) * 100) / 100;
                                distributed += result[q];
                            }
                        }
                        return result;
                    },
                    fmt(val) {
                        if (!val || val <= 0) return '—';
                        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                    }
                }">
                @csrf

                {{-- Hidden year --}}
                <input type="hidden" name="year" value="{{ $currentYear }}">

                {{-- Jenis Pajak --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Jenis Pajak <span class="text-red-500">*</span>
                    </label>
                    <x-searchable-select
                        target-input-id="no_ayat_input"
                        :value="old('no_ayat', $existing?->no_ayat ?? request('no_ayat', ''))"
                        placeholder="Pilih Jenis Pajak"
                        :options="$availableAyat->map(fn($nama, $kode) => ['id' => $kode, 'name' => $kode . ' — ' . $nama])->values()->toArray()"
                    />
                    <input type="hidden"
                        id="no_ayat_input"
                        name="no_ayat"
                        value="{{ old('no_ayat', $existing?->no_ayat ?? request('no_ayat', '')) }}">
                    @error('no_ayat')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mulai Tribulan (otomatis) --}}
                <input type="hidden" name="start_quarter" value="{{ $currentQuarter }}">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Berlaku Mulai Tribulan</label>
                    <div class="flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <span class="text-blue-700 font-black text-sm">Tribulan {{ $currentQuarter }} (T{{ $currentQuarter }})</span>
                        <span class="text-blue-500 text-xs">— bulan ini ({{ now()->translatedFormat('F Y') }}) masuk Tribulan {{ $currentQuarter }}</span>
                    </div>
                </div>

                {{-- Nominal --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Total Nominal Target Tambahan (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        {{-- Input Nominal --}}
                        <div class="relative flex-1">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium">Rp</span>
                            <input type="number" name="additional_target" id="additional_target"
                                x-model.number="total"
                                @input="onTotalInput()"
                                value="{{ old('additional_target', $existing?->additional_target) }}"
                                min="1" step="1" placeholder="0"
                                class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 pl-10 pr-4 text-sm border border-slate-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('additional_target') ring-2 ring-red-500/20 @enderror">
                        </div>
                        {{-- Input Persentase --}}
                        <div class="relative w-28">
                            <input type="number" id="pct_input"
                                x-model.number="pctInput"
                                @input="onPctInput()"
                                min="0" step="0.1" placeholder="0"
                                :disabled="baseTarget <= 0"
                                class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 pl-3 pr-8 text-sm border border-slate-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium">%</span>
                        </div>

                    </div>
                    <div class="flex items-center gap-2 mt-1.5">
                        <template x-if="baseTarget > 0 && pctInput > 0">
                            <p class="text-[11px] text-slate-400">
                                Target awal: <span class="font-semibold text-slate-600" x-text="'Rp ' + Math.round(baseTarget).toLocaleString('id-ID')"></span>
                                → naik <span class="font-bold text-amber-600" x-text="pctInput + '%'"></span>
                            </p>
                        </template>
                    </div>
                    @error('additional_target')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Preview Prorata --}}
                <div x-show="total > 0" class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold text-slate-600 mb-3">Distribusi Prorata Otomatis</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach([1, 2, 3, 4] as $q)
                            <div :class="startQ <= {{ $q }} ? 'bg-blue-50 border-blue-200' : 'bg-white border-slate-200'"
                                class="rounded-lg border p-2.5 text-center transition-colors">
                                <p class="text-xs font-semibold mb-1"
                                    :class="startQ <= {{ $q }} ? 'text-blue-700' : 'text-slate-400'">
                                    T{{ $q }}
                                </p>
                                <p class="text-[11px] font-mono font-bold break-all"
                                    :class="startQ <= {{ $q }} ? 'text-blue-800' : 'text-slate-300'"
                                    x-text="fmt(quarters[{{ $q }}])">
                                </p>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">* Sisa pembulatan masuk ke Tribulan 4</p>
                </div>

                {{-- Catatan --}}
                <div>
                    <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1">Catatan / Alasan</label>
                    <textarea name="notes" id="notes" rows="3"
                        placeholder="Contoh: Peningkatan target berdasarkan potensi penerimaan semester II..."
                        class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 text-sm border border-slate-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20">{{ old('notes', $existing?->notes) }}</textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.tax-targets.report', ['year' => $currentYear]) }}"
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Batal
                    </a>
                    <button type="button" id="btnPreview"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="!total || total <= 0 || !document.getElementById('no_ayat_input').value">
                        Preview Target Tambahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Preview --}}
    <div id="previewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" id="previewTitle">Preview Target Tambahan</h3>
                    <p class="text-xs text-slate-400 mt-0.5" id="previewSubtitle"></p>
                </div>
                <button type="button" id="btnCloseModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-auto flex-1 p-6">
                {{-- Loading --}}
                <div id="previewLoading" class="py-10 text-center text-slate-400 text-sm">Memuat data...</div>

                {{-- Error --}}
                <div id="previewError" class="hidden py-10 text-center text-rose-500 text-sm font-medium" id="previewErrorMsg"></div>

                {{-- Table --}}
                <div id="previewContent" class="hidden">
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-xs text-left whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-4 py-3 font-bold text-slate-600 uppercase tracking-wider">Tribulan</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right border-l border-slate-200">Target Sebelumnya</th>
                                    <th class="px-4 py-3 font-bold text-blue-600 uppercase tracking-wider text-right border-l border-slate-200">+ Tambahan</th>
                                    <th class="px-4 py-3 font-bold text-slate-400 uppercase tracking-wider text-right border-l border-slate-200">%</th>
                                    <th class="px-4 py-3 font-bold text-emerald-700 uppercase tracking-wider text-right border-l border-slate-200">Target Baru</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody" class="divide-y divide-slate-100">
                            </tbody>
                            <tfoot id="previewTableFoot" class="bg-slate-50 border-t-2 border-slate-300">
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">* Tambahan dibagi rata (prorata) mulai Tribulan <span id="previewStartQ"></span> hingga T4</p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
                <button type="button" id="btnCloseModal2"
                    class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                    Kembali Edit
                </button>
                <button type="button" id="btnConfirmSave"
                    class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                    {{ $existing ? 'Perbarui' : 'Simpan' }} Target Tambahan
                </button>
            </div>
        </div>
    </div>

    <script>
        const previewUrl        = '{{ route('admin.upt-additional-targets.preview') }}';
        const pctUrl            = '{{ route('admin.upt-additional-targets.pct') }}';
        const noAyatInput       = document.getElementById('no_ayat_input');
        const additionalInput   = document.getElementById('additional_target');
        const modal             = document.getElementById('previewModal');
        const form              = document.getElementById('targetForm');
        const fmt = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
        const qLabel = q => ['', 'Tribulan 1 (Jan–Mar)', 'Tribulan 2 (Apr–Jun)', 'Tribulan 3 (Jul–Sep)', 'Tribulan 4 (Okt–Des)'][q];

        // ── Update pcts saat jenis pajak berubah ──────────────────────────
        async function fetchAndSetPcts(noAyat) {
            if (!noAyat) return;
            // Reset nominal saat ganti jenis pajak
            form.dispatchEvent(new CustomEvent('set-total', { detail: 0 }));
            additionalInput.value = '';

            try {
                const res = await fetch(`${pctUrl}?no_ayat=${encodeURIComponent(noAyat)}`);
                const data = await res.json();
                form.dispatchEvent(new CustomEvent('set-pcts', { detail: { pcts: data.pcts, base_target: data.base_target } }));
            } catch (e) { /* silent */ }
        }

        // Observer untuk Tom Select (searchable-select menggunakan Tom Select)
        const observer = new MutationObserver(() => {
            const val = noAyatInput.value;
            if (val) fetchAndSetPcts(val);
        });
        observer.observe(noAyatInput, { attributes: true, attributeFilter: ['value'] });

        // Juga listen change event
        noAyatInput.addEventListener('change', () => {
            if (noAyatInput.value) fetchAndSetPcts(noAyatInput.value);
        });

        // ── Preview Modal ─────────────────────────────────────────────────
        document.getElementById('btnPreview').addEventListener('click', async () => {
            const noAyat = noAyatInput.value;
            const total = parseFloat(additionalInput.value);
            if (!noAyat || !total || total <= 0) return;

            modal.classList.remove('hidden');
            document.getElementById('previewLoading').classList.remove('hidden');
            document.getElementById('previewError').classList.add('hidden');
            document.getElementById('previewContent').classList.add('hidden');

            try {
                const res = await fetch(`${previewUrl}?no_ayat=${encodeURIComponent(noAyat)}&additional_target=${total}`);
                const data = await res.json();

                if (!res.ok) {
                    document.getElementById('previewLoading').classList.add('hidden');
                    const errEl = document.getElementById('previewError');
                    errEl.textContent = data.error ?? 'Gagal memuat preview.';
                    errEl.classList.remove('hidden');
                    return;
                }

                document.getElementById('previewTitle').textContent = `Preview: ${data.keterangan} (${data.no_ayat})`;
                document.getElementById('previewSubtitle').textContent = `Tahun ${data.year} · Total tambahan: ${fmt(data.total_tambahan)}`;
                document.getElementById('previewStartQ').textContent = data.start_quarter;

                const tbody = document.getElementById('previewTableBody');
                const tfoot = document.getElementById('previewTableFoot');
                tbody.innerHTML = '';

                for (let q = 1; q <= 4; q++) {
                    const qd = data.quarters[q];
                    const isActive = q >= data.start_quarter;
                    const pct = qd.target_awal > 0 ? ((qd.tambahan / qd.target_awal) * 100).toFixed(1) : '—';
                    const tr = document.createElement('tr');
                    tr.className = isActive ? 'bg-blue-50/40' : '';
                    tr.innerHTML = `
                        <td class="px-4 py-3 font-semibold text-slate-700">${qLabel(q)}</td>
                        <td class="px-4 py-3 text-right text-slate-500 border-l border-slate-100 font-mono">${fmt(qd.target_awal)}</td>
                        <td class="px-4 py-3 text-right border-l border-slate-100 font-mono font-bold ${isActive ? 'text-blue-600' : 'text-slate-300'}">
                            ${isActive ? '+' + fmt(qd.tambahan) : '—'}
                        </td>
                        <td class="px-4 py-3 text-right border-l border-slate-100 font-mono text-[11px] ${isActive ? 'text-blue-400 font-semibold' : 'text-slate-300'}">
                            ${isActive && qd.target_awal > 0 ? '+' + pct + '%' : '—'}
                        </td>
                        <td class="px-4 py-3 text-right border-l border-slate-100 font-mono font-bold ${isActive ? 'text-emerald-700' : 'text-slate-500'}">
                            ${fmt(qd.target_baru)}
                        </td>
                    `;
                    tbody.appendChild(tr);
                }

                const totalPct = data.total_target_awal > 0
                    ? ((data.total_tambahan / data.total_target_awal) * 100).toFixed(1)
                    : '—';

                tfoot.innerHTML = `
                    <tr>
                        <td class="px-4 py-3 font-black text-slate-800 uppercase text-[10px] tracking-wider">Total</td>
                        <td class="px-4 py-3 text-right font-black text-slate-700 border-l border-slate-200 font-mono">${fmt(data.total_target_awal)}</td>
                        <td class="px-4 py-3 text-right font-black text-blue-700 border-l border-slate-200 font-mono">+${fmt(data.total_tambahan)}</td>
                        <td class="px-4 py-3 text-right font-black text-blue-500 border-l border-slate-200 font-mono text-[11px]">+${totalPct}%</td>
                        <td class="px-4 py-3 text-right font-black text-emerald-800 border-l border-slate-200 font-mono">${fmt(data.total_target_baru)}</td>
                    </tr>
                `;

                document.getElementById('previewLoading').classList.add('hidden');
                document.getElementById('previewContent').classList.remove('hidden');

            } catch (e) {
                document.getElementById('previewLoading').classList.add('hidden');
                const errEl = document.getElementById('previewError');
                errEl.textContent = 'Gagal terhubung ke server.';
                errEl.classList.remove('hidden');
            }
        });

        document.getElementById('btnConfirmSave').addEventListener('click', () => {
            modal.classList.add('hidden');
            form.submit();
        });

        document.getElementById('btnCloseModal').addEventListener('click', () => modal.classList.add('hidden'));
        document.getElementById('btnCloseModal2').addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });

        // Cegah auto-submit dari komponen searchable-select
        form.addEventListener('submit', function (e) {
            if (!e.submitter) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
    </script>
</x-layouts.admin>
