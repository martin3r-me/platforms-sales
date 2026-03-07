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
use Platform\Sales\Models\SalesBoardTemplate;

class CreateBoardTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /sales/boards - Erstellt ein neues Vertriebsboard. Optional kann ein Template angegeben werden, um vorgefertigte Spalten zu übernehmen. Ohne Template werden Standard-Spalten (Neu, Erstkontakt, Angebot, Verhandlung) angelegt.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Name des Boards.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Boards.',
                ],
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID eines Board-Templates. Wenn angegeben, werden die Spalten aus dem Template übernommen.',
                ],
            ],
            'required' => ['name'],
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

            $name = trim($arguments['name'] ?? '');
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            // Via Template erstellen
            if (!empty($arguments['template_id'])) {
                $template = SalesBoardTemplate::find((int) $arguments['template_id']);
                if (!$template) {
                    return ToolResult::error('NOT_FOUND', 'Template nicht gefunden. Nutze "sales.templates.GET" um verfügbare Templates zu sehen.');
                }

                if (!$template->is_system && $template->team_id !== $teamId) {
                    return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf dieses Template.');
                }

                $board = $template->createBoard([
                    'name' => $name,
                    'description' => $arguments['description'] ?? $template->description,
                ]);
            } else {
                // Ohne Template: Standard-Slots
                $board = new SalesBoard();
                $board->name = $name;
                $board->description = $arguments['description'] ?? null;
                $board->user_id = $context->user->id;
                $board->team_id = $teamId;
                $board->order = SalesBoard::where('team_id', $teamId)->max('order') + 1;
                $board->save();

                $defaultSlots = [
                    ['name' => 'Neu', 'color' => 'blue'],
                    ['name' => 'Erstkontakt', 'color' => 'yellow'],
                    ['name' => 'Angebot', 'color' => 'orange'],
                    ['name' => 'Verhandlung', 'color' => 'purple'],
                ];

                foreach ($defaultSlots as $index => $slotData) {
                    SalesBoardSlot::create([
                        'sales_board_id' => $board->id,
                        'name' => $slotData['name'],
                        'color' => $slotData['color'],
                        'order' => $index + 1,
                    ]);
                }
            }

            $board->refresh();

            return ToolResult::success([
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
                'slots' => $board->slots()->orderBy('order')->get()->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'color' => $s->color,
                    'order' => $s->order,
                ])->toArray(),
                'message' => "Board '{$board->name}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['sales', 'boards', 'create', 'kanban'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
            'confirmation_required' => false,
        ];
    }
}
