<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

/**
 * Umsatzkategorie-Lookup (datengetrieben, kein Hardcode-Enum).
 *
 * Bildet die Umsatz-Einstufung ab (z. B. A/B/C nach Partner-Umsatz). Bewusst
 * OHNE Seed: die konkreten Stufen/Schwellen sind Mandanten-Fachdaten und werden
 * über die MCP-Tools (sales.revenue_categories.POST/PUT/DELETE) oder das UI
 * gepflegt.
 */
class SalesRevenueCategory extends Model
{
    protected $table = 'sales_revenue_categories';

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());

                $model->uuid = $uuid;
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
