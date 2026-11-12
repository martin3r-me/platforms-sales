<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesRevenueCategory;

/**
 * Tool zum Aktualisieren einer Umsatzkategorie (Lookup "revenue_categories").
 */
class UpdateRevenueCategoryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.revenue_categories.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/revenue-categories/{id} - Aktualisiert eine Umsatzkategorie (Lookup). Parameter: id (required). name, code, order, is_active (optional). Nutze "sales.lookups.GET" (type=revenue_categories) für die ID. is_active=false deaktiviert eine Kategorie, ohne sie zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID der Umsatzkategorie.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer Anzeigename.',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer eindeutiger Code.',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: neue Sortierreihenfolge.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv setzen.',
                ],
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

            $category = SalesRevenueCategory::find((int) $id);
            if (!$category) {
                return ToolResult::error('NOT_FOUND', 'Umsatzkategorie nicht gefunden. Nutze "sales.lookups.GET" (type=revenue_categories).');
            }

            $updated = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $category->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('code', $arguments)) {
                $code = trim((string) $arguments['code']);
                if ($code === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'code darf nicht leer sein.');
                }
                if (SalesRevenueCategory::where('code', $code)->where('id', '!=', $category->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Eine andere Umsatzkategorie mit code '{$code}' existiert bereits.");
                }
                $category->code = $code;
                $updated[] = 'code';
            }

            if (array_key_exists('order', $arguments)) {
                $category->order = (int) $arguments['order'];
                $updated[] = 'order';
            }

            if (array_key_exists('is_active', $arguments)) {
                $category->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $category->save();

            return ToolResult::success([
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'code' => $category->code,
                'order' => $category->order,
                'is_active' => $category->is_active,
                'updated_fields' => $updated,
                'message' => "Umsatzkategorie '{$category->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Umsatzkategorie: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'revenue_category', 'update'],
            'risk_level' => 'write',
        ];
    }
}
