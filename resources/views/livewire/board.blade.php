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
        <x-ui-kanban-board>

            {{-- Mittlere Spalten --}}
            @foreach($groups->filter(fn ($g) => !($g->isWonGroup ?? false)) as $column)
                <x-ui-kanban-column
                    :title="$column->label"
                    :color="$column->color ?? 'blue'"
                    :count="$column->deals->count()">

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

    <!-- Modals -->
    <livewire:sales.board-settings-modal />
    <livewire:sales.board-slot-settings-modal />
</div>