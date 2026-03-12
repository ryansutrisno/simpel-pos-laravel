<?php

namespace App\Livewire;

use App\Models\Store;
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
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $this->stores = Store::pluck('name', 'id')->toArray();
            $this->showSwitcher = count($this->stores) > 1;
        } else {
            $this->stores = Store::where('id', $user->current_store_id)->pluck('name', 'id')->toArray();
            $this->showSwitcher = false;
        }
    }

    public function switchStore(): void
    {
        if (! $this->selectedStoreId) {
            return;
        }

        $user = auth()->user();

        if (! $user->isSuperAdmin()) {
            return;
        }

        app(CurrentStoreService::class)->set($this->selectedStoreId);

        $this->dispatch('store-changed');

        redirect()->to(request()->header('Referer') ?? '/');
    }

    public function render()
    {
        return view('livewire.store-switcher');
    }
}
