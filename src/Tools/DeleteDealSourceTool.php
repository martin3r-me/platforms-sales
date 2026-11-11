<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealSource;

/**
 * Tool zum Löschen einer Deal-Quelle (Lookup "sources").
 *
 * Schützt vor dem Löschen einer Quelle, die noch an Deals hängt
 * (sales_deals.sales_deal_source_id) – in dem Fall is_active=false
 * (deaktivieren) statt löschen.
 */
class DeleteDealSourceTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.deal_sources.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/deal-sources/{id} - Löscht eine Deal-Quelle (Lookup). Hängt die Quelle noch an Deals (sales_deal_source_id), schlägt das Löschen fehl – dann stattdessen via sales.deal_sources.PUT auf is_active=false setzen. Nutze "sales.lookups.GET" (type=sources) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Required: ID der zu löschenden Deal-Quelle.'],
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

            $inUse = SalesDeal::where('sales_deal_source_id', $source->id)->count();
            if ($inUse > 0) {
                return ToolResult::error(
                    'CONFLICT',
                    "Deal-Quelle '{$source->label}' wird noch von {$inUse} Deal(s) verwendet und kann nicht gelöscht werden. Setze sie stattdessen über sales.deal_sources.PUT auf is_active=false."
                );
            }

            $label = $source->label;
            $source->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'label' => $label,
                'message' => "Deal-Quelle '{$label}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Deal-Quelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'deal_source', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
