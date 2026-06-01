<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDealBillable;

class DeleteDealBillableTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_billables.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/deal_billables/{id} - Löscht eine Abrechnungsposition (Billable). Der Deal-Gesamtwert wird automatisch aktualisiert.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'billable_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID der Position.',
                ],
            ],
            'required' => ['billable_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $billableId = $arguments['billable_id'] ?? null;
            if (! $billableId) {
                return ToolResult::error('VALIDATION_ERROR', 'billable_id ist erforderlich.');
            }

            $billable = SalesDealBillable::find((int) $billableId);
            if (! $billable) {
                return ToolResult::error('NOT_FOUND', 'Position nicht gefunden.');
            }

            $deal = $billable->salesDeal;
            if (! $deal) {
                return ToolResult::error('NOT_FOUND', 'Zugehöriger Deal nicht gefunden.');
            }

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $name = $billable->name;
            $billable->delete();
            $deal->refresh();

            return ToolResult::success([
                'deleted_id' => $billableId,
                'deleted_name' => $name,
                'deal_total_value' => (float) $deal->deal_value,
                'message' => "Position '{$name}' gelöscht. Deal-Gesamtwert: " . number_format((float) $deal->deal_value, 2, ',', '.') . ' €.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'billables', 'delete', 'billing'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'destructive',
            'idempotent' => false,
            'confirmation_required' => true,
        ];
    }
}
