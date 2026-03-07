<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoard;

class UpdateBoardTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /sales/boards/{id} - Aktualisiert ein bestehendes Vertriebsboard (Name, Beschreibung). Nutze "sales.boards.GET" um die Board-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Boards.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name des Boards.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
            ],
            'required' => ['board_id'],
        ]);
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

            // Team-Zugriff prüfen
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
                $board->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('description', $arguments)) {
                $board->description = $arguments['description'];
                $updated[] = 'description';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben. Verfügbare Felder: name, description.');
            }

            $board->save();

            return ToolResult::success([
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
                'updated_fields' => $updated,
                'message' => "Board '{$board->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'boards', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
