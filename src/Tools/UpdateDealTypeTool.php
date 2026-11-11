<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesDealType;

/**
 * Tool zum Aktualisieren eines Deal-Typs (Lookup "types").
 */
class UpdateDealTypeTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.deal_types.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/deal-types/{id} - Aktualisiert einen Deal-Typ (Lookup). Parameter: id (required). label, name, order, color, icon, is_active (optional). Nutze "sales.lookups.GET" (type=types) für die ID. is_active=false deaktiviert einen Typ, ohne ihn zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Required: ID des Deal-Typs.'],
                'label' => ['type' => 'string', 'description' => 'Optional: neuer Anzeigename.'],
                'name' => ['type' => 'string', 'description' => 'Optional: neuer eindeutiger Code (lowercase snake).'],
                'order' => ['type' => 'integer', 'description' => 'Optional: neue Sortierreihenfolge.'],
                'color' => ['type' => 'string', 'description' => 'Optional: neuer Farbschlüssel.'],
                'icon' => ['type' => 'string', 'description' => 'Optional: neuer Heroicon-Name (leer = entfernen).'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: aktiv/inaktiv setzen.'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['id'] ?? null;
            if (!is_numeric($id)) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $dealType = SalesDealType::find((int) $id);
            if (!$dealType) {
                return ToolResult::error('NOT_FOUND', 'Deal-Typ nicht gefunden. Nutze "sales.lookups.GET" (type=types).');
            }

            $updated = [];

            if (array_key_exists('label', $arguments)) {
                $label = trim((string) $arguments['label']);
                if ($label === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'label darf nicht leer sein.');
                }
                $dealType->label = $label;
                $updated[] = 'label';
            }

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                if (SalesDealType::where('name', $name)->where('id', '!=', $dealType->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Ein anderer Deal-Typ mit name '{$name}' existiert bereits.");
                }
                $dealType->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('order', $arguments)) {
                $dealType->order = (int) $arguments['order'];
                $updated[] = 'order';
            }

            if (array_key_exists('color', $arguments)) {
                $dealType->color = trim((string) $arguments['color']) ?: 'blue';
                $updated[] = 'color';
            }

            if (array_key_exists('icon', $arguments)) {
                $dealType->icon = trim((string) $arguments['icon']) ?: null;
                $updated[] = 'icon';
            }

            if (array_key_exists('is_active', $arguments)) {
                $dealType->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $dealType->save();

            return ToolResult::success([
                'id' => $dealType->id,
                'name' => $dealType->name,
                'label' => $dealType->label,
                'order' => $dealType->order,
                'color' => $dealType->color,
                'is_active' => $dealType->is_active,
                'updated_fields' => $updated,
                'message' => "Deal-Typ '{$dealType->label}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Deal-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'deal_type', 'update'],
            'risk_level' => 'write',
        ];
    }
}
