<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <div class="space-y-3">
            @forelse($this->activeBundles as $bundle)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $bundle->name }}</span>
                        @if($bundle->discount_type === 'free_item')
                        <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded-full">Gratis</span>
                        @elseif($bundle->discount_type === 'percentage')
                        <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded-full">Diskon {{ $bundle->discount_value }}%</span>
                        @else
                        <span class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded-full">Diskon Rp {{ number_format($bundle->discount_value, 0, ',', '.') }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $bundle->items->count() }} item · Min. {{ $bundle->min_quantity }} qty · Berlaku s/d {{ $bundle->end_date?->format('d/m/Y') ?? 'Selamanya' }}
                    </p>
                </div>
                <a href="{{ route('filament.admin.resources.product-bundles.edit', $bundle) }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 text-sm">
                    Detail →
                </a>
            </div>
            @empty
            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                </svg>
                <p>Tidak ada paket aktif</p>
            </div>
            @endforelse
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('filament.admin.resources.product-bundles.index') }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                Kelola Semua Paket →
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
