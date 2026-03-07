<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Sales\Tools\Concerns\ResolvesSalesTeam;
use Platform\Sales\Models\SalesBoardTemplate;

class ListTemplatesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations, ResolvesSalesTeam;

    public function getName(): string
    {
        return 'sales.templates.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/templates - Listet verfügbare Board-Templates auf. Templates können zum Erstellen neuer Boards mit vordefinierten Spalten genutzt werden ("sales.boards.POST" mit template_id).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Standard: aus dem Kontext. Zeigt Team-eigene und System-Templates.',
                ],
                'include_slots' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Template-Slots mit laden. Standard: false.',
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

            $query = SalesBoardTemplate::query()
                ->where(function ($q) use ($teamId) {
                    $q->where('team_id', $teamId)
                      ->orWhere('is_system', true);
                });

            $allowedFields = ['id', 'name', 'description', 'is_default', 'is_system', 'created_at'];
            $this->applyStandardFilters($query, $arguments, $allowedFields);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, $allowedFields, 'name', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $templates = $result['data']->map(function (SalesBoardTemplate $template) use ($arguments) {
                $data = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_default' => $template->is_default,
                    'is_system' => $template->is_system,
                    'created_at' => $template->created_at?->toIso8601String(),
                ];

                if (!empty($arguments['include_slots'])) {
                    $data['slots'] = $template->slots()->orderBy('order')->get()->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'color' => $s->color,
                        'order' => $s->order,
                    ])->toArray();
                }

                return $data;
            });

            return ToolResult::success([
                'templates' => $templates->toArray(),
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
            'tags' => ['sales', 'templates', 'list', 'board'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
