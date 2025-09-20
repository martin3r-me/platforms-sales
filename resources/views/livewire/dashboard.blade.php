<div class="h-full overflow-y-auto p-6">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sales Dashboard</h1>
                <p class="text-gray-600 mt-1">Übersicht über deine Deals und Performance</p>
            </div>
            <div class="flex items-center gap-3">
                <x-ui-button variant="primary" :href="route('sales.my-deals')" wire:navigate>
                    @svg('heroicon-o-eye', 'w-4 h-4')
                    Meine Deals
                </x-ui-button>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Offene Deals</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $openDealsCount ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        @svg('heroicon-o-clipboard-document-list', 'w-6 h-6 text-blue-600')
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Gewonnene Deals</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $wonDealsCount ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        @svg('heroicon-o-check-circle', 'w-6 h-6 text-green-600')
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Gesamtwert</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $totalValue ? number_format((float) $totalValue, 0, ',', '.') . ' €' : '0 €' }}
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        @svg('heroicon-o-currency-euro', 'w-6 h-6 text-yellow-600')
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Erwarteter Wert</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $expectedValue ? number_format((float) $expectedValue, 0, ',', '.') . ' €' : '0 €' }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        @svg('heroicon-o-chart-bar', 'w-6 h-6 text-purple-600')
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Deals --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Neueste Deals</h2>
            </div>
            <div class="p-6">
                @if(isset($recentDeals) && $recentDeals->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentDeals as $deal)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full {{ $deal->is_done ? 'bg-green-500' : 'bg-blue-500' }}"></div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $deal->title }}</h3>
                                        <p class="text-sm text-gray-600">
                                            {{ $deal->salesBoard?->name ?? 'INBOX' }}
                                            @if($deal->deal_value)
                                                • {{ number_format((float) $deal->deal_value, 0, ',', '.') }} €
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($deal->probability_percent)
                                        <x-ui-badge variant="blue" size="sm">
                                            {{ $deal->probability_percent }}%
                                        </x-ui-badge>
                                    @endif
                                    <x-ui-button 
                                        variant="secondary-outline" 
                                        size="sm" 
                                        :href="route('sales.deals.show', $deal)" 
                                        wire:navigate>
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
    </div>
</div>