<div class="h-full overflow-y-auto p-6">
    <!-- Header mit Datum und Perspektive-Toggle -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Vertriebsboard Dashboard</h1>
                <p class="text-gray-600">{{ $currentDay }}, {{ $currentDate }}</p>
            </div>
            <div class="d-flex items-center gap-4">
                <!-- Perspektive-Toggle -->
                <div class="d-flex bg-gray-100 rounded-lg p-1">
                    <button 
                        wire:click="$set('perspective', 'personal')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'personal' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-user', 'w-4 h-4')
                            <span>Persönlich</span>
                        </div>
                    </button>
                    <button 
                        wire:click="$set('perspective', 'team')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'team' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-users', 'w-4 h-4')
                            <span>Team</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Perspektive-spezifische Statistiken -->
    @if($perspective === 'personal')
        <!-- Persönliche Perspektive -->
        <div class="mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-user', 'w-5 h-5 text-blue-600')
                    <h3 class="text-lg font-semibold text-blue-900">Persönliche Übersicht</h3>
                </div>
                <p class="text-blue-700 text-sm">Deine persönlichen Deals und zuständigen Vertriebsaktivitäten.</p>
            </div>
        </div>
    @else
        <!-- Team-Perspektive -->
        <div class="mb-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-users', 'w-5 h-5 text-green-600')
                    <h3 class="text-lg font-semibold text-green-900">Team-Übersicht</h3>
                </div>
                <p class="text-green-700 text-sm">Alle Deals und Vertriebsaktivitäten des Teams.</p>
            </div>
        </div>
    @endif

    <!-- Hauptstatistiken -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Offene Deals -->
        <x-ui-dashboard-tile
            title="Offene Deals"
            :count="$openDeals"
            icon="clock"
            variant="warning"
        />
        
        <!-- Gewonnene Deals -->
        <x-ui-dashboard-tile
            title="Gewonnene Deals"
            :count="$wonDeals"
            icon="check-circle"
            variant="success"
        />
        
        <!-- High Value Deals -->
        <x-ui-dashboard-tile
            title="High Value Deals"
            :count="$highValueDeals"
            icon="star"
            variant="info"
        />
        
        <!-- Überfällige Deals -->
        <x-ui-dashboard-tile
            title="Überfällige Deals"
            :count="$overdueDeals"
            icon="exclamation-circle"
            variant="danger"
        />
    </div>

    <!-- Umsatz-Statistiken -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Gesamt Deal-Wert -->
        <x-ui-dashboard-tile
            title="Gesamt Deal-Wert"
            :count="number_format((float) $totalDealValue, 0, ',', '.') . ' €'"
            icon="currency-euro"
            variant="primary"
        />
        
        <!-- Erwarteter Wert -->
        <x-ui-dashboard-tile
            title="Erwarteter Wert"
            :count="number_format((float) $expectedValue, 0, ',', '.') . ' €'"
            icon="chart-bar"
            variant="info"
        />
        
        <!-- Gewonnener Wert -->
        <x-ui-dashboard-tile
            title="Gewonnener Wert"
            :count="number_format((float) $wonDealValue, 0, ',', '.') . ' €'"
            icon="check-circle"
            variant="success"
        />
    </div>

    <!-- Ampel-Statistiken -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Rote Deals (0-30%) -->
        <x-ui-dashboard-tile
            title="Rote Deals (0-30%)"
            :count="$redDeals"
            icon="exclamation-triangle"
            variant="danger"
        />
        
        <!-- Gelbe Deals (30-70%) -->
        <x-ui-dashboard-tile
            title="Gelbe Deals (30-70%)"
            :count="$yellowDeals"
            icon="clock"
            variant="warning"
        />
        
        <!-- Grüne Deals (70-100%) -->
        <x-ui-dashboard-tile
            title="Grüne Deals (70-100%)"
            :count="$greenDeals"
            icon="check-circle"
            variant="success"
        />
    </div>

    <!-- Monatliche Performance -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Erstellte Deals (Monat) -->
        <x-ui-dashboard-tile
            title="Erstellte Deals (Monat)"
            :count="$monthlyCreatedDeals"
            icon="plus-circle"
            variant="info"
        />
        
        <!-- Gewonnene Deals (Monat) -->
        <x-ui-dashboard-tile
            title="Gewonnene Deals (Monat)"
            :count="$monthlyWonDeals"
            icon="check-circle"
            variant="success"
        />
    </div>

    <!-- Board-Übersicht -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Aktive Vertriebsboards</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($activeBoardsList as $board)
                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="d-flex justify-between items-start mb-2">
                        <h3 class="font-medium text-gray-900">{{ $board['name'] }}</h3>
                        <a href="{{ route('sales.boards.show', $board['id']) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                           wire:navigate>
                            Öffnen
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="text-gray-600">
                            <span class="font-medium">{{ $board['open_deals'] }}</span> offen
                        </div>
                        <div class="text-gray-600">
                            <span class="font-medium">{{ $board['total_deals'] }}</span> gesamt
                        </div>
                        <div class="text-gray-600">
                            <span class="font-medium">{{ $board['high_value'] }}</span> high value
                        </div>
                        <div class="text-gray-600">
                            <span class="font-medium">{{ number_format((float) $board['total_value'], 0, ',', '.') }} €</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Schnellaktionen -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Schnellaktionen</h2>
        <div class="d-flex gap-4">
            <x-ui-button variant="success" @click="$dispatch('create-sales-board')">
                <div class="d-flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    Neues Vertriebsboard
                </div>
            </x-ui-button>
            <x-ui-button variant="primary" href="{{ route('sales.my-deals') }}" wire:navigate>
                <div class="d-flex items-center gap-2">
                    @svg('heroicon-o-home', 'w-4 h-4')
                    Meine Deals
                </div>
            </x-ui-button>
        </div>
    </div>
</div>