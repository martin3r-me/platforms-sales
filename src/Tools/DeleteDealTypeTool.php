<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealType;

/**
 * Tool zum Löschen eines Deal-Typs (Lookup "types").
 *
 * Schützt vor dem Löschen eines Typs, der noch an Deals hängt
 * (sales_deals.sales_deal_type_id) – in dem Fall is_active=false
 * (deaktivieren) statt löschen.
 */
class DeleteDealTypeTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.deal_types.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/deal-types/{id} - Löscht einen Deal-Typ (Lookup). Hängt der Typ noch an Deals (sales_deal_type_id), schlägt das Löschen fehl – dann stattdessen via sales.deal_types.PUT auf is_active=false setzen. Nutze "sales.lookups.GET" (type=types) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Required: ID des zu löschenden Deal-Typs.'],
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

            $inUse = SalesDeal::where('sales_deal_type_id', $dealType->id)->count();
            if ($inUse > 0) {
                return ToolResult::error(
                    'CONFLICT',
                    "Deal-Typ '{$dealType->label}' wird noch von {$inUse} Deal(s) verwendet und kann nicht gelöscht werden. Setze ihn stattdessen über sales.deal_types.PUT auf is_active=false."
                );
            }

            $label = $dealType->label;
            $dealType->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'label' => $label,
                'message' => "Deal-Typ '{$label}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Deal-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'deal_type', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
