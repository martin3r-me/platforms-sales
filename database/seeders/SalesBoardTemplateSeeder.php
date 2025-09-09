<?php

namespace Platform\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Sales\Models\SalesBoardTemplate;
use Platform\Sales\Models\SalesBoardTemplateSlot;

class SalesBoardTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Standard Sales Pipeline Template
        $standardTemplate = SalesBoardTemplate::create([
            'name' => 'Standard Sales Pipeline',
            'description' => 'Klassischer Vertriebsprozess mit den wichtigsten Stufen',
            'is_system' => true,
            'is_default' => true,
        ]);

        $standardSlots = [
            ['name' => 'Neu', 'color' => 'blue', 'order' => 1],
            ['name' => 'Erstkontakt', 'color' => 'yellow', 'order' => 2],
            ['name' => 'Angebot', 'color' => 'orange', 'order' => 3],
            ['name' => 'Verhandlung', 'color' => 'purple', 'order' => 4],
        ];

        foreach ($standardSlots as $slotData) {
            SalesBoardTemplateSlot::create([
                'sales_board_template_id' => $standardTemplate->id,
                'name' => $slotData['name'],
                'color' => $slotData['color'],
                'order' => $slotData['order'],
            ]);
        }

        // B2B Enterprise Sales Template
        $enterpriseTemplate = SalesBoardTemplate::create([
            'name' => 'B2B Enterprise Sales',
            'description' => 'Erweiterte Pipeline für Enterprise-Kunden',
            'is_system' => true,
            'is_default' => false,
        ]);

        $enterpriseSlots = [
            ['name' => 'Lead', 'color' => 'blue', 'order' => 1],
            ['name' => 'Discovery', 'color' => 'yellow', 'order' => 2],
            ['name' => 'Proposal', 'color' => 'orange', 'order' => 3],
            ['name' => 'Negotiation', 'color' => 'purple', 'order' => 4],
            ['name' => 'Closed Won', 'color' => 'green', 'order' => 5],
            ['name' => 'Closed Lost', 'color' => 'red', 'order' => 6],
        ];

        foreach ($enterpriseSlots as $slotData) {
            SalesBoardTemplateSlot::create([
                'sales_board_template_id' => $enterpriseTemplate->id,
                'name' => $slotData['name'],
                'color' => $slotData['color'],
                'order' => $slotData['order'],
            ]);
        }

        // E-Commerce Sales Template
        $ecommerceTemplate = SalesBoardTemplate::create([
            'name' => 'E-Commerce Sales',
            'description' => 'Pipeline für Online-Vertrieb und E-Commerce',
            'is_system' => true,
            'is_default' => false,
        ]);

        $ecommerceSlots = [
            ['name' => 'Interessent', 'color' => 'blue', 'order' => 1],
            ['name' => 'Warenkorb', 'color' => 'yellow', 'order' => 2],
            ['name' => 'Checkout', 'color' => 'orange', 'order' => 3],
            ['name' => 'Kauf', 'color' => 'green', 'order' => 4],
        ];

        foreach ($ecommerceSlots as $slotData) {
            SalesBoardTemplateSlot::create([
                'sales_board_template_id' => $ecommerceTemplate->id,
                'name' => $slotData['name'],
                'color' => $slotData['color'],
                'order' => $slotData['order'],
            ]);
        }
    }
}
