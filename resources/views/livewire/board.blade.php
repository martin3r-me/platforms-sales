@php
    // Statistiken berechnen
    $openDeals = $groups->filter(fn($g) => !($g->isWonGroup ?? false))->flatMap(fn($g) => $g->deals);
    $wonDeals = $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->deals);
    $allDeals = $groups->flatMap(fn($g) => $g->deals);

    $statsOpen = [
        [
            'title' => 'Offene Deals',
            'count' => $openDeals->count(),
            'icon' => 'clock',
            'variant' => 'warning'
        ],
        [
            'title' => 'Pipeline-Wert',
            'count' => number_format($openDeals->sum('deal_value'), 0, ',', '.') . ' €',
            'icon' => 'currency-euro',
            'variant' => 'primary'
        ],
        [
            'title' => 'Erwarteter Wert',
            'count' => number_format($openDeals->sum('expected_value'), 0, ',', '.') . ' €',
            'icon' => 'chart-bar',
            'variant' => 'info'
        ],
        [
            'title' => 'Hot Deals',
            'count' => $openDeals->filter(fn($d) => $d->is_hot)->count(),
            'icon' => 'fire',
            'variant' => 'danger'
        ],
    ];

    $statsWon = [
        [
            'title' => 'Gewonnen',
            'count' => $wonDeals->count(),
            'icon' => 'check-circle',
            'variant' => 'success'
        ],
        [
            'title' => 'Gewonnener Wert',
            'count' => number_format($wonDeals->sum('deal_value'), 0, ',', '.') . ' €',
            'icon' => 'currency-euro',
            'variant' => 'success'
        ],
    ];
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$salesBoard->name" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        <x-ui-button variant="secondary" size="sm" wire:click="createDeal()">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus','w-4 h-4')
                                <span>Deal</span>
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="secondary" size="sm" wire:click="createBoardSlot">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-square-2-stack','w-4 h-4')
                                <span>Spalte</span>
                            </span>
                        </x-ui-button>
                        {{-- TODO: Board-Einstellungen Modal neu implementieren --}}
                    </div>
                </div>

                {{-- Pipeline-Statistiken: Offen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Pipeline</h3>
                    <div class="space-y-2">
                        @foreach($statsOpen as $stat)
                            <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[var(--ui-' . $stat['variant'] . ')]')
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $stat['title'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">
                                    {{ $stat['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Gewonnen-Statistiken --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Gewonnen</h3>
                    <button
                        wire:click="toggleShowWonColumn"
                        class="w-full flex items-center justify-between py-2.5 px-4 mb-3 bg-[var(--ui-success-5)] hover:bg-[var(--ui-success-10)] border border-[var(--ui-success)]/30 transition-colors group"
                    >
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-[var(--ui-success)]">
                            @if($showWonColumn ?? false)
                                @svg('heroicon-o-eye-slash', 'w-4 h-4')
                                <span>Gewonnene ausblenden</span>
                            @else
                                @svg('heroicon-o-eye', 'w-4 h-4')
                                <span>Gewonnene anzeigen</span>
                            @endif
                        </span>
                        @if($wonDeals->count() > 0)
                            <span class="text-xs font-semibold text-[var(--ui-success)] bg-[var(--ui-success)]/20 px-2 py-0.5 rounded">
                                {{ $wonDeals->count() }}
                            </span>
                        @endif
                    </button>
                    <div class="space-y-2">
                        @foreach($statsWon as $stat)
                            <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[var(--ui-' . $stat['variant'] . ')]')
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $stat['title'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">
                                    {{ $stat['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $salesBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Spalten</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $groups->filter(fn($g) => !($g->isWonGroup ?? false))->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Deals gesamt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $allDeals->count() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="salesActivityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    @foreach(($activities ?? []) as $activity)
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $activity['title'] ?? 'Aktivität' }}</div>
                            <div class="text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Board-Container: Kanban-Spalten --}}
    <x-ui-kanban-container sortable="updateDealGroupOrder" sortable-group="updateDealOrder">
        {{-- Pipeline-Spalten (sortierbar) --}}
        @foreach($groups->filter(fn($g) => !($g->isWonGroup ?? false)) as $column)
            <x-ui-kanban-column :title="($column->label ?? $column->name ?? 'Spalte')" :sortable-id="$column->id" :scrollable="true">
                <x-slot name="headerActions">
                    <button
                        wire:click="createDeal('{{ $column->id }}')"
                        class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                        title="Neuer Deal"
                    >
                        @svg('heroicon-o-plus-circle', 'w-4 h-4')
                    </button>
                    {{-- TODO: Slot-Einstellungen Modal neu implementieren --}}
                </x-slot>

                @foreach($column->deals as $deal)
                    <livewire:sales.deal-preview-card :deal="$deal" :key="'deal-'.$deal->id" />
                @endforeach
            </x-ui-kanban-column>
        @endforeach

        {{-- Gewonnen (nur wenn aktiviert) --}}
        @if($showWonColumn ?? false)
            @php $won = $groups->first(fn($g) => $g->isWonGroup ?? false); @endphp
            @if($won)
                <x-ui-kanban-column :title="($won->label ?? 'Gewonnen')" :sortable-id="'won'" :scrollable="true" :muted="true">
                    <x-slot name="headerActions">
                        <span class="text-xs text-[var(--ui-muted)] font-medium">
                            {{ $won->deals->count() }}
                        </span>
                    </x-slot>
                    @foreach($won->deals as $deal)
                        <livewire:sales.deal-preview-card :deal="$deal" :key="'deal-won-'.$deal->id" />
                    @endforeach
                </x-ui-kanban-column>
            @endif
        @endif
    </x-ui-kanban-container>

    {{-- TODO: Settings Modals neu implementieren --}}
</x-ui-page>
