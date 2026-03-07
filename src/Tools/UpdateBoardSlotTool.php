<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoardSlot;

class UpdateBoardSlotTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.board_slots.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/board_slots/{id} - Aktualisiert eine Spalte (Slot) eines Vertriebsboards. Nutze "sales.board_slots.GET" um die Slot-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Slots.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name der Spalte.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Farbe (blue, green, yellow, orange, red, purple).',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Position.',
                ],
            ],
            'required' => ['slot_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $found = $this->validateAndFindModel($arguments, $context, 'slot_id', SalesBoardSlot::class, 'NOT_FOUND', 'Slot nicht gefunden.');
            if ($found['error']) {
                return $found['error'];
            }
            /** @var SalesBoardSlot $slot */
            $slot = $found['model'];

            // Team-Zugriff über das Board prüfen
            $board = $slot->salesBoard;
            if (!$board) {
                return ToolResult::error('NOT_FOUND', 'Zugehöriges Board nicht gefunden.');
            }

            $resolved = $this->resolveTeam(['team_id' => $board->team_id], $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $updated = [];

            if (isset($arguments['name'])) {
                $name = trim($arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'Name darf nicht leer sein.');
                }
                $slot->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('description', $arguments)) {
                $slot->description = $arguments['description'];
                $updated[] = 'description';
            }

            if (isset($arguments['color'])) {
                $slot->color = $arguments['color'];
                $updated[] = 'color';
            }

            if (isset($arguments['order'])) {
                $slot->order = (int) $arguments['order'];
                $updated[] = 'order';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben. Verfügbare Felder: name, description, color, order.');
            }

            $slot->save();

            return ToolResult::success([
                'id' => $slot->id,
                'board_id' => $slot->sales_board_id,
                'name' => $slot->name,
                'color' => $slot->color,
                'order' => $slot->order,
                'updated_fields' => $updated,
                'message' => "Spalte '{$slot->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'board_slots', 'update', 'kanban'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
