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
            ['name' => 'low', 'label' => 'Niedrig', 'color' => 'green', 'icon' => '⬇', 'order' => 1],
            ['name' => 'normal', 'label' => 'Normal', 'color' => 'blue', 'icon' => '⭘', 'order' => 2],
            ['name' => 'high', 'label' => 'Hoch', 'color' => 'orange', 'icon' => '⬆', 'order' => 3],
            ['name' => 'critical', 'label' => 'Kritisch', 'color' => 'red', 'icon' => '🔥', 'order' => 4],
        ];

        foreach ($priorities as $priority) {
            SalesPriority::create($priority);
        }

        // Sales Deal Sources
        $sources = [
            ['name' => 'website', 'label' => 'Website', 'color' => 'blue', 'icon' => '🌐', 'order' => 1],
            ['name' => 'referral', 'label' => 'Empfehlung', 'color' => 'green', 'icon' => '👥', 'order' => 2],
            ['name' => 'cold_call', 'label' => 'Cold Call', 'color' => 'yellow', 'icon' => '📞', 'order' => 3],
            ['name' => 'linkedin', 'label' => 'LinkedIn', 'color' => 'blue', 'icon' => '💼', 'order' => 4],
            ['name' => 'email', 'label' => 'E-Mail', 'color' => 'purple', 'icon' => '📧', 'order' => 5],
            ['name' => 'trade_show', 'label' => 'Messe', 'color' => 'orange', 'icon' => '🏢', 'order' => 6],
            ['name' => 'social_media', 'label' => 'Social Media', 'color' => 'pink', 'icon' => '📱', 'order' => 7],
            ['name' => 'advertising', 'label' => 'Werbung', 'color' => 'red', 'icon' => '📢', 'order' => 8],
        ];

        foreach ($sources as $source) {
            SalesDealSource::create($source);
        }

        // Sales Deal Types
        $types = [
            ['name' => 'new_customer', 'label' => 'Neukunde', 'color' => 'green', 'icon' => '🆕', 'order' => 1],
            ['name' => 'upsell', 'label' => 'Upsell', 'color' => 'blue', 'icon' => '⬆', 'order' => 2],
            ['name' => 'cross_sell', 'label' => 'Cross-Sell', 'color' => 'purple', 'icon' => '↔', 'order' => 3],
            ['name' => 'renewal', 'label' => 'Verlängerung', 'color' => 'orange', 'icon' => '🔄', 'order' => 4],
            ['name' => 'consulting', 'label' => 'Beratung', 'color' => 'yellow', 'icon' => '💡', 'order' => 5],
            ['name' => 'support', 'label' => 'Support', 'color' => 'red', 'icon' => '🛠', 'order' => 6],
        ];

        foreach ($types as $type) {
            SalesDealType::create($type);
        }
    }
}
