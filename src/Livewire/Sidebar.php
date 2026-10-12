<?php

namespace Platform\Sales\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;
use Platform\Sales\Models\SalesBoardTemplate;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    #[On('updateSidebar')]
    public function updateSidebar()
    {

    }

    #[On('create-sales-board')]
    public function createSalesBoard()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        // 1. Neues Sales Board anlegen
        $board = new SalesBoard();
        $board->name = 'Neues Vertriebsboard';
        $board->user_id = $user->id;
        $board->team_id = $teamId;
        $board->order = SalesBoard::where('team_id', $teamId)->max('order') + 1;
        $board->save();

        // 2. Standard-Slots anlegen: Neu, Erstkontakt, Angebot, Verhandlung
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

        return redirect()->route('sales.boards.show', ['salesBoard' => $board->id]);
    }

    #[On('create-board-from-template')]
    public function createBoardFromTemplate($templateId)
    {
        $user = Auth::user();
        $template = SalesBoardTemplate::findOrFail($templateId);

        // Prüfe Berechtigung
        if (!$template->is_system && $template->team_id !== $user->currentTeam->id) {
            return;
        }

        $board = $template->createBoard([
            'name' => $template->name . ' - ' . now()->format('d.m.Y'),
            'description' => $template->description,
        ]);

        return redirect()->route('sales.boards.show', ['salesBoard' => $board->id]);
    }

    /**
     * Team-Familie im Scope: Root-Team + alle (rekursiven) Kind-Teams. Der Org-Baum
     * ist am Root-Team verankert; ein User in einem Kind-Team muss die dort hängenden
     * Boards der Familie sehen. Spiegelt die Planner-Sidebar.
     */
    protected function relevantTeamIds($user): array
    {
        $current = $user?->currentTeam;
        if (!$current) {
            return [];
        }

        $root = $current->getRootTeam();
        $ids = [$root->id];
        $walker = function ($team) use (&$walker, &$ids) {
            foreach ($team->childTeams()->get() as $child) {
                $ids[] = $child->id;
                $walker($child);
            }
        };
        $walker($root);

        return array_values(array_unique($ids));
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('sales::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedBoards' => collect(),
            ]);
        }

        $teamIds = $this->relevantTeamIds($user);

        $boards = SalesBoard::query()
            ->whereIn('team_id', $teamIds)
            ->orderBy('name')
            ->get();

        $entityTypeGroups = collect();
        $linkedBoardIds = [];

        // Organization ist optionale Abhängigkeit. Ist sie da → Boards nach
        // Partner-Entity gruppieren (wie Planner-Projekte), sonst flache Liste.
        $orgAvailable = class_exists(\Platform\Organization\Services\EntityDimensionBridge::class)
            && class_exists(\Platform\Organization\Models\OrganizationEntity::class)
            && class_exists(\Platform\Organization\Services\EntityAncestorService::class);

        if ($orgAvailable && $boards->isNotEmpty()) {
            [$entityTypeGroups, $linkedBoardIds] = $this->buildEntityGroups($boards);
        }

        $unlinkedBoards = $boards
            ->filter(fn ($b) => !in_array($b->id, $linkedBoardIds))
            ->values();

        return view('sales::livewire.sidebar', [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedBoards' => $unlinkedBoards,
        ]);
    }

    /**
     * Baut die Gruppierung EntityType → Entity-Baum → Boards (analog Planner).
     *
     * @return array{0: \Illuminate\Support\Collection, 1: array<int>}
     */
    protected function buildEntityGroups($boards): array
    {
        $bridge = \Platform\Organization\Services\EntityDimensionBridge::class;

        $boardIds = $boards->pluck('id')->toArray();

        // 1. Entity-Verknüpfungen laden via DimensionLink
        $entityBoardMap = []; // entity_id => [board_ids]
        $linkedBoardIds = [];

        foreach ($bridge::linksForLinkables(['sales_board'], $boardIds) as $link) {
            if (!$link->entity_id) {
                continue;
            }
            $entityBoardMap[$link->entity_id][] = $link->linkable_id;
            $linkedBoardIds[] = $link->linkable_id;
        }

        foreach ($entityBoardMap as $eid => $ids) {
            $entityBoardMap[$eid] = array_unique($ids);
        }
        $linkedBoardIds = array_unique($linkedBoardIds);

        if (empty($entityBoardMap)) {
            return [collect(), $linkedBoardIds];
        }

        // 2. Aufwärts-Traversierung: Baum-Parents ergänzen (nur struktureller Baum)
        $ancestorService = new \Platform\Organization\Services\EntityAncestorService();
        $channels = [];
        $expandedEntityIds = $ancestorService->expandEntitiesWithAncestors(array_keys($entityBoardMap), $channels);
        foreach ($expandedEntityIds as $eid) {
            if (!isset($entityBoardMap[$eid])) {
                $entityBoardMap[$eid] = [];
            }
        }

        // 3. Entities laden + Hierarchie aufbauen
        $entities = \Platform\Organization\Models\OrganizationEntity::with('type')
            ->whereIn('id', array_keys($entityBoardMap))
            ->get()
            ->keyBy('id');

        $hierarchy = $ancestorService->buildParentChildrenMap($entities, $channels);
        $entityChildrenMap = $hierarchy['parent_to_children'];
        $rootEntityIds = $hierarchy['roots'];

        // Rekursiver Baum-Builder
        $buildTree = function (int $entityId) use (&$buildTree, $entities, $entityChildrenMap, $entityBoardMap, $boards): ?array {
            $entity = $entities->get($entityId);
            if (!$entity) {
                return null;
            }

            $childIds = $entityChildrenMap[$entityId] ?? [];
            $childNodes = collect($childIds)
                ->map(fn ($childId) => $buildTree($childId))
                ->filter();

            $childrenByType = $childNodes
                ->groupBy(fn ($child) => $child['type_id'])
                ->map(function ($group) use ($entities) {
                    $firstChild = $group->first();
                    $typeEntity = $entities->get($firstChild['entity_id']);
                    $type = $typeEntity?->type;

                    return [
                        'type_id' => $firstChild['type_id'],
                        'type_name' => $type?->name ?? 'Sonstige',
                        'type_icon' => $type?->icon ?? null,
                        'sort_order' => $type?->sort_order ?? 999,
                        'children' => $group->sortBy('entity_name')->values(),
                    ];
                })
                ->sortBy('sort_order')
                ->values();

            $boardsHere = collect($entityBoardMap[$entityId] ?? [])
                ->map(fn ($bid) => $boards->firstWhere('id', $bid))
                ->filter()
                ->values();

            $totalBoards = $boardsHere->count();
            foreach ($childNodes as $child) {
                $totalBoards += $child['total_boards'];
            }

            // Entity nur anzeigen, wenn sie Boards hat oder Kinder mit Boards
            if ($totalBoards === 0) {
                return null;
            }

            return [
                'entity_id' => $entityId,
                'entity_name' => $entity->name,
                'type_id' => $entity->type?->id,
                'boards' => $boardsHere,
                'children_by_type' => $childrenByType,
                'total_boards' => $totalBoards,
            ];
        };

        // Root-Entities nach Typ gruppieren
        $groupedByType = [];
        foreach ($rootEntityIds as $entityId) {
            $entity = $entities->get($entityId);
            if (!$entity || !$entity->type) {
                continue;
            }

            $tree = $buildTree($entityId);
            if (!$tree) {
                continue;
            }

            $typeId = $entity->type->id;
            if (!isset($groupedByType[$typeId])) {
                $groupedByType[$typeId] = [
                    'type_id' => $typeId,
                    'type_name' => $entity->type->name,
                    'type_icon' => $entity->type->icon,
                    'sort_order' => $entity->type->sort_order ?? 999,
                    'entities' => [],
                ];
            }
            $groupedByType[$typeId]['entities'][] = $tree;
        }

        $entityTypeGroups = collect($groupedByType)
            ->sortBy('sort_order')
            ->map(function ($group) {
                $group['entities'] = collect($group['entities'])->sortBy('entity_name')->values();
                return $group;
            })
            ->values();

        return [$entityTypeGroups, $linkedBoardIds];
    }
}
