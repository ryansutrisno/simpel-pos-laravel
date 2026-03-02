<x-filament-panels::page>
    <x-filament-panels::form wire:submit="mount">
        {{ $this->form }}
    </x-filament-panels::form>

    {{ $this->table }}

    @if($this->topStaff->count() > 0)
        <x-filament::section>
            <x-slot name="heading">
                Top Performer Hari Ini
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($this->topStaff as $index => $staff)
                    <div class="p-4 rounded-lg border {{ $index === 0 ? 'bg-warning-50 border-warning-200' : 'bg-gray-50 border-gray-200' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center text-lg font-bold text-primary-600">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <div class="font-semibold">{{ $staff->name }}</div>
                                <div class="text-sm text-gray-600">
                                    Rp {{ number_format($staff->today_sales ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $staff->today_transactions ?? 0 }} transaksi
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
