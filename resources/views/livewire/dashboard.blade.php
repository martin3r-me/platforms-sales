<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-chart-bar" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Dashboard'],
        ]">
            <x-nx-button variant="primary" :href="route('sales.my-deals')" wire:navigate>
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neuer Deal</span>
                </span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Hero Stats --}}
        <x-nx-stat-grid cols="5" class="mb-6">
            <x-nx-stat
                label="Pipeline-Wert"
                value="{{ number_format((float) $this->pipelineValue, 0, ',', '.') }} €"
                icon="heroicon-o-banknotes"
                accent="var(--nx-info)"
                hint="{{ $this->pipelineValueTrend['trendValue'] }}"
            />
            <x-nx-stat
                label="Umsatz (Monat)"
                value="{{ number_format((float) $this->wonRevenueThisMonth, 0, ',', '.') }} €"
                icon="heroicon-o-trophy"
                accent="var(--nx-success)"
                hint="{{ $this->wonRevenueTrend['trendValue'] }}"
            />
            <x-nx-stat
                label="Win-Rate"
                value="{{ $this->winRate }}%"
                icon="heroicon-o-chart-bar"
                accent="var(--nx-info)"
                hint="{{ $this->winRateTrend['trendValue'] }}"
            />
            <x-nx-stat
                label="Offene Deals"
                value="{{ $this->openDealsCount }}"
                icon="heroicon-o-clipboard-document-list"
                accent="var(--nx-warning)"
                hint="{{ $this->openDealsCountTrend['trendValue'] }}"
            />
            <x-nx-stat
                label="Avg. Deal-Größe"
                value="{{ number_format((float) $this->averageDealSize, 0, ',', '.') }} €"
                icon="heroicon-o-calculator"
                hint="{{ $this->averageDealSizeTrend['trendValue'] }}"
            />
        </x-nx-stat-grid>

        {{-- Tier 2: Pipeline Breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Deals pro Phase --}}
            <x-nx-card>
                <x-nx-section title="Deals pro Phase">
                    @if($this->dealsByStage->count() > 0)
                        @php $maxValue = $this->dealsByStage->max('total_value'); @endphp
                        <div class="space-y-3">
                            @foreach($this->dealsByStage as $stage)
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm text-[color:var(--nx-text)]">{{ $stage->name }}</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-[color:var(--nx-muted)]">{{ $stage->deal_count }} Deals</span>
                                            <span class="text-sm font-semibold text-[color:var(--nx-text)]">{{ number_format((float) $stage->total_value, 0, ',', '.') }} €</span>
                                        </div>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-[color:var(--nx-bg)]">
                                        <div class="h-2 rounded-full bg-[color:var(--nx-info)] transition-all" style="width: {{ $maxValue > 0 ? round(($stage->total_value / $maxValue) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-nx-empty icon="heroicon-o-chart-bar">Keine Deals in der Pipeline</x-nx-empty>
                    @endif
                </x-nx-section>
            </x-nx-card>

            {{-- Überfällige Deals --}}
            <x-nx-card>
                <x-nx-section title="Überfällige Deals" :hint="$this->overdueDeals->count() > 0 ? $this->overdueDeals->count() : null">
                    @if($this->overdueDeals->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->overdueDeals as $deal)
                                <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                                   class="flex items-center justify-between p-2.5 rounded-lg border border-[rgba(224,49,49,0.30)] bg-[rgba(224,49,49,.09)] hover:bg-[rgba(224,49,49,.14)] transition">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-[color:var(--nx-text)] truncate">{{ $deal->title }}</div>
                                        <div class="text-xs text-[color:var(--nx-danger)]">
                                            Fällig: {{ $deal->due_date->format('d.m.Y') }}
                                            ({{ $deal->due_date->diffForHumans() }})
                                        </div>
                                    </div>
                                    @if($deal->deal_value)
                                        <span class="text-sm font-semibold text-[color:var(--nx-text)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-nx-empty icon="heroicon-o-check-circle">Keine überfälligen Deals</x-nx-empty>
                    @endif
                </x-nx-section>
            </x-nx-card>
        </div>

        {{-- Tier 3: Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Zuletzt gewonnen --}}
            <x-nx-card>
                <x-nx-section title="Zuletzt gewonnen" icon="heroicon-o-trophy">
                    @forelse($this->recentWonDeals as $deal)
                        <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                           class="flex items-center justify-between p-2.5 rounded-lg border border-[color:var(--nx-line)] hover:border-[color:var(--nx-success)] transition mb-2">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-[color:var(--nx-text)] truncate">{{ $deal->title }}</div>
                                <div class="text-xs text-[color:var(--nx-muted)]">{{ $deal->done_at?->format('d.m.Y') }}</div>
                            </div>
                            @if($deal->deal_value)
                                <span class="text-sm font-semibold text-[color:var(--nx-success)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                            @endif
                        </a>
                    @empty
                        <x-nx-empty icon="heroicon-o-trophy">Keine gewonnenen Deals</x-nx-empty>
                    @endforelse
                </x-nx-section>
            </x-nx-card>

            {{-- Hot Deals --}}
            <x-nx-card>
                <x-nx-section title="Hot Deals" icon="heroicon-o-fire">
                    @forelse($this->hotDeals as $deal)
                        <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                           class="flex items-center justify-between p-2.5 rounded-lg border border-[color:var(--nx-line)] hover:border-[color:var(--nx-danger)] transition mb-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium text-[color:var(--nx-text)] truncate">{{ $deal->title }}</div>
                                    @if($deal->is_hot)
                                        @svg('heroicon-s-fire', 'w-3.5 h-3.5 text-[color:var(--nx-danger)] flex-shrink-0')
                                    @endif
                                </div>
                                <div class="text-xs text-[color:var(--nx-muted)]">
                                    @if($deal->probability_percent)
                                        {{ $deal->probability_percent }}% Wahrsch.
                                    @endif
                                </div>
                            </div>
                            @if($deal->deal_value)
                                <span class="text-sm font-semibold text-[color:var(--nx-text)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                            @endif
                        </a>
                    @empty
                        <x-nx-empty icon="heroicon-o-fire">Keine Hot Deals</x-nx-empty>
                    @endforelse
                </x-nx-section>
            </x-nx-card>

            {{-- Bald abzuschließen --}}
            <x-nx-card>
                <x-nx-section title="Bald abzuschließen" icon="heroicon-o-calendar">
                    @forelse($this->upcomingCloseDeals as $deal)
                        <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                           class="flex items-center justify-between p-2.5 rounded-lg border border-[color:var(--nx-line)] hover:border-[color:var(--nx-warning)] transition mb-2">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-[color:var(--nx-text)] truncate">{{ $deal->title }}</div>
                                <div class="text-xs text-[color:var(--nx-muted)]">
                                    {{ $deal->close_date->format('d.m.Y') }}
                                    ({{ $deal->close_date->diffForHumans() }})
                                </div>
                            </div>
                            @if($deal->deal_value)
                                <span class="text-sm font-semibold text-[color:var(--nx-text)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                            @endif
                        </a>
                    @empty
                        <x-nx-empty icon="heroicon-o-calendar">Keine Deals in den nächsten 30 Tagen</x-nx-empty>
                    @endforelse
                </x-nx-section>
            </x-nx-card>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-5 bg-[var(--nx-bg)]">
                <section class="p-3 rounded-lg bg-[color:var(--nx-surface)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)] mb-2">Aktionen</h3>
                    <div class="space-y-2">
                        <x-nx-button variant="secondary" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                <span>Meine Deals</span>
                            </span>
                        </x-nx-button>
                        <x-nx-button variant="primary" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Neuer Deal</span>
                            </span>
                        </x-nx-button>
                    </div>
                </section>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4 bg-[var(--nx-bg)]">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--nx-muted)]">Letzte Aktivitäten</div>
                <x-nx-empty icon="heroicon-o-clock">Keine aktuellen Aktivitäten</x-nx-empty>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
