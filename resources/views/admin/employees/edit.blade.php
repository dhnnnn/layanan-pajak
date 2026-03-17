<x-layouts.admin title="Edit Pegawai" :header="'Edit Pegawai: ' . $employee->name">
    <x-slot:headerActions>
        <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <form action="{{ route('admin.employees.update', $employee) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Basic Info --}}
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Informasi Dasar</h3>
                        
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $employee->name) }}" 
                                class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('name') ring-2 ring-red-500/20 @enderror" 
                                placeholder="Nama Pegawai" required>
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" 
                                class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('email') ring-2 ring-red-500/20 @enderror" 
                                placeholder="email@example.com" required>
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="upt_id" class="block text-sm font-semibold text-slate-700 mb-1">UPT</label>
                            <select name="upt_id" id="upt_id" class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('upt_id') ring-2 ring-red-500/20 @enderror">
                                <option value="">-- Pilih UPT --</option>
                                @foreach($upts as $upt)
                                    <option value="{{ $upt->id }}" 
                                        data-district-ids="{{ $upt->districts->pluck('id')->join(',') }}"
                                        @selected(old('upt_id', $employee->upt_id) == $upt->id)>
                                        {{ $upt->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500 italic">Pegawai akan bekerja di wilayah dalam scope UPT yang dipilih.</p>
                            @error('upt_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                <span class="text-[10px] text-slate-400 font-medium bg-slate-100 px-1.5 py-0.5 rounded">Opsional</span>
                            </div>
                            <input type="password" name="password" id="password" 
                                class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 @error('password') ring-2 ring-red-500/20 @enderror" 
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                            <p class="mt-1 text-[10px] text-slate-500">Isi hanya jika ingin mengganti password pegawai.</p>
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" 
                                class="w-full rounded-lg bg-slate-50 text-slate-700 py-2.5 px-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20" 
                                placeholder="Ulangi password baru">
                            <p class="mt-1 text-[10px] text-slate-500">Kata sandi wajib diisi.</p>
                            @error('password_confirmation')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- District Assignment --}}
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Penugasan Wilayah</h3>
                        
                        <div>
                            <p class="block text-sm font-semibold text-slate-700 mb-2">Pilih Kecamatan:</p>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 max-h-64 overflow-y-auto space-y-2" id="districts-container">
                                @foreach($districts as $district)
                                    <label class="district-item flex items-center gap-3 p-2 hover:bg-white rounded transition-colors cursor-pointer border border-transparent hover:border-slate-200" data-district-id="{{ $district->id }}">
                                        <input type="checkbox" name="district_ids[]" value="{{ $district->id }}" 
                                            @checked(in_array($district->id, old('district_ids', $assignedIds)))
                                            class="rounded text-blue-600 bg-slate-100 border-0 focus:ring-2 focus:ring-blue-500">
                                        <span class="text-sm text-slate-700">{{ $district->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[10px] text-slate-500 italic">Kecamatan akan otomatis difilter berdasarkan UPT yang dipilih.</p>
                            @error('district_ids')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.employees.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uptSelect = document.getElementById('upt_id');
            const districtItems = document.querySelectorAll('.district-item');
            
            function filterDistricts() {
                const selectedOption = uptSelect.options[uptSelect.selectedIndex];
                const districtIds = selectedOption.getAttribute('data-district-ids');
                
                if (!districtIds || districtIds === '') {
                    // Jika tidak ada UPT dipilih, tampilkan semua kecamatan
                    districtItems.forEach(item => {
                        item.style.display = 'flex';
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        checkbox.disabled = false;
                    });
                    return;
                }
                
                const allowedIds = districtIds.split(',').map(id => id.trim());
                
                districtItems.forEach(item => {
                    const districtId = item.getAttribute('data-district-id');
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    
                    if (allowedIds.includes(districtId)) {
                        item.style.display = 'flex';
                        checkbox.disabled = false;
                    } else {
                        item.style.display = 'none';
                        checkbox.checked = false;
                        checkbox.disabled = true;
                    }
                });
            }
            
            // Filter saat UPT berubah
            uptSelect.addEventListener('change', filterDistricts);
            
            // Filter saat halaman dimuat (untuk old values)
            filterDistricts();
        });
    </script>
</x-layouts.admin>
