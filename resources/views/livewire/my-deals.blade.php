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
            'variant' => 'info'
        ],
        [
            'title' => 'High Value',
            'count' => $highValueCount,
            'icon' => 'star',
            'variant' => 'info'
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

    // Tone-Mapping für mittlere Spalten
    $tonePalette = ['indigo', 'amber', 'teal', 'violet', 'sky', 'pink', 'rose', 'emerald'];
    $middleColumns = $groups->filter(fn ($g) => !($g->isWonGroup ?? false))->values();
    $columnTones = $middleColumns->mapWithKeys(fn ($col, $i) => [$col->id => $tonePalette[$i % count($tonePalette)]]);
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meine Deals" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Meine Deals'],
        ]">
            <x-nx-button variant="primary" wire:click="createDeal()">
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neuer Deal</span>
                </span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Deal-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-5 bg-[var(--nx-bg)]">
                {{-- Deal-Statistiken: Offen --}}
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

                {{-- Deal-Statistiken: Gewonnen --}}
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Gewonnen</h3>
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

                {{-- Performance-Score --}}
                @if($monthlyPerformanceScore ?? null)
                    <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Performance</h3>
                        <div class="p-3 rounded bg-[var(--nx-bg)]">
                            <div class="text-xs text-[var(--nx-muted)] mb-1">Monatliche Performance</div>
                            <div class="text-lg font-semibold tabular-nums text-[var(--nx-text)]">
                                {{ number_format((float) (($monthlyPerformanceScore ?? 0) * 100), 1) }}%
                            </div>
                            <div class="text-xs text-[var(--nx-muted)] mt-1">
                                {{ number_format((float) ($wonValue ?? 0), 0, ',', '.') }} € gewonnen / {{ number_format((float) ($createdValue ?? 0), 0, ',', '.') }} € erstellt
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-3">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)]">Letzte Aktivitäten</div>
                <x-nx-empty icon="heroicon-o-clock">Keine aktuellen Aktivitäten</x-nx-empty>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Kanban-Board --}}
    <x-nx-kanban-container sortable="updateDealOrder" sortable-group="updateDealOrder">
        @foreach($groups as $group)
            @php
                $grpDeals = $group->tasks;
                $grpTotal = $grpDeals->sum(fn($d) => (float) ($d->deal_value ?? 0));
                $grpOneTime = 0;
                $grpArr = 0;
                foreach ($grpDeals as $d) {
                    if ($d->hasBillables()) {
                        $grpOneTime += $d->billables->filter(fn($b) => $b->isOneTime())->sum('total_value');
                        $grpArr += $d->billables->filter(fn($b) => $b->isRecurring())->sum(function($b) {
                            return match($b->billing_interval) {
                                'monthly' => (float) $b->amount * 12,
                                'quarterly' => (float) $b->amount * 4,
                                'yearly' => (float) $b->amount,
                                default => (float) $b->amount * 12,
                            };
                        });
                    } else {
                        $grpOneTime += (float) ($d->deal_value ?? 0);
                    }
                }
                $isWon = $group->isWonGroup ?? false;
                $tone = $isWon ? 'emerald' : ($columnTones[$group->id] ?? 'indigo');
            @endphp
            <x-nx-kanban-column
                :title="$group->label"
                :sortable-id="$group->id ?? null"
                :scrollable="true"
                :muted="$isWon"
                :tone="$tone"
                :count="$grpDeals->count()">
                <x-slot name="headerActions">
                    <span class="text-[10px] font-semibold tabular-nums text-[color:var(--nx-success)]">{{ number_format($grpTotal, 0, ',', '.') }} €</span>
                    @if(!$isWon)
                        <button
                            wire:click="createDeal('{{ $group->id ?? null }}')"
                            class="text-[var(--nx-muted)] hover:text-[var(--nx-accent)] transition-colors"
                            title="Neuer Deal"
                        >
                            @svg('heroicon-o-plus-circle', 'w-4 h-4')
                        </button>
                    @endif
                </x-slot>

                <x-slot name="footer">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-[var(--nx-muted)] tabular-nums">{{ $grpDeals->count() }} Deal(s)</span>
                        <div class="flex items-center gap-2">
                            @if($grpOneTime > 0)
                                <span class="inline-flex items-center gap-1 text-[color:var(--nx-text)] font-medium tabular-nums" title="Einmalig">
                                    @svg('heroicon-o-banknotes', 'w-3 h-3')
                                    {{ number_format($grpOneTime, 0, ',', '.') }} €
                                </span>
                            @endif
                            @if($grpArr > 0)
                                <span class="inline-flex items-center gap-1 text-[color:var(--nx-info)] font-medium tabular-nums" title="Wiederkehrend pro Jahr">
                                    @svg('heroicon-o-arrow-path', 'w-3 h-3')
                                    {{ number_format($grpArr, 0, ',', '.') }} €/J
                                </span>
                            @endif
                        </div>
                    </div>
                </x-slot>

                @foreach($group->tasks as $deal)
                    @include('sales::livewire.deal-preview-card', ['deal' => $deal])
                @endforeach
            </x-nx-kanban-column>
        @endforeach
    </x-nx-kanban-container>
</x-ui-page>
