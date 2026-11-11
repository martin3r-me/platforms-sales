<?php

namespace Platform\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDealType extends Model
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
     * Generisches Basis-Set an Deal-Typen. Eine Instanz kann es frei anpassen.
     */
    public static function getDefaults(): array
    {
        return [
            ['name' => 'new_customer', 'label' => 'Neukunde', 'color' => 'green', 'icon' => 'user-plus', 'order' => 1],
            ['name' => 'upsell', 'label' => 'Upsell', 'color' => 'blue', 'icon' => 'arrow-trending-up', 'order' => 2],
            ['name' => 'cross_sell', 'label' => 'Cross-Sell', 'color' => 'purple', 'icon' => 'arrows-right-left', 'order' => 3],
            ['name' => 'renewal', 'label' => 'Verlängerung', 'color' => 'yellow', 'icon' => 'arrow-path', 'order' => 4],
            ['name' => 'expansion', 'label' => 'Erweiterung', 'color' => 'orange', 'icon' => 'plus-circle', 'order' => 5],
            ['name' => 'replacement', 'label' => 'Ersatz', 'color' => 'red', 'icon' => 'arrow-path', 'order' => 6],
        ];
    }
}
