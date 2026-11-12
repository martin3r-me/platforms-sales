<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesRevenueCategory;

/**
 * Tool zum Löschen einer Umsatzkategorie (Lookup "revenue_categories").
 *
 * Hinweis: Noch keine FK-Nutzung an Deals – sobald Kategorien an Deals hängen,
 * hier analog zu den Verlustgründen einen In-Use-Schutz ergänzen.
 */
class DeleteRevenueCategoryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.revenue_categories.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/revenue-categories/{id} - Löscht eine Umsatzkategorie (Lookup). Alternativ via sales.revenue_categories.PUT auf is_active=false deaktivieren. Nutze "sales.lookups.GET" (type=revenue_categories) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID der zu löschenden Umsatzkategorie.',
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

            $name = $category->name;
            $category->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'name' => $name,
                'message' => "Umsatzkategorie '{$name}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Umsatzkategorie: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'revenue_category', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
