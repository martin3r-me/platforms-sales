<div class="h-full overflow-y-auto p-6">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @svg('heroicon-o-chart-bar', 'w-6 h-6 text-gray-700')
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Sales Dashboard</h1>
                    <p class="text-gray-600 mt-1">Übersicht über Deals, Pipeline und Performance</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-ui-button variant="secondary-outline" :href="route('sales.my-deals')" wire:navigate>
                    @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                    Meine Deals
                </x-ui-button>
                <x-ui-button variant="primary" :href="route('sales.my-deals')" wire:navigate>
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    Neuer Deal
                </x-ui-button>
            </div>
        </div>

        {{-- Tiles --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <x-ui-dashboard-tile
                title="Offene Deals"
                :count="$openDealsCount ?? 0"
                icon="clipboard-document-list"
                variant="info"
            />
            <x-ui-dashboard-tile
                title="Gewonnene Deals"
                :count="$wonDealsCount ?? 0"
                icon="trophy"
                variant="success"
            />
            <x-ui-dashboard-tile
                title="Gesamtwert"
                :count="(int) ($totalValue ?? 0)"
                icon="currency-euro"
                variant="warning"
            />
            <x-ui-dashboard-tile
                title="Erwarteter Wert"
                :count="(int) ($expectedValue ?? 0)"
                icon="chart-bar"
                variant="primary"
            />
        </div>

        {{-- Two columns --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Recent Deals --}}
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Neueste Deals</h2>
                    <x-ui-button variant="secondary-outline" size="sm" :href="route('sales.my-deals')" wire:navigate>
                        Alle ansehen
                    </x-ui-button>
                </div>
                <div class="p-6">
                    @if(isset($recentDeals) && $recentDeals->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentDeals as $deal)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full {{ $deal->is_done ? 'bg-green-500' : 'bg-blue-500' }}"></div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $deal->title }}</div>
                                            <div class="text-xs text-gray-600">
                                                {{ $deal->salesBoard?->name ?? 'INBOX' }}
                                                @if($deal->deal_value)
                                                    • {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($deal->probability_percent)
                                            <x-ui-badge variant="blue" size="sm">{{ $deal->probability_percent }}%</x-ui-badge>
                                        @endif
                                        <x-ui-button variant="secondary-outline" size="sm" :href="route('sales.deals.show', $deal)" wire:navigate>
                                            Ansehen
                                        </x-ui-button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                @svg('heroicon-o-clipboard-document-list', 'w-8 h-8 text-gray-400')
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Noch keine Deals</h3>
                            <p class="text-gray-500 mb-4">Erstelle deinen ersten Deal um loszulegen</p>
                            <x-ui-button variant="primary" :href="route('sales.my-deals')" wire:navigate>
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Deal erstellen
                            </x-ui-button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: Actions / Shortcuts --}}
            <div class="space-y-4">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Aktionen</div>
                    <div class="flex flex-col gap-2">
                        <x-ui-button variant="primary" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Neuen Deal anlegen
                        </x-ui-button>
                        <x-ui-button variant="secondary-outline" :href="route('sales.my-deals')" wire:navigate class="w-full">
                            @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                            Meine Deals öffnen
                        </x-ui-button>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Hinweis</div>
                    <p class="text-sm text-gray-600">Passe Boards, Spalten und Templates in den Board-Einstellungen an.</p>
                </div>
            </div>
        </div>
    </div>
</div>