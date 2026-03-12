<?php

namespace App\Livewire;

use App\Services\CurrentStoreService;
use Livewire\Component;

class StoreSwitcher extends Component
{
    public ?int $selectedStoreId = null;

    public array $stores = [];

    public bool $showSwitcher = false;

    public function mount(): void
    {
        $this->loadStores();
        $this->selectedStoreId = app(CurrentStoreService::class)->getId();
    }

    public function loadStores(): void
    {
        $storeService = app(CurrentStoreService::class);
        $stores = $storeService->getAvailableStores();

        $this->stores = $stores->pluck('name', 'id')->toArray();
        $this->showSwitcher = count($this->stores) > 1;
    }

    public function switchStore(): void
    {
        if (! $this->selectedStoreId) {
            return;
        }

        $storeService = app(CurrentStoreService::class);

        if (! $storeService->canAccessStore($this->selectedStoreId)) {
            return;
        }

        $storeService->set($this->selectedStoreId);

        $this->dispatch('store-changed');

        redirect()->to(request()->header('Referer') ?? '/');
    }

    public function render()
    {
        return view('livewire.store-switcher');
    }
}
