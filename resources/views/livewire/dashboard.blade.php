<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-chart-bar" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sales', 'href' => route('sales.dashboard'), 'icon' => 'currency-euro'],
            ['label' => 'Dashboard'],
        ]">
            <x-ui-button variant="primary" size="sm" :href="route('sales.my-deals')" wire:navigate>
                <span class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neuer Deal</span>
                </span>
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Hero Tiles --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <x-ui-dashboard-tile
                title="Pipeline-Wert"
                :count="(int) $this->pipelineValue"
                description="{{ number_format((float) $this->pipelineValue, 0, ',', '.') }} €"
                icon="banknotes"
                variant="primary"
                size="lg"
                :trend="$this->pipelineValueTrend['trend']"
                :trendValue="$this->pipelineValueTrend['trendValue']"
            />
            <x-ui-dashboard-tile
                title="Umsatz (Monat)"
                :count="(int) $this->wonRevenueThisMonth"
                description="{{ number_format((float) $this->wonRevenueThisMonth, 0, ',', '.') }} €"
                icon="trophy"
                variant="success"
                size="lg"
                :trend="$this->wonRevenueTrend['trend']"
                :trendValue="$this->wonRevenueTrend['trendValue']"
            />
            <x-ui-dashboard-tile
                title="Win-Rate"
                :count="$this->winRate"
                description="{{ $this->winRate }}%"
                icon="chart-bar"
                variant="info"
                size="lg"
                :trend="$this->winRateTrend['trend']"
                :trendValue="$this->winRateTrend['trendValue']"
            />
            <x-ui-dashboard-tile
                title="Offene Deals"
                :count="$this->openDealsCount"
                icon="clipboard-document-list"
                variant="warning"
                size="lg"
                :trend="$this->openDealsCountTrend['trend']"
                :trendValue="$this->openDealsCountTrend['trendValue']"
            />
            <x-ui-dashboard-tile
                title="Avg. Deal-Größe"
                :count="(int) $this->averageDealSize"
                description="{{ number_format((float) $this->averageDealSize, 0, ',', '.') }} €"
                icon="calculator"
                variant="neutral"
                size="lg"
                :trend="$this->averageDealSizeTrend['trend']"
                :trendValue="$this->averageDealSizeTrend['trendValue']"
            />
        </div>

        {{-- Tier 2: Pipeline Breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Deals pro Phase --}}
            <x-ui-panel>
                <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-4">Deals pro Phase</h3>
                @if($this->dealsByStage->count() > 0)
                    @php $maxValue = $this->dealsByStage->max('total_value'); @endphp
                    <div class="space-y-3">
                        @foreach($this->dealsByStage as $stage)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $stage->name }}</span>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $stage->deal_count }} Deals</span>
                                        <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ number_format((float) $stage->total_value, 0, ',', '.') }} €</span>
                                    </div>
                                </div>
                                <div class="w-full h-2 rounded-full bg-[var(--ui-muted-10)]">
                                    <div class="h-2 rounded-full bg-[var(--ui-primary)] transition-all" style="width: {{ $maxValue > 0 ? round(($stage->total_value / $maxValue) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[var(--ui-muted)] py-4 text-center">Keine Deals in der Pipeline</p>
                @endif
            </x-ui-panel>

            {{-- Überfällige Deals --}}
            <x-ui-panel>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Überfällige Deals</h3>
                    @if($this->overdueDeals->count() > 0)
                        <x-ui-badge variant="danger" size="xs">{{ $this->overdueDeals->count() }}</x-ui-badge>
                    @endif
                </div>
                @if($this->overdueDeals->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->overdueDeals as $deal)
                            <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                               class="flex items-center justify-between p-2.5 rounded-lg border border-red-200 bg-red-50/30 hover:bg-red-50/60 transition">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $deal->title }}</div>
                                    <div class="text-xs text-red-600">
                                        Fällig: {{ $deal->due_date->format('d.m.Y') }}
                                        ({{ $deal->due_date->diffForHumans() }})
                                    </div>
                                </div>
                                @if($deal->deal_value)
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[var(--ui-muted)] py-4 text-center">Keine überfälligen Deals</p>
                @endif
            </x-ui-panel>
        </div>

        {{-- Tier 3: Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Zuletzt gewonnen --}}
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Zuletzt gewonnen</h3>
                    @svg('heroicon-o-trophy', 'w-4 h-4 text-[var(--ui-success)]')
                </div>
                @forelse($this->recentWonDeals as $deal)
                    <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                       class="flex items-center justify-between p-2.5 rounded-lg border border-[var(--ui-border)] hover:border-[var(--ui-success)]/40 transition mb-2">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $deal->title }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $deal->done_at?->format('d.m.Y') }}</div>
                        </div>
                        @if($deal->deal_value)
                            <span class="text-sm font-semibold text-[var(--ui-success)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                        @endif
                    </a>
                @empty
                    <p class="text-sm text-[var(--ui-muted)] py-4 text-center">Keine gewonnenen Deals</p>
                @endforelse
            </x-ui-panel>

            {{-- Hot Deals --}}
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Hot Deals</h3>
                    @svg('heroicon-o-fire', 'w-4 h-4 text-[var(--ui-danger)]')
                </div>
                @forelse($this->hotDeals as $deal)
                    <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                       class="flex items-center justify-between p-2.5 rounded-lg border border-[var(--ui-border)] hover:border-[var(--ui-danger)]/40 transition mb-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <div class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $deal->title }}</div>
                                @if($deal->is_hot)
                                    @svg('heroicon-s-fire', 'w-3.5 h-3.5 text-[var(--ui-danger)] flex-shrink-0')
                                @endif
                            </div>
                            <div class="text-xs text-[var(--ui-muted)]">
                                @if($deal->probability_percent)
                                    {{ $deal->probability_percent }}% Wahrsch.
                                @endif
                            </div>
                        </div>
                        @if($deal->deal_value)
                            <span class="text-sm font-semibold text-[var(--ui-secondary)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                        @endif
                    </a>
                @empty
                    <p class="text-sm text-[var(--ui-muted)] py-4 text-center">Keine Hot Deals</p>
                @endforelse
            </x-ui-panel>

            {{-- Bald abzuschließen --}}
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Bald abzuschließen</h3>
                    @svg('heroicon-o-calendar', 'w-4 h-4 text-[var(--ui-warning)]')
                </div>
                @forelse($this->upcomingCloseDeals as $deal)
                    <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate
                       class="flex items-center justify-between p-2.5 rounded-lg border border-[var(--ui-border)] hover:border-[var(--ui-warning)]/40 transition mb-2">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $deal->title }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">
                                {{ $deal->close_date->format('d.m.Y') }}
                                ({{ $deal->close_date->diffForHumans() }})
                            </div>
                        </div>
                        @if($deal->deal_value)
                            <span class="text-sm font-semibold text-[var(--ui-secondary)] ml-3">{{ number_format((float) $deal->deal_value, 0, ',', '.') }} €</span>
                        @endif
                    </a>
                @empty
                    <p class="text-sm text-[var(--ui-muted)] py-4 text-center">Keine Deals in den nächsten 30 Tagen</p>
                @endforelse
            </x-ui-panel>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary-outline" size="sm" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                <span>Meine Deals</span>
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="primary" size="sm" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Neuer Deal</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-3">
                        @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                    </div>
                    <p class="text-sm text-[var(--ui-muted)]">Keine aktuellen Aktivitäten</p>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
