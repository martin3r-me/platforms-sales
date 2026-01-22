@php
    $openDeals = $groups->filter(fn($g) => !($g->isWonGroup ?? false))->flatMap(fn($g) => $g->tasks);
    $wonDeals = $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->tasks);
    $allDeals = $groups->flatMap(fn($g) => $g->tasks);
    
    $openValue = $openDeals->sum(fn($t) => (float) ($t->deal_value ?? 0));
    $wonValue = $wonDeals->sum(fn($t) => (float) ($t->deal_value ?? 0));
    $openCount = $openDeals->count();
    $wonCount = $wonDeals->count();
    $highValueCount = $allDeals->filter(fn($t) => $t->deal_value && $t->deal_value > 10000)->count();
    $overdueCount = $allDeals->filter(fn($t) => $t->due_date && $t->due_date->isPast() && !$t->is_done)->count();
    
    $statsOpen = [
        [
            'title' => 'Offen',
            'count' => $openCount,
            'icon' => 'clock',
            'variant' => 'warning'
        ],
        [
            'title' => 'Deal-Wert',
            'count' => number_format($openValue, 0, ',', '.') . ' €',
            'icon' => 'currency-euro',
            'variant' => 'primary'
        ],
        [
            'title' => 'High Value',
            'count' => $highValueCount,
            'icon' => 'star',
            'variant' => 'primary'
        ],
        [
            'title' => 'Überfällig',
            'count' => $overdueCount,
            'icon' => 'exclamation-circle',
            'variant' => 'danger'
        ],
    ];
    
    $statsWon = [
        [
            'title' => 'Gewonnen',
            'count' => $wonCount,
            'icon' => 'check-circle',
            'variant' => 'success'
        ],
        [
            'title' => 'Deal-Wert',
            'count' => number_format($wonValue, 0, ',', '.') . ' €',
            'icon' => 'currency-euro',
            'variant' => 'success'
        ],
    ];
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meine Deals" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Deal-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        <x-ui-button variant="secondary" size="sm" wire:click="createDeal()">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus','w-4 h-4')
                                <span>Deal</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Deal-Statistiken: Offen --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Offen</h3>
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

                {{-- Deal-Statistiken: Gewonnen --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Gewonnen</h3>
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

                {{-- Performance-Score --}}
                @if($monthlyPerformanceScore ?? null)
                    <div>
                        <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Performance</h3>
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)] mb-1">Monatliche Performance</div>
                            <div class="text-lg font-semibold text-[var(--ui-secondary)]">
                                {{ number_format((float) (($monthlyPerformanceScore ?? 0) * 100), 1) }}%
                            </div>
                            <div class="text-xs text-[var(--ui-muted)] mt-1">
                                {{ number_format((float) ($wonValue ?? 0), 0, ',', '.') }} € gewonnen / {{ number_format((float) ($createdValue ?? 0), 0, ',', '.') }} € erstellt
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">Meine Deals geladen</div>
                        <div class="text-[var(--ui-muted)]">vor 1 Minute</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Kanban-Board --}}
    <x-ui-kanban-container sortable="updateDealOrder" sortable-group="updateDealOrder">
        @foreach($groups as $group)
            <x-ui-kanban-column
                :title="$group->label"
                :sortable-id="$group->id ?? null"
                :scrollable="true"
                :muted="$group->isWonGroup ?? false">
                <x-slot name="headerActions">
                    @if(!($group->isWonGroup ?? false))
                        <button 
                            wire:click="createDeal('{{ $group->id ?? null }}')" 
                            class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                            title="Neuer Deal"
                        >
                            @svg('heroicon-o-plus-circle', 'w-4 h-4')
                        </button>
                    @endif
                </x-slot>

                @foreach($group->tasks as $deal)
                    <div class="bg-white border border-[var(--ui-border)] rounded-lg p-3 mb-2 hover:shadow-md transition-shadow cursor-pointer"
                         wire:sortable.item="{{ $deal->id }}"
                         wire:click="$dispatch('open-deal', { dealId: {{ $deal->id }} })">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-medium text-[var(--ui-secondary)] text-sm">{{ $deal->title }}</h4>
                            @if($deal->deal_value)
                                <span class="text-xs font-semibold text-[var(--ui-success)]">
                                    {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                </span>
                            @endif
                        </div>
                        
                        @if($deal->probability_percent)
                            <div class="mb-2">
                                <x-ui-badge variant="primary" size="sm">
                                    {{ $deal->probability_percent }}%
                                </x-ui-badge>
                            </div>
                        @endif
                        
                        @if($deal->due_date)
                            <div class="text-xs text-[var(--ui-muted)]">
                                Fällig: {{ $deal->due_date->format('d.m.Y') }}
                                @if($deal->due_date->isPast() && !$deal->is_done)
                                    <span class="text-[var(--ui-danger)] font-medium">(überfällig)</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </x-ui-kanban-column>
        @endforeach
    </x-ui-kanban-container>
</x-ui-page>