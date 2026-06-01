<?php

namespace Platform\Sales\Organization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class SalesEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['sales_deal', 'sales_board'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'sales_deal' => [
                'label' => 'Deals',
                'singular' => 'Deal',
                'icon' => 'currency-euro',
                'route' => 'sales.deals.show',
            ],
            'sales_board' => [
                'label' => 'Vertriebsboards',
                'singular' => 'Vertriebsboard',
                'icon' => 'view-columns',
                'route' => 'sales.boards.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        if ($morphAlias === 'sales_deal') {
            $query->with(['salesBoardSlot:id,name', 'priority:id,name'])
                ->withCount([
                    'billables as billables_count',
                    'activeBillables as active_billables_count',
                ]);
        }

        if ($morphAlias === 'sales_board') {
            $query->withCount([
                'deals',
                'deals as open_deals_count' => fn ($q) => $q->where('is_done', false)->whereNull('lost_at'),
                'deals as won_deals_count' => fn ($q) => $q->where('is_done', true),
                'deals as lost_deals_count' => fn ($q) => $q->whereNotNull('lost_at'),
            ]);
        }
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        if ($morphAlias === 'sales_deal') {
            return [
                'status' => $model->isWon() ? 'won' : ($model->isLost() ? 'lost' : 'open'),
                'is_hot' => (bool) $model->is_hot,
                'slot_name' => $model->salesBoardSlot?->name,
                'priority_name' => $model->priority?->name,
                'deal_value' => (float) ($model->deal_value ?? 0),
                'probability_percent' => (int) ($model->probability_percent ?? 0),
                'expected_value' => (float) ($model->expected_value ?? 0),
                'billables_count' => (int) ($model->billables_count ?? 0),
                'due_date' => $model->due_date?->format('d.m.Y'),
            ];
        }

        if ($morphAlias === 'sales_board') {
            return [
                'deals_total' => (int) ($model->deals_count ?? 0),
                'deals_open' => (int) ($model->open_deals_count ?? 0),
                'deals_won' => (int) ($model->won_deals_count ?? 0),
                'deals_lost' => (int) ($model->lost_deals_count ?? 0),
            ];
        }

        return [];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'sales_deal' => [
                ['field' => 'status', 'format' => 'badge'],
                ['field' => 'slot_name', 'format' => 'text'],
                ['field' => 'deal_value', 'format' => 'prefixed_text', 'prefix' => '€'],
                ['field' => 'probability_percent', 'format' => 'percentage'],
                ['field' => 'is_hot', 'format' => 'boolean_pinned'],
                ['field' => 'due_date', 'format' => 'text'],
            ],
            'sales_board' => [
                ['field' => 'deals_open', 'format' => 'count_ratio', 'done_field' => 'deals_won', 'suffix' => 'Deals'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        return match ($morphAlias) {
            'sales_deal' => $this->dealMetrics($linksByEntity),
            'sales_board' => $this->boardMetrics($linksByEntity),
            default => [],
        };
    }

    protected function dealMetrics(array $linksByEntity): array
    {
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        // Batch-load deal data
        $deals = DB::table('sales_deals')
            ->whereIn('id', $allIds)
            ->whereNull('deleted_at')
            ->select([
                'id',
                'is_done',
                'lost_at',
                'deal_value',
                'probability_percent',
            ])
            ->get()
            ->keyBy('id');

        // Batch-load billable aggregates per deal
        $billableAgg = DB::table('sales_deal_billables')
            ->whereIn('sales_deal_id', $allIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('sales_deal_id')
            ->select([
                'sales_deal_id',
                DB::raw('SUM(total_value) as total_value'),
                DB::raw('SUM(expected_value) as expected_value'),
                DB::raw('SUM(CASE WHEN billing_type = \'recurring\' THEN total_value ELSE 0 END) as recurring_value'),
                DB::raw('SUM(CASE WHEN billing_type = \'one_time\' THEN total_value ELSE 0 END) as one_time_value'),
            ])
            ->get()
            ->keyBy('sales_deal_id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = count($ids);
            $open = 0;
            $won = 0;
            $lost = 0;
            $pipelineValue = 0.0;
            $weightedPipeline = 0.0;
            $wonValue = 0.0;
            $recurringValue = 0.0;
            $oneTimeValue = 0.0;

            foreach ($ids as $id) {
                $deal = $deals[$id] ?? null;
                if (! $deal) {
                    continue;
                }

                $isWon = (bool) $deal->is_done;
                $isLost = $deal->lost_at !== null;
                $isOpen = ! $isWon && ! $isLost;

                if ($isOpen) {
                    $open++;
                } elseif ($isWon) {
                    $won++;
                } elseif ($isLost) {
                    $lost++;
                }

                $dealValue = (float) ($deal->deal_value ?? 0);
                $probability = (int) ($deal->probability_percent ?? 0);

                if ($isOpen) {
                    $pipelineValue += $dealValue;
                    $weightedPipeline += $dealValue * $probability / 100;
                }

                if ($isWon) {
                    $wonValue += $dealValue;
                }

                // Billable aggregates
                $ba = $billableAgg[$id] ?? null;
                if ($ba && ($isOpen || $isWon)) {
                    $recurringValue += (float) ($ba->recurring_value ?? 0);
                    $oneTimeValue += (float) ($ba->one_time_value ?? 0);
                }
            }

            $result[$entityId] = [
                'sales_deals_total' => $total,
                'sales_deals_open' => $open,
                'sales_deals_won' => $won,
                'sales_deals_lost' => $lost,
                'sales_pipeline_value' => round($pipelineValue, 2),
                'sales_weighted_pipeline' => round($weightedPipeline, 2),
                'sales_won_value' => round($wonValue, 2),
                'sales_recurring_value' => round($recurringValue, 2),
                'sales_one_time_value' => round($oneTimeValue, 2),
            ];
        }

        return $result;
    }

    protected function boardMetrics(array $linksByEntity): array
    {
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        // Aggregate deal counts and values per board
        $boardStats = DB::table('sales_deals')
            ->whereIn('sales_board_id', $allIds)
            ->whereNull('deleted_at')
            ->groupBy('sales_board_id')
            ->select([
                'sales_board_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_done = 0 AND lost_at IS NULL THEN 1 ELSE 0 END) as open_count'),
                DB::raw('SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) as won_count'),
                DB::raw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) as lost_count'),
                DB::raw('SUM(CASE WHEN is_done = 0 AND lost_at IS NULL THEN COALESCE(deal_value, 0) ELSE 0 END) as pipeline_value'),
                DB::raw('SUM(CASE WHEN is_done = 1 THEN COALESCE(deal_value, 0) ELSE 0 END) as won_value'),
            ])
            ->get()
            ->keyBy('sales_board_id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $totalDeals = 0;
            $openDeals = 0;
            $wonDeals = 0;
            $lostDeals = 0;
            $pipelineValue = 0.0;
            $wonValue = 0.0;

            foreach ($ids as $id) {
                $stats = $boardStats[$id] ?? null;
                if ($stats) {
                    $totalDeals += (int) $stats->total;
                    $openDeals += (int) $stats->open_count;
                    $wonDeals += (int) $stats->won_count;
                    $lostDeals += (int) $stats->lost_count;
                    $pipelineValue += (float) $stats->pipeline_value;
                    $wonValue += (float) $stats->won_value;
                }
            }

            $conversionRate = $totalDeals > 0
                ? round(($wonDeals / $totalDeals) * 100, 1)
                : 0.0;

            $result[$entityId] = [
                'sales_board_deals_total' => $totalDeals,
                'sales_board_deals_open' => $openDeals,
                'sales_board_deals_won' => $wonDeals,
                'sales_board_deals_lost' => $lostDeals,
                'sales_board_pipeline_value' => round($pipelineValue, 2),
                'sales_board_won_value' => round($wonValue, 2),
                'sales_board_conversion_rate' => $conversionRate,
            ];
        }

        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        if ($morphAlias !== 'sales_board' || empty($linkableIds)) {
            return [];
        }

        // Board → Deals cascade for activity feed
        $dealIds = SalesDeal::whereIn('sales_board_id', $linkableIds)
            ->pluck('id')
            ->all();

        return $dealIds ? [SalesDeal::class => $dealIds] : [];
    }

    public function metricDefinitions(): array
    {
        return [
            // Deal-level metrics
            'sales_deals_total' => [
                'label' => 'Deals (gesamt)',
                'group' => 'sales',
                'direction' => 'neutral',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_deals_open' => [
                'label' => 'Deals (offen)',
                'group' => 'sales',
                'direction' => 'neutral',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'stock',
                'pair' => 'sales_deals_total',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_deals_won' => [
                'label' => 'Deals (gewonnen)',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_deals_lost' => [
                'label' => 'Deals (verloren)',
                'group' => 'sales',
                'direction' => 'down',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_pipeline_value' => [
                'label' => 'Pipeline-Wert (offen)',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'currency',
                'dimension' => 'potential',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_weighted_pipeline' => [
                'label' => 'Pipeline gewichtet',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'currency',
                'dimension' => 'potential',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_won_value' => [
                'label' => 'Gewonnener Umsatz',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'currency',
                'dimension' => 'revenue',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_recurring_value' => [
                'label' => 'Recurring-Umsatz',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'currency',
                'dimension' => 'revenue',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_one_time_value' => [
                'label' => 'Einmal-Umsatz',
                'group' => 'sales',
                'direction' => 'neutral',
                'unit' => 'currency',
                'dimension' => 'revenue',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],

            // Board-level metrics
            'sales_board_deals_total' => [
                'label' => 'Board: Deals gesamt',
                'group' => 'sales',
                'direction' => 'neutral',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_board_deals_open' => [
                'label' => 'Board: Deals offen',
                'group' => 'sales',
                'direction' => 'neutral',
                'unit' => 'count',
                'pair' => 'sales_board_deals_total',
                'dimension' => 'throughput',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_board_deals_won' => [
                'label' => 'Board: Deals gewonnen',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_board_deals_lost' => [
                'label' => 'Board: Deals verloren',
                'group' => 'sales',
                'direction' => 'down',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_board_pipeline_value' => [
                'label' => 'Board: Pipeline-Wert',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'currency',
                'dimension' => 'potential',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_board_won_value' => [
                'label' => 'Board: Gewonnener Umsatz',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'currency',
                'dimension' => 'revenue',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sales_board_conversion_rate' => [
                'label' => 'Board: Conversion-Rate',
                'group' => 'sales',
                'direction' => 'up',
                'unit' => 'percentage',
                'dimension' => 'throughput',
                'type' => 'modulator',
                'aggregation_mode' => 'own',
            ],
        ];
    }
}
