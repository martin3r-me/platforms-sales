<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-chart-bar" />
    </x-slot>

    <x-ui-page-container>
        {{-- Main Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-ui-dashboard-tile
                title="Offene Deals"
                :count="$openDealsCount ?? 0"
                icon="clipboard-document-list"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Gewonnene Deals"
                :count="$wonDealsCount ?? 0"
                icon="trophy"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Gesamtwert"
                :count="(int) ($totalValue ?? 0)"
                subtitle="{{ number_format((float) ($totalValue ?? 0), 2, ',', '.') }} €"
                icon="currency-euro"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Erwarteter Wert"
                :count="(int) ($expectedValue ?? 0)"
                subtitle="{{ number_format((float) ($expectedValue ?? 0), 2, ',', '.') }} €"
                icon="chart-bar"
                variant="secondary"
                size="lg"
            />
        </div>

        {{-- Detail Stats --}}
        <x-ui-detail-stats-grid cols="2" gap="6">
            <x-slot:left>
                <x-ui-panel title="Neueste Deals" subtitle="Die 5 zuletzt erstellten Deals">
                    <div class="space-y-3">
                        @forelse($recentDeals ?? [] as $deal)
                            <a href="{{ route('sales.deals.show', $deal) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-md border border-[var(--ui-border)] bg-white hover:bg-[var(--ui-muted-5)] transition">
                                <div class="w-2 h-2 rounded-full {{ $deal->is_done ? 'bg-[var(--ui-success)]' : 'bg-[var(--ui-primary)]' }}"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $deal->title }}</div>
                                    <div class="text-xs text-[var(--ui-muted)] truncate">
                                        {{ $deal->salesBoard?->name ?? 'INBOX' }}
                                        @if($deal->deal_value)
                                            • {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($deal->probability_percent)
                                        <x-ui-badge variant="primary" size="sm">{{ $deal->probability_percent }}%</x-ui-badge>
                                    @endif
                                    @svg('heroicon-o-arrow-right', 'w-4 h-4 text-[var(--ui-muted)]')
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                                    @svg('heroicon-o-clipboard-document-list', 'w-8 h-8 text-[var(--ui-muted)]')
                                </div>
                                <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Noch keine Deals</h3>
                                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deinen ersten Deal um loszulegen</p>
                                <x-ui-button variant="primary" :href="route('sales.my-deals')" wire:navigate>
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-plus', 'w-4 h-4')
                                        <span>Deal erstellen</span>
                                    </span>
                                </x-ui-button>
                            </div>
                        @endforelse
                    </div>
                </x-ui-panel>
            </x-slot:left>
            <x-slot:right>
                <x-ui-panel title="Aktionen" subtitle="Schnellzugriff auf wichtige Funktionen">
                    <div class="space-y-2">
                        <x-ui-button variant="primary" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2 justify-center">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Neuen Deal anlegen</span>
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="secondary-outline" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2 justify-center">
                                @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                                <span>Meine Deals öffnen</span>
                            </span>
                        </x-ui-button>
                    </div>
                </x-ui-panel>
            </x-slot:right>
        </x-ui-detail-stats-grid>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Quick Actions --}}
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

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schnellstatistiken</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Offene Deals</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $openDealsCount ?? 0 }} Deals</div>
                        </div>
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Gewonnene Deals</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $wonDealsCount ?? 0 }} Deals</div>
                        </div>
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Gesamtwert</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ number_format((float) ($totalValue ?? 0), 2, ',', '.') }} €</div>
                        </div>
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Erwarteter Wert</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ number_format((float) ($expectedValue ?? 0), 2, ',', '.') }} €</div>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Letzte Aktivitäten</h3>
                    <div class="space-y-2 text-sm">
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard geladen</div>
                            <div class="text-[var(--ui-muted)] text-xs">vor 1 Minute</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard geladen</div>
                        <div class="text-[var(--ui-muted)]">vor 1 Minute</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>