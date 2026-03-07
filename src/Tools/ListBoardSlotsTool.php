<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;

class ListBoardSlotsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.board_slots.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/board_slots - Listet die Spalten (Slots) eines Vertriebsboards auf. Jeder Slot ist eine Kanban-Spalte (z.B. "Neu", "Erstkontakt", "Angebot", "Verhandlung"). Nutze "sales.boards.GET" um die Board-ID zu finden.';
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
                    'description' => 'Required: ID des Boards, dessen Slots angezeigt werden sollen.',
                ],
                'include_deal_counts' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Anzahl der Deals pro Slot anzeigen. Standard: false.',
                ],
            ],
            'required' => ['board_id'],
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

            $boardId = $arguments['board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'board_id ist erforderlich. Nutze "sales.boards.GET" um Boards zu finden.');
            }

            $board = SalesBoard::where('id', (int) $boardId)->where('team_id', $teamId)->first();
            if (!$board) {
                return ToolResult::error('NOT_FOUND', 'Board nicht gefunden oder gehört nicht zum Team.');
            }

            $query = SalesBoardSlot::query()->where('sales_board_id', $board->id);

            $allowedFields = ['id', 'name', 'color', 'order', 'created_at'];
            $this->applyStandardFilters($query, $arguments, $allowedFields);
            $this->applyStandardSearch($query, $arguments, ['name']);
            $this->applyStandardSort($query, $arguments, $allowedFields, 'order', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $includeDealCounts = !empty($arguments['include_deal_counts']);

            $slots = $result['data']->map(function (SalesBoardSlot $slot) use ($includeDealCounts) {
                $data = [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'description' => $slot->description,
                    'color' => $slot->color,
                    'order' => $slot->order,
                ];

                if ($includeDealCounts) {
                    $data['open_deals_count'] = $slot->deals()->where('is_done', false)->count();
                    $data['total_deal_value'] = (float) $slot->deals()->where('is_done', false)->sum('deal_value');
                }

                return $data;
            });

            return ToolResult::success([
                'board_id' => $board->id,
                'board_name' => $board->name,
                'slots' => $slots->toArray(),
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
            'tags' => ['sales', 'board_slots', 'list', 'kanban', 'columns'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
