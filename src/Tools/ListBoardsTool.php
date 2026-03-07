<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoard;

class ListBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/boards - Listet alle Vertriebsboards des Teams auf. Jedes Board enthält Kanban-Spalten (Slots) und Deals. REST-Parameter: filters, search, sort, limit, offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext.',
                ],
                'include_slots' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Slots (Spalten) mit laden. Standard: false.',
                ],
                'include_deal_counts' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Anzahl offener/gewonnener Deals pro Board. Standard: false.',
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

            $query = SalesBoard::query()->where('team_id', $teamId);

            $allowedFields = ['id', 'name', 'description', 'created_at', 'updated_at'];
            $this->applyStandardFilters($query, $arguments, $allowedFields);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, $allowedFields, 'name', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $boards = $result['data']->map(function (SalesBoard $board) use ($arguments) {
                $data = [
                    'id' => $board->id,
                    'name' => $board->name,
                    'description' => $board->description,
                    'created_at' => $board->created_at?->toIso8601String(),
                    'updated_at' => $board->updated_at?->toIso8601String(),
                ];

                if (!empty($arguments['include_slots'])) {
                    $data['slots'] = $board->slots()->orderBy('order')->get()->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'color' => $s->color,
                        'order' => $s->order,
                    ])->toArray();
                }

                if (!empty($arguments['include_deal_counts'])) {
                    $data['open_deals_count'] = $board->deals()->where('is_done', false)->count();
                    $data['won_deals_count'] = $board->deals()->where('is_done', true)->count();
                    $data['total_deal_value'] = (float) $board->deals()->where('is_done', false)->sum('deal_value');
                }

                return $data;
            });

            return ToolResult::success([
                'boards' => $boards->toArray(),
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
            'tags' => ['sales', 'boards', 'list', 'kanban'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
