<?php

namespace Platform\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Sales\Models\SalesPriority;
use Platform\Sales\Models\SalesDealSource;
use Platform\Sales\Models\SalesDealType;

class SalesLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sales Priorities
        $priorities = [
            ['name' => 'low', 'label' => 'Niedrig', 'color' => 'green', 'icon' => 'arrow-down', 'order' => 1],
            ['name' => 'medium', 'label' => 'Mittel', 'color' => 'yellow', 'icon' => 'minus', 'order' => 2],
            ['name' => 'high', 'label' => 'Hoch', 'color' => 'orange', 'icon' => 'arrow-up', 'order' => 3],
            ['name' => 'urgent', 'label' => 'Dringend', 'color' => 'red', 'icon' => 'exclamation-triangle', 'order' => 4],
        ];

        foreach ($priorities as $priority) {
            SalesPriority::firstOrCreate(
                ['name' => $priority['name']],
                $priority
            );
        }

        // Sales Deal Sources
        $sources = [
            ['name' => 'website', 'label' => 'Website', 'color' => 'blue', 'icon' => 'globe-alt', 'order' => 1],
            ['name' => 'referral', 'label' => 'Empfehlung', 'color' => 'green', 'icon' => 'user-group', 'order' => 2],
            ['name' => 'cold_call', 'label' => 'Kaltakquise', 'color' => 'yellow', 'icon' => 'phone', 'order' => 3],
            ['name' => 'email', 'label' => 'E-Mail Marketing', 'color' => 'purple', 'icon' => 'envelope', 'order' => 4],
            ['name' => 'social_media', 'label' => 'Social Media', 'color' => 'pink', 'icon' => 'share', 'order' => 5],
            ['name' => 'trade_show', 'label' => 'Messe', 'color' => 'indigo', 'icon' => 'building-storefront', 'order' => 6],
            ['name' => 'partner', 'label' => 'Partner', 'color' => 'orange', 'icon' => 'handshake', 'order' => 7],
            ['name' => 'other', 'label' => 'Sonstige', 'color' => 'gray', 'icon' => 'ellipsis-horizontal', 'order' => 8],
        ];

        foreach ($sources as $source) {
            SalesDealSource::firstOrCreate(
                ['name' => $source['name']],
                $source
            );
        }

        // Sales Deal Types
        $types = [
            ['name' => 'new_customer', 'label' => 'Neukunde', 'color' => 'green', 'icon' => 'user-plus', 'order' => 1],
            ['name' => 'upsell', 'label' => 'Upsell', 'color' => 'blue', 'icon' => 'arrow-trending-up', 'order' => 2],
            ['name' => 'cross_sell', 'label' => 'Cross-Sell', 'color' => 'purple', 'icon' => 'arrows-right-left', 'order' => 3],
            ['name' => 'renewal', 'label' => 'Verlängerung', 'color' => 'yellow', 'icon' => 'arrow-path', 'order' => 4],
            ['name' => 'expansion', 'label' => 'Erweiterung', 'color' => 'orange', 'icon' => 'plus-circle', 'order' => 5],
            ['name' => 'replacement', 'label' => 'Ersatz', 'color' => 'red', 'icon' => 'arrow-path', 'order' => 6],
        ];

        foreach ($types as $type) {
            SalesDealType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}