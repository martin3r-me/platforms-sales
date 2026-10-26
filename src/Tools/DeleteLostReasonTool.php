<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesLostReason;

/**
 * Tool zum Löschen eines Verlustgrunds (Lookup "lost_reasons").
 *
 * Schützt vor dem Löschen eines Grunds, der noch an Deals hängt
 * (sales_deals.lost_reason_code) – in dem Fall is_active=false (deaktivieren)
 * statt löschen.
 */
class DeleteLostReasonTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.lost_reasons.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/lost-reasons/{id} - Löscht einen Verlustgrund (Lookup). Hängt der Grund noch an Deals (lost_reason_code), schlägt das Löschen fehl – dann stattdessen via sales.lost_reasons.PUT auf is_active=false setzen. Nutze "sales.lookups.GET" (type=lost_reasons) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des zu löschenden Verlustgrunds.',
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

            $reason = SalesLostReason::find((int) $id);
            if (!$reason) {
                return ToolResult::error('NOT_FOUND', 'Verlustgrund nicht gefunden. Nutze "sales.lookups.GET" (type=lost_reasons).');
            }

            $inUse = SalesDeal::where('lost_reason_code', $reason->code)->count();
            if ($inUse > 0) {
                return ToolResult::error(
                    'CONFLICT',
                    "Verlustgrund '{$reason->name}' wird noch von {$inUse} Deal(s) verwendet und kann nicht gelöscht werden. Setze ihn stattdessen über sales.lost_reasons.PUT auf is_active=false."
                );
            }

            $name = $reason->name;
            $reason->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'name' => $name,
                'message' => "Verlustgrund '{$name}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Verlustgrunds: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['sales', 'lookup', 'lost_reason', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
