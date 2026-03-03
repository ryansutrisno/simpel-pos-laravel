<x-filament-widgets::widget class="fi-wi-stats-overview">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-trophy class="w-5 h-5 text-warning-500"/>
                Top Performer Hari Ini
            </div>
        </x-slot>

        @php
            $topStaff = $this->getTopStaff();
        @endphp

        @if($topStaff->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($topStaff as $index => $staff)
                    @php
                        $medalColors = [
                            'bg-warning-100 text-warning-600 border-warning-200',
                            'bg-gray-100 text-gray-600 border-gray-200',
                            'bg-orange-100 text-orange-600 border-orange-200',
                        ];
                        $medalColor = $medalColors[$index] ?? 'bg-gray-50 text-gray-500 border-gray-200';
                    @endphp

                    <div class="p-4 rounded-lg border {{ $medalColor }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-lg font-bold shadow-sm">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">{{ $staff->name }}</div>
                                <div class="text-lg font-bold">
                                    Rp {{ number_format($staff->today_sales ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-xs opacity-75">
                                    {{ $staff->today_transactions ?? 0 }} transaksi
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-6 text-gray-500 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <x-heroicon-o-user-group class="w-8 h-8 mx-auto mb-3 text-gray-400"/>
                <p class="text-sm font-medium">Belum ada transaksi hari ini</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
