<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;

class MoveDealTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.deals.move.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/deals/{id}/move - Verschiebt einen Deal in einen anderen Slot (Kanban-Spalte) oder ein anderes Board. Kann auch als "gewonnen" markieren. Nutze "sales.deals.GET" für die Deal-ID und "sales.board_slots.GET" für Slot-IDs.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Deals.',
                ],
                'target_slot_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Ziel-Slot-ID. Wenn angegeben, wird der Deal in diesen Slot verschoben.',
                ],
                'target_board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Ziel-Board-ID. Wenn angegeben, wird der Deal in dieses Board verschoben.',
                ],
                'mark_as_won' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true = Deal als gewonnen markieren und aus dem Slot entfernen.',
                ],
            ],
            'required' => ['deal_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $found = $this->validateAndFindModel($arguments, $context, 'deal_id', SalesDeal::class, 'NOT_FOUND', 'Deal nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var SalesDeal $deal */
            $deal = $found['model'];

            $resolved = $this->resolveTeam(['team_id' => $deal->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $actions = [];

            // Als gewonnen markieren
            if (!empty($arguments['mark_as_won'])) {
                $deal->is_done = true;
                $deal->done_at = now();
                $deal->sales_board_slot_id = null;
                $actions[] = 'als gewonnen markiert';
            }

            // Board wechseln
            if (isset($arguments['target_board_id'])) {
                $targetBoard = SalesBoard::where('id', (int) $arguments['target_board_id'])
                    ->where('team_id', $deal->team_id)
                    ->first();
                if (!$targetBoard) {
                    return ToolResult::error('NOT_FOUND', 'Ziel-Board nicht gefunden oder gehört nicht zum Team.');
                }
                $deal->sales_board_id = $targetBoard->id;
                $deal->sales_board_slot_id = null; // Slot wird zurückgesetzt beim Board-Wechsel
                $actions[] = "in Board '{$targetBoard->name}' verschoben";
            }

            // Slot wechseln
            if (isset($arguments['target_slot_id'])) {
                $targetSlot = SalesBoardSlot::find((int) $arguments['target_slot_id']);
                if (!$targetSlot) {
                    return ToolResult::error('NOT_FOUND', 'Ziel-Slot nicht gefunden.');
                }

                // Sicherstellen, dass der Slot zum Board gehört
                $boardId = $deal->sales_board_id ?? $targetSlot->sales_board_id;
                if ($targetSlot->sales_board_id !== $boardId) {
                    return ToolResult::error('VALIDATION_ERROR', 'Der Slot gehört nicht zum Board des Deals.');
                }

                $deal->sales_board_id = $targetSlot->sales_board_id;
                $deal->sales_board_slot_id = $targetSlot->id;
                $deal->is_done = false;
                $deal->done_at = null;
                $actions[] = "in Slot '{$targetSlot->name}' verschoben";
            }

            if (empty($actions)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Verschiebe-Aktion angegeben. Nutze target_slot_id, target_board_id oder mark_as_won.');
            }

            $deal->save();

            return ToolResult::success([
                'id' => $deal->id,
                'title' => $deal->title,
                'board_id' => $deal->sales_board_id,
                'slot_id' => $deal->sales_board_slot_id,
                'is_done' => $deal->is_done,
                'actions' => $actions,
                'message' => "Deal '{$deal->title}': " . implode(', ', $actions) . '.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'deals', 'move', 'kanban', 'pipeline'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
