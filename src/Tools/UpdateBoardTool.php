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
                'entity_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Organisation-Entity (Partner) dieses Boards setzen. 0 oder null entfernt den Partner. Nutze "organization.entities.GET".',
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

            // Partner-Entity setzen/entfernen (DimensionLink dimension=entity, wie Planner-Projekte)
            $partnerName = null;
            if (array_key_exists('entity_id', $arguments)) {
                $entityId = $arguments['entity_id'];
                if ($entityId === 0 || $entityId === '0') { $entityId = null; }

                if (! class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)) {
                    return ToolResult::error('UNAVAILABLE', 'Organization-Modul ist nicht verfügbar; Entity-Verknüpfung nicht möglich.');
                }

                if ($entityId) {
                    $entity = class_exists(\Platform\Organization\Models\OrganizationEntity::class)
                        ? \Platform\Organization\Models\OrganizationEntity::where('id', (int) $entityId)
                            ->where('team_id', $board->team_id)
                            ->first()
                        : null;
                    if (!$entity) {
                        return ToolResult::error('NOT_FOUND', 'Partner-Entity nicht gefunden oder gehört nicht zum Team. Nutze "organization.entities.GET".');
                    }
                    \Platform\Organization\Services\EntityDimensionBridge::replaceLinks(
                        'sales_board',
                        $board->id,
                        $entity->id,
                        ['team_id' => $board->team_id, 'created_by_user_id' => $context->user->id]
                    );
                    $partnerName = $entity->name;
                } else {
                    // Entfernen: bestehende Entity-Links abhängen
                    foreach (\Platform\Organization\Services\EntityDimensionBridge::linksForLinkables(['sales_board'], [$board->id]) as $link) {
                        if ($link->entity_id) {
                            \Platform\Organization\Services\EntityDimensionBridge::deleteLink($link->entity_id, 'sales_board', $board->id);
                        }
                    }
                }
                $updated[] = 'partner';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben. Verfügbare Felder: name, description, entity_id.');
            }

            $board->save();

            return ToolResult::success([
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
                'partner' => $partnerName,
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
