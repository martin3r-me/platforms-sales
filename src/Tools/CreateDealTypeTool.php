<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesDealType;
use Illuminate\Support\Str;

/**
 * Tool zum Anlegen eines Deal-Typs (Lookup "types").
 *
 * Deal-Typen sind datengetrieben (kein Hardcode): Das Deal-Formular bietet alle
 * aktiven Typen zur Auswahl. Die ID wird in sales_deals.sales_deal_type_id
 * gespeichert.
 */
class CreateDealTypeTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.deal_types.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/deal-types - Legt einen neuen Deal-Typ (Lookup) an. Parameter: label (required, Anzeigename). name (optional technischer Code, lowercase-snake, sonst aus label abgeleitet, muss eindeutig sein). order, color, icon, is_active (optional). Prüfe vorher mit "sales.lookups.GET" (type=types), ob der Typ schon existiert.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'label' => ['type' => 'string', 'description' => 'Required: Anzeigename des Typs (z.B. "Rahmenvertrag").'],
                'name' => ['type' => 'string', 'description' => 'Optional: eindeutiger technischer Code (lowercase snake). Wird ohne Angabe aus label abgeleitet.'],
                'order' => ['type' => 'integer', 'description' => 'Optional: Sortierreihenfolge. Standard: 0.'],
                'color' => ['type' => 'string', 'description' => 'Optional: Farbschlüssel (z.B. blue, green, red). Standard: blue.'],
                'icon' => ['type' => 'string', 'description' => 'Optional: Heroicon-Name ohne Präfix (z.B. user-plus).'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: Ob der Typ auswählbar ist. Standard: true.'],
            ],
            'required' => ['label'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $label = trim((string) ($arguments['label'] ?? ''));
            if ($label === '') {
                return ToolResult::error('VALIDATION_ERROR', 'label ist erforderlich.');
            }

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') {
                $name = Str::slug($label, '_');
            }
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name konnte nicht aus label abgeleitet werden – bitte name explizit angeben.');
            }

            if (SalesDealType::where('name', $name)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', "Ein Deal-Typ mit name '{$name}' existiert bereits. Nutze sales.deal_types.PUT zum Ändern.");
            }

            $dealType = SalesDealType::create([
                'label' => $label,
                'name' => $name,
                'order' => (int) ($arguments['order'] ?? 0),
                'color' => trim((string) ($arguments['color'] ?? '')) ?: 'blue',
                'icon' => array_key_exists('icon', $arguments) ? (trim((string) $arguments['icon']) ?: null) : null,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'id' => $dealType->id,
                'name' => $dealType->name,
                'label' => $dealType->label,
                'order' => $dealType->order,
                'color' => $dealType->color,
                'is_active' => $dealType->is_active,
                'message' => "Deal-Typ '{$dealType->label}' (name: {$dealType->name}) erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Deal-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'deal_type', 'create'],
            'risk_level' => 'write',
        ];
    }
}
