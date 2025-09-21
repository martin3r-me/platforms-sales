<div class="h-full d-flex">
    <!-- Info-Bereich (fixe Breite) -->
    <div class="w-80 border-r border-muted p-4 flex-shrink-0">
        <!-- Dashboard-Info -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Meine Deals</h3>
            <div class="text-sm text-gray-600 mb-4">Persönliche Deals und zuständige Board-Deals</div>
            
            <!-- Statistiken mit Dashboard-Tiles in 2-spaltigem Grid -->
            <div class="grid grid-cols-2 gap-2 mb-4">
                <x-ui-dashboard-tile
                    title="Deal-Wert (offen)"
                    :count="$groups->filter(fn($g) => !($g->isWonGroup ?? false))->flatMap(fn($g) => $g->tasks)->sum(fn($t) => $t->deal_value ?? 0)"
                    icon="currency-euro"
                    variant="warning"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Deal-Wert (gewonnen)"
                    :count="$groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->tasks)->sum(fn($t) => $t->deal_value ?? 0)"
                    icon="check-circle"
                    variant="success"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Offen"
                    :count="$groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => $g->tasks->count())"
                    icon="clock"
                    variant="warning"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Gewonnen"
                    :count="$groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => $g->tasks->count())"
                    icon="check-circle"
                    variant="success"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="High Value"
                    :count="$groups->flatMap(fn($g) => $g->tasks)->filter(fn($t) => $t->deal_value && $t->deal_value > 10000)->count()"
                    icon="star"
                    variant="info"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Überfällig"
                    :count="$groups->flatMap(fn($g) => $g->tasks)->filter(fn($t) => $t->due_date && $t->due_date->isPast() && !$t->is_done)->count()"
                    icon="exclamation-circle"
                    variant="danger"
                    size="sm"
                />
            </div>

            <!-- Performance-Score -->
            @if($monthlyPerformanceScore)
                <div class="bg-gray-50 rounded-lg p-3 mb-4">
                    <div class="text-sm text-gray-600 mb-1">Monatliche Performance</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ number_format((float) ($monthlyPerformanceScore * 100), 1) }}%
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ number_format((float) $wonValue, 0, ',', '.') }} € gewonnen / {{ number_format((float) $createdValue, 0, ',', '.') }} € erstellt
                    </div>
                </div>
            @endif

            <!-- Aktionen -->
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button variant="success-outline" size="sm" wire:click="createDeal()">
                    + Neuer Deal
                </x-ui-button>
            </div>
        </div>
    </div>

    <!-- Kanban-Board (scrollbar) -->
    <div class="flex-grow overflow-x-auto">
        <x-ui-kanban-board wire:sortable="updateDealOrder">

            @foreach($groups as $group)
                <x-ui-kanban-column
                    :title="$group->label"
                    :sortable-id="$group->id"
                    wire:sortable-group="updateDealOrder">

                    <x-slot name="extra">
                        @if(!$group->isWonGroup)
                            <x-ui-button variant="success-outline" size="sm" class="w-full" wire:click="createDeal('{{ $group->id }}')">
                                + Neuer Deal
                            </x-ui-button>
                        @endif
                    </x-slot>

                    @foreach($group->tasks as $deal)
                        <div class="bg-white border border-gray-200 rounded-lg p-3 mb-2 hover:shadow-md transition-shadow cursor-pointer"
                             wire:sortable.item="{{ $deal->id }}"
                             wire:click="$dispatch('open-deal', { dealId: {{ $deal->id }} })">
                            <div class="d-flex justify-between items-start mb-2">
                                <h4 class="font-medium text-gray-900 text-sm">{{ $deal->title }}</h4>
                                @if($deal->deal_value)
                                    <span class="text-xs font-semibold text-green-600">
                                        {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                    </span>
                                @endif
                            </div>
                            
                            @if($deal->probability_percent)
                                <div class="mb-2">
                                    <x-ui-badge variant="blue" size="sm">
                                        {{ $deal->probability_percent }}%
                                    </x-ui-badge>
                                </div>
                            @endif
                            
                            @if($deal->due_date)
                                <div class="text-xs text-gray-500">
                                    Fällig: {{ $deal->due_date->format('d.m.Y') }}
                                    @if($deal->due_date->isPast() && !$deal->is_done)
                                        <span class="text-red-600 font-medium">(überfällig)</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </x-ui-kanban-column>
            @endforeach
        </x-ui-kanban-board>
    </div>
</div>