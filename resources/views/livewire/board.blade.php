{{-- Root auf x-ui-page umstellen, damit volle Höhe/Sidebars korrekt funktionieren --}}
    @php 
        $openDeals = $groups->filter(fn($g) => !($g->isWonGroup ?? false))->flatMap(fn($g) => $g->deals);
        $wonDeals = $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->deals);
        $allDeals = $groups->flatMap(fn($g) => $g->deals);
        
        $totalDealValue = $allDeals->reduce(fn($carry, $d) => $carry + (float) ($d->deal_value ?? 0), 0);
        $totalExpectedValue = $allDeals->reduce(function($carry, $d) { 
            $dealValue = (float) ($d->deal_value ?? 0);
            $probability = (float) ($d->probability_percent ?? 0);
            return $carry + ($dealValue * $probability / 100);
        }, 0);
        
        $statsOpen = [
            [
                'title' => 'Offen',
                'count' => $openDeals->count(),
                'icon' => 'clock',
                'variant' => 'warning'
            ],
            [
                'title' => 'Deal Wert',
                'count' => number_format($openDeals->sum(fn($d) => (float) ($d->deal_value ?? 0)), 0, ',', '.') . ' €',
                'icon' => 'currency-euro',
                'variant' => 'primary'
            ],
            [
                'title' => 'Überfällig',
                'count' => $openDeals->filter(fn($d) => $d->due_date && $d->due_date->isPast() && !$d->is_done)->count(),
                'icon' => 'exclamation-circle',
                'variant' => 'danger'
            ],
            [
                'title' => 'Heute fällig',
                'count' => $openDeals->filter(fn($d) => $d->due_date && $d->due_date->isToday())->count(),
                'icon' => 'calendar',
                'variant' => 'warning'
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
                'title' => 'Deal Wert',
                'count' => number_format($wonDeals->sum(fn($d) => (float) ($d->deal_value ?? 0)), 0, ',', '.') . ' €',
                'icon' => 'currency-euro',
                'variant' => 'success'
            ],
        ];
    @endphp

    {{-- Neues Layout via x-ui-page --}}
    <x-ui-page>
        <x-slot name="navbar">
            <x-ui-page-navbar :title="$salesBoard->name" icon="heroicon-o-folder" />
        </x-slot>

        <x-slot name="sidebar">
            <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
                <div class="p-4 space-y-6">
                    {{-- Aktionen --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                        <div class="flex flex-col gap-2">
                            @can('update', $salesBoard)
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
                            @endcan
                            @can('update', $salesBoard)
                                <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-board-settings', { boardId: {{ $salesBoard->id }} })">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                        <span>Einstellungen</span>
                                    </span>
                                </x-ui-button>
                            @endcan
                        </div>
                    </div>
                    <!-- Board-Statistiken: Offen -->
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Offen</h3>
                        <div class="space-y-2">
                            @foreach($statsOpen as $stat)
                                <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                    <div class="flex items-center gap-2">
                                        @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[var(--ui-' . $stat['variant'] . ')]')
                                        <span class="text-sm text-[var(--ui-secondary)]">{{ $stat['title'] }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-[var(--ui-' . $stat['variant'] . ')]">
                                        {{ $stat['count'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Board-Statistiken: Gewonnen -->
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Gewonnen</h3>
                        <div class="space-y-2">
                            @foreach($statsWon as $stat)
                                <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                    <div class="flex items-center gap-2">
                                        @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[var(--ui-' . $stat['variant'] . ')]')
                                        <span class="text-sm text-[var(--ui-secondary)]">{{ $stat['title'] }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-[var(--ui-' . $stat['variant'] . ')]">
                                        {{ $stat['count'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Board-Details -->
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
                                <span class="text-sm text-[var(--ui-muted)]">Gesamtwert</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ number_format($totalDealValue, 0, ',', '.') }} €
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Erwarteter Wert</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ number_format($totalExpectedValue, 0, ',', '.') }} €
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Gewonnene Deals --}}
                    @if($wonDeals->count() > 0)
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Gewonnene Deals ({{ $wonDeals->count() }})</h3>
                            <div class="space-y-1 max-h-60 overflow-y-auto">
                                @foreach($wonDeals->take(10) as $deal)
                                    <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                                       class="block p-2 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/30 rounded text-sm hover:bg-[var(--ui-success-10)] transition">
                                        <div class="font-medium text-[var(--ui-secondary)]">{{ $deal->title }}</div>
                                        @if($deal->deal_value)
                                            <div class="text-[var(--ui-success)] font-semibold">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</div>
                                        @endif
                                    </a>
                                @endforeach
                                @if($wonDeals->count() > 10)
                                    <div class="text-center text-sm text-[var(--ui-muted)] p-2">
                                        +{{ $wonDeals->count() - 10 }} weitere
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui-page-sidebar>
        </x-slot>

        <x-slot name="activity">
            <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="activityOpen" side="right">
                <div class="p-4 space-y-4">
                    <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                    <div class="space-y-3 text-sm">
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">Board geladen</div>
                            <div class="text-[var(--ui-muted)]">vor 1 Minute</div>
                        </div>
                    </div>
                </div>
            </x-ui-page-sidebar>
        </x-slot>

        <x-ui-page-container padding="p-0" spacing="space-y-0">
            <!-- Board-Container: füllt restliche Breite, Spalten scrollen intern -->
            <x-ui-kanban-container sortable="updateDealGroupOrder" sortable-group="updateDealOrder">
            {{-- Mittlere Spalten (sortierbar) --}}
            @foreach($groups->filter(fn ($g) => !($g->isWonGroup ?? false)) as $column)
                <x-ui-kanban-column :title="($column->label ?? $column->name ?? 'Spalte')" :sortable-id="$column->id" :scrollable="true">
                    <x-slot name="headerActions">
                        @can('update', $salesBoard)
                            <button 
                                wire:click="createDeal('{{ $column->id }}')" 
                                class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                title="Neuer Deal"
                            >
                                @svg('heroicon-o-plus-circle', 'w-4 h-4')
                            </button>
                        @endcan
                        @can('update', $salesBoard)
                            <button 
                                @click="$dispatch('open-modal-board-slot-settings', { boardSlotId: {{ $column->id }} })"
                                class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                title="Einstellungen"
                            >
                                @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                            </button>
                        @endcan
                    </x-slot>

                    @foreach($column->deals as $deal)
                        <livewire:sales.deal-preview-card 
                            :deal="$deal"
                            wire:key="deal-preview-{{ $deal->id }}"
                        />
                    @endforeach
                </x-ui-kanban-column>
            @endforeach
            </x-ui-kanban-container>
        </x-ui-page-container>
        {{-- Modals innerhalb des Page-Roots halten (ein Root-Element) --}}
        <livewire:sales.board-settings-modal />
        <livewire:sales.board-slot-settings-modal />
    </x-ui-page>
