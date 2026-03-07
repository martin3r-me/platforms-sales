<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoard;

class DeleteBoardTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /sales/boards/{id} - Löscht ein Vertriebsboard und alle zugehörigen Slots. ACHTUNG: Deals werden NICHT gelöscht, sondern nur vom Board getrennt. Nutze "sales.boards.GET" um die Board-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des zu löschenden Boards.',
                ],
            ],
            'required' => ['board_id'],
        ]);
    }

    protected function getAccessAction(): string
    {
        return 'delete';
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $found = $this->validateAndFindModel($arguments, $context, 'board_id', SalesBoard::class, 'NOT_FOUND', 'Board nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var SalesBoard $board */
            $board = $found['model'];

            $resolved = $this->resolveTeam(['team_id' => $board->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $boardName = $board->name;
            $dealCount = $board->deals()->count();
            $slotCount = $board->slots()->count();

            // Deals vom Board trennen
            $board->deals()->update([
                'sales_board_id' => null,
                'sales_board_slot_id' => null,
            ]);

            // Slots löschen
            $board->slots()->delete();

            // Board löschen
            $board->delete();

            return ToolResult::success([
                'message' => "Board '{$boardName}' gelöscht. {$slotCount} Spalten entfernt, {$dealCount} Deals wurden vom Board getrennt (nicht gelöscht).",
                'detached_deals' => $dealCount,
                'deleted_slots' => $slotCount,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'boards', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'destructive',
            'idempotent' => false,
            'confirmation_required' => true,
        ];
    }
}
