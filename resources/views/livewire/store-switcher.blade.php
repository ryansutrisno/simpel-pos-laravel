<div>
    @if ($showSwitcher)
        <div class="flex items-center gap-2">
            <select
                wire:model="selectedStoreId"
                wire:change="switchStore"
                class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
            >
                @foreach ($stores as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $stores[$selectedStoreId] ?? 'Tidak ada toko' }}
            </span>
        </div>
    @endif
</div>