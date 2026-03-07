<?php

namespace Platform\Sales\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Sales\Models\SalesPriority;
use Platform\Sales\Models\SalesDealSource;
use Platform\Sales\Models\SalesDealType;

class ListLookupsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'sales.lookups.GET';
    }

    public function getDescription(): string
    {
        return 'GET /sales/lookups - Listet alle Lookup-Daten auf: Prioritäten, Deal-Quellen und Deal-Typen. Diese IDs werden beim Erstellen/Aktualisieren von Deals benötigt (sales_priority_id, sales_deal_source_id, sales_deal_type_id).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'description' => 'Optional: Nur einen Typ laden. Erlaubte Werte: priorities, sources, types. Standard: alle.',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $type = $arguments['type'] ?? null;
            $result = [];

            if (!$type || $type === 'priorities') {
                $result['priorities'] = SalesPriority::active()->ordered()->get()->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => $p->label,
                    'color' => $p->color,
                ])->toArray();
            }

            if (!$type || $type === 'sources') {
                $result['sources'] = SalesDealSource::active()->ordered()->get()->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'label' => $s->label,
                    'color' => $s->color,
                ])->toArray();
            }

            if (!$type || $type === 'types') {
                $result['types'] = SalesDealType::active()->ordered()->get()->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'label' => $t->label,
                    'color' => $t->color,
                ])->toArray();
            }

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['sales', 'lookups', 'priorities', 'sources', 'types'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
            'confirmation_required' => false,
        ];
    }
}
