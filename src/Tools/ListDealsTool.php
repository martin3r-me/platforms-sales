<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;

class ListDealsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deals.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/deals - Listet Deals auf. Kann nach Board, Slot, Status (offen/gewonnen), Verantwortlichem und vielen weiteren Feldern gefiltert werden. REST-Parameter: filters, search, sort, limit, offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext.',
                ],
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Nur Deals dieses Boards anzeigen.',
                ],
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Nur Deals in diesem Slot (Spalte) anzeigen.',
                ],
                'is_done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = nur gewonnene Deals, false = nur offene Deals.',
                ],
                'user_in_charge_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Nur Deals dieses verantwortlichen Users.',
                ],
                'is_hot' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Nur heiße Deals anzeigen.',
                ],
                'is_starred' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Nur markierte Deals anzeigen.',
                ],
                'include_billables' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Billable-Positionen mit laden. Standard: false.',
                ],
                'include_relations' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Board, Slot, Priority, Source, Type mit laden. Standard: false.',
                ],
            ],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = $resolved['team_id'];

            $query = SalesDeal::query()->where('team_id', $teamId);

            // Direkte Filter
            if (isset($arguments['board_id'])) {
                $query->where('sales_board_id', (int) $arguments['board_id']);
            }
            if (isset($arguments['slot_id'])) {
                $query->where('sales_board_slot_id', (int) $arguments['slot_id']);
            }
            if (isset($arguments['is_done'])) {
                $query->where('is_done', (bool) $arguments['is_done']);
            }
            if (isset($arguments['user_in_charge_id'])) {
                $query->where('user_in_charge_id', (int) $arguments['user_in_charge_id']);
            }
            if (isset($arguments['is_hot'])) {
                $query->where('is_hot', (bool) $arguments['is_hot']);
            }
            if (isset($arguments['is_starred'])) {
                $query->where('is_starred', (bool) $arguments['is_starred']);
            }

            $allowedFields = [
                'id', 'title', 'description', 'deal_value', 'probability_percent',
                'is_done', 'is_hot', 'is_starred', 'due_date', 'close_date',
                'sales_board_id', 'sales_board_slot_id', 'user_in_charge_id',
                'sales_priority_id', 'sales_deal_source_id', 'sales_deal_type_id',
                'billing_interval', 'created_at', 'updated_at',
            ];

            $this->applyStandardFilters($query, $arguments, $allowedFields);
            $this->applyStandardSearch($query, $arguments, ['title', 'description', 'notes', 'competitor', 'next_step']);
            $this->applyStandardSort($query, $arguments, $allowedFields, 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $includeRelations = !empty($arguments['include_relations']);
            $includeBillables = !empty($arguments['include_billables']);

            if ($includeRelations) {
                $result['data']->load(['salesBoard', 'salesBoardSlot', 'priority', 'dealSource', 'dealType', 'userInCharge']);
            }
            if ($includeBillables) {
                $result['data']->load('activeBillables');
            }

            $deals = $result['data']->map(function (SalesDeal $deal) use ($includeRelations, $includeBillables) {
                $data = [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'description' => $deal->description,
                    'deal_value' => (float) ($deal->deal_value ?? 0),
                    'expected_value' => (float) ($deal->expected_value ?? 0),
                    'probability_percent' => $deal->probability_percent,
                    'is_done' => $deal->is_done,
                    'is_hot' => $deal->is_hot,
                    'is_starred' => $deal->is_starred,
                    'due_date' => $deal->due_date?->toDateString(),
                    'close_date' => $deal->close_date?->toDateString(),
                    'next_step' => $deal->next_step,
                    'next_step_date' => $deal->next_step_date?->toDateString(),
                    'billing_interval' => $deal->billing_interval,
                    'billing_duration_months' => $deal->billing_duration_months,
                    'monthly_recurring_value' => (float) ($deal->monthly_recurring_value ?? 0),
                    'sales_board_id' => $deal->sales_board_id,
                    'sales_board_slot_id' => $deal->sales_board_slot_id,
                    'user_in_charge_id' => $deal->user_in_charge_id,
                    'created_at' => $deal->created_at?->toIso8601String(),
                ];

                if ($includeRelations) {
                    $data['board_name'] = $deal->salesBoard?->name;
                    $data['slot_name'] = $deal->salesBoardSlot?->name;
                    $data['priority'] = $deal->priority?->name;
                    $data['deal_source'] = $deal->dealSource?->name;
                    $data['deal_type'] = $deal->dealType?->name;
                    $data['user_in_charge_name'] = $deal->userInCharge?->name;
                }

                if ($includeBillables) {
                    $data['billables'] = $deal->activeBillables->map(fn ($b) => [
                        'id' => $b->id,
                        'name' => $b->name,
                        'amount' => (float) $b->amount,
                        'billing_type' => $b->billing_type,
                        'billing_interval' => $b->billing_interval,
                        'total_value' => (float) $b->total_value,
                        'expected_value' => (float) $b->expected_value,
                        'probability_percent' => $b->probability_percent,
                    ])->toArray();
                }

                return $data;
            });

            return ToolResult::success([
                'deals' => $deals->toArray(),
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
            'tags' => ['sales', 'deals', 'list', 'pipeline'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
