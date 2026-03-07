<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class ListDealBillablesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deal_billables.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/deal_billables - Listet die Abrechnungspositionen (Billables) eines Deals auf. Jeder Deal kann mehrere Positionen haben (einmalig oder wiederkehrend). Nutze "sales.deals.GET" um die Deal-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Deals.',
                ],
            ],
            'required' => ['deal_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $dealId = $arguments['deal_id'] ?? null;
            if (!$dealId) {
                return ToolResult::error('VALIDATION_ERROR', 'deal_id ist erforderlich. Nutze "sales.deals.GET" um Deals zu finden.');
            }

            $deal = SalesDeal::find((int) $dealId);
            if (!$deal) {
                return ToolResult::error('NOT_FOUND', 'Deal nicht gefunden.');
            }

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $query = SalesDealBillable::query()->where('sales_deal_id', $deal->id);

            $allowedFields = ['id', 'name', 'amount', 'billing_type', 'billing_interval', 'total_value', 'expected_value', 'is_active', 'order', 'created_at'];
            $this->applyStandardFilters($query, $arguments, $allowedFields);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, $allowedFields, 'order', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $billables = $result['data']->map(function (SalesDealBillable $billable) {
                return [
                    'id' => $billable->id,
                    'name' => $billable->name,
                    'description' => $billable->description,
                    'amount' => (float) $billable->amount,
                    'billing_type' => $billable->billing_type,
                    'billing_interval' => $billable->billing_interval,
                    'duration_months' => $billable->duration_months,
                    'total_value' => (float) $billable->total_value,
                    'expected_value' => (float) $billable->expected_value,
                    'probability_percent' => $billable->probability_percent,
                    'is_active' => $billable->is_active,
                    'order' => $billable->order,
                    'billing_description' => $billable->billing_description,
                    'start_date' => $billable->start_date?->toDateString(),
                    'end_date' => $billable->end_date?->toDateString(),
                ];
            });

            return ToolResult::success([
                'deal_id' => $deal->id,
                'deal_title' => $deal->title,
                'billables' => $billables->toArray(),
                'summary' => [
                    'total_value' => (float) $deal->calculateTotalValueFromBillables(),
                    'expected_value' => (float) $deal->calculateExpectedValueFromBillables(),
                    'count' => $result['pagination']['total'],
                ],
                'pagination' => $result['pagination'],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['sales', 'deals', 'billables', 'billing', 'revenue'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
