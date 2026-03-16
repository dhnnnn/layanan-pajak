<x-layouts.admin title="Detail Pegawai" :header="'Detail Pegawai: ' . $employee->name">
    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Edit Profil
            </a>
        </div>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Top Section: Info & Districts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Profile Card --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl font-bold">
                        {{ substr($employee->name, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ $employee->name }}</h3>
                        <p class="text-sm text-slate-500 italic">{{ $employee->email }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm py-2 border-b border-slate-50">
                        <span class="text-slate-500">Role</span>
                        <span class="font-semibold text-slate-800 uppercase tracking-wider text-xs bg-slate-100 px-2 py-0.5 rounded">Pegawai</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 border-b border-slate-50">
                        <span class="text-slate-500">Terdaftar</span>
                        <span class="font-medium text-slate-800">{{ $employee->created_at->format('d F Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Assigned Districts --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Wilayah Tugas (Kecamatan)</h3>
                </div>
                <div class="p-6 flex-1">
                    @if($employee->districts->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($employee->districts as $district)
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="w-8 h-8 bg-white border border-slate-200 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ $district->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-mono">{{ $district->code }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                            <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.268 14c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-sm">Belum ada kecamatan yang ditugaskan.</p>
                            <a href="{{ route('admin.employees.edit', $employee) }}" class="mt-2 text-blue-600 hover:underline text-xs font-medium">Tugaskan sekarang</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Bottom Section: Realization History --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Riwayat Input Realisasi</h3>
                <span class="text-xs text-slate-500 font-medium">{{ $employee->taxRealizations->count() }} Entri Total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-white text-slate-500 font-bold uppercase text-[10px] border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Tahun</th>
                            <th class="px-6 py-4">Jenis Pajak</th>
                            <th class="px-6 py-4">Kecamatan</th>
                            <th class="px-6 py-4 text-center">Update Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($employee->taxRealizations as $realization)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $realization->year }}</td>
                                <td class="px-6 py-4 font-medium text-slate-700">{{ $realization->taxType->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $realization->district->name }}</td>
                                <td class="px-6 py-4 text-center text-slate-400 font-mono text-xs">
                                    {{ $realization->updated_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Belum ada riwayat input realisasi.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
