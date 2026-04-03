@props([
    'options' => [],
    'name' => '',
    'value' => '',
    'placeholder' => 'Pilih opsi...',
    'id' => null,
    'targetInputId' => null
])

@php
    $id = $id ?? 'select-' . Str::random(8);
    $selectedOption = collect($options)->firstWhere('id', $value);
    $selectedLabel = $selectedOption ? $selectedOption['name'] : $placeholder;
@endphp

<div class="relative w-full" x-data='{
    open: false,
    search: "",
    value: "{{ $value }}",
    label: "{{ $selectedLabel }}",
    targetId: "{{ $targetInputId }}",
    options: {{ json_encode($options) }},
    get filteredOptions() {
        if (!this.search) return this.options;
        return this.options.filter(opt => 
            opt.name.toLowerCase().includes(this.search.toLowerCase())
        );
    },
    select(opt) {
        this.value = opt.id;
        this.label = opt.name;
        this.open = false;
        this.search = "";
        
        let input;
        if (this.targetId) {
            input = document.getElementById(this.targetId);
        } else {
            input = $refs.input;
        }

        if (input) {
            input.value = opt.id;
            // Dispatch a native change event that can be listened to outside
            input.dispatchEvent(new Event("change", { bubbles: true }));
            
            // Still check if there is a form to submit if no AJAX is handling it
            // but we will let the AJAX logic intercept the form is submit event
            // or just rely on the "change" event we just fired.
            const form = input.closest("form");
            if (form) {
                form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
            }
        }
    }
}'>
    @if(!$targetInputId)
        <input type="hidden" name="{{ $name }}" x-ref="input" :value="value">
    @endif
    
    <button type="button" 
        @click="open = !open"
        @click.away="open = false"
        class="w-full flex items-center justify-between px-4 py-2 min-h-[38px] bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/10 transition-all">
        <span x-text="label" :class="value ? 'text-slate-900 font-bold' : 'text-slate-400'"></span>
        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-2 w-full bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden"
        style="display: none;">
        
        <div class="p-2 border-b border-slate-50 bg-slate-50/50">
            <div class="relative">
                <input type="text" 
                    x-model="search"
                    @click.stop
                    placeholder="Cari..." 
                    class="w-full pl-8 pr-4 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 transition-all outline-none">
                <svg class="absolute left-2.5 top-2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="max-h-60 overflow-y-auto py-1 scrollbar-thin scrollbar-thumb-slate-200">
            <button type="button" 
                @click="select({id: '', name: '{{ $placeholder }}'})"
                class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
                :class="!value ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                -- {{ $placeholder }} --
            </button>
            
            <template x-for="opt in filteredOptions" :key="opt.id">
                <button type="button" 
                    @click="select(opt)"
                    class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors group"
                    :class="value == opt.id ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                    <div class="flex items-center justify-between">
                        <span x-text="opt.name" class="uppercase"></span>
                        <svg x-show="value == opt.id" class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </button>
            </template>
            
            <div x-show="filteredOptions.length === 0" class="px-4 py-8 text-center text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                Tidak ada hasil
            </div>
        </div>
    </div>
</div>
