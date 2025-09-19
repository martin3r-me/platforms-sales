<div class="h-full d-flex">
    <!-- Debug Info -->
    <div class="p-4 bg-yellow-100 border border-yellow-300 rounded mb-4">
        <h4 class="font-bold text-yellow-800">Debug Info:</h4>
        <p><strong>SalesBoard ID:</strong> {{ $salesBoard->id ?? 'NULL' }}</p>
        <p><strong>SalesBoard Name:</strong> {{ $salesBoard->name ?? 'NULL' }}</p>
        <p><strong>Groups Type:</strong> {{ gettype($groups) }}</p>
        <p><strong>Groups Count:</strong> {{ $groups ? $groups->count() : 'NULL' }}</p>
        <p><strong>Groups Empty:</strong> {{ $groups ? ($groups->isEmpty() ? 'YES' : 'NO') : 'NULL' }}</p>
    </div>

    <!-- Info-Bereich (fixe Breite) -->
    <div class="w-80 border-r border-muted p-4 flex-shrink-0">
        <!-- Board-Info -->
        <div class="mb-6">
            <div class="d-flex justify-between items-start mb-2">
                <h3 class="text-lg font-semibold">{{ $salesBoard->name ?? 'Unbekanntes Board' }}</h3>
                <x-ui-button variant="info" size="sm" @click="$dispatch('open-modal-board-settings', { boardId: {{ $salesBoard->id ?? 0 }} })">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-information-circle', 'w-4 h-4')
                        Info
                    </div>
                </x-ui-button>
            </div>
            <div class="text-sm text-gray-600 mb-4">{{ $salesBoard->description ?? 'Keine Beschreibung' }}</div>
            
            <!-- Statistiken mit Dashboard-Tiles in 2-spaltigem Grid -->
            <div class="grid grid-cols-2 gap-2 mb-4">
                <x-ui-dashboard-tile
                    title="Deal-Wert (offen)"
                    :count="$groups ? $groups->filter(fn($g) => !($g->isWonGroup ?? false))->flatMap(fn($g) => $g->tasks)->sum(fn($t) => $t->deal_value ?? 0) : 0"
                    icon="currency-euro"
                    variant="warning"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Deal-Wert (gewonnen)"
                    :count="$groups ? $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->tasks)->sum(fn($t) => $t->deal_value ?? 0) : 0"
                    icon="check-circle"
                    variant="success"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Offen"
                    :count="$groups ? $groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => is_array($g->tasks) ? count($g->tasks) : $g->tasks->count()) : 0"
                    icon="clock"
                    variant="warning"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Gesamt"
                    :count="$groups ? collect($groups->flatMap(fn($g) => $g->tasks))->count() : 0"
                    icon="document-text"
                    variant="secondary"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Gewonnen"
                    :count="$groups ? $groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => is_array($g->tasks) ? count($g->tasks) : $g->tasks->count()) : 0"
                    icon="check-circle"
                    variant="success"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Ohne Fälligkeit"
                    :count="$groups ? collect($groups->flatMap(fn($g) => $g->tasks))->filter(fn($t) => !$t->due_date)->count() : 0"
                    icon="calendar"
                    variant="neutral"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="High Value"
                    :count="$groups ? collect($groups->flatMap(fn($g) => $g->tasks))->filter(fn($t) => $t->deal_value && $t->deal_value > 10000)->count() : 0"
                    icon="star"
                    variant="warning"
                    size="sm"
                />
                <x-ui-dashboard-tile
                    title="Überfällig"
                    :count="$groups ? collect($groups->flatMap(fn($g) => $g->tasks))->filter(fn($t) => $t->due_date && $t->due_date->isPast() && !$t->is_done)->count() : 0"
                    icon="exclamation-circle"
                    variant="danger"
                    size="sm"
                />
            </div>

            <!-- Aktionen -->
            @can('update', $salesBoard)
                <div class="d-flex flex-col gap-2 mb-4">
                    <x-ui-button variant="success-outline" size="sm" wire:click="createDeal()">
                        + Neuer Deal
                    </x-ui-button>
                    <x-ui-button variant="primary-outline" size="sm" wire:click="createBoardSlot">
                        + Neue Spalte
                    </x-ui-button>
                </div>
            @endcan
        </div>

        <!-- Gewonnene Deals -->
        @php $wonDeals = $groups ? $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->tasks) : collect(); @endphp
        @if($wonDeals && $wonDeals->count() > 0)
            <div>
                <h4 class="font-medium mb-3">Gewonnene Deals ({{ $wonDeals->count() }})</h4>
                <div class="space-y-1 max-h-60 overflow-y-auto">
                    @foreach($wonDeals->take(10) as $deal)
                        <a href="{{ route('sales.deals.show', $deal) }}" 
                           class="block p-2 bg-gray-50 rounded text-sm hover:bg-gray-100 transition"
                           wire:navigate>
                            <div class="d-flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-green-500"/>
                                <span class="truncate">{{ $deal->title }}</span>
                            </div>
                        </a>
                    @endforeach
                    @if($wonDeals->count() > 10)
                        <div class="text-xs text-gray-500 italic text-center">
                            +{{ $wonDeals->count() - 10 }} weitere
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="text-sm text-gray-500 italic">Noch keine gewonnenen Deals</div>
        @endif
    </div>

    <!-- Kanban-Board (scrollbar) -->
    <div class="flex-grow overflow-x-auto">
        <x-ui-kanban-board wire:sortable="updateDealGroupOrder" wire:sortable-group="updateDealOrder">

            {{-- Mittlere Spalten --}}
            @foreach(($groups ? $groups->filter(fn ($g) => !($g->isWonGroup ?? false)) : collect()) as $column)
                <x-ui-kanban-column
                    :title="$column->label"
                    :sortable-id="$column->id">

                    <x-slot name="extra">
                        <div class="d-flex gap-1">
                            @can('update', $salesBoard)
                                <x-ui-button variant="success-outline" size="sm" class="w-full" wire:click="createDeal('{{ $column->id }}')">
                                    + Neuer Deal
                                </x-ui-button>
                                <x-ui-button variant="primary-outline" size="sm" class="w-full" @click="$dispatch('open-modal-board-slot-settings', { boardSlotId: {{ $column->id }} })">Settings</x-ui-button>
                            @endcan
                        </div>
                    </x-slot>

                    @foreach($column->tasks as $deal)
                        <livewire:sales.deal-preview-card 
                            :deal="$deal"
                            wire:key="deal-preview-{{ $deal->uuid }}"
                        />
                    @endforeach

                </x-ui-kanban-column>
            @endforeach

            {{-- GEWONNEN Spalte --}}
            @php $wonGroup = $groups ? $groups->filter(fn($g) => $g->isWonGroup ?? false)->first() : null; @endphp
            @if($wonGroup)
                <x-ui-kanban-column
                    title="GEWONNEN"
                    :sortable-id="$wonGroup->id">

                    @foreach($wonGroup->tasks as $deal)
                        <livewire:sales.deal-preview-card 
                            :deal="$deal"
                            wire:key="deal-preview-{{ $deal->uuid }}"
                        />
                    @endforeach

                </x-ui-kanban-column>
            @endif

        </x-ui-kanban-board>
    </div>

    <livewire:sales.board-settings-modal/>
    <livewire:sales.board-slot-settings-modal/>
</div>
