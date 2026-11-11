<?php

namespace Platform\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Sales\Models\SalesPriority;
use Platform\Sales\Models\SalesDealSource;
use Platform\Sales\Models\SalesDealType;
use Platform\Sales\Models\SalesLostReason;

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

        // Sales Deal Sources (Basis-Set aus dem Model – eine Quelle der Wahrheit)
        foreach (SalesDealSource::getDefaults() as $source) {
            SalesDealSource::firstOrCreate(
                ['name' => $source['name']],
                $source
            );
        }

        // Sales Deal Types (Basis-Set aus dem Model – eine Quelle der Wahrheit)
        foreach (SalesDealType::getDefaults() as $type) {
            SalesDealType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }

        // Sales Lost Reasons (generisches Basis-Set, pro Instanz anpassbar)
        foreach (SalesLostReason::getDefaultLostReasons() as $reason) {
            SalesLostReason::firstOrCreate(
                ['code' => $reason['code']],
                $reason + ['is_active' => true]
            );
        }
    }
}