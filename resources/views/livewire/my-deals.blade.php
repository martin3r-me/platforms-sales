<div class="h-full d-flex">
    <!-- Linke Spalte: Kanban Board -->
    <div class="flex-grow-1 overflow-y-auto p-4">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Meine Deals</h1>
            <p class="text-gray-600 mt-1">Verwalte deine persönlichen Deals und Board-Zuweisungen</p>
        </div>

        <!-- Kanban Board -->
        <x-ui-kanban-board wire:sortable="updateDealOrder">
            @foreach($groups as $group)
                <x-ui-kanban-column
                    :title="$group->label"
                    :sortable-id="$group->id">

                    <x-slot name="extra">
                        @if(!$group->isWonGroup)
                            <x-ui-button variant="success-outline" size="sm" class="w-full" wire:click="createDeal('{{ $group->id }}')">
                                + Neuer Deal
                            </x-ui-button>
                        @endif
                    </x-slot>

                    @foreach($group->tasks as $deal)
                        <div class="bg-white border border-gray-200 rounded-lg p-3 mb-2 hover:shadow-md transition-shadow cursor-pointer"
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

    <!-- Rechte Spalte: Sidebar -->
    <div class="w-80 border-l border-gray-200 p-4 flex-shrink-0">
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div>
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    @svg('heroicon-o-chart-bar', 'w-4 h-4')
                    Schnellübersicht
                </h3>
                <div class="space-y-3">
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="text-xs text-blue-600 font-medium">Offene Deals</div>
                        <div class="text-lg font-bold text-blue-800">
                            {{ $groups->filter(fn($g) => !($g->isWonGroup ?? false))->sum(fn($g) => $g->tasks->count()) }}
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="text-xs text-green-600 font-medium">Gewonnene Deals</div>
                        <div class="text-lg font-bold text-green-800">
                            {{ $groups->filter(fn($g) => $g->isWonGroup ?? false)->sum(fn($g) => $g->tasks->count()) }}
                        </div>
                    </div>
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="text-xs text-yellow-600 font-medium">Gesamtwert (offen)</div>
                        <div class="text-lg font-bold text-yellow-800">
                            {{ number_format((float) $groups->filter(fn($g) => !($g->isWonGroup ?? false))->flatMap(fn($g) => $g->tasks)->sum(fn($t) => $t->deal_value ?? 0), 0, ',', '.') }} €
                        </div>
                    </div>
                    <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                        <div class="text-xs text-purple-600 font-medium">Gesamtwert (gewonnen)</div>
                        <div class="text-lg font-bold text-purple-800">
                            {{ number_format((float) $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->tasks)->sum(fn($t) => $t->deal_value ?? 0), 0, ',', '.') }} €
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            @if($monthlyPerformanceScore)
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        @svg('heroicon-o-trophy', 'w-4 h-4')
                        Monatliche Performance
                    </h3>
                    <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                        <div class="text-2xl font-bold text-green-800 mb-1">
                            {{ number_format((float) ($monthlyPerformanceScore * 100), 1) }}%
                        </div>
                        <div class="text-sm text-green-600">
                            {{ number_format((float) $wonValue, 0, ',', '.') }} € gewonnen
                        </div>
                        <div class="text-xs text-green-500 mt-1">
                            von {{ number_format((float) $createdValue, 0, ',', '.') }} € erstellt
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div>
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                    Aktionen
                </h3>
                <div class="space-y-2">
                    <x-ui-button variant="success" size="sm" class="w-full" wire:click="createDeal()">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neuer Deal
                    </x-ui-button>
                    <x-ui-button variant="secondary-outline" size="sm" class="w-full" :href="route('sales.dashboard')" wire:navigate>
                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                        Dashboard
                    </x-ui-button>
                </div>
            </div>
        </div>
    </div>
</div>