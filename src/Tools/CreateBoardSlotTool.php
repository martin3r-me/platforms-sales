<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;

class CreateBoardSlotTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.board_slots.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/board_slots - Erstellt eine neue Spalte (Slot) in einem Vertriebsboard. Slots sind die Kanban-Spalten, durch die Deals bewegt werden. Nutze "sales.boards.GET" um die Board-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext.',
                ],
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Boards.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Name der Spalte (z.B. "Qualifizierung", "Demo", "Angebot").',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Spalte.',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Farbe der Spalte (z.B. blue, green, yellow, orange, red, purple). Standard: blue.',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Position der Spalte. Standard: ans Ende.',
                ],
            ],
            'required' => ['board_id', 'name'],
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
                return ToolResult::error('VALIDATION_ERROR', 'board_id ist erforderlich.');
            }

            $board = SalesBoard::where('id', (int) $boardId)->where('team_id', $teamId)->first();
            if (!$board) {
                return ToolResult::error('NOT_FOUND', 'Board nicht gefunden oder gehört nicht zum Team.');
            }

            $name = trim($arguments['name'] ?? '');
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $slot = SalesBoardSlot::create([
                'sales_board_id' => $board->id,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'color' => $arguments['color'] ?? 'blue',
                'order' => $arguments['order'] ?? $board->slots()->count() + 1,
            ]);

            return ToolResult::success([
                'id' => $slot->id,
                'board_id' => $board->id,
                'name' => $slot->name,
                'color' => $slot->color,
                'order' => $slot->order,
                'message' => "Spalte '{$slot->name}' im Board '{$board->name}' erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'board_slots', 'create', 'kanban', 'columns'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
            'confirmation_required' => false,
        ];
    }
}
