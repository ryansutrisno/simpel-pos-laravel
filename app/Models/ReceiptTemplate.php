<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptTemplate extends Model
{
    use BelongsToStore;
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'template_data',
        'is_default',
        'is_active',
        'store_id',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getDefaultTemplate(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function getActiveTemplates(?int $storeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->when($storeId, function ($query, $storeId) {
                $query->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->orWhereNull('store_id');
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function validateTemplateData(): bool
    {
        $required = ['header', 'body', 'footer'];
        $templateData = $this->template_data;

        foreach ($required as $section) {
            if (! isset($templateData[$section])) {
                return false;
            }
        }

        return true;
    }
}
