<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesDealSource;

/**
 * Tool zum Aktualisieren einer Deal-Quelle (Lookup "sources").
 */
class UpdateDealSourceTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.deal_sources.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/deal-sources/{id} - Aktualisiert eine Deal-Quelle (Lookup). Parameter: id (required). label, name, order, color, icon, is_active (optional). Nutze "sales.lookups.GET" (type=sources) für die ID. is_active=false deaktiviert eine Quelle, ohne sie zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Required: ID der Deal-Quelle.'],
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

            $source = SalesDealSource::find((int) $id);
            if (!$source) {
                return ToolResult::error('NOT_FOUND', 'Deal-Quelle nicht gefunden. Nutze "sales.lookups.GET" (type=sources).');
            }

            $updated = [];

            if (array_key_exists('label', $arguments)) {
                $label = trim((string) $arguments['label']);
                if ($label === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'label darf nicht leer sein.');
                }
                $source->label = $label;
                $updated[] = 'label';
            }

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                if (SalesDealSource::where('name', $name)->where('id', '!=', $source->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Eine andere Deal-Quelle mit name '{$name}' existiert bereits.");
                }
                $source->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('order', $arguments)) {
                $source->order = (int) $arguments['order'];
                $updated[] = 'order';
            }

            if (array_key_exists('color', $arguments)) {
                $source->color = trim((string) $arguments['color']) ?: 'blue';
                $updated[] = 'color';
            }

            if (array_key_exists('icon', $arguments)) {
                $source->icon = trim((string) $arguments['icon']) ?: null;
                $updated[] = 'icon';
            }

            if (array_key_exists('is_active', $arguments)) {
                $source->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $source->save();

            return ToolResult::success([
                'id' => $source->id,
                'name' => $source->name,
                'label' => $source->label,
                'order' => $source->order,
                'color' => $source->color,
                'is_active' => $source->is_active,
                'updated_fields' => $updated,
                'message' => "Deal-Quelle '{$source->label}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Deal-Quelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'deal_source', 'update'],
            'risk_level' => 'write',
        ];
    }
}
