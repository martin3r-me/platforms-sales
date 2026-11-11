<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDealSource extends Model
{
    protected $fillable = [
        'name',
        'label',
        'color',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Generisches Basis-Set an Deal-Quellen. Eine Instanz kann es frei anpassen.
     */
    public static function getDefaults(): array
    {
        return [
            ['name' => 'website', 'label' => 'Website', 'color' => 'blue', 'icon' => 'globe-alt', 'order' => 1],
            ['name' => 'referral', 'label' => 'Empfehlung', 'color' => 'green', 'icon' => 'user-group', 'order' => 2],
            ['name' => 'cold_call', 'label' => 'Kaltakquise', 'color' => 'yellow', 'icon' => 'phone', 'order' => 3],
            ['name' => 'email', 'label' => 'E-Mail Marketing', 'color' => 'purple', 'icon' => 'envelope', 'order' => 4],
            ['name' => 'social_media', 'label' => 'Social Media', 'color' => 'pink', 'icon' => 'share', 'order' => 5],
            ['name' => 'trade_show', 'label' => 'Messe', 'color' => 'indigo', 'icon' => 'building-storefront', 'order' => 6],
            ['name' => 'partner', 'label' => 'Partner', 'color' => 'orange', 'icon' => 'handshake', 'order' => 7],
            ['name' => 'other', 'label' => 'Sonstige', 'color' => 'gray', 'icon' => 'ellipsis-horizontal', 'order' => 8],
        ];
    }
}
