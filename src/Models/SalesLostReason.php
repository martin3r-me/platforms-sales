<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

/**
 * Verlustgrund-Lookup (datengetrieben, kein Hardcode-Enum).
 *
 * Der Code (lowercase snake, z.B. "no_response") wird in
 * sales_deals.lost_reason_code gespeichert. Jede Instanz pflegt ihre eigenen
 * Gründe über die MCP-Tools (sales.lost_reasons.POST/PUT/DELETE) oder den Seeder.
 */
class SalesLostReason extends Model
{
    protected $table = 'sales_lost_reasons';

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

    /**
     * Generische Standard-Verlustgründe (Basis-Set für jede neue Instanz).
     * Jede Instanz kann die Liste danach frei über die MCP-Tools anpassen.
     */
    public static function getDefaultLostReasons(): array
    {
        return [
            ['code' => 'no_response',   'name' => 'Keine Antwort', 'order' => 1],
            ['code' => 'too_expensive', 'name' => 'Zu teuer',      'order' => 2],
            ['code' => 'other',         'name' => 'Anderer Grund', 'order' => 3],
        ];
    }
}
