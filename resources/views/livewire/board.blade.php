<div class="h-full d-flex">
    <!-- Info-Bereich -->
    <div class="w-80 border-r border-muted p-4 flex-shrink-0">
        <h3 class="text-lg font-semibold">{{ $salesBoard->name }}</h3>
        <div class="text-sm text-gray-600 mb-4">{{ $salesBoard->description ?? 'Keine Beschreibung' }}</div>
        
            <!-- Dashboard Tiles -->
            <div class="space-y-3 mb-4">
                <h4 class="font-medium text-gray-900">Board Statistiken</h4>
                
                <div class="grid grid-cols-2 gap-2">
                    <x-ui-dashboard-tile
                        title="Offene Deals"
                        :count="$groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => $g->deals->count())"
                        icon="clock"
                        variant="blue"
                        size="sm"
                    />
                    
                    <x-ui-dashboard-tile
                        title="Gewonnene Deals"
                        :count="$groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => $g->deals->count())"
                        icon="check-circle"
                        variant="green"
                        size="sm"
                    />
                </div>
                
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-3 bg-purple-50 border border-purple-200 rounded">
                        <div class="text-sm text-purple-600">Deal Wert</div>
                        <div class="text-xl font-bold text-purple-800">
                            @php
                                $totalDealValue = $groups->flatMap(fn($g) => $g->deals)->reduce(fn($carry, $d) => $carry + (float) ($d->deal_value ?? 0), 0);
                            @endphp
                            {{ number_format($totalDealValue, 0, ',', '.') }} €
                        </div>
                    </div>
                    
                    <div class="p-3 bg-orange-50 border border-orange-200 rounded">
                        <div class="text-sm text-orange-600">Erwarteter Wert</div>
                        <div class="text-xl font-bold text-orange-800">
                            @php
                                $totalExpectedValue = $groups->flatMap(fn($g) => $g->deals)->reduce(function($carry, $d) { 
                                    $dealValue = (float) ($d->deal_value ?? 0);
                                    $probability = (float) ($d->probability_percent ?? 0);
                                    return $carry + ($dealValue * $probability / 100);
                                }, 0);
                            @endphp
                            {{ number_format($totalExpectedValue, 0, ',', '.') }} €
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2">
                    <x-ui-dashboard-tile
                        title="Überfällig"
                        :count="$groups->flatMap(fn($g) => $g->deals)->filter(fn($d) => $d->due_date && $d->due_date->isPast() && !$d->is_done)->count()"
                        icon="exclamation-circle"
                        variant="red"
                        size="sm"
                    />
                    
                    <x-ui-dashboard-tile
                        title="Heute fällig"
                        :count="$groups->flatMap(fn($g) => $g->deals)->filter(fn($d) => $d->due_date && $d->due_date->isToday())->count()"
                        icon="calendar"
                        variant="yellow"
                        size="sm"
                    />
                </div>
            </div>

        <!-- Aktionen -->
        @can('update', $salesBoard)
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button variant="primary" size="sm" wire:click="createDeal">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neuer Deal
                    </div>
                </x-ui-button>
                
                <x-ui-button variant="secondary" size="sm" @click="$dispatch('open-modal-board-settings', { boardId: {{ $salesBoard->id }} })">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                        Board-Einstellungen
                    </div>
                </x-ui-button>
            </div>
        @endcan

        <!-- Gewonnene Deals -->
        @php $wonDeals = $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->deals); @endphp
        @if($wonDeals->count() > 0)
            <div>
                <h4 class="font-medium mb-3">Gewonnene Deals ({{ $wonDeals->count() }})</h4>
                <div class="space-y-1 max-h-60 overflow-y-auto">
                    @foreach($wonDeals->take(10) as $deal)
                        <a href="{{ route('sales.deals.show', $deal) }}" 
                           class="block p-2 bg-green-50 border border-green-200 rounded text-sm hover:bg-green-100 transition">
                            <div class="font-medium">{{ $deal->title }}</div>
                            @if($deal->deal_value)
                                <div class="text-green-600 font-semibold">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</div>
                            @endif
                        </a>
                    @endforeach
                    @if($wonDeals->count() > 10)
                        <div class="text-center text-sm text-gray-500 p-2">
                            +{{ $wonDeals->count() - 10 }} weitere
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Kanban-Board (scrollbar) -->
    <div class="flex-grow overflow-x-auto">
        <x-ui-kanban-board wire:sortable="updateDealGroupOrder" wire:sortable-group="updateDealOrder">

            {{-- Mittlere Spalten --}}
            @foreach($groups->filter(fn ($g) => !($g->isWonGroup ?? false)) as $column)
                <x-ui-kanban-column
                    :title="$column->label"
                    :color="$column->color ?? 'blue'"
                    :count="$column->deals->count()"
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

                    @foreach($column->deals as $deal)
                        <livewire:sales.deal-preview-card 
                            :deal="$deal"
                            wire:key="deal-preview-{{ $deal->id }}"
                        />
                    @endforeach

                </x-ui-kanban-column>
            @endforeach

        </x-ui-kanban-board>
    </div>

    <!-- Modals -->
    <livewire:sales.board-settings-modal />
    <livewire:sales.board-slot-settings-modal />
</div>