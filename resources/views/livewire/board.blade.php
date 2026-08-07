@php
$openDeals = $groups->filter(fn($g) => !($g->isWonGroup ?? false) && !($g->isLostGroup ?? false))->flatMap(fn($g) => $g->deals);
$wonDeals = $groups->filter(fn($g) => $g->isWonGroup ?? false)->flatMap(fn($g) => $g->deals);
$lostDeals = $groups->filter(fn($g) => $g->isLostGroup ?? false)->flatMap(fn($g) => $g->deals);
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
        'variant' => 'info'
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

$statsLost = [
    [
        'title' => 'Verloren',
        'count' => $lostDeals->count(),
        'icon' => 'x-circle',
        'variant' => 'danger'
    ],
    [
        'title' => 'Deal Wert',
        'count' => number_format($lostDeals->sum(fn($d) => (float) ($d->deal_value ?? 0)), 0, ',', '.') . ' €',
        'icon' => 'currency-euro',
        'variant' => 'danger'
    ],
];

// Tone-Mapping für Pipeline-Spalten (mittlere Spalten)
$tonePalette = ['indigo', 'amber', 'teal', 'violet', 'sky', 'pink', 'rose', 'emerald'];
$pipelineColumns = $groups->filter(fn ($g) => !($g->isWonGroup ?? false) && !($g->isLostGroup ?? false))->values();
$columnTones = $pipelineColumns->mapWithKeys(fn ($col, $i) => [$col->id => $tonePalette[$i % count($tonePalette)]]);
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$salesBoard->name" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Boards'],
            ['label' => $salesBoard->name],
        ]">
            @can('update', $salesBoard)
                <x-nx-button variant="primary" wire:click="createDeal()">
                    <span class="flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Deal</span>
                    </span>
                </x-nx-button>
                <x-nx-dropdown label="Aktionen">
                    <x-nx-dropdown-item wire:click="createBoardSlot">
                        @svg('heroicon-o-square-2-stack', 'w-4 h-4')
                        <span>Spalte</span>
                    </x-nx-dropdown-item>
                    <x-nx-dropdown-item x-data @click="$dispatch('open-modal-board-settings', { boardId: {{ $salesBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                        <span>Einstellungen</span>
                    </x-nx-dropdown-item>
                </x-nx-dropdown>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-5 bg-[var(--nx-bg)]">
                {{-- Board-Statistiken: Offen --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Offen</h3>
                    <div class="space-y-1.5">
                        @foreach($statsOpen as $stat)
                            <div class="flex items-center justify-between py-1.5 px-2 rounded bg-[var(--nx-bg)]">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[color:var(--nx-' . $stat['variant'] . ')]')
                                    <span class="text-[13px] text-[var(--nx-text)]">{{ $stat['title'] }}</span>
                                </div>
                                <span class="text-[13px] font-semibold tabular-nums text-[color:var(--nx-{{ $stat['variant'] }})]">
                                    {{ $stat['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Board-Statistiken: Gewonnen --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Gewonnen</h3>
                    <button
                        wire:click="toggleShowWonColumn"
                        class="w-full flex items-center justify-between py-2 px-3 mb-2 rounded bg-[rgba(47,158,68,.09)] hover:bg-[rgba(47,158,68,.15)] border border-[rgba(47,158,68,.30)] transition-colors group"
                    >
                        <span class="inline-flex items-center gap-2 text-[13px] font-medium text-[color:var(--nx-success)]">
                            @if($showWonColumn)
                                @svg('heroicon-o-eye-slash', 'w-4 h-4')
                                <span>Gewonnene ausblenden</span>
                            @else
                                @svg('heroicon-o-eye', 'w-4 h-4')
                                <span>Gewonnene anzeigen</span>
                            @endif
                        </span>
                        @if($wonDeals->count() > 0)
                            <span class="text-xs font-semibold tabular-nums text-[color:var(--nx-success)] bg-[rgba(47,158,68,.18)] px-2 py-0.5 rounded">
                                {{ $wonDeals->count() }}
                            </span>
                        @endif
                    </button>
                    <div class="space-y-1.5">
                        @foreach($statsWon as $stat)
                            <div class="flex items-center justify-between py-1.5 px-2 rounded bg-[var(--nx-bg)]">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[color:var(--nx-' . $stat['variant'] . ')]')
                                    <span class="text-[13px] text-[var(--nx-text)]">{{ $stat['title'] }}</span>
                                </div>
                                <span class="text-[13px] font-semibold tabular-nums text-[color:var(--nx-{{ $stat['variant'] }})]">
                                    {{ $stat['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Board-Statistiken: Verloren --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Verloren</h3>
                    <button
                        wire:click="toggleShowLostColumn"
                        class="w-full flex items-center justify-between py-2 px-3 mb-2 rounded bg-[rgba(224,49,49,.09)] hover:bg-[rgba(224,49,49,.15)] border border-[rgba(224,49,49,.30)] transition-colors group"
                    >
                        <span class="inline-flex items-center gap-2 text-[13px] font-medium text-[color:var(--nx-danger)]">
                            @if($showLostColumn)
                                @svg('heroicon-o-eye-slash', 'w-4 h-4')
                                <span>Verlorene ausblenden</span>
                            @else
                                @svg('heroicon-o-eye', 'w-4 h-4')
                                <span>Verlorene anzeigen</span>
                            @endif
                        </span>
                        @if($lostDeals->count() > 0)
                            <span class="text-xs font-semibold tabular-nums text-[color:var(--nx-danger)] bg-[rgba(224,49,49,.18)] px-2 py-0.5 rounded">
                                {{ $lostDeals->count() }}
                            </span>
                        @endif
                    </button>
                    <div class="space-y-1.5">
                        @foreach($statsLost as $stat)
                            <div class="flex items-center justify-between py-1.5 px-2 rounded bg-[var(--nx-bg)]">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[color:var(--nx-' . $stat['variant'] . ')]')
                                    <span class="text-[13px] text-[var(--nx-text)]">{{ $stat['title'] }}</span>
                                </div>
                                <span class="text-[13px] font-semibold tabular-nums text-[color:var(--nx-{{ $stat['variant'] }})]">
                                    {{ $stat['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Board-Details --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Details</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-2 rounded bg-[var(--nx-bg)]">
                            <span class="text-[13px] text-[var(--nx-muted)]">Erstellt</span>
                            <span class="text-[13px] text-[var(--nx-text)] font-medium tabular-nums">
                                {{ $salesBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-2 rounded bg-[var(--nx-bg)]">
                            <span class="text-[13px] text-[var(--nx-muted)]">Gesamtwert</span>
                            <span class="text-[13px] text-[var(--nx-text)] font-medium tabular-nums">
                                {{ number_format($totalDealValue, 0, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-2 rounded bg-[var(--nx-bg)]">
                            <span class="text-[13px] text-[var(--nx-muted)]">Erwarteter Wert</span>
                            <span class="text-[13px] text-[var(--nx-text)] font-medium tabular-nums">
                                {{ number_format($totalExpectedValue, 0, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                </section>

                {{-- Gewonnene Deals --}}
                @if($wonDeals->count() > 0)
                    <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Gewonnene Deals ({{ $wonDeals->count() }})</h3>
                        <div class="space-y-1 max-h-60 overflow-y-auto">
                            @foreach($wonDeals->take(10) as $deal)
                                <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                                   class="block p-2 rounded bg-[rgba(47,158,68,.09)] border border-[rgba(47,158,68,.30)] text-sm hover:bg-[rgba(47,158,68,.15)] transition">
                                    <div class="font-medium text-[var(--nx-text)]">{{ $deal->title }}</div>
                                    @if($deal->deal_value)
                                        <div class="text-[color:var(--nx-success)] font-semibold tabular-nums">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</div>
                                    @endif
                                </a>
                            @endforeach
                            @if($wonDeals->count() > 10)
                                <div class="text-center text-sm text-[var(--nx-muted)] p-2">
                                    +{{ $wonDeals->count() - 10 }} weitere
                                </div>
                            @endif
                        </div>
                    </section>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="salesActivityOpen" side="right">
            <div class="p-4 space-y-3">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)]">Letzte Aktivitäten</div>
                <x-nx-empty icon="heroicon-o-clock">Keine aktuellen Aktivitäten</x-nx-empty>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Kanban Board --}}
    <x-nx-kanban-container sortable="updateDealGroupOrder" sortable-group="updateDealOrder">
        {{-- Pipeline-Spalten (sortierbar) --}}
        @foreach($pipelineColumns as $column)
            @php
                $colDeals = $column->deals;
                $colTotal = $colDeals->sum(fn($d) => (float) ($d->deal_value ?? 0));
                $colOneTime = 0;
                $colArr = 0;
                foreach ($colDeals as $d) {
                    if ($d->hasBillables()) {
                        $colOneTime += $d->billables->filter(fn($b) => $b->isOneTime())->sum('total_value');
                        $colArr += $d->billables->filter(fn($b) => $b->isRecurring())->sum(function($b) {
                            return match($b->billing_interval) {
                                'monthly' => (float) $b->amount * 12,
                                'quarterly' => (float) $b->amount * 4,
                                'yearly' => (float) $b->amount,
                                default => (float) $b->amount * 12,
                            };
                        });
                    } else {
                        $colOneTime += (float) ($d->deal_value ?? 0);
                    }
                }
                $tone = $columnTones[$column->id] ?? 'indigo';
            @endphp
            <x-nx-kanban-column :title="($column->label ?? $column->name ?? 'Spalte')" :sortable-id="$column->id" :scrollable="true" :tone="$tone" :count="$colDeals->count()">
                <x-slot name="headerActions">
                    <span class="text-[10px] font-semibold tabular-nums text-[color:var(--nx-success)]">{{ number_format($colTotal, 0, ',', '.') }} €</span>
                    @can('update', $salesBoard)
                        <button
                            wire:click="createDeal('{{ $column->id }}')"
                            class="text-[var(--nx-muted)] hover:text-[var(--nx-accent)] transition-colors"
                            title="Neuer Deal"
                        >
                            @svg('heroicon-o-plus-circle', 'w-4 h-4')
                        </button>
                        <button
                            x-data
                            @click="$dispatch('open-modal-board-slot-settings', { boardSlotId: {{ $column->id }} })"
                            class="text-[var(--nx-muted)] hover:text-[var(--nx-accent)] transition-colors"
                            title="Spalten-Einstellungen"
                        >
                            @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                        </button>
                    @endcan
                </x-slot>

                <x-slot name="footer">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-[var(--nx-muted)] tabular-nums">{{ $colDeals->count() }} Deal(s)</span>
                        <div class="flex items-center gap-3">
                            @if($colOneTime > 0)
                                <span class="inline-flex items-center gap-1 text-[color:var(--nx-text)] font-medium tabular-nums" title="Einmalig">
                                    @svg('heroicon-o-banknotes', 'w-3 h-3')
                                    {{ number_format($colOneTime, 0, ',', '.') }} €
                                </span>
                            @endif
                            @if($colArr > 0)
                                <span class="inline-flex items-center gap-1 text-[color:var(--nx-info)] font-medium tabular-nums" title="Wiederkehrend pro Jahr">
                                    @svg('heroicon-o-arrow-path', 'w-3 h-3')
                                    {{ number_format($colArr, 0, ',', '.') }} €/J
                                </span>
                            @endif
                        </div>
                    </div>
                </x-slot>

                @foreach($column->deals as $deal)
                    @include('sales::livewire.deal-preview-card', ['deal' => $deal])
                @endforeach
            </x-nx-kanban-column>
        @endforeach

        {{-- Gewonnen-Spalte (nicht sortierbar) --}}
        @if($showWonColumn)
            @php
                $wonGroup = $groups->firstWhere('isWonGroup', true);
                $wonTotal = $wonGroup ? $wonGroup->deals->sum(fn($d) => (float) ($d->deal_value ?? 0)) : 0;
                $wonOneTime = 0;
                $wonArr = 0;
                if ($wonGroup) {
                    foreach ($wonGroup->deals as $d) {
                        if ($d->hasBillables()) {
                            $wonOneTime += $d->billables->filter(fn($b) => $b->isOneTime())->sum('total_value');
                            $wonArr += $d->billables->filter(fn($b) => $b->isRecurring())->sum(function($b) {
                                return match($b->billing_interval) {
                                    'monthly' => (float) $b->amount * 12,
                                    'quarterly' => (float) $b->amount * 4,
                                    'yearly' => (float) $b->amount,
                                    default => (float) $b->amount * 12,
                                };
                            });
                        } else {
                            $wonOneTime += (float) ($d->deal_value ?? 0);
                        }
                    }
                }
            @endphp
            @if($wonGroup)
                <x-nx-kanban-column title="GEWONNEN" :sortable-id="null" :scrollable="true" :muted="true" tone="emerald" :count="$wonGroup->deals->count()">
                    <x-slot name="headerActions">
                        <span class="text-[10px] font-semibold tabular-nums text-[color:var(--nx-success)]">{{ number_format($wonTotal, 0, ',', '.') }} €</span>
                    </x-slot>

                    <x-slot name="footer">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-[var(--nx-muted)] tabular-nums">{{ $wonGroup->deals->count() }} Deal(s)</span>
                            <div class="flex items-center gap-3">
                                @if($wonOneTime > 0)
                                    <span class="inline-flex items-center gap-1 text-[color:var(--nx-success)] font-medium tabular-nums" title="Einmalig">
                                        @svg('heroicon-o-banknotes', 'w-3 h-3')
                                        {{ number_format($wonOneTime, 0, ',', '.') }} €
                                    </span>
                                @endif
                                @if($wonArr > 0)
                                    <span class="inline-flex items-center gap-1 text-[color:var(--nx-info)] font-medium tabular-nums" title="Wiederkehrend pro Jahr">
                                        @svg('heroicon-o-arrow-path', 'w-3 h-3')
                                        {{ number_format($wonArr, 0, ',', '.') }} €/J
                                    </span>
                                @endif
                            </div>
                        </div>
                    </x-slot>

                    @foreach($wonGroup->deals as $deal)
                        @include('sales::livewire.deal-preview-card', ['deal' => $deal])
                    @endforeach
                </x-nx-kanban-column>
            @endif
        @endif
        {{-- Verloren-Spalte (nicht sortierbar) --}}
        @if($showLostColumn)
            @php
                $lostGroup = $groups->firstWhere('isLostGroup', true);
                $lostTotal = $lostGroup ? $lostGroup->deals->sum(fn($d) => (float) ($d->deal_value ?? 0)) : 0;
            @endphp
            @if($lostGroup)
                <x-nx-kanban-column title="VERLOREN" :sortable-id="null" :scrollable="true" :muted="true" tone="rose" :count="$lostGroup->deals->count()">
                    <x-slot name="headerActions">
                        <span class="text-[10px] font-semibold tabular-nums text-[color:var(--nx-danger)]">{{ number_format($lostTotal, 0, ',', '.') }} €</span>
                    </x-slot>

                    <x-slot name="footer">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-[var(--nx-muted)] tabular-nums">{{ $lostGroup->deals->count() }} Deal(s)</span>
                        </div>
                    </x-slot>

                    @foreach($lostGroup->deals as $deal)
                        @include('sales::livewire.deal-preview-card', ['deal' => $deal])
                    @endforeach
                </x-nx-kanban-column>
            @endif
        @endif
    </x-nx-kanban-container>

    {{-- Modals --}}
    <livewire:sales.board-settings-modal />
    <livewire:sales.board-slot-settings-modal />
</x-ui-page>
