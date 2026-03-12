<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_store_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function suspendedTransactions(): HasMany
    {
        return $this->hasMany(SuspendedTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function currentStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'current_store_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function activeShift(): ?Shift
    {
        return $this->shifts()
            ->where('status', 'open')
            ->whereDate('shift_date', today())
            ->first();
    }

    public function hasActiveShift(): bool
    {
        return $this->activeShift() !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'user_stores');
    }

    public function assignedStores()
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return Store::all();
        }

        return $this->stores;
    }

    public function canAccessStore(int $storeId): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        return $this->stores()->where('store_id', $storeId)->exists();
    }

    public function hasAnyStore(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return Store::exists();
        }

        return $this->stores()->exists();
    }
}
