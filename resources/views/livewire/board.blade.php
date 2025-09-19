<div class="h-full d-flex">
    <!-- Info-Bereich -->
    <div class="w-80 border-r border-muted p-4 flex-shrink-0">
        <h3 class="text-lg font-semibold">{{ $salesBoard->name }}</h3>
        <div class="text-sm text-gray-600 mb-4">{{ $salesBoard->description ?? 'Keine Beschreibung' }}</div>
        
        <!-- Einfache Statistiken -->
        <div class="grid grid-cols-2 gap-2 mb-4">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded">
                <div class="text-sm text-blue-600">Offene Deals</div>
                <div class="text-xl font-bold text-blue-800">{{ $groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => $g->deals->count()) }}</div>
            </div>
            <div class="p-3 bg-green-50 border border-green-200 rounded">
                <div class="text-sm text-green-600">Gewonnene Deals</div>
                <div class="text-xl font-bold text-green-800">{{ $groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => $g->deals->count()) }}</div>
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
    </div>

    <!-- Kanban-Board -->
    <div class="flex-grow-1 overflow-hidden">
        <x-ui-kanban-board>
            @foreach($groups as $group)
                <x-ui-kanban-column 
                    :key="$group->id"
                    :title="$group->label"
                    :color="$group->color ?? 'blue'"
                    :count="$group->deals->count()"
                >
                    @foreach($group->deals as $deal)
                        <x-ui-kanban-card 
                            :key="$deal->id"
                            :title="$deal->title"
                            :href="route('sales.deals.show', $deal)"
                            wire:navigate
                        >
                            <div class="space-y-2">
                                @if($deal->deal_value)
                                    <div class="font-semibold text-primary">
                                        {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                    </div>
                                @endif
                                
                                @if($deal->probability_percent)
                                    <div class="text-sm text-gray-600">
                                        {{ $deal->probability_percent }}% Wahrscheinlichkeit
                                    </div>
                                @endif
                                
                                @if($deal->due_date)
                                    <div class="text-sm {{ $deal->due_date->isPast() ? 'text-red-600' : 'text-gray-600' }}">
                                        Fällig: {{ $deal->due_date->format('d.m.Y') }}
                                    </div>
                                @endif
                                
                                @if($deal->userInCharge)
                                    <div class="text-sm text-gray-600">
                                        {{ $deal->userInCharge->name }}
                                    </div>
                                @endif
                            </div>
                        </x-ui-kanban-card>
                    @endforeach
                </x-ui-kanban-column>
            @endforeach
        </x-ui-kanban-board>
    </div>
</div>